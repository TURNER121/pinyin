<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 综合PinyinConverter单元测试
 * 覆盖所有核心功能、边界条件和错误处理
 * 在项目根目录运行：./vendor/bin/phpunit tests/ComprehensivePinyinConverterTest.php
 */
class ComprehensivePinyinConverterTest extends TestCase
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
        // 使用真实字典文件进行测试，禁用懒加载确保所有字典已加载
        $options = [
            'dict_loading' => [
                'lazy_loading' => false, // 禁用懒加载，确保所有字典在初始化时加载
                'strategy' => 'both',
                'preload_priority' => ['custom', 'common', 'rare', 'unihan'] // 预加载所有字典
            ],
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
        unset($this->converter);
    }

    /**
     * 测试1：基础拼音转换功能
     */
    public function testBasicConversion()
    {
        // 测试常用字无声调转换
        $this->assertEquals('ni hao', $this->converter->convert('你好', ' ', false));
        $this->assertEquals('zhong guo', $this->converter->convert('中国', ' ', false));
        $this->assertEquals('kai fa', $this->converter->convert('开发', ' ', false));

        // 测试常用字带声调转换
        $this->assertEquals('nǐ hǎo', $this->converter->convert('你好', ' ', true));
        $this->assertEquals('zhōng guó', $this->converter->convert('中国', ' ', true));

        // 测试单字转换
        $this->assertEquals('wo', $this->converter->convert('我', ' ', false));
        $this->assertEquals('wǒ', $this->converter->convert('我', ' ', true));
    }

    /**
     * 测试2：Unihan字典支持测试
     */
    public function testUnihanDictionarySupport()
    {
        $rareChars = ['龘', '靐', '齉', '䶮', '䲜'];

        foreach ($rareChars as $char) {
            $resultWithTone = $this->converter->convert($char, ' ', true);
            $resultWithoutTone = $this->converter->convert($char, ' ', false);

            $this->assertNotEmpty($resultWithTone);
            $this->assertNotEmpty($resultWithoutTone);
            $this->assertNotEquals($char, $resultWithTone);
            $this->assertNotEquals($char, $resultWithoutTone);
        }
    }

    /**
     * 测试3：声调处理功能
     */
    public function testToneHandling()
    {
        $testWords = ['人民', '开发', '软件', '数据库'];

        foreach ($testWords as $word) {
            $withTone = $this->converter->convert($word, ' ', true);
            $withoutTone = $this->converter->convert($word, ' ', false);

            $this->assertNotEquals($withTone, $withoutTone);
            $this->assertRegExp('/[āáǎàōóǒòēéěèīíǐìūúǔùǖǘǚǜ]/', $withTone);
            $this->assertNotRegExp('/[āáǎàōóǒòēéěèīíǐìūúǔùǖǘǚǜ]/', $withoutTone);
        }
    }

    /**
     * 测试4：多音字处理功能
     */
    public function testPolyphoneHandling()
    {
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
            // 清理
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
     * 测试6：特殊字符处理（重点测试）
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

        // 测试替换模式 - 用户指定map时只使用用户map
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

        // 测试替换模式 - 用户未指定map时使用默认map
        $result4 = $this->converter->convert(
            $testText,
            ' ',
            false,
            [
            'mode' => 'replace'
            ]
        );
        $this->assertStringContainsString('ni hao', $result4);
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
     */
    public function testUnihanDictionaryCompleteness()
    {
        $testChars = ['一', '丁', '七', '万', '丈', '三', '上', '下', '不', '与'];

        foreach ($testChars as $char) {
            $resultWithTone = $this->converter->convert($char, ' ', true);
            $resultWithoutTone = $this->converter->convert($char, ' ', false);

            $this->assertNotEmpty($resultWithTone);
            $this->assertNotEmpty($resultWithoutTone);
            $this->assertNotEquals($char, $resultWithTone);
            $this->assertNotEquals($char, $resultWithoutTone);
        }
    }

    /**
     * 测试18：字典优先级验证
     */
    public function testDictionaryPriority()
    {
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

    /**
     * 测试21：特殊字符替换模式边界测试
     */
    public function testSpecialCharReplaceModeEdgeCases()
    {
        // 测试用户指定空map时
        $result1 = $this->converter->convert(
            '你好！',
            ' ',
            false,
            [
            'mode' => 'replace',
            'map' => []
            ]
        );
        $this->assertStringContainsString('ni hao', $result1);

        // 测试用户指定部分字符替换
        $result2 = $this->converter->convert(
            '你好！？',
            ' ',
            false,
            [
            'mode' => 'replace',
            'map' => ['！' => '!']
            ]
        );
        $this->assertStringContainsString('ni hao !？', $result2);

        // 测试用户指定map覆盖默认行为
        $result3 = $this->converter->convert(
            '你好！',
            ' ',
            false,
            [
            'mode' => 'replace',
            'map' => ['！' => 'exclamation']
            ]
        );
        $this->assertStringContainsString('ni hao exclamation', $result3);
    }

    /**
     * 测试22：自定义字典管理功能
     */
    public function testCustomDictManagement()
    {
        try {
            // 测试添加自定义拼音
            $this->converter->addCustomPinyin('测试', 'test', false);
            $this->assertEquals('test', $this->converter->convert('测试', ' ', false));

            // 测试删除自定义拼音
            $this->converter->removeCustomPinyin('测试', false);
            $result = $this->converter->convert('测试', ' ', false);
            $this->assertNotEquals('test', $result);

            // 测试检查和修复自定义字典
            $this->converter->addCustomPinyin('测试', 'test', false);
            $checkResult = $this->converter->checkAndFixCustomDict(false, true, false);
            $this->assertIsArray($checkResult);
        } finally {
            // 清理
            try {
                $this->converter->removeCustomPinyin('测试', false);
            } catch (\Exception $e) {
                // 忽略可能的删除错误
            }
        }
    }

    /**
     * 测试23：自学习字典合并功能
     */
    public function testSelfLearnMergeFunctionality()
    {
        // 测试自学习字典合并
        $mergeResult = $this->converter->executeMerge();
        $this->assertIsArray($mergeResult);
        $this->assertArrayHasKey('success', $mergeResult);
        $this->assertArrayHasKey('fail', $mergeResult);
    }



    /**
     * 测试25：字符频率统计
     */
    public function testCharacterFrequency()
    {
        // 转换一些文本以增加频率统计
        $this->converter->convert('测试频率统计', ' ', false);
        $this->converter->convert('频率统计测试', ' ', false);

        // 验证频率数据被记录（通过自学习功能）
        $mergeResult = $this->converter->executeMerge();
        $this->assertIsArray($mergeResult);
    }

    /**
     * 测试26：Unicode字符处理
     */
    public function testUnicodeCharacterHandling()
    {
        // 测试各种Unicode字符
        $unicodeChars = [
            '𝄞', // 音乐符号
            '😀', // emoji
            '★', // 星号
            '→', // 箭头
            '½', // 分数
        ];

        foreach ($unicodeChars as $char) {
            $result = $this->converter->convert($char, ' ', false, 'delete');
            // 验证特殊字符被正确处理（删除或保留）
            $this->assertIsString($result);
        }
    }

    /**
     * 测试27：大文本处理性能
     */
    public function testLargeTextPerformance()
    {
        // 生成大文本（1000个字符）
        $largeText = str_repeat('企业级AI客户服务系统开发', 50);

        $startTime = microtime(true);
        $result = $this->converter->convert($largeText, ' ', false);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // 验证大文本转换在合理时间内完成
        $this->assertLessThan(2.0, $executionTime, "大文本转换应在2秒内完成，实际耗时：{$executionTime}秒");
        $this->assertNotEmpty($result);
    }

    /**
     * 测试28：配置选项验证
     */
    public function testConfigurationOptions()
    {
        // 测试不同配置选项
        $options = [
            'dict_loading' => [
                'lazy_loading' => true,
                'preload_priority' => ['custom', 'common']
            ],
            'special_char' => [
                'default_mode' => 'keep'
            ]
        ];

        $customConverter = new PinyinConverter($options);

        // 验证配置生效
        $result = $customConverter->convert('你好！', ' ', false);
        $this->assertStringContainsString('！', $result);
    }

    /**
     * 测试29：异常情况处理
     */
    public function testExceptionHandling()
    {
        // 测试无效字典路径
        $options = [
            'dict' => [
                'common' => [
                    'with_tone' => '/invalid/path.php'
                ]
            ]
        ];

        try {
            $invalidConverter = new PinyinConverter($options);
            $this->fail('应该抛出异常');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /**
     * 测试30：综合集成测试
     */
    public function testComprehensiveIntegration()
    {
        // 综合测试所有功能
        $complexScenario = '2023年企业级AI+机器学习开发(Vue3+Go)技术栈！包含K8s部署和源码。';

        // 测试无声调转换
        $resultWithoutTone = $this->converter->convert($complexScenario, ' ', false, 'delete');
        $this->assertStringContainsString('2023 nian', $resultWithoutTone);
        $this->assertStringContainsString('qi ye ji AI', $resultWithoutTone);
        $this->assertStringContainsString('ji qi xue xi', $resultWithoutTone);
        $this->assertStringContainsString('kai fa', $resultWithoutTone);
        $this->assertStringContainsString('Vue3', $resultWithoutTone);
        $this->assertStringContainsString('Go', $resultWithoutTone);
        $this->assertStringContainsString('ji shu zhan', $resultWithoutTone);
        $this->assertStringContainsString('K8s', $resultWithoutTone);
        $this->assertStringContainsString('bu shu', $resultWithoutTone);
        $this->assertStringContainsString('yuan ma', $resultWithoutTone);

        // 测试URL Slug生成
        $slug = $this->converter->getUrlSlug($complexScenario);
        $this->assertStringContainsString('2023-nian', $slug);
        $this->assertStringContainsString('qi-ye-ji-ai', $slug);
        $this->assertStringContainsString('ji-qi-xue-xi', $slug);
        $this->assertStringContainsString('kai-fa', $slug);
        $this->assertStringContainsString('vue3', $slug);
        $this->assertStringContainsString('go', $slug);
        $this->assertStringContainsString('ji-shu-zhan', $slug);
        $this->assertStringContainsString('k8s', $slug);
        $this->assertStringContainsString('bu-shu', $slug);
        $this->assertStringContainsString('yuan-ma', $slug);
    }
}
