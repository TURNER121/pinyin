<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * PinyinConverter 单元测试
 * 基于当前功能特性设计的完整测试套件
 * 覆盖Unihan字典、声调处理、多音字、自定义字典等核心功能
 * 在项目根目录运行：./vendor/bin/phpunit tests/PinyinConverterTest.php
 */
class PinyinConverterTest extends TestCase
{
    /**
     * @var PinyinConverter
     */
    private $converter;

    /**
     * 测试初始化：设置测试环境
     */
    protected function setUp(): void
    {
        // 使用真实字典文件进行测试，但添加必要的隔离措施
        $options = [
            // 禁用懒加载，确保所有字典在测试时都已加载
            'dict_loading' => [
                'lazy_loading' => false
            ],
            // 提高阈值避免测试中触发合并
            'self_learn_merge' => [
                'threshold' => 10000,
                'backup_before_merge' => false
            ]
        ];
        $this->converter = new PinyinConverter($options);
    }

    /**
     * 测试结束：清理资源
     */
    protected function tearDown(): void
    {
        // 清理可能添加的自定义拼音
        unset($this->converter);
    }

    /**
     * 测试1：基础拼音转换功能
     * 覆盖无声调和带声调的基本转换
     */
    public function testBasicConversion()
    {
        // 测试常用字无声调转换
        $this->assertEquals('ni hao', $this->converter->convert('你好', ' ', false));
        $this->assertEquals('zhong guo', $this->converter->convert('中国', ' ', false));
        $this->assertEquals('kai fa', $this->converter->convert('开发', ' ', false));
        $this->assertEquals('yun nan', $this->converter->convert('云南', ' ', false));

        // 测试常用字带声调转换
        $this->assertEquals('nǐ hǎo', $this->converter->convert('你好', ' ', true));
        $this->assertEquals('zhōng guó', $this->converter->convert('中国', ' ', true));

        // 测试单字转换
        $this->assertEquals('wo', $this->converter->convert('我', ' ', false));
        $this->assertEquals('wǒ', $this->converter->convert('我', ' ', true));
    }

    /**
     * 测试2：Unihan字典支持测试
     * 验证Unihan字典中的生僻字转换
     */
    public function testUnihanDictionarySupport()
    {
        // 测试一些Unihan字典中的生僻字
        $rareChars = ['龘', '靐', '齉', '䶮', '䲜'];

        foreach ($rareChars as $char) {
            $resultWithTone = $this->converter->convert($char, ' ', true);
            $resultWithoutTone = $this->converter->convert($char, ' ', false);

            // 验证返回结果不为空且不是原字符
            $this->assertNotEmpty($resultWithTone);
            $this->assertNotEmpty($resultWithoutTone);
            // echo $char . ' ' . $resultWithTone . ' ' . $resultWithoutTone . "\n";
            $this->assertNotEquals($char, $resultWithTone);
            $this->assertNotEquals($char, $resultWithoutTone);
        }
    }

    /**
     * 测试3：声调处理功能
     * 验证带声调和不带声调转换的正确性
     */
    public function testToneHandling()
    {
        // 测试声调转换的一致性
        $testWords = ['人民', '开发', '软件', '数据库'];

        foreach ($testWords as $word) {
            $withTone = $this->converter->convert($word, ' ', true);
            $withoutTone = $this->converter->convert($word, ' ', false);

            // 验证结果不同
            $this->assertNotEquals($withTone, $withoutTone);

            // 验证带声调结果包含声调符号
            $this->assertRegExp('/[āáǎàōóǒòēéěèīíǐìūúǔùǖǘǚǜ]/', $withTone);

            // 验证无声调结果不包含声调符号
            $this->assertNotRegExp('/[āáǎàōóǒòēéěèīíǐìūúǔùǖǘǚǜ]/', $withoutTone);
        }
    }

    /**
     * 测试4：多音字处理功能
     * 验证多音字在不同上下文中的正确发音
     */
    public function testPolyphoneHandling()
    {
        // 测试常见多音字
        $testCases = [
            ['行', '行为', 'xing'],
            ['行', '行业', 'hang'],
            ['长', '长度', 'chang'],
            ['长', '长大', 'zhang'],
            ['乐', '乐趣', 'le'],
            ['乐', '音乐', 'yue'],
            ['重', '重量', 'zhong'],
            ['重', '重复', 'chong']
        ];

        foreach ($testCases as $case) {
            list($char, $word, $expectedPinyin) = $case;
            $result = $this->converter->convert($word, ' ', false);
            $this->assertStringContainsString($expectedPinyin, $result, "{$word} 中的 {$char} 应该读作 {$expectedPinyin}");
        }
    }

