<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;

/**
 * Unihan数据对比器
 */
class UnihanComparator
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
     * 对比Unihan数据与当前字典
     * @return array
     */
    public function compare(): array
    {
        $unihanDict = $this->loadUnihanDict();
        $currentDict = $this->loadCurrentDict();
        
        $newChars = array_diff_key($unihanDict, $currentDict);
        $missingChars = array_diff_key($currentDict, $unihanDict);
        $differentPinyin = $this->comparePinyin($unihanDict, $currentDict);
        
        return [
            'unihan_total' => count($unihanDict),
            'current_total' => count($currentDict),
            'new_chars' => $newChars,
            'new_chars_count' => count($newChars),
            'missing_chars' => $missingChars,
            'missing_chars_count' => count($missingChars),
            'different_pinyin' => $differentPinyin,
            'pinyin_diff_count' => count($differentPinyin)
        ];
    }
    
    /**
     * 加载Unihan字典
     * @return array
     */
    private function loadUnihanDict(): array
    {
        $unihanFile = $this->config['output_dir'] . '/all_unihan_pinyin.php';
        
        if (!file_exists($unihanFile)) {
            throw new PinyinException("Unihan字典文件不存在，请先更新数据", PinyinException::ERROR_FILE_NOT_FOUND);
        }
        
        return require $unihanFile;
    }
    
    /**
     * 加载当前字典
     * @return array
     */
    private function loadCurrentDict(): array
    {
        $dictFiles = [
            $this->config['dict_dir'] . '/common_with_tone.php',
            $this->config['dict_dir'] . '/rare_with_tone.php',
            $this->config['dict_dir'] . '/self_learn_with_tone.php',
            $this->config['dict_dir'] . '/custom_with_tone.php'
        ];
        
        $currentDict = [];
        
        foreach ($dictFiles as $file) {
            if (file_exists($file)) {
                $dict = require $file;
                $currentDict = array_merge($currentDict, $dict);
            }
        }
        
        return $currentDict;
    }
    
    /**
     * 对比拼音数据
     * @param array $unihanDict
     * @param array $currentDict
     * @return array
     */
    private function comparePinyin(array $unihanDict, array $currentDict): array
    {
        $differences = [];
        
        foreach ($unihanDict as $char => $unihanPinyin) {
            if (isset($currentDict[$char])) {
                $currentPinyin = $currentDict[$char];
                
                // 比较拼音数组（忽略顺序）
                if (count(array_diff($unihanPinyin, $currentPinyin)) > 0 || 
                    count(array_diff($currentPinyin, $unihanPinyin)) > 0) {
                    $differences[$char] = [
                        'unihan' => $unihanPinyin,
                        'current' => $currentPinyin
                    ];
                }
            }
        }
        
        return $differences;
    }
}