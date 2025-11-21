<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 多音字处理功能测试
 *
 * 测试范围：
 * - 多音字识别
 * - 上下文相关多音字处理
 * - 多音字规则匹配
 * - 常见多音字转换
 */
class PolyphoneTest extends TestCase
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
     * 测试常见多音字转换（无声调）
     */
    public function testCommonPolyphonesWithoutTone()
    {
        // 常见多音字测试
        $testCases = [
            '张长' => 'zhang chang',      // 张(zhāng) 长(cháng)
            '中了' => 'zhong le',         // 中(zhòng) 了(le)
            '长大' => 'zhang da',         // 长(zhǎng) 大(dà)
            '银行' => 'yin hang',         // 行(yín) 行(háng)
            '行动' => 'xing dong',        // 行(xíng) 动(dòng)
            '重量' => 'zhong liang',      // 重(zhòng) 量(liàng)
            '重复' => 'chong fu',         // 重(chóng) 复(fù)
            '音乐' => 'yin yue',          // 乐(yuè) 音(yīn)
            '快乐' => 'kuai le',          // 乐(lè) 快(kuài)
            '朝阳' => 'zhao yang',        // 朝(cháo) 阳(yáng)
            '朝代' => 'chao dai',         // 朝(cháo) 代(dài)
            '朝阳' => 'zhao yang',        // 朝(zhāo) 阳(yáng)
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input, ' ', false);
            $this->assertEquals(
                $expected,
                $result,
                "Polyphone phrase '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试常见多音字转换（带声调）
     */
    public function testCommonPolyphonesWithTone()
    {
        $testCases = [
            '张长' => 'zhāng cháng',      // 张(zhāng) 长(cháng)
            '中了' => 'zhòng le',         // 中(zhòng) 了(le)
            '长大' => 'zhǎng dà',         // 长(zhǎng) 大(dà)
            '银行' => 'yín háng',         // 行(yín) 行(háng)
            '行动' => 'xíng dòng',        // 行(xíng) 动(dòng)
            '重量' => 'zhòng liàng',      // 重(zhòng) 量(liàng)
            '重复' => 'chóng fù',         // 重(chóng) 复(fù)
            '音乐' => 'yīn yuè',          // 乐(yuè) 音(yīn)
            '快乐' => 'kuài lè',          // 乐(lè) 快(kuài)
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input, ' ', true);
            $this->assertEquals(
                $expected,
                $result,
                "Polyphone phrase '$input' with tone should convert to '$expected'"
            );
        }
    }

    /**
     * 测试单字多音字处理
     */
    public function testSingleCharacterPolyphones()
    {
        // 测试单个多音字在不同上下文中的读音
        $testCases = [
            '长' => ['chang', 'zhang'],     // 长(cháng, zhǎng)
            '行' => ['xing', 'hang'],       // 行(xíng, háng)
            '重' => ['zhong', 'chong'],     // 重(zhòng, chóng)
            '乐' => ['le', 'yue'],          // 乐(lè, yuè)
            '朝' => ['chao', 'zhao'],       // 朝(cháo, zhāo)
            '中' => ['zhong', 'zhong'],     // 中(zhōng, zhòng)
            '为' => ['wei', 'wei'],         // 为(wéi, wèi)
            '得' => ['de', 'de', 'dei'],    // 得(de, dé, děi)
        ];

        foreach ($testCases as $char => $expectedReadings) {
            $result = $this->converter->convert($char, ' ', false);
            $this->assertContains(
                $result,
                $expectedReadings,
                "Character '$char' should convert to one of: " . implode(', ', $expectedReadings)
            );
        }
    }

    /**
     * 测试多音字规则添加和使用
     */
    public function testPolyphoneRuleManagement()
    {
        // 添加自定义多音字规则
        $this->converter->addPolyphoneRule(
            '行',
            [
            'type' => 'word',
            'word' => '银行',
            'pinyin' => 'hang',
            'weight' => 1.0
            ]
        );

        // 验证规则生效
        $result = $this->converter->convert('银行', ' ', false);
        $this->assertEquals('yin hang', $result);

        // 添加另一个规则
        $this->converter->addPolyphoneRule(
            '行',
            [
            'type' => 'word',
            'word' => '行动',
            'pinyin' => 'xing',
            'weight' => 1.0
            ]
        );

        $result = $this->converter->convert('行动', ' ', false);
        $this->assertEquals('xing dong', $result);
    }

    /**
     * 测试上下文相关的多音字处理
     */
    public function testContextAwarePolyphoneHandling()
    {
        // 测试基于上下文的多音字选择
        $contextTests = [
            '银行存款' => 'yin hang cun kuan',    // 行(háng)
            '行动迅速' => 'xing dong xun su',     // 行(xíng)
            '重量级' => 'zhong liang ji',         // 重(zhòng)
            '重复劳动' => 'chong fu lao dong',     // 重(chóng)
            '音乐厅' => 'yin yue ting',           // 乐(yuè)
            '快乐时光' => 'kuai le shi guang',     // 乐(lè)
        ];

        foreach ($contextTests as $input => $expected) {
            $result = $this->converter->convert($input, ' ', false);
            $this->assertEquals(
                $expected,
                $result,
                "Context-aware polyphone '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试多音字频率统计
     */
    public function testPolyphoneFrequencyStatistics()
    {
        // 多次转换同一个多音字，观察频率统计效果
        for ($i = 0; $i < 10; $i++) {
            $this->converter->convert('银行');
        }

        // 获取性能报告，检查频率统计
        $report = $this->converter->getPerformanceReport();
        $this->assertArrayHasKey('character_frequency', $report);
    }

    /**
     * 测试复杂多音字组合
     */
    public function testComplexPolyphoneCombinations()
    {
        $complexCases = [
            '中国人民银行' => 'zhong guo ren min yin hang',
            '行动中的重量' => 'xing dong zhong de zhong liang',
            '音乐银行行长' => 'yin yue yin hang hang zhang',
            '快乐成长' => 'kuai le cheng zhang',
        ];

        foreach ($complexCases as $input => $expected) {
            $result = $this->converter->convert($input, ' ', false);
            $this->assertEquals(
                $expected,
                $result,
                "Complex polyphone '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试多音字边界情况
     */
    public function testPolyphoneEdgeCases()
    {
        // 空字符串
        $this->assertEquals('', $this->converter->convert(''));

        // 非中文字符
        $this->assertEquals('hello', $this->converter->convert('hello'));
        $this->assertEquals('123', $this->converter->convert('123'));

        // 混合字符
        $this->assertEquals('hello zhong guo', $this->converter->convert('hello中国'));
        $this->assertEquals('zhong guo 123', $this->converter->convert('中国123'));
    }
}
