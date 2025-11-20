<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;

/**
 * Unihan数据验证器
 */
class UnihanValidator
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
     * 验证字典数据
     * @return array
     */
    public function validate(): array
    {
        $coverage = $this->calculateCoverage();
        
        return [
            'integrity' => $this->checkFileIntegrity(),
            'format' => $this->checkDataFormat(),
            'coverage' => $coverage,
            'coverage_percentage' => round($coverage * 100, 2)
        ];
    }
    
    /**
     * 检查文件完整性
     * @return bool
     */
    private function checkFileIntegrity(): bool
    {
        $requiredFiles = [
            $this->config['dict_dir'] . '/common_with_tone.php',
            $this->config['dict_dir'] . '/rare_with_tone.php',
            $this->config['output_dir'] . '/all_unihan_pinyin.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 检查数据格式
     * @return bool
     */
    private function checkDataFormat(): bool
    {
        try {
            $unihanDict = require $this->config['output_dir'] . '/all_unihan_pinyin.php';
            $commonDict = require $this->config['dict_dir'] . '/common_with_tone.php';
            
            // 检查数据结构
            return is_array($unihanDict) && is_array($commonDict);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 计算字符覆盖度
     * @return float
     */
    private function calculateCoverage(): float
    {
        try {
            $unihanDict = require $this->config['output_dir'] . '/all_unihan_pinyin.php';
            $currentDict = $this->loadCurrentDict();
            
            $commonChars = array_intersect_key($unihanDict, $currentDict);
            
            return count($commonChars) / max(1, count($unihanDict));
        } catch (\Exception $e) {
            return 0.0;
        }
    }
    
    /**
     * 加载当前字典
     * @return array
     */
    private function loadCurrentDict(): array
    {
        $dictFiles = [
            $this->config['dict_dir'] . '/common_with_tone.php',
            $this->config['dict_dir'] . '/rare_with_tone.php'
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
}