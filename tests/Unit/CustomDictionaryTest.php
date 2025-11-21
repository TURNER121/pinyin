<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;
use tekintian\pinyin\Exception\PinyinException;

/**
 * 自定义字典功能测试
 *
 * 测试范围：
 * - 自定义拼音添加
 * - 自定义拼音删除
 * - 自定义多字词语
 * - 字典持久化
 * - 字典优先级
 */
class CustomDictionaryTest extends TestCase
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
     * 测试添加自定义单字拼音
     */
    public function testAddCustomSingleCharacterPinyin()
    {
        // 添加自定义单字拼音（无声调）
        $this->converter->addCustomPinyin('测', 'test');
        $result = $this->converter->convert('测');
        $this->assertEquals('test', $result);
        // 删除自定义单字拼音
        $this->converter->removeCustomPinyin('测');

        // 添加自定义单字拼音（带声调）
        $this->converter->addCustomPinyin('试', 'shì', true);
        $result = $this->converter->convert('试', ' ', true);
        $this->assertEquals('shì', $result);
        // 删除自定义单字拼音
        $this->converter->removeCustomPinyin('试');

        $this->converter->addCustomPinyin('测试', 'test shi');
        $result = $this->converter->convert('测试');
        $this->assertEquals('test shi', $result);
        // 删除自定义多字词语拼音
        $this->converter->removeCustomPinyin('测试');

        // 添加自定义多字词语拼音（带声调）
        $this->converter->addCustomPinyin('拼音转换', 'pīn yīn zhuǎn huàn', true);
        $result = $this->converter->convert('拼音转换', ' ', true);
        $this->assertEquals('pīn yīn zhuǎn huàn', $result);
        // 删除自定义多字词语拼音
        $this->converter->removeCustomPinyin('拼音转换');
    }

    /**
     * 测试删除自定义拼音
     */
    public function testRemoveCustomPinyin()
    {
        // 先添加自定义拼音
        $this->converter->addCustomPinyin('测试', 'test shi');
        $result = $this->converter->convert('测试');
        $this->assertEquals('test shi', $result);

        // 删除自定义拼音
        $this->converter->removeCustomPinyin('测试');

        // 验证删除后的结果（应该回到默认拼音）
        $result = $this->converter->convert('测试');
        $this->assertNotEquals('test shi', $result);
        $this->assertEquals('ce shi', $result); // 默认拼音
    }

    /**
     * 测试自定义拼音优先级
     */
    public function testCustomPinyinPriority()
    {
        // 添加自定义拼音
        $this->converter->addCustomPinyin('银行', 'custom hang');

        // 验证自定义拼音优先于默认拼音
        $result = $this->converter->convert('银行');
        $this->assertEquals('custom hang', $result);

        // 删除自定义拼音后回到默认
        $this->converter->removeCustomPinyin('银行');
        $result = $this->converter->convert('银行');
        $this->assertEquals('yin hang', $result); // 默认拼音
    }

    /**
     * 测试自定义拼音数组
     */
    public function testCustomPinyinArray()
    {
        // 添加多个拼音选项
        $this->converter->addCustomPinyin('行', ['xing', 'hang', 'hang2']);

        $result = $this->converter->convert('行');
        $this->assertContains($result, ['xing', 'hang', 'hang2']);
        // 删除自定义拼音
        $this->converter->removeCustomPinyin('行');
    }

    /**
     * 测试批量添加自定义拼音
     */
    public function testBatchAddCustomPinyin()
    {
        $customDict = [
            '测试1' => 'test1',
            '测试2' => 'test2',
            '测试3' => 'test3',
        ];

        foreach ($customDict as $char => $pinyin) {
            $this->converter->addCustomPinyin($char, $pinyin);
        }

        // 验证所有自定义拼音都生效
        foreach ($customDict as $char => $expected) {
            $result = $this->converter->convert($char);
            $this->assertEquals(
                $expected,
                $result,
                "Custom pinyin for '$char' should be '$expected'"
            );
        }
        // 删除所有自定义拼音数据,避免影响其他测试
        foreach ($customDict as $char => $pinyin) {
            $this->converter->removeCustomPinyin($char);
        }
    }

    /**
     * 测试自定义拼音与多音字规则交互
     */
    public function testCustomPinyinWithPolyphoneRules()
    {
        // 添加多音字规则
        $this->converter->addPolyphoneRule(
            '行',
            [
            'type' => 'word',
            'word' => '银行',
            'pinyin' => 'hang',
            'weight' => 1.0
            ]
        );

        // 添加自定义拼音（应该优先级更高）
        $this->converter->addCustomPinyin('银行', 'custom bank');

        $result = $this->converter->convert('银行');
        $this->assertEquals('custom bank', $result);
        $this->converter->removeCustomPinyin('银行');
    }

    /**
     * 测试自定义拼音持久化
     */
    public function testCustomPinyinPersistence()
    {
        // 创建启用持久化的转换器
        $options = [
            'dict_loading' => ['lazy_loading' => false],
            'custom_dict_persistence' => ['enable_delayed_write' => true],
            'background_tasks' => ['enable' => false]
        ];
        $converter = new PinyinConverter($options);

        // 添加自定义拼音
        $converter->addCustomPinyin('持久化测试', 'chi jiu hua ce shi');

        // 验证立即生效
        $result = $converter->convert('持久化测试');
        $this->assertEquals('chi jiu hua ce shi', $result);
        $converter->removeCustomPinyin('持久化测试');
    }

    /**
     * 测试自定义拼音错误处理
     */
    public function testCustomPinyinErrorHandling()
    {
        // 测试空字符
        $this->expectException(PinyinException::class);
        $this->converter->addCustomPinyin('', 'test');
    }

    /**
     * 测试自定义拼音覆盖
     */
    public function testCustomPinyinOverride()
    {
        // 先添加一个自定义拼音
        $this->converter->addCustomPinyin('测试', 'first test');
        $result = $this->converter->convert('测试');
        $this->assertEquals('first test', $result);

        // 覆盖之前的自定义拼音
        $this->converter->addCustomPinyin('测试', 'second test');
        $result = $this->converter->convert('测试');
        $this->assertEquals('second test', $result);
        $this->converter->removeCustomPinyin('测试');
    }

    /**
     * 测试自定义拼音与声调处理
     */
    public function testCustomPinyinWithToneHandling()
    {
        // 添加带声调的自定义拼音
        $this->converter->addCustomPinyin('声调', 'shēng diào', true);

        // 测试带声调输出
        $result = $this->converter->convert('声调', ' ', true);
        $this->assertEquals('shēng diào', $result);

        // 测试无声调输出
        $result = $this->converter->convert('声调', ' ', false);
        $this->assertEquals('sheng diao', $result);
        $this->converter->removeCustomPinyin('声调');
    }

    /**
     * 测试自定义拼音与分隔符
     */
    public function testCustomPinyinWithSeparators()
    {
        $this->converter->addCustomPinyin('分隔符测试', 'fen ge fu ce shi');

        // 测试不同分隔符
        $result = $this->converter->convert('分隔符测试', '-');
        $this->assertEquals('fen-ge-fu-ce-shi', $result);

        $result = $this->converter->convert('分隔符测试', '_');
        $this->assertEquals('fen_ge_fu_ce_shi', $result);

        $result = $this->converter->convert('分隔符测试', '');
        $this->assertEquals('fengefuceshi', $result);
        // 删除自定义拼音
        $this->converter->removeCustomPinyin('分隔符测试');
    }

    /**
     * 测试自定义拼音与特殊字符
     */
    public function testCustomPinyinWithSpecialCharacters()
    {
        $this->converter->addCustomPinyin('特殊字符', 'te shu zi fu');

        // 测试与特殊字符混合
        $result = $this->converter->convert('特殊字符！@#');
        $this->assertEquals('te shu zi fu', $result);

        $result = $this->converter->convert('特殊字符123');
        $this->assertEquals('te shu zi fu 123', $result);
        $this->converter->removeCustomPinyin('特殊字符');
    }

    /**
     * 测试自定义拼音性能
     */
    public function testCustomPinyinPerformance()
    {
        // 添加大量自定义拼音
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->addCustomPinyin("测试{$i}", "test{$i}");
        }

        // 测试转换性能
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->converter->convert("测试{$i}");
        }
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // 清理测试数据
        for ($i = 0; $i < 1000; $i++) {
            $this->converter->removeCustomPinyin("测试{$i}");
        }

        $this->assertLessThan(
            0.5,
            $executionTime,
            'Custom pinyin conversion should be fast'
        );

        
    }
}
