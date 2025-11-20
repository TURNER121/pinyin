# UnihanMerger 类文档

## 概述

`UnihanMerger` 类负责将Unihan提取的拼音数据合并到PinyinConverter的字典文件中。它提供了智能的冲突检测和解决机制，确保数据合并的准确性和完整性。

## 主要功能

### 1. 智能数据合并
- 自动检测字符是否已存在
- 支持多文件批量合并
- 可配置的合并策略

### 2. 冲突检测与解决
- 拼音差异自动检测
- 交互式冲突解决
- 批量处理支持

### 3. 数据完整性保护
- 避免重复合并
- 保持数据格式一致性
- 错误处理和回滚机制

### 4. 灵活配置
- 可配置目标字典文件
- 支持自定义合并规则
- 多种处理模式选择

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

#### 数据合并
```php
public function mergeToPinyinConverter(array $config = []): array
```
合并Unihan数据到PinyinConverter字典。

#### 配置获取
```php
public function getConfig(): array
```
获取当前配置信息。

### 私有方法

#### 非自定义字典加载
```php
private function loadNonCustomDicts(): array
```
加载所有非自定义字典用于冲突检查。

#### 合并执行
```php
private function performMerge(array $sourceData, array &$targetDict, array $nonCustomDicts, array $config): array
```
执行具体的合并操作。

#### 字符存在检查
```php
private function charExistsInDicts(string $char, array $dicts): bool
```
检查字符是否存在于字典中。

#### 冲突检测
```php
private function checkConflict(string $char, array $unihanPinyin, array $dicts): ?array
```
检测拼音冲突。

#### 拼音数组标准化
```php
private function normalizePinyinArray(array $pinyinArray): array
```
标准化拼音数组用于比较。

#### 冲突处理
```php
private function handleConflicts(array $conflicts, array $config): void
```
处理检测到的冲突。

#### 批量冲突处理
```php
private function handleBulkConflicts(array $conflicts, int $currentIndex): void
```
批量处理冲突。

#### 拼音替换
```php
private function replacePinyinInDict(string $char, array $newPinyin, string $dictFile): void
```
替换字典中的拼音。

#### 字典文件保存
```php
private function saveDictFile(array $dict, string $filePath, string $name, int $count): void
```
保存字典文件。

## 使用示例

### 基本合并操作
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanMerger;

$config = [
    'output_dir' => __DIR__ . '/data/unihan',
    'dict_dir' => __DIR__ . '/data'
];

$merger = new UnihanMerger($config);

$mergeConfig = [
    'unihan_files' => ['displayable_unihan_pinyin.php'],
    'target_dict' => 'rare_with_tone.php',
    'check_existing' => true,
    'prompt_conflicts' => true,
    'auto_merge' => false
];

$result = $merger->mergeToPinyinConverter($mergeConfig);
print_r($result);
```

### 通过UnihanDataManager使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$result = $manager->mergeToPinyinConverter([
    'unihan_files' => ['displayable_unihan_pinyin.php'],
    'target_dict' => 'rare_with_tone.php',
    'prompt_conflicts' => true
]);

print_r($result);
```

### 自动合并模式
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanMerger;

$merger = new UnihanMerger($config);

$result = $merger->mergeToPinyinConverter([
    'unihan_files' => ['displayable_unihan_pinyin.php'],
    'target_dict' => 'rare_with_tone.php',
    'check_existing' => true,
    'prompt_conflicts' => false, // 禁用交互提示
    'auto_merge' => true         // 自动跳过冲突
]);

print_r($result);
```

### 多文件合并
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanMerger;

$merger = new UnihanMerger($config);

$result = $merger->mergeToPinyinConverter([
    'unihan_files' => [
        'displayable_unihan_pinyin.php',
        'non_displayable_unihan_pinyin.php'
    ],
    'target_dict' => 'rare_with_tone.php',
    'prompt_conflicts' => true
]);

print_r($result);
```

## 合并配置详解

### unihan_files
- **类型**: array
- **默认**: `['displayable_unihan_pinyin.php']`
- **说明**: 要合并的Unihan文件列表
- **可选值**: 
  - `displayable_unihan_pinyin.php` - 可显示字符字典
  - `non_displayable_unihan_pinyin.php` - 不可显示字符字典
  - `all_unihan_pinyin.php` - 完整字典

### target_dict
- **类型**: string
- **默认**: `'rare_with_tone.php'`
- **说明**: 目标字典文件名
- **可选值**: 
  - `common_with_tone.php` - 常用字带声调
  - `rare_with_tone.php` - 生僻字带声调
  - `self_learn_with_tone.php` - 自学习字典
  - `custom_with_tone.php` - 自定义字典

### check_existing
- **类型**: bool
- **默认**: true
- **说明**: 是否检查字符是否已存在

### prompt_conflicts
- **类型**: bool
- **默认**: true
- **说明**: 是否提示冲突并让用户选择

### auto_merge
- **类型**: bool
- **默认**: false
- **说明**: 是否自动合并（不提示）

## 合并流程

### 1. 数据准备阶段
```
加载目标字典 → 加载非自定义字典 → 准备冲突检查环境
```

### 2. 文件处理阶段
```
遍历Unihan文件 → 加载源数据 → 执行合并操作
```

### 3. 冲突检测阶段
```
检查字符存在 → 检测拼音差异 → 记录冲突信息
```

