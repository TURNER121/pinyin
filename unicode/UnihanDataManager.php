<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;

/**
 * Unihan数据管理器
 * 负责Unihan数据的获取、更新、验证和对比
 */
class UnihanDataManager
{
    /**
     * 远程Unihan数据URL
     */
    const REMOTE_URL = 'https://unicode.org/Public/UCD/latest/ucd/Unihan.zip';
    
    /**
     * 默认缓存天数
     */
    const DEFAULT_CACHE_DAYS = 30;
    
    /**
     * 最大重试次数
     */
    const MAX_RETRIES = 3;
    
    /**
     * 下载超时时间（秒）
     */
    const DOWNLOAD_TIMEOUT = 30;
    
    /**
     * 配置参数
     * @var array
     */
    private $config;
    
    /**
     * 构造函数
     * @param array $config 配置参数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_days' => self::DEFAULT_CACHE_DAYS,
            'max_retries' => self::MAX_RETRIES,
            'timeout' => self::DOWNLOAD_TIMEOUT,
            'zip_file' => __DIR__ . '/Unihan.zip',
            'extract_dir' => __DIR__ . '/temp_unihan',
            'output_dir' => dirname(__DIR__) . '/data/unihan',
            'dict_dir' => dirname(__DIR__) . '/data'
        ], $config);
    }
    
    /**
     * 检查并更新Unihan数据
     * @param bool $force 是否强制更新
     * @return bool 是否进行了更新
     */
    public function updateData(bool $force = false): bool
    {
        $downloader = new UnihanDownloader($this->config);
        $extractor = new UnihanExtractor($this->config);
        
        // 检查是否需要下载
        if ($downloader->shouldDownload($force)) {
            echo "开始更新Unihan数据...\n";
            
            // 下载文件
            if (!$downloader->download()) {
                throw new PinyinException("Unihan数据下载失败", PinyinException::ERROR_DICT_LOAD_FAIL);
            }
            
            // 提取数据
            $extractor->extract();
            
            echo "Unihan数据更新完成\n";
            return true;
        }
        
        echo "使用本地缓存数据\n";
        return false;
    }
    
    /**
     * 使用提取器进行分类提取
     * @return array 提取结果
     */
    public function extractWithClassification(): array
    {
        $downloader = new UnihanDownloader($this->config);
        $extractor = new UnihanExtractor($this->config);
        
        // 确保数据已下载
        if ($downloader->shouldDownload(false)) {
            echo "下载Unihan数据...\n";
            if (!$downloader->download()) {
                throw new PinyinException("Unihan数据下载失败", PinyinException::ERROR_DICT_LOAD_FAIL);
            }
        }
        
        echo "开始使用提取器进行分类提取...\n";
        return $extractor->extractWithClassification();
    }
    
    /**
     * 对比Unihan数据与当前字典数据
     * @return array 对比结果
     */
    public function compareWithCurrentDict(): array
    {
        $comparator = new UnihanComparator($this->config);
        return $comparator->compare();
    }
    
    /**
     * 验证字典数据的完整性
     * @return array 验证结果
     */
    public function validateDict(): array
    {
        $validator = new UnihanValidator($this->config);
        return $validator->validate();
    }
    
    /**
     * 生成字典差异报告
     * @return string 报告内容
     */
    public function generateReport(): string
    {
        $comparison = $this->compareWithCurrentDict();
        $validation = $this->validateDict();
        
        $report = "# Unihan数据对比报告\n\n";
        $report .= "生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        // 对比结果
        $report .= "## 数据对比结果\n\n";
        $report .= "- Unihan总字符数: " . $comparison['unihan_total'] . "\n";
        $report .= "- 当前字典字符数: " . $comparison['current_total'] . "\n";
        $report .= "- 新增字符数: " . count($comparison['new_chars']) . "\n";
        $report .= "- 差异字符数: " . count($comparison['different_pinyin']) . "\n\n";
        
        // 验证结果
        $report .= "## 数据验证结果\n\n";
        $report .= "- 字典文件完整性: " . ($validation['integrity'] ? '✓' : '✗') . "\n";
        $report .= "- 数据格式正确性: " . ($validation['format'] ? '✓' : '✗') . "\n";
        $report .= "- 字符覆盖度: " . round($validation['coverage'] * 100, 2) . "%\n\n";
        
        return $report;
    }
    
    /**
     * 获取配置信息
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 创建UnihanMerger实例
     * @return UnihanMerger
     */
    public function createMerger(): UnihanMerger
    {
        return new UnihanMerger($this->config);
    }

    /**
     * 合并Unihan数据到PinyinConverter字典（通过UnihanMerger类）
     * @param array $config 合并配置
     * @return array 合并结果
     */
    public function mergeToPinyinConverter(array $config = []): array
    {
        $merger = $this->createMerger();
        return $merger->mergeToPinyinConverter($config);
    }
}