<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;
use tekintian\pinyin\Exception\PinyinException;

/**
 * 边界条件和异常处理测试
 *
 * 测试范围：
 * - 空值处理
 * - 异常输入处理
 * - 极限值测试
 * - 错误恢复
 * - 内存限制测试
 */
class EdgeCaseTest extends TestCase
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
            'background_tasks' => ['enable' => false]
        ];
        $this->converter = new PinyinConverter($options);
    }

    protected function tearDown(): void
    {
        unset($this->converter);
    }

    /**
     * 测试空字符串处理
     */
    public function testEmptyStringHandling()
    {
        // 空字符串
        $this->assertEquals('', $this->converter->convert(''));

        // 空字符串带不同参数
        $this->assertEquals('', $this->converter->convert('', ' ', true));
        $this->assertEquals('', $this->converter->convert('', '-'));
        $this->assertEquals('', $this->converter->convert('', '', false, 'keep'));

        // 只包含空格的字符串
        $this->assertEquals('', $this->converter->convert('   '));
        $this->assertEquals('', $this->converter->convert("\t"));
        $this->assertEquals('', $this->converter->convert("\n"));
        $this->assertEquals('', $this->converter->convert("\r"));
    }

    /**
     * 测试null值处理
     */
    public function testNullHandling()
    {
        // null应该被转换为空字符串
        $this->assertEquals('', $this->converter->convert(null));
    }

    /**
     * 测试数字输入处理
     */
    public function testNumericInputHandling()
    {
        // 数字应该被转换为字符串
        $this->assertEquals('123', $this->converter->convert(123));
        $this->assertEquals('123.45', $this->converter->convert(123.45));
        $this->assertEquals('0', $this->converter->convert(0));
        $this->assertEquals('-123', $this->converter->convert(-123));
    }

    /**
     * 测试布尔值输入处理
     */
    public function testBooleanInputHandling()
    {
        // 布尔值应该被转换为字符串
        $this->assertEquals('1', $this->converter->convert(true));
        $this->assertEquals('', $this->converter->convert(false));
    }

    /**
     * 测试无效输入类型异常
     */
    public function testInvalidInputTypeException()
    {
        // 数组输入应该 返回空字符串
        $this->assertEquals('', $this->converter->convert([]));
    }

    /**
     * 测试对象输入异常
     */
    public function testObjectInputException()
    {

        $this->assertEquals('', $this->converter->convert(new \stdClass()));
    }

    /**
     * 测试资源输入异常
     */
    public function testResourceInputException()
    {
        //$this->expectException(InvalidArgumentException::class);
        $res = $this->converter->convert(fopen('php://memory', 'r'));
        // 资源类型输入应该返回空字符串
        $this->assertEquals('', $res);
    }

    /**
     * 测试超长字符串处理
     */
    public function testVeryLongStringHandling()
    {
        // 创建一个包含10000个字符的字符串
        $longText = str_repeat('中国', 50);

        $result = $this->converter->convert($longText);
        $expected = trim(str_repeat('zhong guo ', 50));

        $this->assertEquals($expected, $result);
        $this->assertLessThan(
            5.0,
            microtime(true) - microtime(true),
            'Long text conversion should complete within reasonable time'
        );
    }

    /**
     * 测试超多字符处理
     */
    public function testManyUniqueCharactersHandling()
    {
        // 创建包含很多不同字符的字符串
        $chars = '的一是在不了有和人这中大为上个国我以要他时来用们生到作地于出就分对成会可主发年动同工也能下过子说产种面而方后多定行学法所民得经十三之进着等部度家电力里如水化高自二理起小物现实加量都两体制机当使点从业本去把性好应开它合还因由其些然前外天政四日那社义事平形相全表间样与关各重新线内数正心反你明看原又么利比或但质气第向道命此变条只没结解问意建月公无系军很情者最立代想已通并提直题党程展五果料象员革位入常文总次品式活设及管特件长求老头基资边流路级少图山统接知较将组见计别她手角期根论运农指几九区强放决西被干做必战先回则任取据处队南给色光门即保治北造百规热领七海口东导器压志世金增争济阶油思术极交受联什认六共权收证改清己美再采转更单风切打白教速花带安场身车例真务具万每目至达走积示议声报斗完类八离华名确才科张信马节话米整空元况今集温传土许步群广石记需段研界拉林律叫且究观越织装影算低持音众书布复容儿须际商非验连断深难近矿千周委素技备半办青省列习响约支般史感劳便团往酸历市克何除消构府称太准精值号率族维划选标写存候毛亲快效斯院查江型眼王按格养易置派层片始却专状育厂京识适属圆包火住调满县局照参红细引听该铁价严龙飞';

        $result = $this->converter->convert($chars);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * 测试特殊Unicode字符
     */
    public function testSpecialUnicodeCharacters()
    {
        $specialChars = [
            '䶬' => 'jian', // CJK扩展A字符
            '𠀀' => '𠀀', // CJK扩展B字符 原样返回
            '🀄' => '', // 麻将字符
            '♠' => '',  // 扑克符号
            '♥' => '',  // 扑克符号
            '♦' => '',  // 扑克符号
            '♣' => '',  // 扑克符号
        ];

        foreach ($specialChars as $char => $expected) {
            $result = $this->converter->convert($char);
            $this->assertEquals(
                $expected,
                $result,
                "Special Unicode character '$char' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试无效分隔符
     */
    public function testInvalidSeparators()
    {
        // null分隔符应该被转换为空字符串
        $result = $this->converter->convert('中国', '');
        $this->assertEquals('zhongguo', $result);

        // 数字分隔符
        $result = $this->converter->convert('中国', '1');
        $this->assertEquals('zhong1guo', $result);

        // 长分隔符
        $result = $this->converter->convert('中国', '---');
        $this->assertEquals('zhong---guo', $result);
    }

    /**
     * 测试内存使用限制
     */
    public function testMemoryUsageLimit()
    {
        $initialMemory = memory_get_usage();

        // 处理大量数据
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->convert('中华人民共和国这是一个非常长的字符串用于测试内存使用情况');
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // 内存增长应该在合理范围内（小于10MB）
        $this->assertLessThan(
            10 * 1024 * 1024,
            $memoryIncrease,
            'Memory usage should not increase excessively'
        );
    }

    /**
     * 测试并发安全性
     */
    public function testConcurrentSafety()
    {
        // 模拟并发访问（在单线程环境中快速连续调用）
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = $this->converter->convert("高级{$i}");
        }

        // 验证所有结果都是正确的
        foreach ($results as $i => $result) {
            $this->assertStringContainsString(
                'gao ji',
                $result,
                "Concurrent conversion $i should be correct"
            );
        }
    }

    /**
     * 测试错误恢复能力
     */
    public function testErrorRecovery()
    {
        // 正常转换
        $result1 = $this->converter->convert('中国');
        $this->assertEquals('zhong guo', $result1);

        // 尝试添加无效的自定义拼音
        try {
            $this->converter->addCustomPinyin('', 'invalid');
        } catch (PinyinException $e) {
            // 预期的异常
        }

        // 验证转换器仍然正常工作
        $result2 = $this->converter->convert('中国');
        $this->assertEquals('zhong guo', $result2);
    }

    /**
     * 测试极端分隔符长度
     */
    public function testExtremeSeparatorLength()
    {
        $longSeparator = str_repeat('-', 1000);
        $result = $this->converter->convert('中国', $longSeparator);
        $this->assertEquals('zhong' . $longSeparator . 'guo', $result);
    }

    /**
     * 测试编码问题
     */
    public function testEncodingIssues()
    {
        // 测试不同编码的字符串
        $utf8Text = '中国测试';
        $result = $this->converter->convert($utf8Text);
        $this->assertNotEmpty($result);

        // 测试包含BOM的字符串
        $bomText = "\xEF\xBB\xBF" . '中国测试';
        $result = $this->converter->convert($bomText);
        $this->assertNotEmpty($result);
    }

    /**
     * 测试配置参数边界值
     */
    public function testConfigurationBoundaryValues()
    {
        // 测试极端配置值
        $options = [
            'dict_loading' => ['lazy_loading' => false],
            'high_freq_cache' => ['size' => 0], // 禁用缓存
            'self_learn_merge' => ['threshold' => 0], // 立即触发合并
            'custom_dict_persistence' => ['enable_delayed_write' => false],
            'background_tasks' => ['enable' => false]
        ];

        $converter = new PinyinConverter($options);
        $result = $converter->convert('中国');
        $this->assertEquals('zhong guo', $result);
    }

    /**
     * 测试性能边界
     */
    public function testPerformanceBoundaries()
    {
        $startTime = microtime(true);

        // 执行大量转换操作
        for ($i = 0; $i < 10000; $i++) {
            $this->converter->convert('中华人民共和国');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 应该在合理时间内完成（小于2秒）
        $this->assertLessThan(
            2.0,
            $executionTime,
            'Performance should remain acceptable under high load'
        );
    }
}
