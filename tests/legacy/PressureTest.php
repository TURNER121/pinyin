<?php

namespace tekintian\pinyin\Tests;

require_once __DIR__ . '/../vendor/autoload.php';
use tekintian\pinyin\PinyinConverter;

/**
 * PinyinConverter 压力测试套件
 * 全面测试高并发、大数据量下的性能表现和内存使用情况
 * 运行方法：php tests/legacy/PressureTest.php
 */
class PressureTest
{
    private $converter;
    private $memoryMonitor = [];
    private $performanceData = [];

    public function __construct()
    {
        // 禁用延迟写入和后台任务，避免测试干扰
        $options = [
            'dict_loading' => ['lazy_loading' => false],
            'custom_dict_persistence' => ['enable_delayed_write' => false],
            'background_tasks' => ['enable' => false],
            'self_learn_merge' => ['threshold' => 100000]
        ];
        $this->converter = new PinyinConverter($options);
    }

    /**
     * 记录内存使用情况
     */
    private function recordMemoryUsage($stage)
    {
        $this->memoryMonitor[$stage] = [
            'peak' => memory_get_peak_usage(true),
            'current' => memory_get_usage(true),
            'time' => microtime(true)
        ];
    }

    /**
     * 生成测试文本
     */
    private function generateTestTexts()
    {
        $testScenarios = [
            // 基础场景：常用短句
            'basic' => [
                '互联网技术发展迅速',
                '人工智能与机器学习是未来趋势',
                '数据库性能优化很重要',
                '区块链技术应用场景广泛',
                '云计算和边缘计算各有优势'
            ],

            // 长文本场景
            'long_text' => [
                str_repeat('这是一个用于压力测试的长文本内容，包含各种汉字和标点符号。', 50), // ~2000字符
                str_repeat('测试内存使用和性能表现的极端场景文本数据。', 100), // ~3000字符
            ],

            // 生僻字场景
            'rare_chars' => [
                '㐀㐁㐂㐃㐄㐅㐆㐇㐈㐉㐊㐋㐌㐍㐎㐏㐐㐑㐒㐓',
                '鿃鿄鿅鿆鿇鿈鿉鿊鿋鿌鿍鿎鿏鿐鿑鿒鿓鿔鿕',
            ],

            // 混合内容场景
            'mixed_content' => [
                'Hello 世界！123 Test 测试 🚀 Emoji 😊 特殊符号 ★',
                '混合内容测试：中文English123!@#$%^&*()汉字',
            ],

            // 极端长度场景
            'extreme_length' => [
                str_repeat('极端长度测试文本', 5000), // ~40000字符
            ]
        ];

        return $testScenarios;
    }

    /**
     * 单线程压力测试
     */
    public function singleThreadPressureTest($iterations = 1000)
    {
        echo "开始单线程压力测试（{$iterations}次迭代）...\n";

        $this->recordMemoryUsage('single_thread_start');
        $startTime = microtime(true);

        $testTexts = $this->generateTestTexts();
        $totalConversions = 0;

        for ($i = 0; $i < $iterations; $i++) {
            // 随机选择测试场景
            $scenario = array_rand($testTexts);
            $text = $testTexts[$scenario][array_rand($testTexts[$scenario])];

            // 随机选择参数
            $withTone = (bool)rand(0, 1);
            $separator = [' ', '-', '_', ''][rand(0, 3)];

            $result = $this->converter->convert($text, $separator, $withTone);
            $totalConversions++;

            // 每100次记录一次内存使用
            if ($i % 100 === 0) {
                $this->recordMemoryUsage("single_thread_iteration_{$i}");
            }
        }

        $endTime = microtime(true);
        $this->recordMemoryUsage('single_thread_end');

        $duration = $endTime - $startTime;
        $conversionsPerSecond = $totalConversions / $duration;

        $this->performanceData['single_thread'] = [
            'iterations' => $iterations,
            'duration' => round($duration, 3),
            'conversions_per_second' => round($conversionsPerSecond, 2),
            'total_conversions' => $totalConversions
        ];

        echo "单线程测试完成：{$conversionsPerSecond} 次转换/秒\n";
    }

    /**
     * 内存泄漏检测测试
     */
    public function memoryLeakTest($iterations = 5000)
    {
        echo "开始内存泄漏检测测试（{$iterations}次迭代）...\n";

        $this->recordMemoryUsage('memory_leak_start');

        $memoryReadings = [];
        $testText = '这是一个用于内存泄漏检测的标准测试文本';

        for ($i = 0; $i < $iterations; $i++) {
            $this->converter->convert($testText);

            // 每100次记录一次内存使用
            if ($i % 100 === 0) {
                $memoryReadings[$i] = memory_get_usage(true);

                // 检查内存增长趋势
                if ($i >= 200) {
                    $recentIncrease = $memoryReadings[$i] - $memoryReadings[$i - 200];
                    if ($recentIncrease > 2 * 1024 * 1024) { // 2MB增长阈值
                        echo "警告：检测到可能的内存泄漏，迭代 {$i} 时增长: " .
                             round($recentIncrease / 1024 / 1024, 2) . " MB\n";
                    }
                }
            }
        }

        $this->recordMemoryUsage('memory_leak_end');

        // 分析内存增长
        $startMemory = $memoryReadings[0];
        $endMemory = $memoryReadings[max(array_keys($memoryReadings))];
        $totalIncrease = $endMemory - $startMemory;

        $this->performanceData['memory_leak'] = [
            'iterations' => $iterations,
            'memory_increase_bytes' => $totalIncrease,
            'memory_increase_mb' => round($totalIncrease / 1024 / 1024, 2),
            'memory_growth_per_iteration' => round($totalIncrease / $iterations, 2)
        ];

        echo "内存泄漏检测完成：总增长 " . round($totalIncrease / 1024 / 1024, 2) . " MB\n";
    }

