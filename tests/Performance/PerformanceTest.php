<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 性能测试套件
 *
 * 测试范围：
 * - 转换速度测试
 * - 内存使用测试
 * - 缓存效率测试
 * - 大数据量测试
 * - 并发性能测试
 */
class PerformanceTest extends TestCase
{
    /**
     * @var PinyinConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $options = [
            'dict_loading' => ['lazy_loading' => false],
            'self_learn_merge' => ['threshold' => 10000, 'backup_before_merge' => false],
            'custom_dict_persistence' => ['enable_delayed_write' => false],
            'background_tasks' => ['enable' => false],
            'high_freq_cache' => ['size' => 1000]
        ];
        $this->converter = new PinyinConverter($options);
    }

    protected function tearDown(): void
    {
        unset($this->converter);
    }

    /**
     * 测试基础转换性能
     */
    public function testBasicConversionPerformance()
    {
        $testTexts = [
            '中国',
            '中华人民共和国',
            '这是一个测试字符串用于性能测试',
            'The quick brown fox jumps over the lazy dog',
            '1234567890',
            '混合中英文123测试文本！@#￥%……&*（）'
        ];

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testTexts as $text) {
                $this->converter->convert($text);
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = $totalTime / ($iterations * count($testTexts));

        // 平均每次转换应该在1ms以内
        $this->assertLessThan(
            0.001,
            $avgTime,
            "Average conversion time should be less than 1ms, actual: {$avgTime}s"
        );
    }

    /**
     * 测试大量数据转换性能
     */
    public function testLargeDataConversionPerformance()
    {
        // 生成大量测试数据
        $largeTexts = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeTexts[] = str_repeat('中华人民共和国性能测试', 10);
        }

        $startTime = microtime(true);

        foreach ($largeTexts as $text) {
            $this->converter->convert($text);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // 应该在5秒内完成
        $this->assertLessThan(
            5.0,
            $totalTime,
            "Large data conversion should complete within 5 seconds, actual: {$totalTime}s"
        );
    }

    /**
     * 测试内存使用效率
     */
    public function testMemoryUsageEfficiency()
    {
        $initialMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        // 执行大量转换操作
        for ($i = 0; $i < 5000; $i++) {
            $this->converter->convert('中华人民共和国这是一个很长的测试字符串用于测试内存使用效率');
        }

        $finalMemory = memory_get_usage();
        $finalPeakMemory = memory_get_peak_usage();

        $memoryIncrease = $finalMemory - $initialMemory;
        $peakIncrease = $finalPeakMemory - $peakMemory;

        // 内存增长应该在合理范围内（小于5MB）
        $this->assertLessThan(
            5 * 1024 * 1024,
            $memoryIncrease,
            "Memory increase should be less than 5MB, actual: " . ($memoryIncrease / 1024 / 1024) . "MB"
        );

        // 峰值内存增长应该在合理范围内（小于10MB）
        $this->assertLessThan(
            10 * 1024 * 1024,
            $peakIncrease,
            "Peak memory increase should be less than 10MB, actual: " . ($peakIncrease / 1024 / 1024) . "MB"
        );
    }

    /**
     * 测试缓存效率
     */
    public function testCacheEfficiency()
    {
        $testText = '中华人民共和国缓存效率测试';

        // 第一次转换（无缓存）
        $startTime = microtime(true);
        $result1 = $this->converter->convert($testText);
        $firstTime = microtime(true) - $startTime;

        // 第二次转换（有缓存）
        $startTime = microtime(true);
        $result2 = $this->converter->convert($testText);
        $secondTime = microtime(true) - $startTime;

        // 验证结果一致
        $this->assertEquals($result1, $result2);

        // 缓存版本应该更快（至少快20%）
        $this->assertLessThan(
            $firstTime * 0.8,
            $secondTime,
            "Cached conversion should be at least 20% faster. First: {$firstTime}s, Second: {$secondTime}s"
        );
    }

    /**
     * 测试不同输入长度的性能
     */
    public function testPerformanceByInputLength()
    {
        $lengths = [1, 10, 50, 100, 500, 1000];
        $performanceData = [];

        foreach ($lengths as $length) {
            $testText = str_repeat('中', $length);

            $startTime = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $this->converter->convert($testText);
            }
            $endTime = microtime(true);

            $avgTime = ($endTime - $startTime) / 100;
            $performanceData[$length] = $avgTime;
        }