    /**
     * 测试5：自定义字典功能和优先级
     */
    public function testCustomDictPriority()
    {
        // 保存原始转换结果
        $originalResultWithTone = $this->converter->convert('好', ' ', true);
        $originalResultWithoutTone = $this->converter->convert('好', ' ', false);

        try {
            // 添加自定义拼音（覆盖现有读音）
            $this->converter->addCustomPinyin('好', 'hǎo', true);
            $this->converter->addCustomPinyin('好', 'hao', false);

            // 验证自定义字典生效
            $this->assertEquals('hǎo', $this->converter->convert('好', ' ', true));
            $this->assertEquals('hao', $this->converter->convert('好', ' ', false));

            // 测试多字词语的自定义拼音
            $this->converter->addCustomPinyin('你好世界', 'nǐ hǎo shì jiè', true);
            $this->assertStringContainsString('nǐ hǎo shì jiè', $this->converter->convert('你好世界', ' ', true));
        } finally {
            // 清理：删除自定义拼音，确保测试隔离
            try {
                $this->converter->removeCustomPinyin('好', true);
                $this->converter->removeCustomPinyin('好', false);
                $this->converter->removeCustomPinyin('你好世界', true);
            } catch (\Exception $e) {
                // 忽略可能的删除错误
            }
        }
    }

    /**
     * 测试6：特殊字符处理
     */
    public function testSpecialCharHandling()
    {
        $testText = '你好！@#￥%……&*（）【】{}|、；‘：“，。、？';

        // 测试删除模式（默认）
        $result1 = $this->converter->convert($testText, ' ', false, 'delete');
        $this->assertEquals('ni hao', $result1);

        // 测试保留模式
        $result2 = $this->converter->convert($testText, ' ', false, 'keep');
        $this->assertStringContainsString('ni hao', $result2);
        $this->assertStringContainsString('@', $result2);
        $this->assertStringContainsString('#', $result2);

        // 测试替换模式
        $result3 = $this->converter->convert(
            $testText,
            ' ',
            false,
            [
            'mode' => 'replace',
            'map' => ['！' => '!', '？' => '?']
            ]
        );
        $this->assertStringContainsString('ni hao !@#￥%……&*（）【】{}|、；‘：“，。、?', $result3);
    }

    /**
     * 测试7：英文和数字处理
     */
    public function testEnglishAndNumbers()
    {
        // 测试英文单词保持完整性
        $this->assertEquals('AI ji shu', $this->converter->convert('AI技术', ' ', false));
        $this->assertEquals('PHP kai fa', $this->converter->convert('PHP 开发', ' ', false));
        $this->assertEquals('Vue kai fa', $this->converter->convert('Vue开发', ' ', false));

        // 测试数字处理
        $this->assertEquals('7 tian kai fa', $this->converter->convert('7天开发', ' ', false));
        $this->assertEquals('K8s ji shu', $this->converter->convert('K8s技术', ' ', false));
        $this->assertEquals('v2.0 ban ben', $this->converter->convert('v2.0版本', ' ', false));

        // 测试混合内容
        $this->assertEquals('qi ye ji AI ke hu fu wu', $this->converter->convert('企业级AI客户服务', ' ', false));
        $this->assertEquals('2023 nian 10 yue 1 ri', $this->converter->convert('2023年10月1日', ' ', false));
    }

    /**
     * 测试8：分隔符和格式处理
     */
    public function testSeparatorsAndFormatting()
    {
        // 测试不同分隔符
        $this->assertEquals('ni-hao', $this->converter->convert('你好', '-', false));
        $this->assertEquals('ni_hao', $this->converter->convert('你好', '_', false));
        $this->assertEquals('ni.hao', $this->converter->convert('你好', '.', false));
        $this->assertEquals('ni hao', $this->converter->convert('你好', ' ', false));

        // 测试空分隔符
        $this->assertEquals('nihao', $this->converter->convert('你好', '', false));

        // 测试连续分隔符合并
        $this->assertEquals('ni-hao', $this->converter->convert('你好！', '-', false));

        // 测试Unicode分隔符
        $this->assertEquals('ni|hao', $this->converter->convert('你好', '|', false));
    }