### 4. 冲突解决阶段
```
交互式用户选择 → 批量处理选项 → 执行拼音替换
```

### 5. 结果保存阶段
```
保存目标字典 → 生成合并报告 → 返回处理结果
```

## 冲突检测机制

### 1. 字符存在性检查
检查字符是否已存在于以下字典中：
- `common_with_tone.php`
- `common_no_tone.php`
- `rare_with_tone.php`
- `rare_no_tone.php`
- `self_learn_with_tone.php`
- `self_learn_no_tone.php`

### 2. 拼音差异检测
比较Unihan拼音与现有拼音的差异：
- **标准化处理**: 去除空格，排序数组
- **精确比较**: 数组元素顺序无关的比较
- **差异记录**: 记录具体的拼音差异

### 3. 冲突信息格式
```php
[
    'char' => '字',                    // 冲突字符
    'dict_file' => 'common_with_tone.php', // 冲突字典文件
    'unihan_pinyin' => ['pinyin1', 'pinyin2'], // Unihan拼音
    'existing_pinyin' => ['pinyin1']          // 现有拼音
]
```

## 交互式冲突解决

### 1. 冲突显示格式
```
冲突 #1:
字符: 说
字典文件: common_with_tone.php
现有拼音: shuō, shuì
Unihan拼音: shuō, shuì, yuè
请选择处理方式:
1. 使用Unihan拼音替换现有拼音
2. 保留现有拼音
3. 跳过此冲突
4. 对所有冲突使用相同处理方式
```

### 2. 处理选项说明

#### 选项1: 替换拼音
- 使用Unihan拼音完全替换现有拼音
- 适用于Unihan数据更准确的情况

#### 选项2: 保留现有拼音
- 保持现有拼音不变
- 适用于自定义拼音优先级更高的情况

#### 选项3: 跳过冲突
- 暂时跳过此冲突
- 后续可以单独处理

#### 选项4: 批量处理
- 对所有剩余冲突应用相同处理方式
- 提高处理效率

## 返回结果格式

### 成功合并结果
```php
[
    'merged_count' => 100,       // 成功合并的字符数
    'skipped_count' => 50,      // 跳过的字符数（已存在）
    'conflicts' => [            // 冲突列表
        [
            'char' => '说',
            'dict_file' => 'common_with_tone.php',
            'unihan_pinyin' => ['shuō', 'shuì', 'yuè'],
            'existing_pinyin' => ['shuō', 'shuì']
        ]
    ],
    'errors' => []              // 错误信息列表
]
```

### 错误结果示例
```php
[
    'merged_count' => 0,
    'skipped_count' => 0,
    'conflicts' => [],
    'errors' => [
        "Unihan文件不存在: /path/to/displayable_unihan_pinyin.php",
        "目标字典文件不存在: /path/to/rare_with_tone.php"
    ]
]
```

## 错误处理

### 1. 文件相关错误
- **Unihan文件不存在** - 检查文件路径和提取流程
- **目标字典不存在** - 确保目标字典文件存在
- **文件格式错误** - 验证字典文件格式

### 2. 数据相关错误
- **字符编码问题** - 确保使用UTF-8编码
- **拼音格式错误** - 验证拼音数据格式
- **内存不足** - 调整PHP内存限制

### 3. 权限相关错误
- **文件写入权限** - 检查目录写入权限
- **文件锁定** - 避免并发写入冲突

## 性能优化建议

### 1. 内存使用优化
- 分批处理大字典文件
- 及时释放临时变量
- 使用生成器处理大量数据

### 2. 磁盘I/O优化
- 减少不必要的文件读写
- 使用缓存提高读取速度
- 合理设置临时文件位置

### 3. 冲突处理优化
- 使用批量处理模式提高效率
- 预先过滤明显无冲突的字符
- 实现智能冲突预测

## 安全考虑

### 1. 数据完整性
- 合并前备份原始字典
- 实现操作回滚机制
- 验证合并结果的正确性

### 2. 用户输入验证
- 验证配置参数的有效性
- 防止路径遍历攻击
- 过滤恶意字符输入

### 3. 错误恢复
- 提供详细的错误信息
- 实现优雅的错误处理
- 支持中断恢复功能

## 扩展和自定义

### 1. 自定义合并策略
```php
class CustomUnihanMerger extends UnihanMerger
{
    protected function performMerge(array $sourceData, array &$targetDict, array $nonCustomDicts, array $config): array
    {
        // 实现自定义合并逻辑
        // ...
    }
}
```

### 2. 添加新的冲突解决选项
```php
class CustomUnihanMerger extends UnihanMerger
{
    protected function handleConflicts(array $conflicts, array $config): void
    {
        // 添加新的冲突解决选项
        // ...
    }
}
```

### 3. 自定义字典加载逻辑
```php
class CustomUnihanMerger extends UnihanMerger
{
    protected function loadNonCustomDicts(): array
    {
        // 实现自定义字典加载逻辑
        // ...
    }
}
```

## 相关类

- `UnihanDataManager` - 数据管理器
- `UnihanExtractor` - 数据提取器
- `UnihanComparator` - 数据对比器
- `UnihanValidator` - 数据验证器

## 版本历史

### v1.0.0
- 初始版本，实现基本合并功能
- 支持冲突检测和交互式解决
- 集成到Unihan数据处理流程

## 技术支持

如有合并问题或需要自定义功能，请参考项目文档或提交Issue。