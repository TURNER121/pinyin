#!/usr/bin/env php
<?php
/**
 * 拼音后台任务运行器
 * 支持多种执行模式：一次性执行、守护进程、定时任务
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tekintian\pinyin\PinyinConverter;
use tekintian\pinyin\BackgroundTaskManager;

class TaskRunner {
    private $converter;
    private $taskManager;
    private $options;
    
    public function __construct() {
        $this->converter = new PinyinConverter();
        $this->taskManager = new BackgroundTaskManager();
        $this->parseOptions();
    }
    
    private function parseOptions() {
        $shortopts = "m:b:l:d:h";
        $longopts = [
            "mode:",      // 执行模式：batch, daemon, once
            "batch-size:", // 批量大小
            "limit:",     // 限制处理数量
            "daemon",     // 守护进程模式
            "interval:",  // 检查间隔（秒）
            "help"        // 帮助信息
        ];
        
        $this->options = getopt($shortopts, $longopts);
        
        if (isset($this->options['h']) || isset($this->options['help'])) {
            $this->showHelp();
            exit(0);
        }
    }
    
    public function run() {
        $mode = $this->getOption('mode', 'm', 'batch');
        
        switch ($mode) {
            case 'daemon':
                $this->runDaemon();
                break;
            case 'once':
                $this->runOnce();
                break;
            case 'batch':
            default:
                $this->runBatch();
                break;
        }
    }
    
    /**
     * 批量处理模式
     */
    private function runBatch() {
        $batchSize = $this->getOption('batch-size', 'b', 50);
        $limit = $this->getOption('limit', 'l', 0);
        
        echo "开始批量处理任务...\n";
        
        $processed = 0;
        do {
            $results = $this->taskManager->processBatch($this->converter, $batchSize);
            
            if ($results['processed'] > 0) {
                echo "[" . date('Y-m-d H:i:s') . "] 处理了 " . $results['processed'] . " 个任务\n";
                $processed += $results['processed'];
            } else {
                echo "[" . date('Y-m-d H:i:s') . "] 没有待处理任务\n";
                break;
            }
            
            // 如果设置了限制，检查是否达到
            if ($limit > 0 && $processed >= $limit) {
                echo "达到处理限制 ($limit)，停止处理\n";
                break;
            }
            
        } while (true);
        
        $this->showStats();
    }
    
    /**
     * 一次性执行模式
     */
    private function runOnce() {
        $batchSize = $this->getOption('batch-size', 'b', 10);
        
        echo "执行一次性任务处理...\n";
        
        $results = $this->taskManager->processBatch($this->converter, $batchSize);
        
        echo "处理结果：\n";
        echo "已处理: " . $results['processed'] . " 个任务\n";
        echo "成功: " . $results['succeeded'] . " 个任务\n";
        echo "失败: " . $results['failed'] . " 个任务\n";
        
        $this->showStats();
    }
    
    /**
     * 守护进程模式
     */
    private function runDaemon() {
        $interval = $this->getOption('interval', 'i', 60);
        $pidFile = '/tmp/pinyin_task_daemon.pid';
        
        // 检查是否已有守护进程运行
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            if ($this->isProcessRunning($pid)) {
                echo "守护进程已在运行 (PID: $pid)\n";
                exit(0);
            }
        }
        
        // 创建守护进程
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("无法创建守护进程\n");
        } elseif ($pid) {
            // 父进程退出
            file_put_contents($pidFile, $pid);
            echo "守护进程已启动 (PID: $pid)\n";
            exit(0);
        }
        
        // 子进程成为守护进程
        posix_setsid();
        
        echo "[" . date('Y-m-d H:i:s') . "] 守护进程开始运行，检查间隔: {$interval}秒\n";
        
        // 信号处理
        pcntl_signal(SIGTERM, function() use ($pidFile) {
            echo "[" . date('Y-m-d H:i:s') . "] 收到终止信号，退出守护进程\n";
            if (file_exists($pidFile)) {
                unlink($pidFile);
            }
            exit(0);
        });
        
        pcntl_signal(SIGINT, function() use ($pidFile) {
            echo "[" . date('Y-m-d H:i:s') . "] 收到中断信号，退出守护进程\n";
            if (file_exists($pidFile)) {
                unlink($pidFile);
            }
            exit(0);
        });
        
        // 主循环
        while (true) {
            pcntl_signal_dispatch();
            
            $results = $this->taskManager->processBatch($this->converter, 10);
            
            if ($results['processed'] > 0) {
                echo "[" . date('Y-m-d H:i:s') . "] 处理了 " . $results['processed'] . " 个任务\n";
            }
            
            sleep($interval);
        }
    }
    
    /**
     * 显示统计信息
     */
    private function showStats() {
        $stats = $this->taskManager->getStats();
        
        echo "\n=== 任务统计 ===\n";
        echo "总任务数: " . $stats['total'] . "\n";
        echo "待处理: " . $stats['pending'] . "\n";
        echo "执行中: " . $stats['running'] . "\n";
        echo "已完成: " . $stats['completed'] . "\n";
        echo "失败: " . $stats['failed'] . "\n";
        echo "================\n";
    }
    
    /**
     * 检查进程是否在运行
     */
    private function isProcessRunning($pid) {
        if (function_exists('posix_getpgid')) {
            return posix_getpgid($pid) !== false;
        }
        
        // Windows 或其他系统
        $output = [];
        exec("ps -p $pid 2>&1", $output);
        return count($output) > 1;
    }
    
    /**
     * 获取选项值
     */
    private function getOption($long, $short, $default = null) {
        if (isset($this->options[$long])) {
            return $this->options[$long];
        }
        if (isset($this->options[$short])) {
            return $this->options[$short];
        }
        return $default;
    }
    
    /**
     * 显示帮助信息
     */
    private function showHelp() {
        echo "拼音后台任务运行器\n";
        echo "用法: php " . basename(__FILE__) . " [选项]\n\n";
        echo "选项:\n";
        echo "  -m, --mode MODE        执行模式: batch, daemon, once (默认: batch)\n";
        echo "  -b, --batch-size SIZE 批量处理大小 (默认: 50)\n";
        echo "  -l, --limit LIMIT     限制处理任务数量 (默认: 无限制)\n";
        echo "  -i, --interval SEC     守护进程模式下的检查间隔 (默认: 60秒)\n";
        echo "  -h, --help            显示此帮助信息\n\n";
        echo "示例:\n";
        echo "  php " . basename(__FILE__) . " -m batch -b 100      # 批量处理100个任务\n";
        echo "  php " . basename(__FILE__) . " -m daemon -i 30     # 守护进程模式，30秒检查一次\n";
        echo "  php " . basename(__FILE__) . " -m once             # 一次性执行\n";
    }
}

// 运行任务运行器
$runner = new TaskRunner();
$runner->run();
?>