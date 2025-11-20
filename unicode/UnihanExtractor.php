<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;
use tekintian\pinyin\Utils\PinyinHelper;
/**
 * Unihan数据提取器
 */
class UnihanExtractor
{
    /**
     * 配置参数
     * @var array
     */
    private $config;
    
    /**
     * 可正常显示的Unicode范围
     * @var array
     */
    private $displayableRanges = [
        [0x4E00, 0x9FFF],   // CJK基本汉字
        [0x3400, 0x4DBF],   // CJK扩展A
        [0xF900, 0xFAFF],   // CJK兼容汉字
        [0x2F00, 0x2FDF],   // 康熙部首
        [0x2E80, 0x2EFF],   // CJK部首补充
        [0x31C0, 0x31EF],   // CJK笔画
        [0x2FF0, 0x2FFF],   // 汉字结构描述字符
        [0x3000, 0x303F],   // CJK符号和标点
        [0x3200, 0x32FF],   // 带圈字母数字
        [0x3300, 0x33FF],   // CJK兼容
        [0xFE30, 0xFE4F],   // CJK兼容形式
        [0x1F200, 0x1F2FF], // 带圈补充符号
    ];
    
    /**
     * 无法正常显示的Unicode范围（生僻字、异体字等）
     * @var array
     */
    private $nonDisplayableRanges = [
        [0x20000, 0x2A6DF], // CJK扩展B
        [0x2A700, 0x2B73F], // CJK扩展C
        [0x2B740, 0x2B81F], // CJK扩展D
        [0x2B820, 0x2CEAF], // CJK扩展E
        [0x2CEB0, 0x2EBEF], // CJK扩展F
        [0x30000, 0x3134F], // CJK扩展G
        [0x31350, 0x323AF], // CJK扩展H
        [0x2EBF0, 0x2EE5F], // CJK扩展I
    ];
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * 提取Unihan数据
     */
    public function extract(): void
    {
        $this->extractWithClassification();
    }
    
    /**
     * 提取Unihan数据并分类
     */
    public function extractWithClassification(): array
    {
        $zipFile = $this->config['zip_file'];
        $extractDir = $this->config['extract_dir'];
        $outputDir = $this->config['output_dir'];
        
        // 创建临时目录
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }
        