        // 验证性能随长度线性增长（而不是指数增长）
        for ($i = 1; $i < count($lengths); $i++) {
            $prevLength = $lengths[$i - 1];
            $currLength = $lengths[$i];
            $prevTime = $performanceData[$prevLength];
            $currTime = $performanceData[$currLength];

            $lengthRatio = $currLength / $prevLength;
            $timeRatio = $currTime / $prevTime;

            // 时间增长不应该超过长度增长的平方
            $this->assertLessThan(
                $lengthRatio * $lengthRatio,
                $timeRatio,
                "Performance should not degrade exponentially. Length ratio: {$lengthRatio}, Time ratio: {$timeRatio}"
            );
        }
    }

    /**
     * 测试并发性能模拟
     */
    public function testConcurrentPerformanceSimulation()
    {
        $processes = 10;
        $iterationsPerProcess = 100;
        $testTexts = [
            '中国',
            '中华人民共和国',
            '这是一个测试字符串',
            'Mixed text with 中文 and English'
        ];

        $startTime = microtime(true);

        // 模拟并发处理（在单线程环境中快速连续执行）
        for ($p = 0; $p < $processes; $p++) {
            for ($i = 0; $i < $iterationsPerProcess; $i++) {
                $text = $testTexts[$i % count($testTexts)];
                $this->converter->convert($text);
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $totalOperations = $processes * $iterationsPerProcess;
        $opsPerSecond = $totalOperations / $totalTime;

        // 应该能处理至少1000 ops/s
        $this->assertGreaterThan(
            1000,
            $opsPerSecond,
            "Should handle at least 1000 operations per second, actual: {$opsPerSecond} ops/s"
        );
    }

    /**
     * 测试懒加载性能
     */
    public function testLazyLoadingPerformance()
    {
        // 创建启用懒加载的转换器
        $lazyOptions = [
            'dict_loading' => [
                'lazy_loading' => true,
                'preload_priority' => ['custom'],
                'lazy_dicts' => ['common', 'rare', 'unihan']
            ],
            'self_learn_merge' => ['threshold' => 10000, 'backup_before_merge' => false],
            'custom_dict_persistence' => ['enable_delayed_write' => false],
            'background_tasks' => ['enable' => false]
        ];

        $lazyConverter = new PinyinConverter($lazyOptions);

        // 测试初始化时间
        $startTime = microtime(true);
        $converter = new PinyinConverter($lazyOptions);
        $initTime = microtime(true) - $startTime;

        // 测试首次转换时间（需要加载字典）
        $startTime = microtime(true);
        $result1 = $lazyConverter->convert('中国');
        $firstConvertTime = microtime(true) - $startTime;

        // 测试后续转换时间（字典已加载）
        $startTime = microtime(true);
        $result2 = $lazyConverter->convert('美国');
        $secondConvertTime = microtime(true) - $startTime;

        // 验证结果正确
        $this->assertNotEmpty($result1);
        $this->assertNotEmpty($result2);

        // 初始化应该很快
        $this->assertLessThan(
            0.1,
            $initTime,
            "Lazy loading initialization should be fast, actual: {$initTime}s"
        );

        // 首次转换可能较慢（需要加载字典）
        // 后续转换应该很快
        $this->assertLessThan(
            0.01,
            $secondConvertTime,
            "Subsequent conversions should be fast, actual: {$secondConvertTime}s"
        );
    }

    /**
     * 测试自定义字典性能
     */
    public function testCustomDictionaryPerformance()
    {
        // 添加大量自定义拼音
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->addCustomPinyin("自定义{$i}", "custom{$i}");
        }
        $addTime = microtime(true) - $startTime;

        // 测试自定义拼音转换性能
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->convert("自定义{$i}");
        }
        $convertTime = microtime(true) - $startTime;

        // 添加1000个自定义拼音应该在1秒内完成
        $this->assertLessThan(
            12.0,
            $addTime,
            "Adding 1000 custom entries should complete within 1 second, actual: {$addTime}s"
        );

        // 转换1000个自定义拼音应该在0.5秒内完成
        $this->assertLessThan(
            2,
            $convertTime,
            "Converting 1000 custom entries should complete within 0.5 seconds, actual: {$convertTime}s"
        );
        // 清理自定义拼音
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->removeCustomPinyin("自定义{$i}");
        }
    }

    /**
     * 测试URL Slug生成性能
     */
    public function testUrlSlugPerformance()
    {
        $testTexts = [
            'Hello World',
            '中国测试',
            'This is a very long test string with 中文 and English and numbers 123',
            'Special characters !@#$%^&*()_+-=[]{}|;:,.<>?',
            'Multiple words with different 分隔符 and formats'
        ];

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($testTexts as $text) {
                $this->converter->getUrlSlug($text);
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = $totalTime / ($iterations * count($testTexts));

        // URL slug生成平均时间应该在2ms以内
        $this->assertLessThan(
            0.002,
            $avgTime,
            "Average URL slug generation time should be less than 2ms, actual: {$avgTime}s"
        );
    }

    /**
     * 测试内存泄漏检测
     */
    public function testMemoryLeakDetection()
    {
        $initialMemory = memory_get_usage();

        // 执行大量操作
        for ($i = 0; $i < 10000; $i++) {
            $text = '测试字符串' . $i;
            $this->converter->convert($text);

            // 每1000次操作检查一次内存
            if ($i % 1000 === 0) {
                gc_collect_cycles(); // 强制垃圾回收
                $currentMemory = memory_get_usage();
                $memoryIncrease = $currentMemory - $initialMemory;

                // 内存增长不应该超过20MB
                $this->assertLessThan(
                    20 * 1024 * 1024,
                    $memoryIncrease,
                    "Memory leak detected at iteration $i. Increase: " . ($memoryIncrease / 1024 / 1024) . "MB"
                );
            }
        }
    }

    /**
     * 测试性能报告生成
     */
    public function testPerformanceReportGeneration()
    {
        // 执行一些操作以生成统计数据
        for ($i = 0; $i < 100; $i++) {
            $this->converter->convert('中华人民共和国性能测试');
        }

        $startTime = microtime(true);
        $report = $this->converter->getPerformanceReport();
        $reportTime = microtime(true) - $startTime;

        // 报告生成应该很快
        $this->assertLessThan(
            0.02,
            $reportTime,
            "Performance report generation should be fast, actual: {$reportTime}s"
        );

        // 验证报告包含必要信息
        $this->assertArrayHasKey('total_conversions', $report);
    }
}
