<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * PinyinConverter 完整单元测试套件
 * 覆盖所有核心功能、边界场景和异常处理
 * 运行方法：./vendor/bin/phpunit tests/legacy/UnitTest.php
 */
class UnitTest extends TestCase
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
            'custom_dict_persistence' => ['enable_delayed_write' => false]
        ];
        $this->converter = new PinyinConverter($options);
    }

    protected function tearDown(): void
    {
        unset($this->converter);
    }

    // ==================== 基础功能测试 ====================

    public function testBasicConversion()
    {
        // 常用汉字转换
        $this->assertEquals('zhong guo', $this->converter->convert('中国'));
        $this->assertEquals('zhōng guó', $this->converter->convert('中国', ' ', true));
        $this->assertEquals('zhong-guo', $this->converter->convert('中国', '-'));
    }

    public function testSpecialCharacters()
    {
        // 特殊字符处理
        $this->assertEquals('zhong guo', $this->converter->convert('中国!'));
        $this->assertEquals('zhong guo 123', $this->converter->convert('中国123'));
        $this->assertEquals('zhong guo abc', $this->converter->convert('中国abc'));
    }

    public function testPolyphoneHandling()
    {
        // 多音字处理
        $this->assertEquals('zhāng cháng', $this->converter->convert('张长', ' ', true));
        $this->assertEquals('zhōng le', $this->converter->convert('中了', ' ', true));
    }

    // ==================== 边界场景测试 ====================

    public function testEmptyString()
    {
        // 空字符串
        $this->assertEquals('', $this->converter->convert(''));
        $this->assertEquals('', $this->converter->convert('', ' ', true));
    }

    public function testVeryLongText()
    {
        // 超长文本（1000个汉字）
        $longText = str_repeat('这是一个测试文本', 125); // 1000个汉字
        $result = $this->converter->convert($longText);
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(1000, strlen($result));
    }

    public function testRareCharacters()
    {
        // 生僻字测试
        $rareChars = '㐀㐁㐂㐃㐄㐅㐆㐇㐈㐉';
        $result = $this->converter->convert($rareChars);
        $this->assertNotEmpty($result);
    }

    public function testMixedContent()
    {
        // 混合内容
        $mixed = 'Hello 世界！123 测试 Test 汉字';
        $result = $this->converter->convert($mixed);
        $this->assertStringContainsString('shi jie', $result);
        $this->assertStringContainsString('ce shi', $result);
        $this->assertStringContainsString('han zi', $result);
    }

    public function testUnicodeCharacters()
    {
        // Unicode字符
        $unicodeText = '汉字测试 🚀 emoji 😊 特殊符号 ★';
        $result = $this->converter->convert($unicodeText);
        $this->assertNotEmpty($result);
    }

    // ==================== 自定义字典功能测试 ====================

    public function testCustomDictionary()
    {
        // 添加自定义拼音  注意这里的 ce4 shi4 在添加的时候会自动统一为 cè shì
        $this->converter->addCustomPinyin('测试', 'ce4 shi4', true);
        $this->assertEquals('cè shì', $this->converter->convert('测试', ' ', true));

        // 删除自定义拼音
        $this->converter->removeCustomPinyin('测试', true);
    }

    public function testBatchConversion()
    {
        // 批量转换
        $texts = ['中国', '美国', '日本', '韩国'];
        $results = $this->converter->batchConvert($texts);
        $this->assertCount(4, $results);
        $this->assertEquals('zhong guo', $results[0]);
    }

    // ==================== URL Slug 功能测试 ====================

    public function testUrlSlug()
    {
        $this->assertEquals('zhong-guo', $this->converter->getUrlSlug('中国'));
        $this->assertEquals('hello-world-123', $this->converter->getUrlSlug('Hello World 123'));
        $this->assertEquals('test-url-slug', $this->converter->getUrlSlug('Test URL Slug!'));
    }

    // ==================== 性能监控测试 ====================

    public function testPerformanceReport()
    {
        $report = $this->converter->getPerformanceReport();
        $this->assertIsArray($report);
        $this->assertArrayHasKey('memory_usage', $report);
        $this->assertArrayHasKey('execution_time', $report);
    }

    public function testStatistics()
    {
        $stats = $this->converter->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_conversions', $stats);
    }

    // ==================== 异常处理测试 ====================

    public function testInvalidInput()
    {
        // 非字符串输入 会强制转换为字符串 或者返回空字符串
        $this->assertEquals('123', $this->converter->convert(123));
        // 非字符串输入 会强制转换为字符串 或者返回空字符串
        $this->assertEquals('', $this->converter->convert(new stdClass()));
        // 非字符串输入 会强制转换为字符串 或者返回空字符串
        $this->assertEquals('', $this->converter->convert(null));
        // 非字符串输入 会强制转换为字符串  这里的bool 会被强制转换为字符串 1
        $this->assertEquals('1', $this->converter->convert(true));
    }

    public function testLargeMemoryUsage()
    {
        // 内存使用监控
        $memoryBefore = memory_get_usage(true);

        // 执行大量转换
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->convert('这是一个测试文本');
        }

        $memoryAfter = memory_get_usage(true);
        $memoryIncrease = $memoryAfter - $memoryBefore;

        // 内存增长应该在合理范围内（小于10MB）
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease, '内存泄漏检测');
    }

    // ==================== 搜索功能测试 ====================

    public function testSearchByPinyin()
    {
        $results = $this->converter->searchByPinyin('zhong');
        $this->assertIsArray($results);
        $this->assertContains('中', $results);
    }

    // ==================== 缓存功能测试 ====================

    public function testCacheClear()
    {
        // 先执行一些转换
        $this->converter->convert('测试缓存');

        // 清理缓存
        $this->converter->clearExpiredCache(0);

        // 再次转换应该正常工作
        $result = $this->converter->convert('测试缓存');
        $this->assertNotEmpty($result);
    }

    // ==================== 自学习功能测试 ====================

    public function testSelfLearnMerge()
    {
        $mergeNeed = $this->converter->checkMergeNeed();
        $this->assertIsBool($mergeNeed);

        if ($mergeNeed) {
            $mergeResult = $this->converter->executeMerge();
            $this->assertIsArray($mergeResult);
        }
    }

    // ==================== 压力边界测试 ====================

    public function testExtremeLengthText()
    {
        // 极端长度文本（10万个字符）
        $extremeText = str_repeat('这是一个非常长的测试文本用于测试极端情况', 2000);
        $result = $this->converter->convert($extremeText);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testRepeatedConversion()
    {
        // 重复转换测试（检测内存泄漏）
        $memoryUsage = [];

        for ($i = 0; $i < 100; $i++) {
            $memoryUsage[$i] = memory_get_usage(true);
            $this->converter->convert('重复转换测试文本');

            // 每10次检查一次内存增长
            if ($i % 10 === 0 && $i > 0) {
                $memoryIncrease = $memoryUsage[$i] - $memoryUsage[$i - 10];
                $this->assertLessThan(1 * 1024 * 1024, $memoryIncrease, "第{$i}次迭代内存泄漏检测");
            }
        }
    }

    public function testConcurrentAccess()
    {
        // 模拟并发访问（使用多进程测试）
        $texts = [
            '第一个测试文本',
            '第二个测试文本',
            '第三个测试文本',
            '第四个测试文本'
        ];

        $results = [];
        foreach ($texts as $text) {
            $results[] = $this->converter->convert($text);
        }

        $this->assertCount(4, $results);
        foreach ($results as $result) {
            $this->assertNotEmpty($result);
        }
    }
}
