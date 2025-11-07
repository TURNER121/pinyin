<?php
namespace tekintian\pinyin;
/**
 * 汉字转拼音工具（优化版）
 * 核心功能：支持汉字转拼音（带声调/无声调）、自定义词典（单字/多字词语）、自学习生僻字、多音字规则
 * 优先级顺序：用户临时指定 > 自定义字典 > 自学习字典 > 常用字典 > 生僻字字典
 */
class PinyinConverter {
    /**
     * 配置参数
     * @var array
     *  - dict: 各类字典文件路径配置
     *  - special_char: 特殊字符处理配置（保留/删除/替换）
     *  - high_freq_cache: 高频转换结果缓存配置
     *  - polyphone_priority: 多音字默认优先级（无规则时使用）
     *  - self_learn_merge: 自学习字典合并配置（阈值、频率限制等）
     */
    private $config = [
        'dict' => [
            'common' => [
                'with_tone' => __DIR__.'/../data/common_with_tone.php',   // 常用字带声调字典
                'no_tone' => __DIR__.'/../data/common_no_tone.php'       // 常用字无声调字典
            ],
            'rare' => [
                'with_tone' => __DIR__.'/../data/rare_with_tone.php',     // 生僻字带声调字典
                'no_tone' => __DIR__.'/../data/rare_no_tone.php'         // 生僻字无声调字典
            ],
            'self_learn' => [
                'with_tone' => __DIR__.'/../data/self_learn_with_tone.php', // 自学习带声调字典
                'no_tone' => __DIR__.'/../data/self_learn_no_tone.php',     // 自学习无声调字典
                'frequency' => __DIR__.'/../data/self_learn_frequency.php'  // 自学习字使用频率记录
            ],
            'custom' => [
                'with_tone' => __DIR__.'/../data/custom_with_tone.php',   // 自定义带声调字典
                'no_tone' => __DIR__.'/../data/custom_no_tone.php'       // 自定义无声调字典
            ],
            'polyphone_rules' => __DIR__.'/../data/polyphone_rules.php', // 多音字规则字典
            'backup' => __DIR__.'/../data/backup/'                      // 字典备份目录
        ],
        'special_char' => [
            'default_mode' => 'delete',                     // 特殊字符默认处理模式
            'default_map' => [                              // 特殊字符默认替换映射
                '，' => ',', '。' => '.', '！' => '!', '？' => '?',
                '（' => '(', '）' => ')', '【' => '[', '】' => ']',
                '、' => ',', '；' => ';', '：' => ':'
            ],
            'delete_allow' => 'a-zA-Z0-9_\-+.'              // 允许保留的特殊字符（delete模式下）
        ],
        'high_freq_cache' => [
            'size' => 1000                                  // 高频缓存最大条目数
        ],
        'polyphone_priority' => [                          // 多音字默认读音优先级（索引）
            '行' => 0, '长' => 0, '乐' => 0
        ],
        'self_learn_merge' => [
            'threshold' => 1000,       // 触发合并的自学习条目数阈值
            'incremental' => true,     // 是否启用增量合并（仅合并超过阈值的部分）
            'max_per_merge' => 500,    // 每次合并的最大条目数
            'frequency_limit' => 86400,// 合并频率限制（秒），默认1天
            'backup_before_merge' => true, // 合并前是否备份字典
            'sort_by_frequency' => true // 合并时是否按使用频率排序
        ]
    ];

    /**
     * 字典数据缓存（内存中）
     * @var array
     *  - 各类字典的内存缓存，避免重复读取文件
     */
    private $dicts = [
        'common' => ['with_tone' => null, 'no_tone' => null],
        'rare' => ['with_tone' => null, 'no_tone' => null],
        'self_learn' => ['with_tone' => null, 'no_tone' => null],
        'self_learn_frequency' => null,  // 自学习字频率缓存
        'custom' => ['with_tone' => null, 'no_tone' => null],  // 自定义字典缓存
        'polyphone_rules' => null        // 多音字规则缓存
    ];

    /**
     * 新增自学习字缓存（未持久化到文件）
     * @var array
     */
    private $learnedChars = [
        'with_tone' => [],
        'no_tone' => []
    ];

    /**
     * 自学习字使用频率计数（内存临时存储）
     * @var array
     */
    private $charFrequency = [];

    /**
     * 上次合并时间记录（按声调类型）
     * @var array
     */
    private $lastMergeTime = [];

    /**
     * 高频转换结果缓存
     * @var SplObjectStorage
     */
    private $cache;

    /**
     * 特殊字符最终替换映射（默认+自定义）
     * @var array
     */
    private $finalCharMap = [];

    /**
     * 自定义多字词语缓存（按长度降序，用于优先匹配）
     * @var array
     */
    private $customMultiWords = [
        'with_tone' => [],
        'no_tone' => []
    ];

