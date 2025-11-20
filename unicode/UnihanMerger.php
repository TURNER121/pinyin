<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;

/**
 * Unihan数据合并器
 * 负责将Unihan提取的数据合并到PinyinConverter字典中
 */
class UnihanMerger
{
    /**
     * 配置参数
     * @var array
     */
    private $config;
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * 合并Unihan数据到PinyinConverter字典
     * @param array $config 合并配置
     * @return array 合并结果
     */
    public function mergeToPinyinConverter(array $config = []): array
    {
        $defaultConfig = [
            'unihan_files' => ['displayable_unihan_pinyin.php'], // 要合并的Unihan文件
            'target_dict' => 'rare_with_tone.php', // 目标字典文件
            'check_existing' => true, // 是否检查已存在的内容
            'prompt_conflicts' => true, // 是否提示冲突并让用户选择
            'auto_merge' => false // 是否自动合并（不提示）
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        $result = [
            'merged_count' => 0,
            'skipped_count' => 0,
            'conflicts' => [],
            'errors' => []
        ];
        
        try {
            // 加载目标字典
            $targetDictPath = $this->config['dict_dir'] . '/' . $config['target_dict'];
            if (!file_exists($targetDictPath)) {
                throw new PinyinException("目标字典文件不存在: {$targetDictPath}", PinyinException::ERROR_FILE_NOT_FOUND);
            }
            
            $targetDict = include $targetDictPath;
            if (!is_array($targetDict)) {
                $targetDict = [];
            }
            
            // 加载所有非自定义字典用于冲突检查
            $nonCustomDicts = $this->loadNonCustomDicts();
            
            // 处理每个Unihan文件
            foreach ($config['unihan_files'] as $unihanFile) {
                $unihanPath = $this->config['output_dir'] . '/' . $unihanFile;
                if (!file_exists($unihanPath)) {
                    $result['errors'][] = "Unihan文件不存在: {$unihanPath}";
                    continue;
                }
                
                $unihanData = include $unihanPath;
                if (!is_array($unihanData)) {
                    $result['errors'][] = "Unihan文件格式错误: {$unihanPath}";
                    continue;
                }
                
                // 合并数据
                $mergeResult = $this->performMerge($unihanData, $targetDict, $nonCustomDicts, $config);
                
                $result['merged_count'] += $mergeResult['merged_count'];
                $result['skipped_count'] += $mergeResult['skipped_count'];
                $result['conflicts'] = array_merge($result['conflicts'], $mergeResult['conflicts']);
            }
            
            // 如果有合并的内容，保存目标字典
            if ($result['merged_count'] > 0) {
                $this->saveDictFile($targetDict, $targetDictPath, "合并后的生僻字字典", count($targetDict));
                echo "成功合并 {$result['merged_count']} 个字符到 {$config['target_dict']}\n";
            }
            
            // 处理冲突
            if (!empty($result['conflicts']) && $config['prompt_conflicts']) {
                $this->handleConflicts($result['conflicts'], $config);
            }
            
        } catch (PinyinException $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * 加载所有非自定义字典
     * @return array
     */
    private function loadNonCustomDicts(): array
    {
        $dicts = [];
        $dictFiles = [
            'common_with_tone.php',
            'common_no_tone.php', 
            'rare_with_tone.php',
            'rare_no_tone.php',
            'self_learn_with_tone.php',
            'self_learn_no_tone.php'
        ];
        
        foreach ($dictFiles as $file) {
            $path = $this->config['dict_dir'] . '/' . $file;
            if (file_exists($path)) {
                $data = include $path;
                if (is_array($data)) {
                    $dicts[$file] = $data;
                }
            }
        }
        
        return $dicts;
    }
    
    /**
     * 执行合并操作
     * @param array $sourceData 源数据
     * @param array &$targetDict 目标字典（引用）
     * @param array $nonCustomDicts 非自定义字典
     * @param array $config 配置
     * @return array 合并结果
     */
    private function performMerge(array $sourceData, array &$targetDict, array $nonCustomDicts, array $config): array
    {
        $result = [
            'merged_count' => 0,
            'skipped_count' => 0,
            'conflicts' => []
        ];
        
        // 检查目标字典是否为无声调字典
        $isNoToneDict = strpos($config['target_dict'], '_no_tone.php') !== false;
        
        foreach ($sourceData as $char => $pinyin) {
            // 处理拼音数据：如果是无声调字典，需要去除声调并去重
            $processedPinyin = $this->processPinyinForTarget($pinyin, $isNoToneDict);
            
            // 检查是否已存在
            if ($config['check_existing'] && $this->charExistsInDicts($char, $nonCustomDicts)) {
                // 检查是否存在冲突
                $conflict = $this->checkConflict($char, $processedPinyin, $nonCustomDicts);
                if ($conflict) {
                    $result['conflicts'][] = $conflict;
                }
                $result['skipped_count']++;
                continue;
            }
            
            // 添加到目标字典
            $targetDict[$char] = $processedPinyin;
            $result['merged_count']++;
        }
        
        return $result;
    }
    
    /**
     * 检查字符是否存在于非自定义字典中
     * @param string $char 字符
     * @param array $dicts 字典集合
     * @return bool
     */
    private function charExistsInDicts(string $char, array $dicts): bool
    {
        foreach ($dicts as $dictData) {
            if (isset($dictData[$char])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 检查拼音冲突
     * @param string $char 字符
     * @param array $unihanPinyin Unihan拼音
     * @param array $dicts 字典集合
     * @return array|null 冲突信息，无冲突返回null
     */
    private function checkConflict(string $char, array $unihanPinyin, array $dicts): ?array
    {
        foreach ($dicts as $dictFile => $dictData) {
            if (isset($dictData[$char])) {
                $existingPinyin = $dictData[$char];
                
                // 检查字典类型，如果是无声调字典，需要将Unihan拼音也转换为无声调格式进行比较
                $isNoToneDict = strpos($dictFile, '_no_tone.php') !== false;
                
                if ($isNoToneDict) {
                    // 将Unihan拼音转换为无声调格式进行比较
                    $normalizedUnihan = $this->processPinyinForTarget($unihanPinyin, true);
                    $normalizedExisting = $this->normalizePinyinArray($existingPinyin);
                } else {
                    // 有声调字典，直接比较
                    $normalizedUnihan = $this->normalizePinyinArray($unihanPinyin);
                    $normalizedExisting = $this->normalizePinyinArray($existingPinyin);
                }
                
                // 检查是否有差异
                if ($normalizedUnihan != $normalizedExisting) {
                    return [
                        'char' => $char,
                        'dict_file' => $dictFile,
                        'unihan_pinyin' => $unihanPinyin,
                        'existing_pinyin' => $existingPinyin
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * 根据目标字典类型处理拼音数据
     * @param array $pinyinArray 原始拼音数组
     * @param bool $isNoToneDict 是否为无声调字典
     * @return array 处理后的拼音数组
     */
    private function processPinyinForTarget(array $pinyinArray, bool $isNoToneDict): array
    {
        $processed = [];
        
        foreach ($pinyinArray as $pinyin) {
            $pinyin = trim($pinyin);
            
            if ($isNoToneDict) {
                // 去除声调：移除数字声调标记
                $noTonePinyin = preg_replace('/[1-5]/', '', $pinyin);
                
                // 如果去除声调后为空，保留原始拼音
                if (empty($noTonePinyin)) {
                    $noTonePinyin = $pinyin;
                }
                
                $processed[] = $noTonePinyin;
            } else {
                // 有声调字典，保留原始拼音
                $processed[] = $pinyin;
            }
        }
        
        // 去重并排序
        $processed = array_unique($processed);
        sort($processed);
        
        return array_values($processed);
    }
    
    /**
     * 标准化拼音数组
     * @param array $pinyinArray 拼音数组
     * @return array 标准化后的数组
     */
    private function normalizePinyinArray(array $pinyinArray): array
    {
        $normalized = [];
        foreach ($pinyinArray as $pinyin) {
            // 去除空格并排序
            $normalized[] = trim($pinyin);
        }
        sort($normalized);
        return $normalized;
    }
    
    /**
     * 处理冲突
     * @param array $conflicts 冲突列表
     * @param array $config 配置
     */
    private function handleConflicts(array $conflicts, array $config): void
    {
        if (empty($conflicts)) {
            return;
        }
        
        echo "\n发现 " . count($conflicts) . " 个拼音冲突：\n";
        
        foreach ($conflicts as $index => $conflict) {
            echo "\n冲突 #" . ($index + 1) . ":\n";
            echo "字符: {$conflict['char']}\n";
            echo "字典文件: {$conflict['dict_file']}\n";
            echo "现有拼音: " . implode(', ', $conflict['existing_pinyin']) . "\n";
            echo "Unihan拼音: " . implode(', ', $conflict['unihan_pinyin']) . "\n";
            
            if ($config['auto_merge']) {
                echo "自动选择: 跳过（已存在）\n";
                continue;
            }
            
            // 提示用户选择
            echo "请选择处理方式:\n";
            echo "1. 使用Unihan拼音替换现有拼音\n";
            echo "2. 保留现有拼音\n";
            echo "3. 跳过此冲突\n";
            echo "4. 对所有冲突使用相同处理方式\n";
            
            $choice = trim(fgets(STDIN));
            
            switch ($choice) {
                case '1':
                    // 替换拼音
                    $this->replacePinyinInDict($conflict['char'], $conflict['unihan_pinyin'], $conflict['dict_file']);
                    echo "已替换拼音\n";
                    break;
                case '2':
                    echo "保留现有拼音\n";
                    break;
                case '3':
                    echo "跳过此冲突\n";
                    break;
                case '4':
                    // 批量处理
                    $this->handleBulkConflicts($conflicts, $index);
                    return;
                default:
                    echo "无效选择，跳过此冲突\n";
            }
        }
    }
    
    /**
     * 批量处理冲突
     * @param array $conflicts 冲突列表
     * @param int $currentIndex 当前索引
     */
    private function handleBulkConflicts(array $conflicts, int $currentIndex): void
    {
        echo "请选择批量处理方式:\n";
        echo "1. 全部使用Unihan拼音替换\n";
        echo "2. 全部保留现有拼音\n";
        
        $choice = trim(fgets(STDIN));
        
        for ($i = $currentIndex; $i < count($conflicts); $i++) {
            $conflict = $conflicts[$i];
            
            if ($choice === '1') {
                $this->replacePinyinInDict($conflict['char'], $conflict['unihan_pinyin'], $conflict['dict_file']);
                echo "已替换 {$conflict['char']} 的拼音\n";
            } else {
                echo "保留 {$conflict['char']} 的现有拼音\n";
            }
        }
    }
    
    /**
     * 替换字典中的拼音
     * @param string $char 字符
     * @param array $newPinyin 新拼音
     * @param string $dictFile 字典文件
     */
    private function replacePinyinInDict(string $char, array $newPinyin, string $dictFile): void
    {
        $dictPath = $this->config['dict_dir'] . '/' . $dictFile;
        $dictData = include $dictPath;
        
        if (is_array($dictData) && isset($dictData[$char])) {
            $dictData[$char] = $newPinyin;
            $this->saveDictFile($dictData, $dictPath, "更新后的字典", count($dictData));
        }
    }
    
    /**
     * 保存字典文件
     * @param array $dict 字典数据
     * @param string $filePath 文件路径
     * @param string $name 字典名称
     * @param int $count 字符数量
     */
    private function saveDictFile(array $dict, string $filePath, string $name, int $count): void
    {
        $content = "<?php\n/** {$name} 生成时间：" . date('Y-m-d H:i:s') . " 条目数：{$count} **/\nreturn [\n";
        
        foreach ($dict as $char => $pinyin) {
            if (is_array($pinyin)) {
                $pinyinStr = "['" . implode("', '", $pinyin) . "']";
            } else {
                $pinyinStr = "['" . $pinyin . "']";
            }
            $content .= "    '" . $char . "' => " . $pinyinStr . ",\n";
        }
        
        $content .= "];\n";
        file_put_contents($filePath, $content);
    }
    
    /**
     * 获取配置信息
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}