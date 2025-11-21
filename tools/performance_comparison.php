#!/usr/bin/env php
<?php
/**
 * 字典策略性能对比测试工具
 * 用于测试不同字典加载策略的性能差异
 * 
 * 使用方法:
 * php tools/performance_comparison.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use tekintian\pinyin\PinyinConverter;
use tekintian\pinyin\Utils\PinyinConstants;

/**
 * 性能测试函数
 */
function testStrategy($strategy, $testTexts, $iterations = 100)
{
    echo "\n=== 测试策略: {$strategy} ===\n";
    
    // 设置环境变量
    putenv(PinyinConstants::ENV_DICT_STRATEGY . '=' . $strategy);
    
    $memoryBefore = memory_get_usage(true);
    $startTime = microtime(true);
    
    $converter = new PinyinConverter();
    $initTime = microtime(true) - $startTime;
    $initMemory = memory_get_usage(true) - $memoryBefore;
    
    echo "初始化时间: " . number_format($initTime * 1000, 2) . " ms\n";
    echo "初始化内存: " . formatBytes($initMemory) . "\n";
    
    // 预热
    foreach ($testTexts as $text) {
        $converter->convert($text);
        $converter->convert($text, ' ', false);
    }
    
    // 性能测试
    $testStartTime = microtime(true);
    $testMemoryBefore = memory_get_usage(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        foreach ($testTexts as $text) {
            $converter->convert($text);
            $converter->convert($text, ' ', false);
        }
    }
    
    $testTime = microtime(true) - $testStartTime;
    $testMemory = memory_get_usage(true) - $testMemoryBefore;
    $peakMemory = memory_get_peak_usage(true);
    
    echo "测试时间: " . number_format($testTime, 2) . " s\n";
    echo "测试内存: " . formatBytes($testMemory) . "\n";
    echo "峰值内存: " . formatBytes($peakMemory) . "\n";
    echo "平均转换时间: " . number_format(($testTime / ($iterations * count($testTexts) * 2)) * 1000, 3) . " ms\n";
    
    return [
        'strategy' => $strategy,
        'init_time' => $initTime,
        'init_memory' => $initMemory,
        'test_time' => $testTime,
        'test_memory' => $testMemory,
        'peak_memory' => $peakMemory
    ];
}

/**
 * 格式化字节数
 */
function formatBytes($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 主测试函数
 */
function main()
{
    echo "拼音转换字典策略性能对比测试\n";
    echo "================================\n";
    
    // 测试文本集
    $testTexts = [
        '中华人民共和国',
        '计算机科学与技术',
        '人工智能机器学习深度学习',
        '电子商务平台系统架构设计',
        '云计算大数据物联网区块链',
        '移动应用开发前端后端全栈',
        '网络信息安全数据加密解密',
        '操作系统数据库编程语言算法',
        '项目管理敏捷开发持续集成',
        '用户体验界面设计交互优化'
    ];
    
    $strategies = [
        PinyinConstants::DICT_STRATEGY_BOTH,
        PinyinConstants::DICT_STRATEGY_WITH_TONE,
        PinyinConstants::DICT_STRATEGY_NO_TONE
    ];
    
    $results = [];
    
    foreach ($strategies as $strategy) {
        $results[] = testStrategy($strategy, $testTexts);
        
        // 清理内存，避免相互影响
        gc_collect_cycles();
        sleep(1);
    }
    
    // 生成对比报告
    echo "\n\n=== 性能对比报告 ===\n";
    echo sprintf("%-20s %12s %12s %12s %12s %12s\n", 
        '策略', '初始化时间', '初始化内存', '测试时间', '测试内存', '峰值内存');
    echo str_repeat('-', 90) . "\n";
    
    foreach ($results as $result) {
        echo sprintf("%-20s %10s ms %10s %10s s %10s %10s\n",
            $result['strategy'],
            number_format($result['init_time'] * 1000, 1),
            formatBytes($result['init_memory']),
            number_format($result['test_time'], 2),
            formatBytes($result['test_memory']),
            formatBytes($result['peak_memory'])
        );
    }
    
    // 推荐建议
    echo "\n=== 推荐建议 ===\n";
    
    $fastestInit = min(array_column($results, 'init_time'));
    $fastestTest = min(array_column($results, 'test_time'));
    $lowestMemory = min(array_column($results, 'peak_memory'));
    
    foreach ($results as $result) {
        if ($result['init_time'] == $fastestInit) {
            echo "• 启动最快: {$result['strategy']}\n";
        }
        if ($result['test_time'] == $fastestTest) {
            echo "• 转换最快: {$result['strategy']}\n";
        }
        if ($result['peak_memory'] == $lowestMemory) {
            echo "• 内存最省: {$result['strategy']}\n";
        }
    }
    
    echo "\n使用建议:\n";
    echo "• 高并发场景: 推荐使用 " . PinyinConstants::DICT_STRATEGY_BOTH . "\n";
    echo "• 内存受限场景: 推荐使用 " . PinyinConstants::DICT_STRATEGY_WITH_TONE . "\n";
    echo "• 纯无声调场景: 推荐使用 " . PinyinConstants::DICT_STRATEGY_NO_TONE . "\n";
}

if (php_sapi_name() === 'cli') {
    main();
} else {
    echo "此脚本只能在命令行中运行。\n";
}