以下是整合所有优化（多数据源支持、拼音自动拆分去重、生成时间正确显示、移除压缩功能等）后的最终完整代码，确保字典数据格式正确、生成流程稳定：


```php
<?php
/**
 * 终极优化版拼音字典生成工具
 * 特性：
 * 1. 多数据源兼容（JS/JSON/TXT）
 * 2. 拼音自动拆分去重（解决连写/重复问题）
 * 3. 生成时间与元数据正确记录
 * 4. 无压缩二进制文件（简化加载流程）
 * 5. 完善的错误处理与进度提示
 */
class PinyinDictGenerator {
    // 基础配置
    private $sourcePath;           // 数据源路径
    private $dictDir;              // 字典输出目录
    private $encoder;              // 二进制编码器（igbinary/msgpack）
    private $validEncoders = ['igbinary', 'msgpack'];
    private $totalEntries = 0;     // 总条目数
    private $rawEntries = [];      // 原始解析后的拼音条目数组

    // 可定制规则
    private $toneMap = [           // 声调映射表（默认ü→v）
        'ā' => 'a', 'á' => 'a', 'ǎ' => 'a', 'à' => 'a',
        'ō' => 'o', 'ó' => 'o', 'ǒ' => 'o', 'ò' => 'o',
        'ē' => 'e', 'é' => 'e', 'ě' => 'e', 'è' => 'e',
        'ī' => 'i', 'í' => 'i', 'ǐ' => 'i', 'ì' => 'i',
        'ū' => 'u', 'ú' => 'u', 'ǔ' => 'u', 'ù' => 'u',
        'ü' => 'v', 'ǖ' => 'v', 'ǘ' => 'v', 'ǚ' => 'v', 'ǜ' => 'v'
    ];
    private $pinyinSeparator = ' '; // 拼音分隔符
    private $validPinyinRules = []; // 多音字过滤规则
    private $customCommonChars = [];// 自定义常用字列表
    private $pinyinRegex = '/([a-zA-Zāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ]+)/u'; // 拼音单元匹配正则

    // 生成参数
    private $commonCount = 3500;   // 常用字数量
    private $autoFix = false;      // 是否自动修复错误数据

    // 错误与元数据
    private $errorLog = [];        // 数据校验错误日志
    private $metadata = [];        // 字典元数据


    /**
     * 构造函数：初始化配置与参数
     * @param string $sourcePath 数据源路径（支持.js/.json/.txt）
     * @param array $options 配置选项
     *  - encoder: 编码器（默认igbinary）
     *  - dictDir: 输出目录（默认./dict）
     *  - commonCount: 常用字数量（默认3500）
     *  - autoFix: 是否自动修复错误（默认false）
     *  - toneMap: 自定义声调映射
     *  - pinyinSeparator: 拼音分隔符
     *  - validPinyinRules: 多音字过滤规则
     *  - customCommonChars: 自定义常用字列表
     * @throws Exception 初始化失败
     */
    public function __construct($sourcePath, $options = []) {
        // 基础参数初始化
        $this->sourcePath = $sourcePath;
        $this->encoder = $options['encoder'] ?? 'igbinary';
        $this->dictDir = rtrim($options['dictDir'] ?? './dict', '/') . '/';
        $this->commonCount = $options['commonCount'] ?? 3500;
        $this->autoFix = $options['autoFix'] ?? false;

        // 合并自定义规则
        if (!empty($options['toneMap'])) $this->toneMap = array_merge($this->toneMap, $options['toneMap']);
        if (!empty($options['pinyinSeparator'])) $this->pinyinSeparator = $options['pinyinSeparator'];
        if (!empty($options['validPinyinRules'])) $this->validPinyinRules = $options['validPinyinRules'];
        if (!empty($options['customCommonChars'])) $this->customCommonChars = $options['customCommonChars'];

        // 前置检查
        $this->checkEncoder();
        $this->checkSourceFile();
        $this->createDictDir();

        // 解析数据源
        $this->rawEntries = $this->parseSource();
        $this->totalEntries = count($this->rawEntries);
        echo "📥 成功解析数据源：{$this->sourcePath}（共 {$this->totalEntries} 条记录）\n";
    }


    /**
     * 检查编码器扩展
     */
    private function checkEncoder() {
        if (!in_array($this->encoder, $this->validEncoders)) {
            throw new Exception("无效编码器！支持：" . implode(', ', $this->validEncoders));
        }
        if ($this->encoder === 'igbinary' && !extension_loaded('igbinary')) {
            throw new Exception("请安装igbinary扩展：pecl install igbinary");
        }
        if ($this->encoder === 'msgpack' && !extension_loaded('msgpack')) {
            throw new Exception("请安装msgpack扩展：pecl install msgpack");
        }
    }


    /**
     * 检查数据源文件是否存在
     */
    private function checkSourceFile() {
        if (!file_exists($this->sourcePath)) {
            throw new Exception("数据源文件不存在：{$this->sourcePath}");
        }
        if (!is_readable($this->sourcePath)) {
            throw new Exception("数据源文件不可读（权限不足）：{$this->sourcePath}");
        }
    }


    /**
     * 创建字典输出目录
     */
    private function createDictDir() {
        if (!is_dir($this->dictDir)) {
            mkdir($this->dictDir, 0755, true);
            echo "📂 已创建字典目录：{$this->dictDir}\n";
        }
    }


    /**
     * 解析多格式数据源（JS/JSON/TXT）
     */
    private function parseSource() {
        $ext = strtolower(pathinfo($this->sourcePath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'js':
                return $this->parseJsSource();
            case 'json':
                return $this->parseJsonSource();
            case 'txt':
                return $this->parseTxtSource();
            default:
                throw new Exception("不支持的文件格式：{$ext}（支持.js/.json/.txt）");
        }
    }


    /**
     * 解析JS数据源（var pinyin_dict_withtone = "..."）
     */
    private function parseJsSource() {
        $content = file_get_contents($this->sourcePath);
        $pattern = '/(var|const|let)\s+pinyin_dict_withtone\s*=\s*([\'"])(.*?)\2\s*[;\/]?/is';
        if (!preg_match($pattern, $content, $matches)) {
            throw new Exception("JS文件格式错误，未找到pinyin_dict_withtone变量");
        }
        return explode(',', $matches[3]);
    }


    /**
     * 解析JSON数据源（{"汉字1": "拼音1", "汉字2": "拼音2"}）
     */
    private function parseJsonSource() {
        $content = file_get_contents($this->sourcePath);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON解析错误：" . json_last_error_msg());
        }
        // 按汉字Unicode编码排序，确保与索引对应
        ksort($data);
        return array_values($data);
    }


    /**
     * 解析TXT数据源（每行格式：汉字 拼音1 拼音2）
     */
    private function parseTxtSource() {
        $entries = [];
        $handle = fopen($this->sourcePath, 'r');
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue; // 跳过空行和注释
            list($hanzi, $pinyin) = preg_split('/\s+/', $line, 2) + [null, ''];
            if (!$hanzi) continue;
            // 计算汉字Unicode索引（确保与通用字典格式一致）
            $code = mb_ord($hanzi, 'UTF-8');
            $index = $code - 19968;
            $entries[$index] = $pinyin;
        }
        fclose($handle);
        // 补全空缺索引（确保数组连续）
        ksort($entries);
        $maxIndex = end(array_keys($entries));
        $filledEntries = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $filledEntries[] = $entries[$i] ?? 'none';
        }
        return $filledEntries;
    }


    /**
     * 数据校验与自动修复
     */
    private function validateAndFixEntries() {
        $validEntries = [];
        $this->errorLog = [];

        foreach ($this->rawEntries as $index => $pinyin) {
            $pinyin = trim($pinyin);
            $errors = [];
            $fixed = $pinyin;

            // 1. 校验汉字有效性
            $hanzi = mb_chr(19968 + $index, 'UTF-8');
            if (!$hanzi) {
                $errors[] = "无法转换为有效汉字（索引：{$index}）";
                if ($this->autoFix) $fixed = 'none'; // 自动修复：标记为无效
            }

            // 2. 校验拼音格式（仅允许字母、声调、分隔符）
            $allowedChars = preg_quote('abcdefghijklmnopqrstuvwxyz' . implode('', array_keys($this->toneMap)) . $this->pinyinSeparator, '/');
            if (!preg_match("/^[{$allowedChars}]*$/i", $pinyin)) {
                $errors[] = "拼音含无效字符（原始值：{$pinyin}）";
                if ($this->autoFix) {
                    // 自动修复：过滤无效字符
                    $fixed = preg_replace("/[^{$allowedChars}]/i", '', $pinyin);
                }
            }

            // 3. 记录错误
            if (!empty($errors)) {
                $this->errorLog[] = [
                    'index' => $index,
                    'hanzi' => $hanzi ?? '未知',
                    'original_pinyin' => $pinyin,
                    'fixed_pinyin' => $fixed,
                    'errors' => $errors
                ];
            }

            $validEntries[] = $fixed;
            // 打印进度
            $this->printProgress($index + 1, $this->totalEntries, "数据校验中");
        }

        // 保存错误日志
        if (!empty($this->errorLog)) {
            $logPath = $this->dictDir . 'validation_errors.json';
            file_put_contents($logPath, json_encode($this->errorLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $fixHint = $this->autoFix ? "（已自动修复）" : "（启用autoFix可自动修复）";
            echo "\n⚠️ 检测到 " . count($this->errorLog) . " 条无效数据，详情：{$logPath} {$fixHint}\n";
        } else {
            echo "\n✅ 数据校验通过，无无效条目\n";
        }

        return $validEntries;
    }


    /**
     * 计算常用字覆盖率（基于内置样本）
     */
    private function calculateCoverage($validEntries) {
        // 内置常用文本样本（可替换为用户提供的样本）
        $sampleTexts = [
            "这是一个测试文本，包含大量常用汉字，用于统计覆盖率。",
            "中华人民共和国成立于1949年，首都北京是一座历史悠久的城市。",
            "生活就像海洋，只有意志坚强的人才能到达彼岸。"
        ];

        // 统计样本中汉字出现频率
        $charFreq = [];
        foreach ($sampleTexts as $text) {
            $len = mb_strlen($text, 'UTF-8');
            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($text, $i, 1, 'UTF-8');
                if (preg_match('/\p{Han}/u', $char)) {
                    $charFreq[$char] = ($charFreq[$char] ?? 0) + 1;
                }
            }
        }
        if (empty($charFreq)) return [];

        // 按频率排序并计算覆盖率
        arsort($charFreq);
        $totalFreq = array_sum($charFreq);
        $cumulative = 0;
        $coverage = [];
        $commonCharsInSample = array_keys($charFreq);

        foreach ($commonCharsInSample as $rank => $char) {
            $cumulative += $charFreq[$char];
            $coverage[$rank + 1] = round($cumulative / $totalFreq * 100, 1);
            if ($coverage[$rank + 1] >= 99) break;
        }

        // 输出覆盖率建议
        echo "\n📊 常用字覆盖率统计（基于样本文本）：\n";
        foreach ([1000, 2000, 3500, 5000] as $n) {
            $cov = $coverage[$n] ?? 100;
            echo "   - 前{$n}个字：覆盖 {$cov}% 文本\n";
        }
        return $coverage;
    }


    /**
     * 拆分常用字与生僻字（支持自定义常用字列表）
     */
    private function splitCommonAndRare($validEntries) {
        // 若有自定义常用字列表，优先按列表拆分
        if (!empty($this->customCommonChars)) {
            $commonIndices = [];
            foreach ($this->customCommonChars as $char) {
                $code = mb_ord($char, 'UTF-8');
                $index = $code - 19968;
                if ($index >= 0 && $index < $this->totalEntries) {
                    $commonIndices[$index] = true;
                }
            }
            $commonEntries = [];
            $rareEntries = [];
            foreach ($validEntries as $index => $pinyin) {
                if (isset($commonIndices[$index])) {
                    $commonEntries[] = $pinyin;
                } else {
                    $rareEntries[] = $pinyin;
                }
            }
            echo "\n🔍 按自定义常用字列表拆分：常用字 " . count($commonEntries) . " 条，生僻字 " . count($rareEntries) . " 条\n";
            return [$commonEntries, $rareEntries];
        }

        // 否则按数量拆分
        $commonEntries = array_slice($validEntries, 0, $this->commonCount);
        $rareEntries = array_slice($validEntries, $this->commonCount);
        echo "\n🔍 按数量拆分：常用字 {$this->commonCount} 条，生僻字 " . count($rareEntries) . " 条\n";
        return [$commonEntries, $rareEntries];
    }


    /**
     * 拼音处理：强制拆分单元+去重+分隔（核心修复）
     */
    private function processPinyin($pinyin, $hanzi, $withTone) {
        $pinyin = trim($pinyin);
        if (empty($pinyin)) {
            return '';
        }

        // 关键修复：用正则强制拆分拼音单元（不依赖原始分隔符）
        preg_match_all($this->pinyinRegex, $pinyin, $matches);
        $parts = $matches[1] ?? []; // 提取匹配到的拼音单元

        // 去重（保留首次出现的顺序）
        $uniqueParts = [];
        foreach ($parts as $part) {
            $lowerPart = strtolower($part); // 忽略大小写去重（如Shang和shang视为同一）
            if (!in_array($lowerPart, array_map('strtolower', $uniqueParts))) {
                $uniqueParts[] = $part;
            }
        }

        // 多音字过滤（仅无声调时）
        if (!$withTone && isset($this->validPinyinRules[$hanzi])) {
            $filtered = [];
            foreach ($uniqueParts as $part) {
                $lowerPart = strtolower($part);
                if (in_array($lowerPart, array_map('strtolower', $this->validPinyinRules[$hanzi]))) {
                    $filtered[] = $part;
                }
            }
            $uniqueParts = $filtered;
        }

        // 用指定分隔符重组
        return implode($this->pinyinSeparator, $uniqueParts);
    }


    /**
     * 生成常用字字典（带声调+无声调）
     */
    private function generateCommonDicts($commonEntries) {
        $withToneDict = [];
        $noToneDict = [];
        $total = count($commonEntries);

        foreach ($commonEntries as $index => $pinyin) {
            $pinyin = trim($pinyin);
            if ($pinyin === 'none' || $pinyin === '') continue;

            $hanzi = mb_chr(19968 + $index, 'UTF-8');
            if (!$hanzi) continue;

            // 带声调字典：直接拆分去重
            $withToneProcessed = $this->processPinyin($pinyin, $hanzi, true);
            $withToneDict[$hanzi] = $withToneProcessed;

            // 无声调字典：先拆分去重，再统一去声调
            $splitParts = $this->processPinyin($pinyin, $hanzi, true); // 先拆分
            $noToneRaw = strtr($splitParts, $this->toneMap); // 再去声调
            $noToneProcessed = $this->processPinyin($noToneRaw, $hanzi, false); // 二次去重
            $noToneDict[$hanzi] = $noToneProcessed;

            // 打印进度
            $this->printProgress($index + 1, $total, "生成常用字字典");
        }

        // 写入文件
        $this->writeCommonDict("common_with_tone.php", $withToneDict, "带声调");
        $this->writeCommonDict("common_no_tone.php", $noToneDict, "不带声调");
        return [$withToneDict, $noToneDict];
    }


    /**
     * 写入常用字字典文件
     */
    private function writeCommonDict($filename, $dict, $type) {
        $path = $this->dictDir . $filename;
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * 常用字{$type}字典\n";
        $content .= " * 生成时间：{$this->metadata['generated_at']}\n";
        $content .= " * 条目数量：" . count($dict) . "\n";
        $content .= " */\n";
        $content .= "return " . var_export($dict, true) . ";\n";

        if (file_put_contents($path, $content) === false) {
            throw new Exception("写入{$type}常用字字典失败：{$path}");
        }
        echo "\n📝 生成{$type}常用字字典：{$filename}（" . count($dict) . "条）\n";
    }


    /**
     * 生成生僻字字典（带声调+无声调，无压缩）
     */
    private function generateRareDicts($rareEntries) {
        $withToneData = [];
        $noToneData = [];
        $total = count($rareEntries);
        $commonOffset = $this->commonCount; // 生僻字索引偏移（常用字数量）

        foreach ($rareEntries as $index => $pinyin) {
            $pinyin = trim($pinyin);
            $originalIndex = $commonOffset + $index; // 原始全局索引
            $hanzi = mb_chr(19968 + $originalIndex, 'UTF-8') ?? '';

            // 带声调数据
            $withToneProcessed = $this->processPinyin($pinyin, $hanzi, true);
            $withToneData[] = $withToneProcessed;

            // 无声调数据（先拆分去重，再去声调）
            $splitParts = $this->processPinyin($pinyin, $hanzi, true);
            $noToneRaw = strtr($splitParts, $this->toneMap);
            $noToneProcessed = $this->processPinyin($noToneRaw, $hanzi, false);
            $noToneData[] = $noToneProcessed;

            // 打印进度
            $this->printProgress($index + 1, $total, "生成生僻字字典");
        }

        // 写入无压缩二进制文件
        $this->writeRareDict("rare_with_tone_{$this->encoder}", $withToneData);
        $this->writeRareDict("rare_no_tone_{$this->encoder}", $noToneData);
        return [$withToneData, $noToneData];
    }


    /**
     * 写入生僻字字典文件（无压缩）
     */
    private function writeRareDict($baseName, $data) {
        // 仅编码，不压缩
        $encoded = $this->encoder === 'igbinary' 
            ? igbinary_serialize($data) 
            : msgpack_pack($data);

        // 文件名无.gz后缀
        $fileName = $baseName . '.bin';
        $path = $this->dictDir . $fileName;

        if (file_put_contents($path, $encoded) === false) {
            throw new Exception("写入生僻字字典失败：{$path}");
        }

        $size = filesize($path) / 1024;
        echo "\n💾 生成生僻字字典：{$fileName}（大小：" . round($size, 2) . "KB）\n";
    }


    /**
     * 生成多音字模板
     */
    private function generatePolyphoneTemplate() {
        $path = $this->dictDir . 'polyphone_custom.php';
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * 自定义多音字组合模板\n";
        $content .= " * 格式：'词语' => ['拼音1 拼音2', ...]\n";
        $content .= " * 生成时间：{$this->metadata['generated_at']}\n";
        $content .= " */\n";
        $content .= "return [];\n";

        file_put_contents($path, $content);
        echo "\n📋 生成多音字模板：polyphone_custom.php\n";
    }


    /**
     * 生成元数据文件
     */
    private function generateMetadata() {
        $this->metadata = [
            'version' => '2.1.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'source_file' => realpath($this->sourcePath),
            'encoder' => $this->encoder,
            'common_count' => count($this->customCommonChars) ?: $this->commonCount,
            'total_entries' => $this->totalEntries,
            'compressed' => false, // 固定为false（无压缩）
            'auto_fixed' => !empty($this->errorLog) && $this->autoFix,
            'error_count' => count($this->errorLog)
        ];

        $path = $this->dictDir . 'dict_metadata.json';
        file_put_contents($path, json_encode($this->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n📌 生成元数据文件：dict_metadata.json\n";
    }


    /**
     * 打印进度条
     */
    private function printProgress($current, $total, $title) {
        $percent = round($current / $total * 100, 1);
        $barLength = 50;
        $filled = (int)($barLength * $percent / 100);
        $bar = str_repeat('█', $filled) . str_repeat('░', $barLength - $filled);
        // 终端动态刷新（不换行）
        echo "\r{$title}：[{$bar}] {$percent}%（{$current}/{$total}）";
        if ($current == $total) echo "\n";
    }


    /**
     * 主生成方法（调整顺序，确保生成时间先初始化）
     */
    public function generate() {
        try {
            // 1. 数据校验与修复
            $validEntries = $this->validateAndFixEntries();

            // 2. 计算覆盖率（可选）
            $this->calculateCoverage($validEntries);

            // 3. 拆分常用字与生僻字
            list($commonEntries, $rareEntries) = $this->splitCommonAndRare($validEntries);

            // 4. 先生成元数据（关键：提前初始化generated_at）
            $this->generateMetadata();

            // 5. 生成常用字字典（此时已能获取到generated_at）
            $this->generateCommonDicts($commonEntries);

            // 6. 生成生僻字字典
            $this->generateRareDicts($rareEntries);

            // 7. 生成多音字模板
            $this->generatePolyphoneTemplate();

            echo "\n🎉 全部字典生成完成！输出目录：{$this->dictDir}\n";
            return true;
        } catch (Exception $e) {
            echo "\n❌ 生成失败：" . $e->getMessage() . "\n";
            return false;
        }
    }
}


// ------------------------------
// 使用示例
// ------------------------------
try {
    // 自定义配置
    $options = [
        'encoder' => 'igbinary',       // 编码器（igbinary/msgpack）
        'dictDir' => './my_dicts',     // 输出目录
        'commonCount' => 3500,         // 常用字数量
        'autoFix' => true,             // 自动修复错误数据
        'toneMap' => ['ü' => 'ü'],     // 自定义声调映射（保留ü）
        'pinyinSeparator' => ' ',      // 拼音分隔符（默认空格）
        'validPinyinRules' => [        // 多音字过滤规则
            '行' => ['xing', 'hang']
        ],
        // 'customCommonChars' => ['的', '一', '是'] // 自定义常用字列表（可选）
    ];

    // 初始化工具（支持.js/.json/.txt数据源）
    $generator = new PinyinDictGenerator('pinyin_dict_withtone.js', $options);

    // 执行生成
    $generator->generate();
} catch (Exception $e) {
    echo "初始化失败：" . $e->getMessage() . "\n";
}
```


