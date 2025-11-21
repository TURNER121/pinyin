<?php

namespace tekintian\pinyin\Tests;

use PHPUnit\Framework\TestCase;
use tekintian\pinyin\PinyinConverter;

/**
 * 完整工作流集成测试
 *
 * 测试范围：
 * - 完整转换流程
 * - 多功能组合使用
 * - 实际使用场景模拟
 * - 端到端测试
 */
class CompleteWorkflowTest extends TestCase
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
            'background_tasks' => ['enable' => false],
            'high_freq_cache' => ['size' => 1000]
        ];
        $this->converter = new PinyinConverter($options);
    }

    protected function tearDown(): void
    {
        unset($this->converter);
    }

    /**
     * 测试完整的文章转换工作流
     */
    public function testCompleteArticleConversionWorkflow()
    {
        $article = <<<'ARTICLE'
# 中文技术文章标题

这是一个关于中文拼音转换的技术文章。文章包含了各种类型的内容：

## 基础汉字
中华人民共和国成立于1949年，首都北京。

## 多音字测试
银行(yínháng)的行长(zhǎng)很长(cháng)。

## 特殊字符
这里有一些特殊字符：中文、English、数字123、符号！@#￥%……

## 技术术语
JavaScript、Python、C++、HTML5、CSS3等编程语言。

## 混合内容
学习编程需要掌握算法和数据结构，这是计算机科学的基础。
ARTICLE;

        // 1. 基础转换（无声调）
        $result1 = $this->converter->convert($article);
        $this->assertNotEmpty($result1);
        $this->assertStringContainsString('zhong hua ren min gong he guo', $result1);
        $this->assertStringContainsString('JavaScript', $result1);
        $this->assertStringContainsString('123', $result1);

        // 2. 带声调转换
        $result2 = $this->converter->convert($article, ' ', true);
        $this->assertNotEmpty($result2);
        $this->assertStringContainsString('zhōng huá rén mín gòng hé guó', $result2);

        // 3. URL Slug生成
        $slug = $this->converter->getUrlSlug('中文技术文章标题');
        $this->assertEquals('zhong-wen-ji-shu-wen-zhang-biao-ti', $slug);

        // 4. 批量转换
        $sentences = [
            '中华人民共和国成立于1949年',
            '银行行长很长',
            '学习编程需要掌握算法和数据结构'
        ];
        $batchResults = $this->converter->batchConvert($sentences);
        $this->assertCount(3, $batchResults);
        $this->assertStringContainsString('zhong hua ren min gong he guo', $batchResults[0]);
    }

    /**
     * 测试自定义字典完整工作流
     */
    public function testCustomDictionaryCompleteWorkflow()
    {
        // 1. 添加专业术语自定义拼音
        $technicalTerms = [
            '人工智能' => 'ren gong zhi neng',
            '机器学习' => 'ji qi xue xi',
            '深度学习' => 'shen du xue xi',
            '神经网络' => 'shen jing wang luo',
            '自然语言处理' => 'zi ran yu yan chu li'
        ];

        foreach ($technicalTerms as $term => $pinyin) {
            $this->converter->addCustomPinyin($term, $pinyin);
        }

        // 2. 验证自定义拼音生效
        foreach ($technicalTerms as $term => $expected) {
            $result = $this->converter->convert($term);
            $this->assertEquals(
                $expected,
                $result,
                "Custom term '$term' should convert to '$expected'"
            );
        }

        // 3. 测试在长文本中的使用
        $technicalText = '人工智能和机器学习是深度学习的基础，神经网络是自然语言处理的核心技术。';
        $result = $this->converter->convert($technicalText);

        foreach ($technicalTerms as $term => $pinyin) {
            $this->assertStringContainsString(
                $pinyin,
                $result,
                "Technical text should contain custom pinyin for '$term'"
            );
        }

        // 4. 添加多音字规则
        $this->converter->addPolyphoneRule(
            '行',
            [
            'type' => 'word',
            'word' => '银行',
            'pinyin' => 'hang',
            'weight' => 1.0
            ]
        );

        // 5. 测试多音字规则与自定义字典的优先级
        $this->converter->addCustomPinyin('银行', 'custom bank');
        $result = $this->converter->convert('银行');
        $this->assertEquals('custom bank', $result); // 自定义字典优先级更高

        // 6. 删除自定义拼音
        $this->converter->removeCustomPinyin('银行');
        $result = $this->converter->convert('银行');
        $this->assertEquals('yin hang', $result); // 回到多音字规则
    }

    /**
     * 测试多音字处理完整工作流
     */
    public function testPolyphoneCompleteWorkflow()
    {
        // 1. 基础多音字测试
        $polyphoneTests = [
            '银行存款' => 'yin hang cun kuan',
            '行动迅速' => 'xing dong xun su',
            '重量级选手' => 'zhong liang ji xuan shou',
            '重复劳动' => 'chong fu lao dong',
            '音乐厅' => 'yin yue ting',
            '快乐时光' => 'kuai le shi guang'
        ];

        foreach ($polyphoneTests as $input => $expected) {
            $result = $this->converter->convert($input);
            $this->assertEquals(
                $expected,
                $result,
                "Polyphone phrase '$input' should convert to '$expected'"
            );
        }

        // 2. 添加复杂多音字规则
        $this->converter->addPolyphoneRule(
            '长',
            [
            'type' => 'post',
            'char' => '城',
            'pinyin' => 'zhang',
            'weight' => 1.0
            ]
        );

        $this->converter->addPolyphoneRule(
            '长',
            [
            'type' => 'pre',
            'char' => '短',
            'pinyin' => 'chang',
            'weight' => 1.0
            ]
        );

        // 3. 测试规则生效
        $result = $this->converter->convert('长城');
        $this->assertStringContainsString('zhang', $result);

        $result = $this->converter->convert('长短');
        $this->assertStringContainsString('chang', $result);

        // 4. 测试复杂多音字组合
        $complexText = '长城很长，但长短不一。银行的行长在行动，重复强调重量问题。';
        $result = $this->converter->convert($complexText);
        $this->assertNotEmpty($result);
    }

    /**
     * 测试特殊字符处理完整工作流
     */
    public function testSpecialCharacterCompleteWorkflow()
    {
        $mixedContent = 'Hello世界！@#这是一个高级123，包含中文、English、数字123、符号！@#￥%……';

        // 1. 默认删除模式
        $result1 = $this->converter->convert($mixedContent);
        $this->assertEquals('Hello shi jie zhe shi yi ge gao ji 123 bao han zhong wen English shu zi 123 fu hao', $result1);

        // 2. 保留模式
        $result2 = $this->converter->convert($mixedContent, ' ', false, 'keep');
        $this->assertStringContainsString('！', $result2);
        $this->assertStringContainsString('@#', $result2);

        // 3. 替换模式
        $result3 = $this->converter->convert($mixedContent, ' ', false, 'replace');
        $this->assertNotEmpty($result3);

        // 4. 自定义映射
        $customMap = [
            '！' => '!',
            '，' => ',',
            '。' => '.'
        ];
        $result4 = $this->converter->convert(
            $mixedContent,
            ' ',
            false,
            [
            'mode' => 'replace',
            'map' => $customMap
            ]
        );
        $this->assertStringContainsString('!', $result4);
        $this->assertStringContainsString(',', $result4);

        // 5. URL Slug生成
        $slug = $this->converter->getUrlSlug($mixedContent);
        $this->assertEquals('hello-shi-jie-zhe-shi-yi-ge-gao-ji-123-bao-han-zhong-wen-english-shu-zi-123-fu-hao', $slug);
    }

    /**
     * 测试实际应用场景
     */
    public function testRealWorldApplicationScenarios()
    {
        // 场景1：SEO URL生成
        $seoTitles = [
            '中文网站SEO优化指南',
            'JavaScript前端开发教程',
            'Python数据分析入门',
            '机器学习算法详解'
        ];

        foreach ($seoTitles as $title) {
            $slug = $this->converter->getUrlSlug($title);
            $this->assertNotEmpty($slug);
            $this->assertRegExp('/^[a-z0-9-]+$/', $slug);
        }

        // 场景2：内容搜索索引
        $documents = [
            '人工智能在医疗领域的应用',
            '区块链技术的发展趋势',
            '云计算架构设计原则',
            '物联网安全防护措施'
        ];

        $searchIndex = [];
        foreach ($documents as $doc) {
            $pinyin = $this->converter->convert($doc);
            $searchIndex[] = $pinyin;
        }

        $this->assertCount(4, $searchIndex);
        foreach ($searchIndex as $index) {
            $this->assertNotEmpty($index);
        }

        // 场景3：多语言内容处理
        $multilingualContent = [
            '中文内容处理',
            'English content processing',
            'Mixed 中英文 content',
            '123数字内容',
            'Special!@#characters'
        ];

        foreach ($multilingualContent as $content) {
            $result = $this->converter->convert($content);
            $this->assertNotEmpty($result);
        }

        // 场景4：批量文档处理
        $batchDocuments = [
            'doc1' => '这是第一个文档',
            'doc2' => 'This is the second document',
            'doc3' => '第三个文档包含123数字',
            'doc4' => 'Fourth document with 中文 content'
        ];

        $batchResults = $this->converter->batchConvert(array_values($batchDocuments));
        $this->assertCount(4, $batchResults);

        foreach ($batchResults as $i => $result) {
            $this->assertNotEmpty($result);
            $this->assertIsString($result);
        }
    }

    /**
     * 测试性能监控工作流
     */
    public function testPerformanceMonitoringWorkflow()
    {
        // 1. 执行一系列操作
        $operations = [
            'basic_conversion' => function () {
                for ($i = 0; $i < 100; $i++) {
                    $this->converter->convert('中华人民共和国');
                }
            },
            'custom_dict_operations' => function () {
                for ($i = 0; $i < 50; $i++) {
                    $this->converter->addCustomPinyin("测试{$i}", "test{$i}");
                }
                for ($i = 0; $i < 50; $i++) {
                    $this->converter->convert("测试{$i}");
                }
            },
            'url_slug_generation' => function () {
                for ($i = 0; $i < 100; $i++) {
                    $this->converter->getUrlSlug('这是一个测试标题用于生成URL Slug');
                }
            }
        ];

        // 2. 执行操作并监控性能
        foreach ($operations as $name => $operation) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();

            $operation();

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;

            // 验证性能指标
            $this->assertLessThan(
                2.0,
                $executionTime,
                "Operation '{$name}' should complete within 2 seconds"
            );
            $this->assertLessThan(
                5 * 1024 * 1024,
                $memoryUsage,
                "Operation '{$name}' should use less than 5MB memory"
            );
        }

        // 3. 生成性能报告
        $report = $this->converter->getPerformanceReport();

        $this->assertArrayHasKey('total_conversions', $report);
        $this->assertArrayHasKey('cache_efficiency', $report);
        $this->assertIsArray($report['cache_efficiency']);
        // Check relevant metrics in the cache_efficiency subarray
        if (isset($report['cache_efficiency']['total_entries'])) {
            $this->assertGreaterThanOrEqual(0, $report['cache_efficiency']['total_entries']);
        }
        if (isset($report['cache_efficiency']['recent_hit_rate'])) {
            $this->assertGreaterThanOrEqual(0, $report['cache_efficiency']['recent_hit_rate']);
        }

        // 验证统计数据
        $this->assertGreaterThan(0, $report['total_conversions']);
        $this->assertGreaterThan(0, $report['cache_efficiency']['total_entries']);
    }

    /**
     * 测试错误恢复工作流
     */
    public function testErrorRecoveryWorkflow()
    {
        // 1. 正常操作
        $result1 = $this->converter->convert('中国');
        $this->assertEquals('zhong guo', $result1);

        // 2. 尝试添加无效自定义拼音
        try {
            $this->converter->addCustomPinyin('', 'invalid');
            $this->fail('Should have thrown exception for empty character');
        } catch (\Exception $e) {
            // 预期的异常
        }

        // 3. 验证转换器仍然正常工作
        $result2 = $this->converter->convert('中国');
        $this->assertEquals('zhong guo', $result2);

        // 4. 添加有效的自定义拼音
        $this->converter->addCustomPinyin('恢复测试', 'hui fu ce shi999');
        $result3 = $this->converter->convert('恢复测试');
        $this->assertEquals('hui fu ce shi999', $result3);

        // 5. 删除自定义拼音
        $this->converter->removeCustomPinyin('恢复测试');
        $result4 = $this->converter->convert('恢复测试');
        $this->assertEquals('hui fu ce shi', $result4);

        // 6. 验证整体功能仍然正常
        $finalTest = '中华人民共和国';
        $finalResult = $this->converter->convert($finalTest);
        $this->assertEquals('zhong hua ren min gong he guo', $finalResult);
    }
}
