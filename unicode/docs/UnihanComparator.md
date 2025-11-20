# UnihanComparator 类文档

## 概述

`UnihanComparator` 类负责对比Unihan提取的拼音数据与当前PinyinConverter字典数据之间的差异。它提供了详细的对比分析，帮助用户了解数据更新情况和潜在问题。

## 主要功能

### 1. 数据差异分析
- 对比Unihan数据与当前字典的字符覆盖情况
- 识别新增字符和缺失字符
- 检测拼音读音差异

### 2. 多维度对比
- 字符数量统计对比
- 拼音读音精确比较
- 差异类型分类

### 3. 结果格式化
- 结构化的对比结果
- 易于理解的差异信息
- 支持进一步处理和分析

### 4. 错误处理
- 文件不存在检查
- 数据格式验证
- 异常情况处理

## 类结构

### 属性

#### 配置参数
```php
private $config
```
包含以下配置项：
- `output_dir`: Unihan输出目录
- `dict_dir`: 字典文件目录

### 公共方法

#### 构造函数
```php
public function __construct(array $config)
```
初始化配置参数。

#### 数据对比
```php
public function compare(): array
```
执行Unihan数据与当前字典的对比分析。

### 私有方法

#### Unihan字典加载
```php
private function loadUnihanDict(): array
```
加载Unihan字典文件。

#### 当前字典加载
```php
private function loadCurrentDict(): array
```
加载当前所有字典文件。

#### 拼音对比
```php
private function comparePinyin(array $unihanDict, array $currentDict): array
```
对比拼音数据的差异。

## 使用示例

### 基本对比操作
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanComparator;

$config = [
    'output_dir' => __DIR__ . '/data/unihan',
    'dict_dir' => __DIR__ . '/data'
];

$comparator = new UnihanComparator($config);
$result = $comparator->compare();

print_r($result);
```

### 通过UnihanDataManager使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$result = $manager->compareWithCurrentDict();

print_r($result);
```

### 生成对比报告
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$report = $manager->generateReport();

echo $report;

// 保存报告到文件
file_put_contents('unihan_comparison_report.md', $report);
```

## 对比流程

### 1. 数据加载阶段
```
加载Unihan字典 → 加载当前字典 → 准备对比环境
```

### 2. 差异分析阶段
```
字符数量对比 → 字符存在性检查 → 拼音差异检测
```

### 3. 结果生成阶段
```
格式化对比结果 → 分类差异信息 → 返回结构化数据
```

## 对比结果详解

### 1. 字符数量统计
- `unihan_total`: Unihan字典中的总字符数
- `current_total`: 当前字典中的总字符数

### 2. 字符存在性差异
- `new_chars`: Unihan中存在但当前字典中不存在的字符
- `missing_chars`: 当前字典中存在但Unihan中不存在的字符

### 3. 拼音读音差异
- `different_pinyin`: 字符存在但拼音读音不同的情况

### 4. 完整结果格式
```php
[
    'unihan_total' => 12345,             // Unihan总字符数
    'current_total' => 10000,            // 当前字典字符数
    'new_chars' => [                     // 新增字符
        '字1' => ['pinyin1', 'pinyin2'],
        '字2' => ['pinyin3']
    ],
    'missing_chars' => [                 // 缺失字符
        '字3' => ['pinyin4'],
        '字4' => ['pinyin5', 'pinyin6']
    ],
    'different_pinyin' => [               // 拼音差异
        '字5' => [
            'unihan' => ['pinyin7', 'pinyin8'],
            'current' => ['pinyin7']
        ]
    ]
]
```

## 对比算法详解

### 1. 字符存在性检查
使用PHP的数组键比较功能：
```php
$new_chars = array_diff_key($unihanDict, $currentDict);
$missing_chars = array_diff_key($currentDict, $unihanDict);
```

### 2. 拼音差异检测
精确比较拼音数组的差异：
```php
// 比较两个拼音数组的差异
$diff1 = array_diff($unihanPinyin, $currentPinyin);
$diff2 = array_diff($currentPinyin, $unihanPinyin);