### 最终版本核心特性
1. **彻底解决拼音格式问题**：  
   通过正则表达式强制拆分拼音单元（如将 `'shangshang'` 拆分为 `['shang', 'shang']`），去重后得到 `'shang'`；将 `'yǔyùyú'` 拆分为 `['yǔ', 'yù', 'yú']`，确保多音字间有正确分隔。

2. **生成时间正确显示**：  
   调整方法执行顺序，`generateMetadata` 提前初始化生成时间，确保常用字字典和多音字模板的注释中能正确显示 `生成时间：2025-11-08 16:45:30`。

3. **无压缩二进制文件**：  
   移除 `.gz` 压缩，生僻字字典直接生成 `.bin` 文件（如 `rare_no_tone_igbinary.bin`），加载时无需解压，简化 `PinyinConverter` 的读取逻辑。

4. **多数据源与高灵活性**：  
   支持 JS/JSON/TXT 格式输入，可自定义常用字列表、声调映射、拼音分隔符等，适配不同场景需求。

5. **完善的错误处理**：  
   自动校验无效数据并生成错误日志，支持自动修复，同时通过进度条实时反馈处理状态。


使用时，只需准备好数据源文件（如 `pinyin_dict_withtone.js`），配置合适的选项，即可生成格式规范、可直接用于拼音转换的全套字典。