<?php

require_once __DIR__ . '/vendor/autoload.php';

// 定义测试环境常量
define('PHPUNIT_RUNNING', true);

try {
    echo "开始测试 PinyinConverter...\n";

    $options = [
        'dict_loading' => ['lazy_loading' => false],
        'self_learn_merge' => ['threshold' => 10000, 'backup_before_merge' => false],
        'custom_dict_persistence' => ['enable_delayed_write' => false]
    ];

    $converter = new \tekintian\pinyin\PinyinConverter($options);
    echo "✅ PinyinConverter 实例化成功\n";

    // 测试基本转换
    $result = $converter->convert('中国');
    echo "✅ 基本转换测试: '中国' -> '$result'\n";

    // 测试带声调转换
    $result = $converter->convert('中国', ' ', true);
    echo "✅ 带声调转换测试: '中国' -> '$result'\n";

    // 测试边界情况
    $result = $converter->convert('');
    echo "✅ 空字符串测试: '' -> '$result'\n";

    echo "\n🎉 所有基础测试通过！\n";
} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}