        // 解压文件
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== TRUE) {
            throw new PinyinException("无法打开Unihan.zip文件", PinyinException::ERROR_DICT_LOAD_FAIL);
        }
        
        $zip->extractTo($extractDir);
        $zip->close();
        
        echo "成功解压Unihan.zip\n";
        
        // 提取拼音数据并分类
        $result = $this->extractAndClassifyPinyinData($extractDir, $outputDir);
        
        // 清理临时文件
        $this->cleanup($extractDir);
        
        return $result;
    }
    
    /**
     * 提取并分类拼音数据
     * @param string $extractDir
     * @param string $outputDir
     * @return array
     */
    private function extractAndClassifyPinyinData(string $extractDir, string $outputDir): array
    {
        $readingsFile = $extractDir . '/Unihan_Readings.txt';
        
        if (!file_exists($readingsFile)) {
            throw new PinyinException("Unihan_Readings.txt文件不存在", PinyinException::ERROR_FILE_NOT_FOUND);
        }
        
        // 创建输出目录
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // 读取并处理数据
        $pinyinDict = $this->parseReadingsFile($readingsFile);
        
        // 分类字符
        list($displayableDict, $nonDisplayableDict) = $this->classifyCharacters($pinyinDict);
        
        // 保存分类后的字典
        $this->saveClassifiedDicts($displayableDict, $nonDisplayableDict, $outputDir);
        
        // 保存分类后的无声调字典
        $this->saveClassifiedDictsWithoutTone($displayableDict, $nonDisplayableDict, $outputDir);
        
        // 按区块保存数据
        $this->saveByBlocks($pinyinDict, $outputDir);
        
        // 按区块保存无声调数据
        $this->saveByBlocksWithoutTone($pinyinDict, $outputDir);
        
        // 保存完整字典
        $this->saveCompleteDict($pinyinDict, $outputDir);
        
        // 保存完整无声调字典
        $this->saveCompleteDictWithoutTone($pinyinDict, $outputDir);
        
        // 保存多音字字典
        $this->savePolyphoneDict($pinyinDict, $outputDir);
        
        // 保存多音字无声调字典
        $this->savePolyphoneDictWithoutTone($pinyinDict, $outputDir);
        
        // 统计多音字数量
        $polyphoneCount = 0;
        foreach ($pinyinDict as $pinyins) {
            if (is_array($pinyins) && count($pinyins) > 1) {
                $polyphoneCount++;
            }
        }
        
        return [
            'total_chars' => count($pinyinDict),
            'displayable_chars' => count($displayableDict),
            'non_displayable_chars' => count($nonDisplayableDict),
            'polyphone_chars' => $polyphoneCount,
            'displayable_percentage' => round(count($displayableDict) / count($pinyinDict) * 100, 2),
            'polyphone_percentage' => round($polyphoneCount / count($pinyinDict) * 100, 2),
            'output_dir' => $outputDir
        ];
    }
    
    /**
     * 提取拼音数据
     * @param string $extractDir
     * @param string $outputDir
     */
    private function extractPinyinData(string $extractDir, string $outputDir): void
    {
        $readingsFile = $extractDir . '/Unihan_Readings.txt';
        
        if (!file_exists($readingsFile)) {
            throw new PinyinException("Unihan_Readings.txt文件不存在", PinyinException::ERROR_FILE_NOT_FOUND);
        }
        
        // 创建输出目录
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // 读取并处理数据  
        $pinyinDict = $this->parseReadingsFile($readingsFile);
        
        // 按区块保存数据
        $this->saveByBlocks($pinyinDict, $outputDir);
        
        // 保存完整字典
        $this->saveCompleteDict($pinyinDict, $outputDir);
    }
    
    /**
     * 解析Readings文件
     * @param string $filePath
     * @return array
     */
    private function parseReadingsFile(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new PinyinException("无法打开Unihan_Readings.txt文件", PinyinException::ERROR_FILE_NOT_FOUND);
        }
        
        $charData = [];
        $totalCount = 0;
        
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            
            // 跳过注释行和空行
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            $parts = explode("\t", $line);
            if (count($parts) !== 3) {
                continue;
            }
            
            list($unicode, $field, $value) = $parts;
            
            if (!preg_match('/^U\+([0-9A-F]{4,5})$/i', $unicode, $matches)) {
                continue;
            }
            
            $codePoint = hexdec($matches[1]);
            $char = mb_chr($codePoint, 'UTF-8');
            
            if (!$char) {
                continue;
            }
            
            // 检查是否为有效汉字字符
            if (preg_match('/[\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}\x{20000}-\x{2A6DF}\x{2A700}-\x{2B73F}\x{2B740}-\x{2B81F}\x{2B820}-\x{2CEAF}\x{2CEB0}-\x{2EBEF}\x{30000}-\x{3134F}]/u', $char)) {
                if (!isset($charData[$char])) {
                    $charData[$char] = [];
                }
                $charData[$char][$field] = $value;
                $totalCount++;
            }
        }
        
        fclose($handle);
        
        // 处理拼音数据
        return $this->processPinyinData($charData);
    }
    
    /**
     * 处理拼音数据
     * @param array $charData
     * @return array
     */
    private function processPinyinData(array $charData): array
    {
        $pinyinDict = [];
        $validCount = 0;
        
        foreach ($charData as $char => $data) {
            $pinyins = $this->extractPinyins($data);
            
            if (!empty($pinyins)) {
                $pinyinDict[$char] = $pinyins;
                $validCount++;
            }
            
            if ($validCount % 1000 === 0) {
                echo "已处理: $validCount 个汉字...\n";
            }
        }
        
        ksort($pinyinDict);
        return $pinyinDict;
    }
    
    /**
     * 分类字符：可正常显示 vs 无法正常显示
     * @param array $pinyinDict
     * @return array [displayableDict, nonDisplayableDict]
     */
    private function classifyCharacters(array $pinyinDict): array
    {
        $displayableDict = [];
        $nonDisplayableDict = [];
        
        foreach ($pinyinDict as $char => $pinyin) {
            $codePoint = mb_ord($char, 'UTF-8');
            $isDisplayable = $this->isCharacterDisplayable($char, $codePoint);
            
            if ($isDisplayable) {
                $displayableDict[$char] = $pinyin;
            } else {
                $nonDisplayableDict[$char] = $pinyin;
            }
        }
        
        ksort($displayableDict);
        ksort($nonDisplayableDict);
        
        return [$displayableDict, $nonDisplayableDict];
    }
    
    /**
     * 判断字符是否可正常显示
     * @param string $char
     * @param int $codePoint
     * @return bool
     */
    private function isCharacterDisplayable(string $char, int $codePoint): bool
    {
        // 检查是否在可显示范围内
        foreach ($this->displayableRanges as $range) {
            if ($codePoint >= $range[0] && $codePoint <= $range[1]) {
                return true;
            }
        }
        
        // 检查是否在不可显示范围内
        foreach ($this->nonDisplayableRanges as $range) {
            if ($codePoint >= $range[0] && $codePoint <= $range[1]) {
                return false;
            }
        }
        
        // 默认情况下，如果不在任何定义范围内，认为是可显示的
        return true;
    }
    
    /**
     * 保存分类后的字典
     * @param array $displayableDict
     * @param array $nonDisplayableDict
     * @param string $outputDir
     */
    private function saveClassifiedDicts(array $displayableDict, array $nonDisplayableDict, string $outputDir): void
    {
        // 保存可正常显示的字符
        $this->saveDictFile($displayableDict, $outputDir . '/displayable_unihan_pinyin.php', 
            '可正常显示的汉字拼音字典', count($displayableDict));
        echo "已生成可正常显示字典: " . count($displayableDict) . " 个字符\n";
        
        // 保存无法正常显示的字符
        $this->saveDictFile($nonDisplayableDict, $outputDir . '/non_displayable_unihan_pinyin.php', 
            '无法正常显示的汉字拼音字典', count($nonDisplayableDict));
        echo "已生成无法正常显示字典: " . count($nonDisplayableDict) . " 个字符\n";
    }
    
    /**
     * 提取拼音
     * @param array $data
     * @return array
     */
    private function extractPinyins(array $data): array
    {
        $pinyins = [];
        $priorityPinyins = []; // 高优先级拼音（kMandarin）
        $otherPinyins = [];     // 其他字段拼音
        
        // 定义字段优先级
        $priorityFields = ['kMandarin']; // 最高优先级字段
        $otherFields = [
            'kHanyuPinyin',   // 汉语拼音
            'kXHC1983',       // 现代汉语词典
            'kTGHZ2013',      // 通用规范汉字字典
            'kHanyuPinlu',    // 汉语拼音频率
            'kSMSZD2003Readings' // 商务印书社字典
        ];
        
        // 提取高优先级字段的拼音
        foreach ($priorityFields as $field) {
            if (isset($data[$field])) {
                $fieldPinyins = $this->parsePinyinField($field, $data[$field]);
                if (!empty($fieldPinyins)) {
                    $priorityPinyins = array_merge($priorityPinyins, $fieldPinyins);
                }
            }
        }
        
        // 提取其他字段的拼音
        foreach ($otherFields as $field) {
            if (isset($data[$field])) {
                $fieldPinyins = $this->parsePinyinField($field, $data[$field]);
                if (!empty($fieldPinyins)) {
                    $otherPinyins = array_merge($otherPinyins, $fieldPinyins);
                }
            }
        }
        
        // 合并所有拼音，高优先级在前，其他在后
        $allPinyins = array_merge($priorityPinyins, $otherPinyins);
        
        // 去重处理，但保持优先级顺序
        $uniquePinyins = [];
        foreach ($allPinyins as $pinyin) {
            if (!in_array($pinyin, $uniquePinyins)) {
                $uniquePinyins[] = $pinyin;
            }
        }
        
        return $uniquePinyins;
    }
    
    /**
     * 解析拼音字段
     * @param string $field
     * @param string $value
     * @return array
     */
    private function parsePinyinField(string $field, string $value): array
    {
        $pinyins = [];
        
        switch ($field) {
            case 'kMandarin':
                // 解析kMandarin格式：拼音1 拼音2（多个拼音用空格分隔）
                $entries = preg_split('/\s+/', $value);
                
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    // 验证拼音格式（只包含字母和声调符号）
                    if (preg_match('/^[a-zāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜü]+$/u', $entry)) {
                        $pinyins[] = $entry;
                    }
                }
                break;
                
            case 'kXHC1983':
            case 'kTGHZ2013':
                // 解析kXHC1983/kTGHZ2013格式：页码.编号:拼音
                // 先按分号分割不同条目（如果有多个条目）
                $entries = explode(';', $value);
                
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    // 分割页码和拼音部分
                    $parts = explode(':', $entry, 2);
                    if (count($parts) === 2) {
                        $pinyinPart = trim($parts[1]);
                        
                        // 按逗号分割多个读音
                        $entryPinyins = explode(',', $pinyinPart);
                        
                        foreach ($entryPinyins as $pinyin) {
                            $pinyin = trim($pinyin);
                            // 验证拼音格式（只包含字母和声调符号）
                            if (preg_match('/^[a-zāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜü]+$/u', $pinyin)) {
                                $pinyins[] = $pinyin;
                            }
                        }
                    }
                }
                break;
                
            case 'kHanyuPinyin':
                // 解析kHanyuPinyin格式：页码.编号:拼音1,拼音2;页码.编号:拼音3
                // 先按分号分割不同条目
                $entries = explode(';', $value);
                
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    // 分割页码和拼音部分
                    $parts = explode(':', $entry, 2);
                    if (count($parts) === 2) {
                        $pinyinPart = trim($parts[1]);
                        
                        // 按逗号分割多个读音
                        $entryPinyins = explode(',', $pinyinPart);
                        
                        foreach ($entryPinyins as $pinyin) {
                            $pinyin = trim($pinyin);
                            // 验证拼音格式（只包含字母和声调符号）
                            if (preg_match('/^[a-zāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜü]+$/u', $pinyin)) {
                                $pinyins[] = $pinyin;
                            }
                        }
                    }
                }
                break;
                
            case 'kHanyuPinlu':
                // 解析kHanyuPinlu格式：拼音(频率) 拼音(频率)
                // 先按空格分割多个读音（如果有多个读音）
                $entries = preg_split('/\s+/', $value);
                
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    // 匹配拼音(频率)格式
                    if (preg_match('/^([a-zāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜü]+)\(\d+\)$/', $entry, $matches)) {
                        $pinyins[] = $matches[1];
                    }
                }
                break;
                
            case 'kSMSZD2003Readings':
                // 解析kSMSZD2003Readings格式：拼音1,拼音2
                $entries = explode(',', $value);
                
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;
                    
                    // 验证拼音格式
                    if (preg_match('/^[a-zāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜü]+$/u', $entry)) {
                        $pinyins[] = $entry;
                    }
                }
                break;
        }
        
        return $pinyins;
    }
    
    /**
     * 按区块保存数据
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function saveByBlocks(array $pinyinDict, string $outputDir): void
    {
        $blocks = [
            'cjk_basic' => ['name' => 'CJK基本汉字', 'start' => 0x4E00, 'end' => 0x9FFF],
            'cjk_ext_a' => ['name' => 'CJK扩展A区', 'start' => 0x3400, 'end' => 0x4DBF],
            'cjk_ext_b' => ['name' => 'CJK扩展B区', 'start' => 0x20000, 'end' => 0x2A6DF],
            'cjk_ext_c' => ['name' => 'CJK扩展C区', 'start' => 0x2A700, 'end' => 0x2B73F],
            'cjk_ext_d' => ['name' => 'CJK扩展D区', 'start' => 0x2B740, 'end' => 0x2B81F],
            'cjk_ext_e' => ['name' => 'CJK扩展E区', 'start' => 0x2B820, 'end' => 0x2CEAF],
            'cjk_ext_f' => ['name' => 'CJK扩展F区', 'start' => 0x2CEB0, 'end' => 0x2EBEF],
            'cjk_ext_g' => ['name' => 'CJK扩展G区', 'start' => 0x30000, 'end' => 0x3134F]
        ];
        
        foreach ($blocks as $blockKey => $blockInfo) {
            $blockDict = [];
            
            foreach ($pinyinDict as $char => $pinyin) {
                $codePoint = mb_ord($char, 'UTF-8');
                if ($codePoint >= $blockInfo['start'] && $codePoint <= $blockInfo['end']) {
                    $blockDict[$char] = $pinyin;
                }
            }
            
            ksort($blockDict);
            $this->saveDictFile($blockDict, $outputDir . '/' . $blockKey . '.php', $blockInfo['name'], count($blockDict));
            echo "已生成: " . $blockInfo['name'] . " - " . count($blockDict) . " 个字符\n";
        }
    }
    
    /**
     * 保存完整字典
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function saveCompleteDict(array $pinyinDict, string $outputDir): void
    {
        $this->saveDictFile($pinyinDict, $outputDir . '/all_unihan_pinyin.php', '完整Unihan拼音字典', count($pinyinDict));
        echo "完整字典文件: all_unihan_pinyin.php - " . count($pinyinDict) . " 个字符\n";
    }
    
    /**
     * 保存字典文件
     * @param array $dict
     * @param string $filePath
     * @param string $name
     * @param int $count
     */
    private function saveDictFile(array $dict, string $filePath, string $name, int $count): void
    {
        $content = "<?php\n/**\n * 基于Unihan数据库的{$name}\n * 生成时间：" . date('Y-m-d H:i:s') . "\n * 数据来源：Unihan_Readings.txt\n * 总字符数：{$count}\n */\nreturn [\n";
        
        foreach ($dict as $char => $pinyin) {
            $pinyinStr = !empty($pinyin) ? "['" . implode("', '", $pinyin) . "']" : "[]";
            $content .= "    '" . $char . "' => " . $pinyinStr . ",\n";
        }
        
        $content .= "];\n";
        file_put_contents($filePath, $content);
    }
    
    /**
     * 保存多音字字典
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function savePolyphoneDict(array $pinyinDict, string $outputDir): void
    {
        $displayablePolyphoneDict = [];
        $nonDisplayablePolyphoneDict = [];
        
        foreach ($pinyinDict as $char => $pinyins) {
            // 如果拼音数量大于1，说明是多音字
            if (is_array($pinyins) && count($pinyins) > 1) {
                // 检查字符是否可正常显示
                $codePoint = mb_ord($char, 'UTF-8');
                if ($this->isCharacterDisplayable($char, $codePoint)) {
                    $displayablePolyphoneDict[$char] = $pinyins;
                } else {
                    $nonDisplayablePolyphoneDict[$char] = $pinyins;
                }
            }
        }
        
        // 保存可正常显示的多音字字典
        ksort($displayablePolyphoneDict);
        $this->saveDictFile($displayablePolyphoneDict, $outputDir . '/displayable_polyphone_unihan_pinyin.php', '可正常显示的多音字Unihan拼音字典', count($displayablePolyphoneDict));
        echo "可显示多音字字典文件: displayable_polyphone_unihan_pinyin.php - " . count($displayablePolyphoneDict) . " 个多音字\n";
        
        // 保存不可正常显示的多音字字典
        ksort($nonDisplayablePolyphoneDict);
        $this->saveDictFile($nonDisplayablePolyphoneDict, $outputDir . '/non_displayable_polyphone_unihan_pinyin.php', '不可正常显示的多音字Unihan拼音字典', count($nonDisplayablePolyphoneDict));
        echo "不可显示多音字字典文件: non_displayable_polyphone_unihan_pinyin.php - " . count($nonDisplayablePolyphoneDict) . " 个多音字\n";
        
        // 同时保存完整的多音字字典（兼容性）
        $allPolyphoneDict = array_merge($displayablePolyphoneDict, $nonDisplayablePolyphoneDict);
        ksort($allPolyphoneDict);
        $this->saveDictFile($allPolyphoneDict, $outputDir . '/polyphone_unihan_pinyin.php', '多音字Unihan拼音字典', count($allPolyphoneDict));
        echo "完整多音字字典文件: polyphone_unihan_pinyin.php - " . count($allPolyphoneDict) . " 个多音字\n";
    }
       /**
     * 保存分类后的无声调字典
     * @param array $displayableDict
     * @param array $nonDisplayableDict
     * @param string $outputDir
     */
    private function saveClassifiedDictsWithoutTone(array $displayableDict, array $nonDisplayableDict, string $outputDir): void
    {
        // 转换为无声调字典
        $displayableDictNoTone = $this->convertDictToNoTone($displayableDict);
        $nonDisplayableDictNoTone = $this->convertDictToNoTone($nonDisplayableDict);
        
        // 保存可正常显示的无声调字典
        ksort($displayableDictNoTone);
        $this->saveDictFile($displayableDictNoTone, $outputDir . '/displayable_unihan_pinyin_no_tone.php', '可正常显示的Unihan拼音字典(无声调)', count($displayableDictNoTone));
        echo "可显示无声调字典文件: displayable_unihan_pinyin_no_tone.php - " . count($displayableDictNoTone) . " 个字符\n";
        
        // 保存不可正常显示的无声调字典
        ksort($nonDisplayableDictNoTone);
        $this->saveDictFile($nonDisplayableDictNoTone, $outputDir . '/non_displayable_unihan_pinyin_no_tone.php', '不可正常显示的Unihan拼音字典(无声调)', count($nonDisplayableDictNoTone));
        echo "不可显示无声调字典文件: non_displayable_unihan_pinyin_no_tone.php - " . count($nonDisplayableDictNoTone) . " 个字符\n";
    }
    
    /**
     * 按区块保存无声调数据
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function saveByBlocksWithoutTone(array $pinyinDict, string $outputDir): void
    {
        $blocks = [
            'cjk_basic' => ['name' => 'CJK基本汉字', 'start' => 0x4E00, 'end' => 0x9FFF],
            'cjk_ext_a' => ['name' => 'CJK扩展A区', 'start' => 0x3400, 'end' => 0x4DBF],
            'cjk_ext_b' => ['name' => 'CJK扩展B区', 'start' => 0x20000, 'end' => 0x2A6DF],
            'cjk_ext_c' => ['name' => 'CJK扩展C区', 'start' => 0x2A700, 'end' => 0x2B73F],
            'cjk_ext_d' => ['name' => 'CJK扩展D区', 'start' => 0x2B740, 'end' => 0x2B81F],
            'cjk_ext_e' => ['name' => 'CJK扩展E区', 'start' => 0x2B820, 'end' => 0x2CEAF],
            'cjk_ext_f' => ['name' => 'CJK扩展F区', 'start' => 0x2CEB0, 'end' => 0x2EBEF],
            'cjk_ext_g' => ['name' => 'CJK扩展G区', 'start' => 0x30000, 'end' => 0x3134F]
        ];
        
        foreach ($blocks as $blockKey => $blockInfo) {
            $blockDict = [];
            
            foreach ($pinyinDict as $char => $pinyin) {
                $codePoint = mb_ord($char, 'UTF-8');
                if ($codePoint >= $blockInfo['start'] && $codePoint <= $blockInfo['end']) {
                    $blockDict[$char] = $pinyin;
                }
            }
            
            // 转换为无声调字典
            $blockDictNoTone = $this->convertDictToNoTone($blockDict);
            
            ksort($blockDictNoTone);
            $this->saveDictFile($blockDictNoTone, $outputDir . '/' . $blockKey . '_no_tone.php', $blockInfo['name'] . '(无声调)', count($blockDictNoTone));
            echo "已生成: " . $blockInfo['name'] . '(无声调) - ' . count($blockDictNoTone) . " 个字符\n";
        }
    }
    
    /**
     * 保存完整无声调字典
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function saveCompleteDictWithoutTone(array $pinyinDict, string $outputDir): void
    {
        // 转换为无声调字典
        $pinyinDictNoTone = $this->convertDictToNoTone($pinyinDict);
        
        $this->saveDictFile($pinyinDictNoTone, $outputDir . '/all_unihan_pinyin_no_tone.php', '完整Unihan拼音字典(无声调)', count($pinyinDictNoTone));
        echo "完整无声调字典文件: all_unihan_pinyin_no_tone.php - " . count($pinyinDictNoTone) . " 个字符\n";
    }
    
    /**
     * 保存多音字无声调字典
     * @param array $pinyinDict
     * @param string $outputDir
     */
    private function savePolyphoneDictWithoutTone(array $pinyinDict, string $outputDir): void
    {
        $displayablePolyphoneDict = [];
        $nonDisplayablePolyphoneDict = [];
        
        foreach ($pinyinDict as $char => $pinyins) {
            // 如果拼音数量大于1，说明是多音字
            if (is_array($pinyins) && count($pinyins) > 1) {
                // 检查字符是否可正常显示
                $codePoint = mb_ord($char, 'UTF-8');
                if ($this->isCharacterDisplayable($char, $codePoint)) {
                    $displayablePolyphoneDict[$char] = $pinyins;
                } else {
                    $nonDisplayablePolyphoneDict[$char] = $pinyins;
                }
            }
        }
        
        // 转换为无声调字典
        $displayablePolyphoneDictNoTone = $this->convertDictToNoTone($displayablePolyphoneDict);
        $nonDisplayablePolyphoneDictNoTone = $this->convertDictToNoTone($nonDisplayablePolyphoneDict);
        $allPolyphoneDictNoTone = array_merge($displayablePolyphoneDictNoTone, $nonDisplayablePolyphoneDictNoTone);
        
        // 保存可正常显示的多音字无声调字典
        ksort($displayablePolyphoneDictNoTone);
        $this->saveDictFile($displayablePolyphoneDictNoTone, $outputDir . '/displayable_polyphone_unihan_pinyin_no_tone.php', '可正常显示的多音字Unihan拼音字典(无声调)', count($displayablePolyphoneDictNoTone));
        echo "可显示多音字无声调字典文件: displayable_polyphone_unihan_pinyin_no_tone.php - " . count($displayablePolyphoneDictNoTone) . " 个多音字\n";
        
        // 保存不可正常显示的多音字无声调字典
        ksort($nonDisplayablePolyphoneDictNoTone);
        $this->saveDictFile($nonDisplayablePolyphoneDictNoTone, $outputDir . '/non_displayable_polyphone_unihan_pinyin_no_tone.php', '不可正常显示的多音字Unihan拼音字典(无声调)', count($nonDisplayablePolyphoneDictNoTone));
        echo "不可显示多音字无声调字典文件: non_displayable_polyphone_unihan_pinyin_no_tone.php - " . count($nonDisplayablePolyphoneDictNoTone) . " 个多音字\n";
        
        // 同时保存完整的多音字无声调字典（兼容性）
        ksort($allPolyphoneDictNoTone);
        $this->saveDictFile($allPolyphoneDictNoTone, $outputDir . '/polyphone_unihan_pinyin_no_tone.php', '多音字Unihan拼音字典(无声调)', count($allPolyphoneDictNoTone));
        echo "完整多音字无声调字典文件: polyphone_unihan_pinyin_no_tone.php - " . count($allPolyphoneDictNoTone) . " 个多音字\n";
    }
    
    /**
     * 将有声调字典转换为无声调字典
     * @param array $dict
     * @return array
     */
    private function convertDictToNoTone(array $dict): array
    {
        $result = [];
        
        foreach ($dict as $char => $pinyins) {
            if (is_array($pinyins)) {
                $noTonePinyins = [];
                foreach ($pinyins as $pinyin) {
                    $noTonePinyins[] = remove_tone($pinyin);
                }
                // 去重
                $noTonePinyins = array_unique($noTonePinyins);
                $result[$char] = array_values($noTonePinyins);
            } else {
                $result[$char] = [remove_tone($pinyins)];
            }
        }
        
        return $result;
    }
    /**
     * 清理临时文件
     * @param string $extractDir
     */
    private function cleanup(string $extractDir): void
    {
        array_map('unlink', glob("$extractDir/*"));
        rmdir($extractDir);
    }
}