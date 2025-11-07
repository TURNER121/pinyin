<?php
/**
 * 对齐JS逻辑的拼音字典生成工具（无硬编码通用版）
 * 核心：通用去声调+重复拼音去重，无任何特定汉字硬编码
 */
class PinyinDictGenerator {
    // 基础配置
    private $sourcePath;
    private $dictDir;
    private $totalEntries = 0;
    private $rawEntries = [];

    // 完全沿用JS的声调映射表
    private $toneMap = [
        'ā' => 'a', 'á' => 'a', 'ǎ' => 'a', 'à' => 'a',
        'ō' => 'o', 'ó' => 'o', 'ǒ' => 'o', 'ò' => 'o',
        'ē' => 'e', 'é' => 'e', 'ě' => 'e', 'è' => 'e',
        'ī' => 'i', 'í' => 'i', 'ǐ' => 'i', 'ì' => 'i',
        'ū' => 'u', 'ú' => 'u', 'ǔ' => 'u', 'ù' => 'u',
        'ü' => 'v', 'ǖ' => 'v', 'ǘ' => 'v', 'ǚ' => 'v', 'ǜ' => 'v',
        'ń' => 'n', 'ň' => 'n', '' => 'm'
    ];

    // 生成参数（无特定字配置）
    private $commonCount = 3500;
    private $autoFix = false;
    private $errorLog = [];
    private $metadata = [];

    /**
     * 构造函数：仅初始化通用配置
     */
    public function __construct($sourcePath, $options = []) {
        $this->sourcePath = $sourcePath;
        $this->dictDir = rtrim($options['dictDir'] ?? './data', '/') . '/';
        $this->commonCount = $options['commonCount'] ?? 3500;
        $this->autoFix = $options['autoFix'] ?? false;

        $this->checkSourceFile();
        $this->createDictDir();
        $this->rawEntries = $this->parseSource();
        $this->totalEntries = count($this->rawEntries);
        echo "📥 成功解析数据源：{$this->sourcePath}（共 {$this->totalEntries} 条记录）\n";
    }

    /**
     * 基础校验：仅检查文件存在性和可读性
     */
    private function checkSourceFile() {
        if (!file_exists($this->sourcePath)) {
            throw new Exception("数据源文件不存在：{$this->sourcePath}");
        }
        if (!is_readable($this->sourcePath)) {
            throw new Exception("数据源文件不可读：{$this->sourcePath}");
        }
    }

    /**
     * 创建目录
     */
    private function createDictDir() {
        if (!is_dir($this->dictDir)) {
            mkdir($this->dictDir, 0755, true);
            echo "📂 已创建字典目录：{$this->dictDir}\n";
        }
    }

    /**
     * 解析数据源：完全对齐JS的索引映射逻辑
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
                throw new Exception("不支持的格式：{$ext}（支持.js/.json/.txt）");
        }
    }

    /**
     * 解析JS数据源：提取pinyin_dict_withtone数组
     */
    private function parseJsSource() {
        $content = file_get_contents($this->sourcePath);
        $pattern = '/(var|const|let)\s+pinyin_dict_withtone\s*=\s*([\'"])(.*?)\2\s*[;\/]?/is';
        if (!preg_match($pattern, $content, $matches)) {
            throw new Exception("未找到pinyin_dict_withtone变量");
        }
        $entries = explode(',', $matches[3]);
        return array_filter($entries, fn($item) => trim($item) !== '');
    }