    /**
     * 构造函数：初始化配置、加载字典、检查合并需求
     * @param array $options 自定义配置（覆盖默认配置）
     */
    public function __construct($options = []) {
        // 合并用户配置与默认配置
        $this->config = array_replace_recursive($this->config, $options);
        // 初始化缓存存储
        $this->cache = new \SplObjectStorage();
        // 初始化特殊字符替换映射
        $this->finalCharMap = $this->config['special_char']['default_map'];
        if (isset($options['special_char']['custom_map']) && is_array($options['special_char']['custom_map'])) {
            $this->finalCharMap = array_merge($this->finalCharMap, $options['special_char']['custom_map']);
        }
        // 初始化目录（创建不存在的字典/备份目录）
        $this->initDirectories();
        // 加载各类字典
        $this->loadSelfLearnDict(true);
        $this->loadSelfLearnDict(false);
        $this->loadSelfLearnFrequency();
        $this->loadCustomDict(true);
        $this->loadCustomDict(false);
        $this->loadPolyphoneRules();
        // 初始化自定义多字词语缓存（按长度降序）
        $this->initCustomMultiWords();
        // 加载上次合并时间
        $this->loadLastMergeTime();
        // 检查是否需要合并（仅记录，不执行）
        $this->checkMergeNeed();
    }

    /**
     * 初始化目录结构（创建字典文件、备份目录等）
     */
    private function initDirectories() {
        $backupDir = $this->config['dict']['backup'];
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        // 确保自定义字典文件存在
        foreach (['with_tone', 'no_tone'] as $type) {
            $path = $this->config['dict']['custom'][$type];
            if (!file_exists($path)) {
                file_put_contents($path, "<?php\nreturn [];\n");
            }
        }
        // 确保自学习频率文件存在
        $freqPath = $this->config['dict']['self_learn']['frequency'];
        if (!file_exists($freqPath)) {
            file_put_contents($freqPath, "<?php\nreturn [];\n");
        }
        // 确保其他字典文件存在（常用字、生僻字、自学习等）
        foreach (['common', 'rare', 'self_learn'] as $dictType) {
            foreach (['with_tone', 'no_tone'] as $toneType) {
                $path = $this->config['dict'][$dictType][$toneType];
                if (!file_exists($path)) {
                    file_put_contents($path, "<?php\nreturn [];\n");
                }
            }
        }
        // 确保多音字规则文件存在
        $polyPath = $this->config['dict']['polyphone_rules'];
        if (!file_exists($polyPath)) {
            file_put_contents($polyPath, "<?php\nreturn [];\n");
        }
    }

    /**
     * 初始化自定义多字词语缓存（提取长度>1的词语，按长度降序排序）
     * 作用：优先匹配长词语，避免被短词语拆分
     */
    private function initCustomMultiWords() {
        foreach (['with_tone', 'no_tone'] as $type) {
            $words = [];
            foreach ($this->dicts['custom'][$type] as $word => $pinyin) {
                $wordLen = mb_strlen($word, 'UTF-8');
                if ($wordLen > 1) { // 仅处理多字词语（长度>1）
                    $words[] = [
                        'word' => $word,
                        'length' => $wordLen,
                        'pinyin' => $pinyin
                    ];
                }
            }
            // 按词语长度降序排序（长词语优先匹配）
            usort($words, function ($a, $b) {
                return $b['length'] - $a['length'];
            });
            $this->customMultiWords[$type] = $words;
        }
    }

