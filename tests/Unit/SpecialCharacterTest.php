<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 特殊字符处理功能测试
 *
 * 测试范围：
 * - 标点符号处理
 * - 数字处理
 * - 英文字母处理
 * - 混合字符处理
 * - 特殊字符模式（keep/delete/replace）
 */
class SpecialCharacterTest extends TestCase
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
     * 测试标点符号处理（默认delete模式）
     */
    public function testPunctuationHandling()
    {
        $testCases = [
            '中国！' => 'zhong guo',
            '中国？' => 'zhong guo',
            '中国。' => 'zhong guo',
            '中国，' => 'zhong guo',
            '中国；' => 'zhong guo',
            '中国：' => 'zhong guo',
            '中国"' => 'zhong guo',
            '中国\'' => 'zhong guo',
            '中国（' => 'zhong guo',
            '中国）' => 'zhong guo',
            '中国【' => 'zhong guo',
            '中国】' => 'zhong guo',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Punctuation '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试数字处理
     */
    public function testNumberHandling()
    {
        $testCases = [
            '中国123' => 'zhong guo 123',
            '123中国' => '123 zhong guo',
            '中1国2' => 'zhong 1 guo 2',
            '2023年' => '2023 nian',
            '第1名' => 'di 1 ming',
            '3.14' => '3.14',
            '100%' => '100',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Number handling '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试英文字母处理
     */
    public function testEnglishLetterHandling()
    {
        $testCases = [
            '中国ABC' => 'zhong guo ABC',
            'ABC中国' => 'ABC zhong guo',
            '中A国B' => 'zhong A guo B',
            'Hello中国' => 'Hello zhong guo',
            '中国World' => 'zhong guo World',
            'PHP开发' => 'PHP kai fa',
            'Java编程' => 'Java bian cheng',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "English letter handling '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试混合字符处理
     */
    public function testMixedCharacterHandling()
    {
        $testCases = [
            'Hello中国123!' => 'Hello zhong guo 123',
            '中国2023年Go!' => 'zhong guo 2023 nian Go',
            'C++编程语言' => 'C++bian cheng yu yan',
            'JavaScript框架' => 'JavaScript kuang jia',
            'Python3.8' => 'Python3.8',
            'HTML5+CSS3' => 'HTML5+CSS3',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Mixed character handling '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试特殊字符的keep模式
     */
    public function testSpecialCharacterKeepMode()
    {
        $testCases = [
            '中国！' => 'zhong guo ！',
            '中国？' => 'zhong guo ？',
            '中国，' => 'zhong guo ，',
            '中国。' => 'zhong guo 。',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input, ' ', false, 'keep');
            $this->assertEquals(
                $expected,
                $result,
                "Keep mode '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试特殊字符的replace模式
     */
    public function testSpecialCharacterReplaceMode()
    {
        $testCases = [
            '中国！' => 'zhong guo !',
            '中国？' => 'zhong guo ?',
            '中国，' => 'zhong guo ,',
            '中国。' => 'zhong guo .',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input, ' ', false, ['mode' => 'replace']);
            $this->assertEquals(
                $expected,
                $result,
                "Replace mode '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试自定义特殊字符映射
     */
    public function testCustomSpecialCharacterMapping()
    {
        $customMapping = [
            '！' => '!',
            '？' => '?',
            '，' => ',',
            '。' => '.',
        ];

        $testCases = [
            '中国！' => 'zhong guo !',
            '中国？' => 'zhong guo ?',
            '中国，' => 'zhong guo ,',
            '中国。' => 'zhong guo .',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert(
                $input,
                ' ',
                false,
                [
                'mode' => 'replace',
                'map' => $customMapping
                ]
            );
            $this->assertEquals(
                $expected,
                $result,
                "Custom mapping '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试URL Slug生成中的特殊字符处理
     */
    public function testUrlSlugSpecialCharacterHandling()
    {
        $testCases = [
            'Hello World!' => 'hello-world',
            '中国，你好！' => 'zhong-guo-ni-hao',
            'Test URL Slug!' => 'test-url-slug',
            'PHP编程语言' => 'php-bian-cheng-yu-yan',
            'JavaScript框架' => 'javascript-kuang-jia',
            'C++开发工具' => 'c-kai-fa-gong-ju',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->getUrlSlug($input);
            $this->assertEquals(
                $expected,
                $result,
                "URL slug '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试空白字符处理
     */
    public function testWhitespaceHandling()
    {
        $testCases = [
            '中国  你好' => 'zhong guo ni hao',        // 多个空格
            "中国\t你好" => 'zhong guo ni hao',        // Tab字符
            "中国\n你好" => 'zhong guo ni hao',        // 换行符
            '中国  你好  世界' => 'zhong guo ni hao shi jie', // 多个空格
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Whitespace handling '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试特殊符号处理
     */
    public function testSpecialSymbolHandling()
    {
        $testCases = [
            '中国@#￥%' => 'zhong guo',
            '中国&*()' => 'zhong guo',
            '中国+-*/' => 'zhong guo + -',
            '中国[]{}' => 'zhong guo',
            '中国|\\' => 'zhong guo',
            '中国<>=' => 'zhong guo',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Special symbol handling '$input' should convert to '$expected'"
            );
        }
    }

    /**
     * 测试Unicode特殊字符
     */
    public function testUnicodeSpecialCharacters()
    {
        $testCases = [
            '中国©' => 'zhong guo',
            '中国®' => 'zhong guo',
            '中国™' => 'zhong guo',
            '中国€' => 'zhong guo',
            '中国£' => 'zhong guo',
            '中国¥' => 'zhong guo',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Unicode special character '$input' should convert to '$expected'"
            );
        }
    }
}
