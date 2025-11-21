<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 基础拼音转换功能测试
 *
 * 测试范围：
 * - 基本汉字拼音转换
 * - 声调处理（有声调/无声调）
 * - 分隔符处理
 * - 简单词语转换
 */
class BasicConversionTest extends TestCase
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
     * 测试基本汉字转换（无声调）
     */
    public function testBasicConversionWithoutTone()
    {
        // 单字测试
        $this->assertEquals('zhong', $this->converter->convert('中'));
        $this->assertEquals('guo', $this->converter->convert('国'));
        $this->assertEquals('ni', $this->converter->convert('你'));
        $this->assertEquals('hao', $this->converter->convert('好'));

        // 词语测试
        $this->assertEquals('zhong guo', $this->converter->convert('中国'));
        $this->assertEquals('ni hao', $this->converter->convert('你好'));
        $this->assertEquals('kai fa', $this->converter->convert('开发'));
        $this->assertEquals('bei jing', $this->converter->convert('北京'));
    }

    /**
     * 测试基本汉字转换（带声调）
     */
    public function testBasicConversionWithTone()
    {
        // 单字测试
        $this->assertEquals('zhōng', $this->converter->convert('中', ' ', true));
        $this->assertEquals('guó', $this->converter->convert('国', ' ', true));
        $this->assertEquals('nǐ', $this->converter->convert('你', ' ', true));
        $this->assertEquals('hǎo', $this->converter->convert('好', ' ', true));

        // 词语测试
        $this->assertEquals('zhōng guó', $this->converter->convert('中国', ' ', true));
        $this->assertEquals('nǐ hǎo', $this->converter->convert('你好', ' ', true));
        $this->assertEquals('kāi fā', $this->converter->convert('开发', ' ', true));
        $this->assertEquals('běi jīng', $this->converter->convert('北京', ' ', true));
    }

    /**
     * 测试分隔符处理
     */
    public function testSeparatorHandling()
    {
        // 默认空格分隔符
        $this->assertEquals('zhong guo', $this->converter->convert('中国', ' '));

        // 自定义分隔符
        $this->assertEquals('zhong-guo', $this->converter->convert('中国', '-'));
        $this->assertEquals('zhong_guo', $this->converter->convert('中国', '_'));
        $this->assertEquals('zhong.guo', $this->converter->convert('中国', '.'));
        $this->assertEquals('zhong*guo', $this->converter->convert('中国', '*'));

        // 无分隔符
        $this->assertEquals('zhongguo', $this->converter->convert('中国', ''));
    }

    /**
     * 测试多字词语转换
     */
    public function testMultiCharacterWords()
    {
        // 三字词语
        $this->assertEquals('zhong hua ren min gong he guo', $this->converter->convert('中华人民共和国'));
        $this->assertEquals('ji suan ji', $this->converter->convert('计算机'));
        $this->assertEquals('wang luo an quan', $this->converter->convert('网络安全'));

        // 四字成语
        $this->assertEquals('yi xin yi yi', $this->converter->convert('一心一意'));
        $this->assertEquals('long ma jing shen', $this->converter->convert('龙马精神'));
        $this->assertEquals('bai fa bai zhong', $this->converter->convert('百发百中'));
    }

    /**
     * 测试声调符号格式
     */
    public function testToneMarkFormat()
    {
        $result = $this->converter->convert('啊', ' ', true);
        $letter = split_pinyin_tone($result)['letter'];
        $this->assertEquals('a', $letter);
    }

    /**
     * 测试常用高频字
     */
    public function testHighFrequencyCharacters()
    {
        $highFreqChars = [
            '的' => 'de',
            '一' => 'yi',
            '是' => 'shi',
            '不' => 'bu',
            '了' => 'le',
            '在' => 'zai',
            '有' => 'you',
            '和' => 'he',
            '人' => 'ren',
            '这' => 'zhe'
        ];

        foreach ($highFreqChars as $char => $expectedPinyin) {
            $result = $this->converter->convert($char);
            $this->assertEquals(
                $expectedPinyin,
                $result,
                "Character '$char' should convert to '$expectedPinyin'"
            );
        }
    }
}