    /**
     * 大数据量批量转换测试
     */
    public function batchConversionTest($batchSize = 1000)
    {
        echo "开始批量转换测试（{$batchSize}条数据）...\n";

        $this->recordMemoryUsage('batch_start');
        $startTime = microtime(true);

        // 生成批量测试数据
        $batchData = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $batchData[] = "批量测试数据第{$i}条：这是一个测试文本";
        }

        $results = $this->converter->batchConvert($batchData);

        $endTime = microtime(true);
        $this->recordMemoryUsage('batch_end');

        $duration = $endTime - $startTime;
        $itemsPerSecond = $batchSize / $duration;

        $this->performanceData['batch_conversion'] = [
            'batch_size' => $batchSize,
            'duration' => round($duration, 3),
            'items_per_second' => round($itemsPerSecond, 2),
            'success_count' => count(array_filter($results))
        ];

        echo "批量转换测试完成：{$itemsPerSecond} 条/秒\n";
    }

    /**
     * 边界场景测试
     */
    public function edgeCaseTest()
    {
        echo "开始边界场景测试...\n";

        $edgeCases = [
            'empty_string' => '',
            'only_special_chars' => '!@#$%^&*()',
            'only_numbers' => '1234567890',
            'only_english' => 'abcdefghijklmnopqrstuvwxyz',
            'very_long_special' => str_repeat('!', 10000),
            'mixed_boundary' => str_repeat('测!试@文#本$', 100)
        ];

        $results = [];

        foreach ($edgeCases as $caseName => $text) {
            $startTime = microtime(true);
            $result = $this->converter->convert($text);
            $endTime = microtime(true);

            $results[$caseName] = [
                'result' => $result,
                'duration' => round($endTime - $startTime, 5),
                'memory_used' => memory_get_usage(true)
            ];
        }

        $this->performanceData['edge_cases'] = $results;
        echo "边界场景测试完成\n";
    }

    /**
     * 生成测试报告
     */
    public function generateReport()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "压力测试报告\n";
        echo str_repeat("=", 60) . "\n\n";

        // 性能汇总
        echo "性能汇总：\n";
        foreach ($this->performanceData as $testName => $data) {
            echo "- {$testName}:\n";
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                echo "  {$key}: {$value}\n";
            }
            echo "\n";
        }

        // 内存使用分析
        echo "内存使用分析：\n";
        $stages = array_keys($this->memoryMonitor);
        if (count($stages) > 1) {
            $startMemory = $this->memoryMonitor[$stages[0]]['current'];
            $peakMemory = max(array_column($this->memoryMonitor, 'peak'));

            echo "- 起始内存: " . round($startMemory / 1024 / 1024, 2) . " MB\n";
            echo "- 峰值内存: " . round($peakMemory / 1024 / 1024, 2) . " MB\n";
            echo "- 内存增长: " . round(($peakMemory - $startMemory) / 1024 / 1024, 2) . " MB\n";
        }

        // 内存泄漏检测结果
        if (isset($this->performanceData['memory_leak'])) {
            $leakData = $this->performanceData['memory_leak'];
            echo "\n内存泄漏检测结果：\n";
            echo "- 总内存增长: " . $leakData['memory_increase_mb'] . " MB\n";
            echo "- 每次迭代平均增长: " . $leakData['memory_growth_per_iteration'] . " 字节\n";

            if ($leakData['memory_increase_mb'] > 10) {
                echo "⚠️  警告：检测到显著的内存增长，可能存在内存泄漏\n";
            } elseif ($leakData['memory_increase_mb'] > 5) {
                echo "ℹ️  提示：内存增长在可接受范围内\n";
            } else {
                echo "✅ 良好：内存使用稳定，未检测到明显泄漏\n";
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
    }

    /**
     * 运行完整测试套件
     */
    public function runFullTestSuite()
    {
        echo "PinyinConverter 压力测试套件开始运行...\n\n";

        $this->recordMemoryUsage('test_suite_start');

        // 执行各项测试
        $this->singleThreadPressureTest(1000);
        $this->memoryLeakTest(2000);
        $this->batchConversionTest(500);
        $this->edgeCaseTest();

        $this->recordMemoryUsage('test_suite_end');

        // 生成报告
        $this->generateReport();
    }
}

// 命令行执行
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'PressureTest.php') {
    $test = new PressureTest();
    $test->runFullTestSuite();
}
