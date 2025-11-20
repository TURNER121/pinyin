<?php
namespace tekintian\pinyin\unicode;

use tekintian\pinyin\Exception\PinyinException;

/**
 * Unihan数据下载器
 */
class UnihanDownloader
{
    /**
     * 远程Unihan数据URL
     */
    const REMOTE_URL = 'https://unicode.org/Public/UCD/latest/ucd/Unihan.zip';
    
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
     * 检查是否需要下载
     * @param bool $force
     * @return bool
     */
    public function shouldDownload(bool $force = false): bool
    {
        $zipFile = $this->config['zip_file'];
        
        // 如果文件不存在，需要下载
        if (!file_exists($zipFile)) {
            return true;
        }
        
        // 如果强制下载模式，需要下载
        if ($force) {
            return true;
        }
        
        // 检查文件是否过期
        $fileTime = filemtime($zipFile);
        $cacheTime = $this->config['cache_days'] * 24 * 60 * 60;
        
        return (time() - $fileTime) > $cacheTime;
    }
    
    /**
     * 下载Unihan数据
     * @return bool
     */
    public function download(): bool
    {
        $zipFile = $this->config['zip_file'];
        $maxRetries = $this->config['max_retries'];
        $timeout = $this->config['timeout'];
        
        echo "开始下载Unihan数据...\n";
        echo "文件信息: " . $this->getFileInfo($zipFile) . "\n";
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            echo "下载尝试 {$attempt}/{$maxRetries}...\n";
            
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => $timeout,
                        'header' => "User-Agent: Mozilla/5.0 (compatible; Unihan Pinyin Extractor)\\r\\n"
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                
                $fileContent = file_get_contents(self::REMOTE_URL, false, $context);
                
                if ($fileContent !== false) {
                    $bytesWritten = file_put_contents($zipFile, $fileContent);
                    if ($bytesWritten !== false) {
                        echo "下载成功: " . $this->formatBytes(strlen($fileContent)) . "\n";
                        return true;
                    }
                }
            } catch (\Exception $e) {
                echo "下载异常: " . $e->getMessage() . "\n";
            }
            
            if ($attempt < $maxRetries) {
                $waitTime = pow(2, $attempt);
                echo "等待 {$waitTime} 秒后重试...\n";
                sleep($waitTime);
            }
        }
        
        throw new PinyinException("下载失败，已达到最大重试次数", PinyinException::ERROR_DICT_LOAD_FAIL);
    }
    
    /**
     * 获取文件信息
     * @param string $filePath
     * @return string
     */
    private function getFileInfo(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return "文件不存在";
        }
        
        $fileTime = filemtime($filePath);
        $fileSize = filesize($filePath);
        $daysOld = floor((time() - $fileTime) / (24 * 60 * 60));
        
        return sprintf("大小: %s, 修改时间: %s (%d 天前)", 
            $this->formatBytes($fileSize), 
            date('Y-m-d H:i:s', $fileTime), 
            $daysOld
        );
    }
    
    /**
     * 格式化字节大小
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}