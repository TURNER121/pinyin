#!/usr/bin/env php
<?php

require __DIR__ . '/functions.php';

/**
 * 静默词库部署脚本
 * 适用于 CI/CD 环境或自动化部署
 */
class SilentDictionaryDeployer
{
    private $vendorDir;
    private $projectRoot;
    private $sourceDictDir;
    private $configFile;
    private $envFile;
    
    public function __construct()
    {
        $this->vendorDir = dirname(__DIR__) . '/vendor';
        $this->projectRoot = dirname($this->vendorDir);
        $this->sourceDictDir = __DIR__ . '/../data';
        $this->configFile = $this->projectRoot . '/.pinyin-dict-config.json';
        $this->envFile = $this->projectRoot . '/.env';
        parse_env_file($this->envFile);
    }
    
    /**
     * 静默部署
     */
    public function deploy($targetDir = null, $strategy = 'skip')
    {
        // 检查是否需要部署
        if (!$this->shouldDeploy()) {
            return true;
        }
        
        // 获取目标目录
        $targetDir = $targetDir ?: $this->getDefaultTargetDir();
        
        // 确保目录绝对路径
        if (!strpos($targetDir, '/') === 0) {
            $targetDir = $this->projectRoot . '/' . $targetDir;
        }
        
        // 检查目录存在性
        if (is_dir($targetDir)) {
            switch ($strategy) {
                case 'skip':
                    $this->updateEnvironment(['env_var' => $targetDir]);
                    echo "词库目录已存在，跳过部署: {$targetDir}\n";
                    return true;
                    
                case 'overwrite':
                    $backupDir = $targetDir . '.backup.' . date('Y-m-d_H-i-s');
                    if ($this->copyDirectory($targetDir, $backupDir)) {
                        echo "现有词库已备份到: {$backupDir}\n";
                    }
                    break;
                    
                default:
                    echo "词库目录已存在且策略为 {$strategy}，跳过部署\n";
                    return false;
            }
        }
        
        // 执行部署
        if ($this->executeDeploy($targetDir)) {
            $config = [
                'target_dir' => $targetDir,
                'env_var' => $targetDir,
                'strategy' => $strategy,
                'last_deploy' => date('Y-m-d H:i:s')
            ];
            
            $this->saveConfig($config);
            $this->updateEnvironment($config);
            
            echo "词库部署完成: {$targetDir}\n";
            return true;
        }
        
        return false;
    }
    
    /**
     * 检查是否需要部署
     */
    private function shouldDeploy()
    {
        if (getenv('CI') || getenv('COMPOSER_PROD_INSTALL')) {
            return false;
        }
        
        if (getenv('PINYIN_SKIP_AUTO_DEPLOY')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 获取默认目标目录
     */
    private function getDefaultTargetDir()
    {
        // 优先使用环境变量
        $envPath = getenv('PINYIN_DICT_ROOT_PATH');
        if ($envPath) {
            return $envPath;
        }
        
        // 使用配置文件
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            if ($config && isset($config['target_dir'])) {
                return $config['target_dir'];
            }
        }
        
        // 默认路径
        return $this->projectRoot . '/data';
    }
    
    /**
     * 执行部署
     */
    private function executeDeploy($targetDir)
    {
        try {
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    echo "无法创建目标目录: {$targetDir}\n";
                    return false;
                }
            }
            
            if (!$this->copyDirectory($this->sourceDictDir, $targetDir)) {
                echo "词库文件复制失败\n";
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            echo "部署错误: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 复制目录
     */
    private function copyDirectory($source, $target)
    {
        if (!is_dir($source)) {
            echo "源目录不存在: {$source}\n";
            return false;
        }
        
        // 确保目标目录存在
        if (!is_dir($target)) {
            if (!mkdir($target, 0755, true)) {
                echo "无法创建目标目录: {$target}\n";
                return false;
            }
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $item->getPathname());
            $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;
            
            if ($item->isDir()) {
                if (!mkdir($targetPath, 0755, true)) {
                    return false;
                }
            } else {
                // 确保文件的父目录存在
                $parentDir = dirname($targetPath);
                if (!is_dir($parentDir)) {
                    if (!mkdir($parentDir, 0755, true)) {
                        return false;
                    }
                }
                
                if (!copy($item, $targetPath)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * 保存配置
     */
    private function saveConfig($config)
    {
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * 更新环境变量
     */
    private function updateEnvironment($config)
    {
        $envContent = '';
        
        if (file_exists($this->envFile)) {
            $envContent = file_get_contents($this->envFile);
        }
        
        $envLine = "PINYIN_DICT_ROOT_PATH=" . $config['env_var'];
        
        if (strpos($envContent, 'PINYIN_DICT_ROOT_PATH') !== false) {
            $envContent = preg_replace('/^PINYIN_DICT_ROOT_PATH=.*$/m', $envLine, $envContent);
        } else {
            if (!empty($envContent) && !preg_match('/\n$/', $envContent)) {
                $envContent .= "\n";
            }
            $envContent .= $envLine . "\n";
        }
        
        file_put_contents($this->envFile, $envContent);
        
        putenv($envLine);
        $_ENV['PINYIN_DICT_ROOT_PATH'] = $config['env_var'];
    }
}

// 命令行使用
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['target:', 'strategy::']);
    
    $targetDir = $options['target'] ?? null;
    $strategy = $options['strategy'] ?? 'skip';
    
    $deployer = new SilentDictionaryDeployer();
    $success = $deployer->deploy($targetDir, $strategy);
    
    exit($success ? 0 : 1);
}