    /**
     * 加载自定义字典
     * @param bool $withTone 是否带声调（true:带声调，false:无声调）
     */
    private function loadCustomDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['custom'][$type] !== null) {
            return; // 已加载则直接返回
        }
        $path = $this->config['dict']['custom'][$type];
        $data = file_exists($path) ? require $path : [];
        // 格式化拼音数组（确保值为数组类型）
        $this->dicts['custom'][$type] = is_array($data) ? $this->formatPinyinArray($data) : [];
    }

    /**
     * 动态添加自定义拼音（支持单字和多字词语）
     * @param string $char 汉字/词语（如"云南"、"张"）
     * @param array|string $pinyin 拼音（支持数组，如["yunnan"]或"yunnan"）
     * @param bool $withTone 是否带声调（true:带声调，false:无声调）
     */
    public function addCustomPinyin($char, $pinyin, $withTone = false) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        $this->loadCustomDict($withTone); // 确保字典已加载

        // 格式化拼音为数组（统一格式）
        $pinyinArray = is_array($pinyin) ? $pinyin : [$pinyin];
        $this->dicts['custom'][$type][$char] = $pinyinArray;

        // 持久化到文件（使用短数组格式[]）
        $path = $this->config['dict']['custom'][$type];
        $arrayStr = $this->shortArrayExport($this->dicts['custom'][$type]);
        $content = "<?php\n/** 自定义词典（{$type}）**/\nreturn {$arrayStr};\n";
        file_put_contents($path, $content);

        // 更新多字词语缓存（确保新添加的多字词语生效）
        $this->initCustomMultiWords();

        echo "\n✅ 已添加自定义拼音：{$char} → " . implode('/', $pinyinArray);
    }

    /**
     * 删除自定义拼音
     * @param string $char 汉字/词语
     * @param bool $withTone 是否带声调
     */
    public function removeCustomPinyin($char, $withTone = false) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        $this->loadCustomDict($withTone);

        if (isset($this->dicts['custom'][$type][$char])) {
            unset($this->dicts['custom'][$type][$char]);
            // 持久化到文件
            $path = $this->config['dict']['custom'][$type];
            $arrayStr = $this->shortArrayExport($this->dicts['custom'][$type]);
            $content = "<?php\n/** 自定义词典（{$type}）**/\nreturn {$arrayStr};\n";
            file_put_contents($path, $content);
            // 更新多字词语缓存
            $this->initCustomMultiWords();
            echo "\n✅ 已删除自定义拼音：{$char}";
        }
    }

    /**
     * 加载自学习字频率数据
     */
    private function loadSelfLearnFrequency() {
        if ($this->dicts['self_learn_frequency'] !== null) {
            return;
        }
        $path = $this->config['dict']['self_learn']['frequency'];
        $data = file_exists($path) ? require $path : [];
        $this->dicts['self_learn_frequency'] = is_array($data) ? $data : [];
        $this->charFrequency = $this->dicts['self_learn_frequency']; // 内存副本用于临时计数
    }

    /**
     * 保存自学习字频率数据到文件
     */
    private function saveSelfLearnFrequency() {
        $path = $this->config['dict']['self_learn']['frequency'];
        $arrayStr = $this->shortArrayExport($this->charFrequency);
        $content = "<?php\n/** 自学习汉字使用频率 **/\nreturn {$arrayStr};\n";
        file_put_contents($path, $content);
        $this->dicts['self_learn_frequency'] = $this->charFrequency;
    }

    /**
     * 加载上次合并时间记录
     */
    private function loadLastMergeTime() {
        $this->lastMergeTime = [
            'with_tone' => $this->getLastMergeTimeFile('with_tone'),
            'no_tone' => $this->getLastMergeTimeFile('no_tone')
        ];
    }

    /**
     * 获取指定声调类型的上次合并时间
     * @param string $toneType 声调类型（with_tone/no_tone）
     * @return int 时间戳
     */
    private function getLastMergeTimeFile($toneType) {
        $path = $this->config['dict']['backup'] . "/last_merge_{$toneType}.txt";
        return file_exists($path) ? (int)file_get_contents($path) : 0;
    }

    /**
     * 更新合并时间记录
     * @param string $toneType 声调类型
     */
    private function updateLastMergeTime($toneType) {
        $now = time();
        $path = $this->config['dict']['backup'] . "/last_merge_{$toneType}.txt";
        file_put_contents($path, $now);
        $this->lastMergeTime[$toneType] = $now;
    }

    /**
     * 检查是否允许合并（基于频率限制）
     * @param string $toneType 声调类型
     * @return bool 是否允许合并
     */
    private function canMerge($toneType) {
        $now = time();
        $lastTime = $this->lastMergeTime[$toneType];
        return ($now - $lastTime) >= $this->config['self_learn_merge']['frequency_limit'];
    }

    /**
     * 备份字典文件（合并前）
     * @param string $type 字典类型（common/self_learn等）
     * @param string $toneType 声调类型
     */
    private function backupDict($type, $toneType) {
        if (!$this->config['self_learn_merge']['backup_before_merge']) {
            return;
        }
        $sourcePath = $this->config['dict'][$type][$toneType];
        if (!file_exists($sourcePath)) {
            return;
        }
        $backupDir = $this->config['dict']['backup'];
        $filename = basename($sourcePath, '.php') . '_' . date('YmdHis') . '.php';
        copy($sourcePath, $backupDir . '/' . $filename);
        echo "\n📦 已备份字典：{$filename}";
    }

    /**
     * 检查是否需要合并自学习字典（仅记录状态，不执行合并）
     * 作用：避免在业务流程中执行重操作，合并由定时任务触发
     */
    private function checkMergeNeed() {
        $needMerge = [];
        foreach (['with_tone', 'no_tone'] as $toneType) {
            $this->loadSelfLearnDict($toneType === 'with_tone');
            $selfLearnCount = count($this->dicts['self_learn'][$toneType]);
            if ($selfLearnCount >= $this->config['self_learn_merge']['threshold'] && $this->canMerge($toneType)) {
                $needMerge[$toneType] = true;
            }
        }
        if (!empty($needMerge)) {
            error_log("[PinyinConverter] 需要合并的字典：" . implode(',', array_keys($needMerge)));
        }
    }

    /**
     * 执行自学习字典合并（供定时任务调用）
     * @return array 合并结果（成功/失败列表）
     */
    public function executeMerge() {
        $result = ['success' => [], 'fail' => []];
        foreach (['with_tone', 'no_tone'] as $toneType) {
            try {
                $this->loadSelfLearnDict($toneType === 'with_tone');
                $selfLearnCount = count($this->dicts['self_learn'][$toneType]);
                // 检查是否满足合并条件
                if ($selfLearnCount < $this->config['self_learn_merge']['threshold'] || !$this->canMerge($toneType)) {
                    continue;
                }

                echo "\n🔗 开始合并自学习字典（{$toneType}），当前条目数：{$selfLearnCount}";
                // 计算本次合并数量（增量合并或全量合并）
                $mergeCount = $this->config['self_learn_merge']['incremental']
                    ? min($selfLearnCount - $this->config['self_learn_merge']['threshold'] + 1, $this->config['self_learn_merge']['max_per_merge'])
                    : $selfLearnCount;

                // 执行合并
                $this->mergeToCommonDict($toneType, $mergeCount);
                $this->cleanupAfterMerge($toneType, $mergeCount);
                $this->updateLastMergeTime($toneType);
                $result['success'][] = $toneType;
            } catch (Exception $e) {
                $result['fail'][] = [
                    'toneType' => $toneType,
                    'error' => $e->getMessage()
                ];
                error_log("[PinyinConverter] 合并失败（{$toneType}）：" . $e->getMessage());
            }
        }
        return $result;
    }

    /**
     * 将自学习字典内容合并到常用字典
     * @param string $toneType 声调类型
     * @param int $mergeCount 本次合并的条目数
     */
    private function mergeToCommonDict($toneType, $mergeCount) {
        // 备份字典
        $this->backupDict('common', $toneType);
        $this->backupDict('self_learn', $toneType);

        // 读取常用字典数据
        $commonPath = $this->config['dict']['common'][$toneType];
        $commonData = file_exists($commonPath) ? require $commonPath : [];
        $commonData = $this->formatPinyinArray($commonData);

        // 读取自学习字典并按频率排序（高频优先）
        $selfLearnData = $this->dicts['self_learn'][$toneType];
        $sortedChars = $this->sortSelfLearnByFrequency($selfLearnData, $toneType);

        // 合并高频字到常用字典（排除已存在的字）
        $mergedChars = [];
        foreach ($sortedChars as $char) {
            if (count($mergedChars) >= $mergeCount) {
                break;
            }
            if (!isset($commonData[$char])) {
                $commonData[$char] = $selfLearnData[$char];
                $mergedChars[] = $char;
            }
        }

        // 按频率排序常用字典（提升可读性）
        if ($this->config['self_learn_merge']['sort_by_frequency']) {
            $commonData = $this->sortCommonDictByFrequency($commonData, $toneType);
        }

        // 保存合并后的常用字典（短数组格式）
        $arrayStr = $this->shortArrayExport($commonData);
        $content = "<?php\n/** 常用字典（{$toneType}，含自学习合并）**/\nreturn {$arrayStr};\n";
        file_put_contents($commonPath, $content);
        $this->dicts['common'][$toneType] = $commonData;
        echo "\n✅ 已合并" . count($mergedChars) . "条至常用字典（{$toneType}）";
    }

    /**
     * 按使用频率排序自学习汉字（降序）
     * @param array $selfLearnData 自学习字典数据
     * @param string $toneType 声调类型
     * @return array 排序后的汉字列表
     */
    private function sortSelfLearnByFrequency($selfLearnData, $toneType) {
        $chars = array_keys($selfLearnData);
        usort($chars, function ($a, $b) use ($toneType) {
            $freqA = $this->charFrequency[$toneType][$a] ?? 0;
            $freqB = $this->charFrequency[$toneType][$b] ?? 0;
            return $freqB - $freqA; // 降序（高频在前）
        });
        return $chars;
    }

    /**
     * 按使用频率排序常用字典（高频字在前）
     * @param array $commonData 常用字典数据
     * @param string $toneType 声调类型
     * @return array 排序后的字典数据
     */
    private function sortCommonDictByFrequency($commonData, $toneType) {
        $chars = array_keys($commonData);
        usort($chars, function ($a, $b) use ($toneType) {
            $freqA = $this->charFrequency[$toneType][$a] ?? 0;
            $freqB = $this->charFrequency[$toneType][$b] ?? 0;
            return $freqB - $freqA;
        });
        // 重新构建有序数组
        $sorted = [];
        foreach ($chars as $char) {
            $sorted[$char] = $commonData[$char];
        }
        return $sorted;
    }

    /**
     * 合并后清理自学习字典和生僻字字典
     * @param string $toneType 声调类型
     * @param int $mergeCount 合并的条目数
     */
    private function cleanupAfterMerge($toneType, $mergeCount) {
        $withTone = $toneType === 'with_tone';
        $selfLearnData = $this->dicts['self_learn'][$toneType];
        $sortedChars = $this->sortSelfLearnByFrequency($selfLearnData, $toneType);
        // 仅清理已合并的条目
        $charsToClean = array_slice($sortedChars, 0, $mergeCount);

        // 从自学习字典中删除已合并的字
        foreach ($charsToClean as $char) {
            unset($selfLearnData[$char]);
        }
        $selfLearnPath = $this->config['dict']['self_learn'][$toneType];
        $arrayStr = $this->shortArrayExport($selfLearnData);
        $content = "<?php\nreturn {$arrayStr};\n";
        file_put_contents($selfLearnPath, $content);
        $this->dicts['self_learn'][$toneType] = $selfLearnData;
        $this->learnedChars[$toneType] = array_diff_key($this->learnedChars[$toneType], array_flip($charsToClean));
        echo "\n🧹 已移除" . count($charsToClean) . "条自学习内容（{$toneType}）";

        // 从频率记录中删除已合并的字
        foreach ($charsToClean as $char) {
            unset($this->charFrequency[$toneType][$char]);
        }
        $this->saveSelfLearnFrequency();

        // 清理生僻字字典中对应条目
        $this->loadRareDict($withTone);
        $rarePath = $this->config['dict']['rare'][$toneType];
        $rareData = $this->dicts['rare'][$toneType];
        $commonCount = count($this->dicts['common'][$toneType] ?? []);

        foreach ($charsToClean as $char) {
            $code = mb_ord($char, 'UTF-8');
            // 仅处理汉字Unicode范围（19968-40869）
            if ($code < 19968 || $code > 40869) {
                continue;
            }
            $index = $code - 19968; // 计算在基本区中的索引
            $rareIndex = $index - $commonCount; // 生僻字在数组中的索引
            if ($rareIndex >= 0 && isset($rareData[$rareIndex])) {
                unset($rareData[$rareIndex]);
            }
        }

        // 重新索引并保存生僻字字典
        $rareData = array_values($rareData);
        $arrayStr = $this->shortArrayExport($rareData);
        file_put_contents($rarePath, "<?php\nreturn {$arrayStr};\n");
        $this->dicts['rare'][$toneType] = $rareData;
    }

    /**
     * 加载多音字规则字典
     */
    private function loadPolyphoneRules() {
        if ($this->dicts['polyphone_rules'] !== null) {
            return;
        }
        $path = $this->config['dict']['polyphone_rules'];
        if (!file_exists($path)) {
            file_put_contents($path, "<?php\nreturn [];\n");
            $this->dicts['polyphone_rules'] = [];
            return;
        }
        $data = require $path;
        $this->dicts['polyphone_rules'] = is_array($data) ? $data : [];
    }

    /**
     * 加载自学习字典
     * @param bool $withTone 是否带声调
     */
    private function loadSelfLearnDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['self_learn'][$type] !== null) {
            return;
        }
        $path = $this->config['dict']['self_learn'][$type];
        $data = file_exists($path) ? require $path : [];
        $this->dicts['self_learn'][$type] = is_array($data) ? $this->formatPinyinArray($data) : [];
    }

    /**
     * 格式化拼音数组（确保值为数组类型，统一格式）
     * @param array $data 原始字典数据
     * @return array 格式化后的字典数据
     */
    private function formatPinyinArray($data) {
        $formatted = [];
        foreach ($data as $char => $pinyin) {
            $formatted[$char] = is_array($pinyin) ? $pinyin : [$pinyin];
        }
        return $formatted;
    }

    /**
     * 加载常用字字典
     * @param bool $withTone 是否带声调
     */
    private function loadCommonDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['common'][$type] !== null) {
            return;
        }
        $path = $this->config['dict']['common'][$type];
        $this->dicts['common'][$type] = file_exists($path) ? $this->formatPinyinArray(require $path) : [];
    }

    /**
     * 加载生僻字字典
     * @param bool $withTone 是否带声调
     */
    private function loadRareDict($withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        if ($this->dicts['rare'][$type] !== null) {
            return;
        }
        $path = $this->config['dict']['rare'][$type];
        $this->dicts['rare'][$type] = file_exists($path) ? require $path : [];
    }

    /**
     * 获取单个汉字的拼音（核心逻辑）
     * 优先级：用户临时指定 > 自定义字典 > 自学习字典 > 常用字典 > 生僻字字典
     * @param string $char 单个汉字
     * @param bool $withTone 是否带声调
     * @param array $context 上下文（前后字符、词语），用于多音字匹配
     * @param array $tempMap 用户临时指定的拼音映射
     * @return string 拼音结果
     */
    private function getCharPinyin($char, $withTone, $context = [], $tempMap = []) {
        $type = $withTone ? 'with_tone' : 'no_tone';

        // 1. 最高优先级：用户临时指定（业务场景手动干预）
        if (isset($tempMap[$char])) {
            return $withTone ? $tempMap[$char] : $this->removeTone($tempMap[$char]);
        }

        // 2. 次高优先级：自定义字典（用户手动配置）
        $this->loadCustomDict($withTone);
        if (isset($this->dicts['custom'][$type][$char])) {
            return $this->getFirstPinyin($this->dicts['custom'][$type][$char]);
        }

        // 3. 后续优先级：自学习/常用/生僻字字典
        $pinyinArray = $this->getAllPinyinOptions($char, $withTone);
        if (count($pinyinArray) <= 1) {
            $pinyin = $this->getFirstPinyin($pinyinArray);
        } else {
            // 多音字规则匹配
            $matchedPinyin = $this->matchPolyphoneRule($char, $pinyinArray, $context, $withTone);
            $pinyin = $matchedPinyin !== null ? $matchedPinyin : $pinyinArray[$this->config['polyphone_priority'][$char] ?? 0];
        }

        // 记录自学习字的使用频率
        if (isset($this->dicts['self_learn'][$type][$char])) {
            $this->charFrequency[$type][$char] = ($this->charFrequency[$type][$char] ?? 0) + 1;
        }

        return $pinyin;
    }

    /**
     * 按优先级获取所有可能的拼音选项（自学习 > 常用 > 生僻字）
     * @param string $char 单个汉字
     * @param bool $withTone 是否带声调
     * @return array 拼音数组（可能含多音字）
     */
    private function getAllPinyinOptions($char, $withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';

        // 1. 自学习字典（系统自动学习的生僻字）
        if (isset($this->dicts['self_learn'][$type][$char])) {
            return $this->dicts['self_learn'][$type][$char];
        }

        // 2. 常用字字典（系统内置通用字）
        $this->loadCommonDict($withTone);
        if (isset($this->dicts['common'][$type][$char])) {
            return $this->dicts['common'][$type][$char];
        }

        // 3. 生僻字字典（系统内置罕见字）
        $this->loadRareDict($withTone);
        $code = mb_ord($char, 'UTF-8');
        // 仅处理汉字Unicode范围（19968-40869）
        if ($code >= 19968 && $code <= 40869) {
            $index = $code - 19968; // 计算在基本区中的索引
            $commonCount = count($this->dicts['common'][$type] ?? []);
            $rareIndex = $index - $commonCount; // 生僻字在数组中的索引
            if ($rareIndex >= 0 && isset($this->dicts['rare'][$type][$rareIndex]) && !empty($this->dicts['rare'][$type][$rareIndex])) {
                $rawPinyin = $this->dicts['rare'][$type][$rareIndex];
                $this->learnChar($char, $rawPinyin, $withTone); // 自动学习到自学习字典
                return is_array($rawPinyin) ? $rawPinyin : [$rawPinyin];
            }
        }

        // 所有字典未命中，返回原字符
        return [$char];
    }

    /**
     * 匹配多音字规则（基于上下文）
     * @param string $char 汉字
     * @param array $pinyinArray 可能的拼音选项
     * @param array $context 上下文（prev:前一个字, next:后一个字, word:词语）
     * @param bool $withTone 是否带声调
     * @return string|null 匹配到的拼音（未匹配则返回null）
     */
    private function matchPolyphoneRule($char, $pinyinArray, $context, $withTone) {
        $rules = $this->dicts['polyphone_rules'][$char] ?? [];
        if (empty($rules)) {
            return null;
        }

        $prevChar = $context['prev'] ?? '';
        $nextChar = $context['next'] ?? '';
        $word = $context['word'] ?? '';

        foreach ($rules as $rule) {
            $ruleType = $rule['type'] ?? ''; // 规则类型：pre(前字匹配)/post(后字匹配)/word(词语匹配)
            $target = $rule['char'] ?? $rule['word'] ?? ''; // 匹配目标
            $rulePinyin = $rule['pinyin'] ?? ''; // 规则对应的拼音

            // 拼音不在候选列表中，跳过
            if (empty($rulePinyin) || !in_array($rulePinyin, $pinyinArray)) {
                continue;
            }

            // 匹配前字规则
            if ($ruleType === 'pre' && $prevChar === $target) {
                return $rulePinyin;
            }
            // 匹配后字规则
            if ($ruleType === 'post' && $nextChar === $target) {
                return $rulePinyin;
            }
            // 匹配词语规则
            if ($ruleType === 'word' && $word === $target) {
                return $rulePinyin;
            }
        }

        return null;
    }

    /**
     * 自动学习生僻字到自学习字典
     * @param string $char 汉字
     * @param array|string $rawPinyin 拼音
     * @param bool $withTone 是否带声调
     */
    private function learnChar($char, $rawPinyin, $withTone) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        // 已学习过则跳过
        if (isset($this->dicts['self_learn'][$type][$char]) || isset($this->learnedChars[$type][$char])) {
            return;
        }
        // 格式化拼音数组
        $pinyinArray = is_array($rawPinyin) ? $rawPinyin : [$rawPinyin];
        if ($withTone) {
            $this->learnedChars[$type][$char] = $pinyinArray;
            $showPinyin = implode('/', $pinyinArray);
            echo "\n🔍 自动学习带声调汉字：{$char}（拼音：{$showPinyin}）";
        } else {
            $noToneArray = array_map([$this, 'removeTone'], $pinyinArray);
            $this->learnedChars[$type][$char] = $noToneArray;
            $showPinyin = implode('/', $noToneArray);
            echo "\n🔍 自动学习无声调汉字：{$char}（拼音：{$showPinyin}）";
        }
        // 临时保存到内存字典
        $this->dicts['self_learn'][$type][$char] = $this->learnedChars[$type][$char];
        // 初始化频率为0（首次使用时+1）
        $this->charFrequency[$type][$char] = 0;
    }

    /**
     * 保存自学习内容到文件（对象销毁时触发）
     */
    private function saveLearnedChars() {
        foreach (['with_tone', 'no_tone'] as $type) {
            if (empty($this->learnedChars[$type])) {
                continue;
            }
            $path = $this->config['dict']['self_learn'][$type];
            $existing = require $path;
            $existing = $this->formatPinyinArray($existing);
            // 合并新学习的内容
            $merged = array_merge($existing, $this->learnedChars[$type]);
            // 保存为短数组格式
            $arrayStr = $this->shortArrayExport($merged);
            $content = "<?php\n/** 自学习字典（{$type}）**/\nreturn {$arrayStr};\n";
            file_put_contents($path, $content);
            $this->dicts['self_learn'][$type] = $merged;
            $this->learnedChars[$type] = []; // 清空临时缓存
        }
        // 保存频率数据
        $this->saveSelfLearnFrequency();
        // 检查合并需求
        $this->checkMergeNeed();
    }

    /**
     * 获取拼音数组中的第一个有效拼音
     * @param array $pinyinArray 拼音数组
     * @return string 第一个非空拼音
     */
    private function getFirstPinyin($pinyinArray) {
        foreach ($pinyinArray as $pinyin) {
            if (!empty(trim($pinyin))) {
                return trim($pinyin);
            }
        }
        return '';
    }

    /**
     * 移除拼音中的声调
     * @param string $pinyin 带声调的拼音
     * @return string 无声调的拼音
     */
    private function removeTone($pinyin) {
        $toneMap = [
            'ā' => 'a', 'á' => 'a', 'ǎ' => 'a', 'à' => 'a',
            'ō' => 'o', 'ó' => 'o', 'ǒ' => 'o', 'ò' => 'o',
            'ē' => 'e', 'é' => 'e', 'ě' => 'e', 'è' => 'e',
            'ī' => 'i', 'í' => 'i', 'ǐ' => 'i', 'ì' => 'i',
            'ū' => 'u', 'ú' => 'u', 'ǔ' => 'u', 'ù' => 'u',
            'ü' => 'v', 'ǖ' => 'v', 'ǘ' => 'v', 'ǚ' => 'v', 'ǜ' => 'v',
            'ń' => 'n', 'ň' => 'n', '' => 'm'
        ];
        return strtr($pinyin, $toneMap);
    }

    /**
     * 处理特殊字符（保留/删除/替换）
     * @param string $char 特殊字符
     * @param array $charConfig 处理配置（mode:模式, map:替换映射）
     * @return string 处理后的字符
     */
    private function handleSpecialChar($char, $charConfig) {
        $mode = $charConfig['mode'];
        $customMap = $charConfig['map'];
        $deleteAllow = $this->config['special_char']['delete_allow'];

        // 汉字不处理
        if (preg_match('/\p{Han}/u', $char)) {
            return $char;
        }

        switch ($mode) {
            case 'keep':
                return $char; // 保留所有特殊字符
            case 'delete':
                // 仅保留允许的字符（delete_allow配置）
                return preg_match("/^[{$deleteAllow}]$/", $char) ? $char : '';
            case 'replace':
                // 按映射替换，无映射则使用默认映射
                return $customMap[$char] ?? $this->finalCharMap[$char] ?? $char;
            default:
                return '';
        }
    }

    /**
     * 解析特殊字符处理参数
     * @param string|array $specialCharParam 特殊字符处理参数（模式或数组配置）
     * @return array 标准化的配置（mode:模式, map:替换映射）
     */
    private function parseCharParam($specialCharParam) {
        $defaultMode = $this->config['special_char']['default_mode'];
        if (is_string($specialCharParam)) {
            return [
                'mode' => in_array($specialCharParam, ['keep', 'delete', 'replace']) ? $specialCharParam : $defaultMode,
                'map' => []
            ];
        }
        if (is_array($specialCharParam)) {
            return [
                'mode' => isset($specialCharParam['mode']) && in_array($specialCharParam['mode'], ['keep', 'delete', 'replace'])
                    ? $specialCharParam['mode']
                    : $defaultMode,
                'map' => isset($specialCharParam['map']) && is_array($specialCharParam['map'])
                    ? $specialCharParam['map']
                    : []
            ];
        }
        return ['mode' => $defaultMode, 'map' => []];
    }

    /**
     * 替换文本中的自定义多字词语为拼音
     * @param string $text 原始文本
     * @param bool $withTone 是否带声调
     * @param string $separator 拼音分隔符
     * @return string 替换后的文本（多字词语已替换为拼音）
     */
    private function replaceCustomMultiWords($text, $withTone, $separator) {
        $type = $withTone ? 'with_tone' : 'no_tone';
        $result = $text;
        $replaced = []; // 记录已替换的位置，避免重复替换

        foreach ($this->customMultiWords[$type] as $item) {
            $word = $item['word'];
            $wordLen = $item['length'];
            $pinyin = implode($separator, $item['pinyin']); // 拼接多字拼音
            $textLen = mb_strlen($result, 'UTF-8');

            // 遍历文本查找匹配的词语
            for ($i = 0; $i <= $textLen - $wordLen; $i++) {
                if (isset($replaced[$i])) {
                    continue; // 跳过已替换的位置
                }

                $substr = mb_substr($result, $i, $wordLen, 'UTF-8');
                if ($substr === $word) {
                    // 替换当前位置的词语为拼音
                    $result = mb_substr($result, 0, $i, 'UTF-8')
                        . $pinyin
                        . mb_substr($result, $i + $wordLen, null, 'UTF-8');
                    // 标记已替换的位置
                    for ($j = $i; $j < $i + $wordLen; $j++) {
                        $replaced[$j] = true;
                    }
                    // 重新计算文本长度（替换后长度可能变化）
                    $textLen = mb_strlen($result, 'UTF-8');
                }
            }
        }

        return $result;
    }

    /**
     * 自定义短数组序列化（生成[]格式，替代var_export的array()）
     * @param array $array 要序列化的数组
     * @param int $indent 缩进空格数（美化格式）
     * @return string 短数组格式的字符串
     */
    private function shortArrayExport($array, $indent = 4) {
        if (empty($array)) {
            return '[]';
        }

        // 判断是否为关联数组
        $isAssoc = array_keys($array) !== range(0, count($array) - 1);
        $spaces = str_repeat(' ', $indent);
        $result = "[" . "\n";

        foreach ($array as $key => $value) {
            // 处理键名（关联数组需要key => value格式）
            $keyStr = $isAssoc ? (is_string($key) ? "'{$key}'" : $key) . " => " : '';

            // 递归处理值（数组/字符串/其他类型）
            if (is_array($value)) {
                $valueStr = $this->shortArrayExport($value, $indent + 4);
            } elseif (is_string($value)) {
                // 转义单引号，避免语法错误
                $valueStr = "'" . str_replace("'", "\'", $value) . "'";
            } else {
                // 数字、布尔等类型直接导出
                $valueStr = var_export($value, true);
            }

            $result .= "{$spaces}{$keyStr}{$valueStr},\n";
        }

        // 闭合数组并调整缩进
        $result .= str_repeat(' ', $indent - 4) . "]";
        return $result;
    }

    /**
     * 转换文本为拼音
     * @param string $text 待转换的文本
     * @param string $separator 拼音之间的分隔符（默认空格）
     * @param bool $withTone 是否带声调（默认false）
     * @param string|array $specialCharParam 特殊字符处理参数（模式或配置数组）
     * @param array $polyphoneTempMap 用户临时指定的多音字映射（如['行' => 'xíng']）
     * @return string 转换后的拼音文本
     */
    public function convert(
        $text,
        $separator = ' ',
        $withTone = false,
        $specialCharParam = '',
        $polyphoneTempMap = []
    ) {
        // 解析特殊字符处理配置
        $charConfig = $this->parseCharParam($specialCharParam);
        // 生成缓存键（基于所有参数）
        $cacheKey = md5(json_encode([$text, $separator, $withTone, $charConfig, $polyphoneTempMap]));

        // 检查缓存（命中则返回）
        foreach ($this->cache as $item) {
            if ($item->key === $cacheKey) {
                $this->cache->detach($item);
                $this->cache->attach($item); // 移到末尾，提升热点缓存优先级
                return $item->value;
            }
        }

        // 优先替换自定义多字词语
        $textAfterMultiWords = $this->replaceCustomMultiWords($text, $withTone, $separator);

        // 处理剩余字符（单字或未匹配的多字）
        $rawChars = [];
        $len = mb_strlen($textAfterMultiWords, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($textAfterMultiWords, $i, 1, 'UTF-8');
            $isHan = preg_match('/\p{Han}/u', $char) ? true : false;
            $handledChar = $isHan ? $char : $this->handleSpecialChar($char, $charConfig);
            if ($handledChar !== '' || $isHan) {
                $rawChars[] = [
                    'value' => $handledChar,
                    'isHan' => $isHan,
                    'index' => $i
                ];
            }
        }

        // 拼接最终结果
        $result = '';
        $prevIsHan = null;
        $total = count($rawChars);

        for ($i = 0; $i < $total; $i++) {
            $item = $rawChars[$i];
            $value = $item['value'];
            $currentIsHan = $item['isHan'];

            if ($value === '') {
                continue;
            }

            // 构建上下文（用于多音字匹配）
            $context = [];
            if ($currentIsHan) {
                $context['prev'] = ($i > 0 && $rawChars[$i - 1]['isHan']) ? $rawChars[$i - 1]['value'] : '';
                $context['next'] = ($i < $total - 1 && $rawChars[$i + 1]['isHan']) ? $rawChars[$i + 1]['value'] : '';
                $wordChars = [];
                if ($i > 0 && $rawChars[$i - 1]['isHan']) {
                    $wordChars[] = $rawChars[$i - 1]['value'];
                }
                $wordChars[] = $value;
                if ($i < $total - 1 && $rawChars[$i + 1]['isHan']) {
                    $wordChars[] = $rawChars[$i + 1]['value'];
                }
                $context['word'] = implode('', $wordChars);
            }

            // 获取拼音（单字处理）
            $currentValue = $currentIsHan
                ? $this->getCharPinyin($value, $withTone, $context, $polyphoneTempMap)
                : $value;

            // 添加分隔符
            if ($result !== '') {
                if ($currentIsHan) {
                    $result .= $separator;
                } elseif ($prevIsHan !== null && $prevIsHan !== $currentIsHan) {
                    $result .= $separator;
                }
            }

            $result .= $currentValue;
            $prevIsHan = $currentIsHan;
        }

        // 缓存结果（超出大小则移除最久未使用的）
        $cacheItem = (object)['key' => $cacheKey, 'value' => $result];
        $this->cache->attach($cacheItem);
        if ($this->cache->count() > $this->config['high_freq_cache']['size']) {
            $this->cache->rewind();
            $this->cache->detach($this->cache->current());
        }

        return $result;
    }

    /**
     * 转换文本为URL友好的拼音slug（小写、连字符分隔）
     * @param string $text 待转换的文本
     * @param string $separator 分隔符（默认连字符-）
     * @return string URL slug
     */
    public function getUrlSlug($text, $separator = '-') {
        $pinyin = $this->convert($text, $separator, false, 'delete');
        return strtolower(preg_replace('/-+/', '-', trim($pinyin, '-')));
    }

    /**
     * 析构函数：保存自学习内容（对象销毁时触发）
     */
    public function __destruct() {
        $this->saveLearnedChars();
    }
}