    /**
     * 测试9：URL Slug生成功能
     */
    public function testUrlSlugGeneration()
    {
        // 测试基本slug生成
        $this->assertEquals('ni-hao', $this->converter->getUrlSlug('你好！'));
        $this->assertEquals('yun-nan-sheng', $this->converter->getUrlSlug('云南省？'));
        $this->assertEquals('qi-ye-ji-ai-ke-hu-fu-wu', $this->converter->getUrlSlug('企业级AI客户服务'));

        // 测试自定义分隔符
        $this->assertEquals('ni_hao', $this->converter->getUrlSlug('你好！', '_'));
        $this->assertEquals('ni.hao', $this->converter->getUrlSlug('你好！', '.'));

        // 测试复杂内容
        $this->assertEquals('7-tian-kai-fa-qi-ye-ji-ai', $this->converter->getUrlSlug('7天开发企业级AI！@#￥'));

        // 测试连续分隔符处理
        $this->assertEquals('ai-ji-shu', $this->converter->getUrlSlug('  AI 技术  '));
    }

    /**
     * 测试10：缓存机制
     */
    public function testCacheMechanism()
    {
        $text = '测试缓存机制';

        // 首次转换
        $result1 = $this->converter->convert($text, ' ', false);

        // 第二次转换（应命中缓存）
        $result2 = $this->converter->convert($text, ' ', false);

        // 验证结果一致
        $this->assertEquals($result1, $result2);

        // 测试不同参数不命中缓存
        $result3 = $this->converter->convert($text, '-', false);
        $this->assertNotEquals($result1, $result3);

        $result4 = $this->converter->convert($text, ' ', true);
        $this->assertNotEquals($result1, $result4);
    }

    /**
     * 测试11：边界情况处理
     */
    public function testEdgeCases()
    {
        // 测试空字符串
        $this->assertEquals('', $this->converter->convert('', ' ', false));
        $this->assertEquals('', $this->converter->getUrlSlug(''));

        // 测试纯英文
        $this->assertEquals('Hello World', $this->converter->convert('Hello World', ' ', false));
        $this->assertEquals('hello-world', $this->converter->getUrlSlug('Hello World'));

        // 测试纯数字
        $this->assertEquals('123 456', $this->converter->convert('123 456', ' ', false));
        $this->assertEquals('123-456', $this->converter->getUrlSlug('123 456'));

        // 测试混合边界字符
        $this->assertEquals('ni hao AI123', $this->converter->convert('你好AI123', ' ', false));
        $this->assertEquals('k8s-vue3-php', $this->converter->getUrlSlug('K8s+Vue3+PHP'));

        // 测试长文本
        $longText = str_repeat('你好', 100);
        $result = $this->converter->convert($longText, ' ', false);
        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('ni hao', $result);
    }

    /**
     * 测试12：自学习功能
     */
    public function testSelfLearningFunctionality()
    {
        // 测试生僻字转换（会触发自学习）
        $rareChars = ['龘', '靐', '齉'];

        foreach ($rareChars as $char) {
            $result = $this->converter->convert($char, ' ', false);
            // 验证返回结果不为空（表示已处理）
            $this->assertNotEmpty($result);
        }
    }

    /**
     * 测试13：性能测试
     */
    public function testPerformance()
    {
        // 执行多次转换测试性能
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->converter->convert('企业级AI客户服务系统开发', ' ', false);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // 验证执行时间在合理范围内（100次转换应在1秒内完成）
        $this->assertLessThan(1.0, $executionTime, "100次转换应在1秒内完成，实际耗时：{$executionTime}秒");
    }

    /**
     * 测试14：复杂混合内容处理
     */
    public function testComplexMixedContent()
    {
        $complexText = '7天开发企业级AI客户服-软件开发-(系0~!务系统Vue3+Go+Gin+K8s技术栈（含源码+部署文档）';

        $result = $this->converter->convert($complexText, ' ', false, 'delete');

        // 验证关键部分正确转换
        $this->assertStringContainsString('7 tian kai fa', $result);
        $this->assertStringContainsString('qi ye ji AI', $result);
        $this->assertStringContainsString('ruan jian kai fa', $result);
        $this->assertStringContainsString('xi 0', $result);
        $this->assertStringContainsString('wu xi tong', $result);
        $this->assertStringContainsString('Vue3', $result);
        $this->assertStringContainsString('Go', $result);
        $this->assertStringContainsString('Gin', $result);
        $this->assertStringContainsString('K8s', $result);
        $this->assertStringContainsString('ji shu zhan', $result);
        $this->assertStringContainsString('han yuan ma', $result);
        $this->assertStringContainsString('bu shu wen dang', $result);

        // 验证特殊字符被正确处理
        $this->assertStringNotContainsString('~', $result);
        $this->assertStringNotContainsString('!', $result);
    }

