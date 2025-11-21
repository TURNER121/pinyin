#!/usr/bin/env php
<?php

require __DIR__ . '/functions.php';

/**
 * Composer 词库自动部署脚本
 * 在 composer install/update 时自动部署词库文件
 */
class DictionaryDeployer
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
     * 主部署方法
     */
    public function deploy()
    {
        echo "=== 拼音词库自动部署 ===\n\n";
        
        // 检查是否需要部署
        if (!$this->shouldDeploy()) {
            echo "词库部署已跳过。\n";
            return;
        }
        
        // 获取部署配置
        $config = $this->getDeployConfig();
        
        // 确认部署
        if (!$this->confirmDeploy($config)) {
            echo "词库部署已取消。\n";
            return;
        }
        
        // 执行部署
        if ($this->executeDeploy($config)) {
            $this->saveConfig($config);
            $this->updateEnvironment($config);
            echo "\n✅ 词库部署完成！\n";
            echo "词库位置: {$config['target_dir']}\n";
            echo "环境变量: PINYIN_DICT_ROOT_PATH=" . ($config['env_var'] ?: '未设置') . "\n";
        } else {
            echo "\n❌ 词库部署失败！\n";
            exit(1);
        }
    }
    
    /**
     * 检查是否需要部署
     */
    private function shouldDeploy()
    {
        // 检查是否为生产环境
        if (getenv('CI') || getenv('COMPOSER_PROD_INSTALL')) {
            echo "检测到生产环境，跳过词库部署。\n";
            return false;
        }
        
        // 检查是否禁用自动部署
        if (getenv('PINYIN_SKIP_AUTO_DEPLOY')) {
            echo "自动部署已禁用 (PINYIN_SKIP_AUTO_DEPLOY=1)。\n";
            return false;
        }
        
        return true;
    }
    
    /**
     * 获取部署配置
     */
    private function getDeployConfig()
    {
        $config = $this->loadConfig();
        
        if (!$config) {
            $config = $this->interactiveConfig();
        }
        
        return $config;
    }
    
    /**
     * 加载已保存的配置
     */
    private function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            if ($config && isset($config['target_dir'])) {
                echo "发现已有部署配置:\n";
                echo "目标目录: {$config['target_dir']}\n";
                echo "上次部署: {$config['last_deploy']}\n\n";
                return $config;
            }
        }
        return null;
    }
    
    /**
     * 交互式配置
     */
    private function interactiveConfig()
    {
        echo "首次部署，请配置词库位置:\n\n";
        
        $defaultDir = $this->projectRoot . '/data';
        echo "默认词库目录: {$defaultDir}\n";
        echo "请输入词库目录路径 (直接回车使用默认): ";
        
        $targetDir = trim(fgets(STDIN));
        if (empty($targetDir)) {
            $targetDir = $defaultDir;
        }
        
        // 确保目录绝对路径
        if (!strpos($targetDir, '/') === 0) {
            $targetDir = $this->projectRoot . '/' . $targetDir;
        }
        
        $config = [
            'target_dir' => $targetDir,
            'env_var' => $targetDir,
            'strategy' => 'prompt', // prompt, skip, overwrite
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $config;
    }
    
    /**
     * 确认部署
     */
    private function confirmDeploy($config)
    {
        $targetDir = $config['target_dir'];
        
        // 检查目标目录是否存在
        if (is_dir($targetDir)) {
            echo "\n⚠️  目标目录已存在: {$targetDir}\n";
            echo "请选择处理方式:\n";
            echo "1. 跳过部署 (使用现有词库)\n";
            echo "2. 覆盖部署 (备份现有词库)\n";
            echo "3. 更换目录\n";
            echo "请选择 (1-3): ";
            
            $choice = trim(fgets(STDIN));
            
            switch ($choice) {
                case '1':
                    // 跳过部署，但更新环境变量
                    $config['strategy'] = 'skip';
                    $this->updateEnvironment($config);
                    echo "已跳过部署，使用现有词库。\n";
                    return false;
                    
                case '2':
                    $config['strategy'] = 'overwrite';
                    // 备份现有词库
                    $backupDir = $targetDir . '.backup.' . date('Y-m-d_H-i-s');
                    if ($this->copyDirectory($targetDir, $backupDir)) {
                        echo "现有词库已备份到: {$backupDir}\n";
                    }
                    break;
                    
                case '3':
                    echo "请输入新的词库目录: ";
                    $newDir = trim(fgets(STDIN));
                    if (empty($newDir)) {
                        echo "目录不能为空，取消部署。\n";
                        return false;
                    }
                    $config['target_dir'] = $newDir;
                    $config['env_var'] = $newDir;
                    break;
                    
                default:
                    echo "无效选择，取消部署。\n";
                    return false;
            }
        }
        
        echo "\n准备部署词库到: {$config['target_dir']}\n";
        echo "确认部署? (y/N): ";
        
        $confirm = trim(fgets(STDIN));
        return strtolower($confirm) === 'y';
    }
    
    /**
     * 执行部署
     */
    private function executeDeploy($config)
    {
        $targetDir = $config['target_dir'];
        
        try {
            // 创建目标目录
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    echo "❌ 无法创建目标目录: {$targetDir}\n";
                    return false;
                }
                echo "✅ 创建目标目录: {$targetDir}\n";
            }
            
            // 复制词库文件
            echo "正在复制词库文件...\n";
            if (!$this->copyDirectory($this->sourceDictDir, $targetDir)) {
                echo "❌ 词库文件复制失败\n";
                return false;
            }
            
            echo "✅ 词库文件复制完成\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ 部署过程中发生错误: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * 复制目录
     */
    private function copyDirectory($source, $target)
    {
        if (!is_dir($source)) {
            echo "❌ 源目录不存在: {$source}\n";
            return false;
        }
        
        // 确保目标目录存在
        if (!is_dir($target)) {
            if (!mkdir($target, 0755, true)) {
                echo "❌ 无法创建目标目录: {$target}\n";
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
        $config['last_deploy'] = date('Y-m-d H:i:s');
        file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * 更新环境变量
     */
    private function updateEnvironment($config)
    {
        $envContent = '';
        
        // 读取现有 .env 文件
        if (file_exists($this->envFile)) {
            $envContent = file_get_contents($this->envFile);
        }
        
        // 更新或添加 PINYIN_DICT_ROOT_PATH
        $envLine = "PINYIN_DICT_ROOT_PATH=" . $config['env_var'];
        
        if (strpos($envContent, 'PINYIN_DICT_ROOT_PATH') !== false) {
            // 替换现有行
            $envContent = preg_replace('/^PINYIN_DICT_ROOT_PATH=.*$/m', $envLine, $envContent);
        } else {
            // 添加新行
            if (!empty($envContent) && !preg_match('/\n$/', $envContent)) {
                $envContent .= "\n";
            }
            $envContent .= $envLine . "\n";
        }
        
        file_put_contents($this->envFile, $envContent);
        
        // 设置当前进程的环境变量
        putenv($envLine);
        $_ENV['PINYIN_DICT_ROOT_PATH'] = $config['env_var'];
        
        echo "✅ 环境变量已更新: {$envLine}\n";
    }
}

// 执行部署
if (php_sapi_name() === 'cli') {
    $deployer = new DictionaryDeployer();
    $deployer->deploy();
}