    /**
     * 解析JSON数据源：转为索引数组
     */
    private function parseJsonSource() {
        $content = file_get_contents($this->sourcePath);
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON解析错误：" . json_last_error_msg());
        }
        ksort($data);
        $entries = [];
        foreach ($data as $char => $pinyin) {
            $index = mb_ord($char, 'UTF-8') - 19968;
            if ($index >= 0) $entries[$index] = $pinyin;
        }
        ksort($entries);
        $maxIndex = end(array_keys($entries)) ?? 0;
        $filled = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $filled[$i] = $entries[$i] ?? '';
        }
        return $filled;
    }

    /**
     * 解析TXT数据源：转为索引数组
     */
    private function parseTxtSource() {
        $entries = [];
        $handle = fopen($this->sourcePath, 'r');
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            list($char, $pinyin) = preg_split('/\s+/', $line, 2) + [null, ''];
            if ($char) {
                $index = mb_ord($char, 'UTF-8') - 19968;
                if ($index >= 0) $entries[$index] = $pinyin;
            }
        }
        fclose($handle);
        return $entries;
    }

    /**
     * 通用数据校验：仅过滤非法字符
     */
    private function validateEntries() {
        $valid = [];
        $this->errorLog = [];
        foreach ($this->rawEntries as $index => $pinyin) {
            $pinyin = trim($pinyin);
            $char = mb_chr($index + 19968, 'UTF-8');
            if (!$char) {
                $this->errorLog[] = "索引{$index}无法转为汉字";
                $valid[$index] = '';
                continue;
            }
            // 仅过滤非拼音相关字符
            $pinyin = preg_replace('/[^\p{L}\sāáǎàōóǒòēéěèīíǐìūúǔùüǖǘǚǜ]/u', '', $pinyin);
            $valid[$index] = $pinyin;
        }
        if (!empty($this->errorLog)) {
            $logPath = $this->dictDir . 'errors.json';
            file_put_contents($logPath, json_encode($this->errorLog, JSON_PRETTY_PRINT));
            echo "\n⚠️  检测到" . count($this->errorLog) . "条无效数据，日志：{$logPath}\n";
        }
        return $valid;
    }

    /**
     * 沿用JS的去声调逻辑：字符直接替换
     */
    private function removeTone($pinyin) {
        return strtr($pinyin, $this->toneMap);
    }

    /**
     * 通用拼音去重：仅去除完全重复的拼音（不处理多音字）
     */
    private function uniquePinyin($pinyin) {
        // 1. 统一空格格式
        $pinyin = preg_replace('/\s+/', ' ', trim($pinyin));
        if (empty($pinyin)) return '';
        // 2. 拆分后去重，保留首次出现顺序
        $parts = explode(' ', $pinyin);
        $uniqueParts = array_unique($parts);
        // 3. 重组
        return implode(' ', $uniqueParts);
    }

    /**
     * 拆分常用字/生僻字：仅按索引拆分，无特殊处理
     */
    private function splitCommonAndRare($validEntries) {
        $common = array_slice($validEntries, 0, $this->commonCount);
        $rare = array_slice($validEntries, $this->commonCount);
        echo "\n🔍 拆分完成：常用字{$this->commonCount}条，生僻字" . count($rare) . "条\n";
        return [$common, $rare];
    }

    /**
     * 生成常用字字典：通用逻辑，无硬编码
     */
    private function generateCommonDicts($commonEntries) {
        $withTone = [];
        $noTone = [];
        foreach ($commonEntries as $index => $pinyin) {
            $pinyin = trim($pinyin);
            if (empty($pinyin)) continue;
            $char = mb_chr($index + 19968, 'UTF-8');
            if (!$char) continue;
            // 去重
            $uniqueWithTone = $this->uniquePinyin($pinyin);
            $withTone[$char] = $uniqueWithTone;
            // 去声调+去重
            $noToneRaw = $this->removeTone($uniqueWithTone);
            $noTone[$char] = $this->uniquePinyin($noToneRaw);
        }
        $this->writeDict('common_with_tone.php', $withTone, '带声调');
        $this->writeDict('common_no_tone.php', $noTone, '不带声调');
        return [$withTone, $noTone];
    }

    /**
     * 生成生僻字字典：通用逻辑
     */
    private function generateRareDicts($rareEntries) {
        $withTone = [];
        $noTone = [];
        foreach ($rareEntries as $pinyin) {
            $pinyin = trim($pinyin);
            $uniqueWithTone = $this->uniquePinyin($pinyin);
            $withTone[] = $uniqueWithTone;
            $noToneRaw = $this->removeTone($uniqueWithTone);
            $noTone[] = $this->uniquePinyin($noToneRaw);
        }
        $this->writeDict('rare_with_tone.php', $withTone, '带声调生僻字');
        $this->writeDict('rare_no_tone.php', $noTone, '不带声调生僻字');
        return [$withTone, $noTone];
    }

    /**
     * 写入字典文件
     */
    private function writeDict($filename, $data, $desc) {
        $path = $this->dictDir . $filename;
        $content = "<?php\n/** 常用字{$desc}字典 生成时间：{$this->metadata['generated_at']} 条目数：" . count($data) . " **/\nreturn " . var_export($data, true) . ";\n";
        if (file_put_contents($path, $content) === false) {
            throw new Exception("写入{$desc}字典失败：{$path}");
        }
        echo "\n📝 生成{$desc}字典：{$filename}";
    }

    /**
     * 生成辅助文件
     */
    private function generateAuxFiles() {
        $this->metadata['generated_at'] = date('Y-m-d H:i:s');
        $this->metadata['source'] = realpath($this->sourcePath);
        $this->metadata['common_count'] = $this->commonCount;
        $this->metadata['total_entries'] = $this->totalEntries;
        file_put_contents($this->dictDir . 'metadata.json', json_encode($this->metadata, JSON_PRETTY_PRINT));
        $polyContent = "<?php\n/** 自定义多音字组合模板 **/\nreturn [];\n";
        file_put_contents($this->dictDir . 'polyphone_custom.php', $polyContent);
        echo "\n📋 生成元数据和多音字模板";
    }

    /**
     * 优化校验逻辑：适配多音字，取第一个读音对比（对齐JS）
     */
    private function validateCriticalChars($noToneCommon, $noToneRare) {
        // 校验规则：支持多音字，默认对比第一个读音
        $critical = [
            '天' => 'tian', '开' => 'kai', '发' => 'fa', '源' => 'yuan',
            '文' => 'wen', '术' => 'shu', '业' => 'ye', '务' => 'wu'
        ];
        $errors = [];

        foreach ($critical as $char => $expected) {
            $actual = '';
            $charCode = mb_ord($char, 'UTF-8');
            $globalIndex = $charCode - 19968;

            // 优先查常用字
            if (isset($noToneCommon[$char])) {
                $actual = $noToneCommon[$char];
            }
            // 再查生僻字
            else {
                $rareIndex = $globalIndex - $this->commonCount;
                if ($rareIndex >= 0 && isset($noToneRare[$rareIndex]) && !empty($noToneRare[$rareIndex])) {
                    $actual = $noToneRare[$rareIndex];
                } else {
                    $errors[] = "缺失汉字：{$char}";
                    continue;
                }
            }

            // 取第一个读音对比（对齐JS的默认行为）
            $firstPinyin = explode(' ', $actual)[0];
            if (strtolower($firstPinyin) !== strtolower($expected)) {
                $errors[] = "{$char}：实际读音{$actual}，预期默认读音{$expected}";
            }
        }

        if (!empty($errors)) {
            throw new Exception("字典校验失败：" . implode('，', $errors));
        }
        echo "\n✅ 关键汉字校验通过";
    }

    /**
     * 主生成方法
     */
    public function generate() {
        try {
            $valid = $this->validateEntries();
            list($common, $rare) = $this->splitCommonAndRare($valid);
            $this->metadata['generated_at'] = date('Y-m-d H:i:s');
            list($withToneCommon, $noToneCommon) = $this->generateCommonDicts($common);
            list($withToneRare, $noToneRare) = $this->generateRareDicts($rare);
            $this->generateAuxFiles();
            $this->validateCriticalChars($noToneCommon, $noToneRare);
            echo "\n🎉 字典生成完成！输出目录：{$this->dictDir}\n";
            return true;
        } catch (Exception $e) {
            echo "\n❌ 生成失败：" . $e->getMessage() . "\n";
            return false;
        }
    }
}

// 使用示例
try {
   // 字典生成工具调用时修改参数
    $generator = new PinyinDictGenerator('pinyin_dict_withtone.js', [
        'dictDir' => './data',
        'commonCount' => 3500, // 扩大常用字范围
        'autoFix' => true
    ]);
    $generator->generate();
} catch (Exception $e) {
    echo "初始化失败：" . $e->getMessage();
}