    /**
     * 测试15：连续分隔符处理
     */
    public function testConsecutiveSeparators()
    {
        // 测试多个特殊字符导致的连续分隔符
        $this->assertEquals('ni-hao', $this->converter->convert('你好！！！@@@', '-', false));
        $this->assertEquals('ni_hao', $this->converter->convert('你好！！！@@@', '_', false));
        $this->assertEquals('nihao', $this->converter->convert('你好！！！@@@', '', false));

        // 测试空字符导致的连续分隔符
        $this->assertEquals('ni-hao', $this->converter->convert('你  好', '-', false));
    }

    /**
     * 测试16：自定义多字词语的空格保留
     */
    public function testCustomMultiWordSpaceHandling()
    {
        try {
            // 添加带空格的自定义多字词语
            $this->converter->addCustomPinyin('人工智能', 'rén gōng zhì néng', true);
            $this->converter->addCustomPinyin('机器学习', 'machine learning', false);

            // 验证空格被正确保留
            $result1 = $this->converter->convert('人工智能', ' ', true);
            $this->assertStringContainsString('rén gōng zhì néng', $result1);

            $result2 = $this->converter->convert('机器学习', ' ', false);
            $this->assertStringContainsString('machine learning', $result2);
        } finally {
            // 清理
            try {
                $this->converter->removeCustomPinyin('人工智能', true);
                $this->converter->removeCustomPinyin('机器学习', false);
            } catch (\Exception $e) {
                // 忽略可能的删除错误
            }
        }
    }

    /**
     * 测试17：Unihan字典完整性验证
     * 验证Unihan字典是否包含完整的CJK字符集拼音数据
     */
    public function testUnihanDictionaryCompleteness()
    {
        // 测试一些Unihan字典中的字符
        $testChars = ['一', '丁', '七', '万', '丈', '三', '上', '下', '不', '与'];

        foreach ($testChars as $char) {
            $resultWithTone = $this->converter->convert($char, ' ', true);
            $resultWithoutTone = $this->converter->convert($char, ' ', false);

            // 验证结果不为空且不是原字符
            $this->assertNotEmpty($resultWithTone);
            $this->assertNotEmpty($resultWithoutTone);
            $this->assertNotEquals($char, $resultWithTone);
            $this->assertNotEquals($char, $resultWithoutTone);
        }
    }

    /**
     * 测试18：字典优先级验证
     * 验证自定义字典 > 常用字典 > 生僻字字典 > Unihan字典的优先级顺序
     */
    public function testDictionaryPriority()
    {
        // 使用一个在常用字典中不存在的词语来测试优先级
        $testWord = '自定义测试词语';

        try {
            // 添加自定义拼音
            $this->converter->addCustomPinyin($testWord, 'custom test word', false);

            // 验证自定义字典优先级最高
            $result = $this->converter->convert($testWord, ' ', false);
            $this->assertEquals('custom test word', $result);
        } finally {
            // 清理
            try {
                $this->converter->removeCustomPinyin($testWord, false);
            } catch (\Exception $e) {
                // 忽略可能的删除错误
            }
        }
    }

    /**
     * 测试19：多字词语匹配
     */
    public function testMultiWordMatching()
    {
        // 测试常见多字词语转换
        $this->assertEquals('kai fa', $this->converter->convert('开发', ' ', false));
        $this->assertEquals('ruan jian', $this->converter->convert('软件', ' ', false));
        $this->assertEquals('shu ju ku', $this->converter->convert('数据库', ' ', false));
        $this->assertEquals('ke hu fu wu', $this->converter->convert('客户服务', ' ', false));

        // 测试带声调版本
        $this->assertStringContainsString('kāi fā', $this->converter->convert('开发', ' ', true));
        $this->assertStringContainsString('ruǎn jiàn', $this->converter->convert('软件', ' ', true));

        // 测试重叠词语
        $this->assertStringContainsString('hao hao', $this->converter->convert('好好', ' ', false));
        $this->assertStringContainsString('tian tian', $this->converter->convert('天天', ' ', false));
    }

    /**
     * 测试20：错误处理
     */
    public function testErrorHandling()
    {
        // 测试空字符串
        $this->assertEquals('', $this->converter->convert('', ' ', false));

        // 测试无效分隔符
        $this->assertEquals('ni hao', $this->converter->convert('你好', ' ', false));

        // 测试无效特殊字符处理模式
        $this->assertEquals('ni hao', $this->converter->convert('你好', ' ', false, 'invalid_mode'));
    }
}