// 如果有差异，记录详细信息
if (count($diff1) > 0 || count($diff2) > 0) {
    $differences[$char] = [
        'unihan' => $unihanPinyin,
        'current' => $currentPinyin
    ];
}
```

### 3. 字典文件加载
加载以下字典文件进行对比：
- `common_with_tone.php` - 常用字带声调
- `rare_with_tone.php` - 生僻字带声调
- `self_learn_with_tone.php` - 自学习字典
- `custom_with_tone.php` - 自定义字典

## 错误处理

### 1. 文件不存在错误
- **Unihan字典文件不存在** - 需要先运行数据提取流程
- **当前字典文件不存在** - 检查字典文件路径和名称

### 2. 数据格式错误
- **字典文件格式不正确** - 验证PHP数组格式
- **字符编码问题** - 确保使用UTF-8编码

### 3. 内存不足错误
- **大字典文件处理** - 调整PHP内存限制
- **分批处理建议** - 对于超大字典可考虑分批处理

## 性能优化建议

### 1. 内存使用优化
- 及时释放临时变量
- 使用生成器处理大量数据
- 分批加载大字典文件

### 2. 处理速度优化
- 使用数组键比较提高效率
- 避免不必要的循环嵌套
- 缓存已加载的字典数据

### 3. 磁盘I/O优化
- 减少不必要的文件读取
- 使用文件缓存机制
- 合理设置文件路径

## 应用场景

### 1. 数据更新检查
```php
// 检查是否有新的Unihan数据需要合并
$comparison = $comparator->compare();
if (count($comparison['new_chars']) > 0) {
    echo "发现 " . count($comparison['new_chars']) . " 个新字符需要合并\n";
}
```

### 2. 数据质量评估
```php
// 评估当前字典的覆盖度
$coverage = $comparison['current_total'] / $comparison['unihan_total'];
echo "当前字典覆盖度: " . round($coverage * 100, 2) . "%\n";
```

### 3. 冲突检测准备
```php
// 为合并操作准备冲突检测
$conflicts = $comparison['different_pinyin'];
if (count($conflicts) > 0) {
    echo "发现 " . count($conflicts) . " 个拼音冲突需要处理\n";
}
```

## 扩展和自定义

### 1. 自定义对比逻辑
```php
class CustomUnihanComparator extends UnihanComparator
{
    public function compare(): array
    {
        $result = parent::compare();
        
        // 添加自定义对比逻辑
        $result['custom_analysis'] = $this->customAnalysis($result);
        
        return $result;
    }
    
    private function customAnalysis(array $comparison): array
    {
        // 实现自定义分析逻辑
        return [];
    }
}
```

### 2. 添加新的对比维度
```php
class EnhancedUnihanComparator extends UnihanComparator
{
    private function comparePinyin(array $unihanDict, array $currentDict): array
    {
        $differences = parent::comparePinyin($unihanDict, $currentDict);
        
        // 添加拼音频率对比
        $frequencyDifferences = $this->comparePinyinFrequency($unihanDict, $currentDict);
        
        return array_merge($differences, $frequencyDifferences);
    }
    
    private function comparePinyinFrequency(array $unihanDict, array $currentDict): array
    {
        // 实现拼音频率对比逻辑
        return [];
    }
}
```

### 3. 自定义字典加载
```php
class CustomUnihanComparator extends UnihanComparator
{
    private function loadCurrentDict(): array
    {
        // 实现自定义字典加载逻辑
        $customDicts = [
            // 添加自定义字典文件
        ];
        
        return array_merge(parent::loadCurrentDict(), $customDicts);
    }
}
```

## 相关类

- `UnihanDataManager` - 数据管理器，提供对比接口
- `UnihanExtractor` - 数据提取器，生成Unihan字典
- `UnihanMerger` - 数据合并器，处理对比发现的差异
- `UnihanValidator` - 数据验证器，验证数据完整性

## 版本历史

### v1.0.0
- 初始版本，实现基本对比功能
- 支持字符存在性和拼音差异检测
- 集成到Unihan数据处理流程

## 技术支持

如有对比问题或需要自定义功能，请参考项目文档或提交Issue。