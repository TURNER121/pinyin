# UnihanDataManager 类文档

## 概述

`UnihanDataManager` 类是Unihan数据处理系统的核心管理器，负责协调整个Unihan数据处理流程，包括数据下载、提取、验证、对比和合并等操作。

## 主要功能

### 1. 数据流程管理
- 统一管理Unihan数据的完整处理流程
- 协调各个组件（下载器、提取器、验证器等）的工作
- 提供简化的API接口

### 2. 数据更新管理
- 智能检查数据是否需要更新
- 支持强制更新模式
- 提供更新状态反馈

### 3. 数据质量保证
- 数据完整性验证
- 数据格式检查
- 字符覆盖度计算

### 4. 报告生成
- 自动生成数据对比报告
- 提供详细的统计信息
- 支持自定义报告格式

## 类结构

### 常量定义

```php
const REMOTE_URL = 'https://unicode.org/Public/UCD/latest/ucd/Unihan.zip';
const DEFAULT_CACHE_DAYS = 30;
const MAX_RETRIES = 3;
const DOWNLOAD_TIMEOUT = 30;
```

### 属性

#### 配置参数
```php
private $config
```
包含以下配置项：
- `cache_days`: 缓存天数（默认30天）
- `max_retries`: 最大重试次数（默认3次）
- `timeout`: 下载超时时间（默认30秒）
- `zip_file`: Unihan.zip文件路径
- `extract_dir`: 临时解压目录
- `output_dir`: 输出目录
- `dict_dir`: 字典文件目录

### 公共方法

#### 构造函数
```php
public function __construct(array $config = [])
```
初始化配置参数，支持默认配置。

#### 数据更新
```php
public function updateData(bool $force = false): bool
```
检查并更新Unihan数据。

#### 分类提取
```php
public function extractWithClassification(): array
```
使用提取器进行分类提取。

#### 数据对比
```php
public function compareWithCurrentDict(): array
```
对比Unihan数据与当前字典数据。

#### 数据验证
```php
public function validateDict(): array
```
验证字典数据的完整性。

#### 报告生成
```php
public function generateReport(): string
```
生成字典差异报告。

#### 合并器创建
```php
public function createMerger(): UnihanMerger
```
创建UnihanMerger实例。

#### 数据合并
```php
public function mergeToPinyinConverter(array $config = []): array
```
合并Unihan数据到PinyinConverter字典。

## 使用示例

### 基本数据更新
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 检查并更新数据
if ($manager->updateData()) {
    echo "数据已更新\n";
} else {
    echo "使用缓存数据\n";
}
```

### 完整数据处理流程
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 1. 更新数据
$manager->updateData();

// 2. 分类提取
$extractResult = $manager->extractWithClassification();
print_r($extractResult);

// 3. 数据对比
$comparison = $manager->compareWithCurrentDict();
print_r($comparison);

// 4. 数据验证
$validation = $manager->validateDict();
print_r($validation);

// 5. 生成报告
$report = $manager->generateReport();
echo $report;

// 6. 合并数据
$mergeResult = $manager->mergeToPinyinConverter([
    'unihan_files' => ['displayable_unihan_pinyin.php'],
    'target_dict' => 'rare_with_tone.php',
    'prompt_conflicts' => true
]);
print_r($mergeResult);
```

### 自定义配置
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$config = [
    'cache_days' => 7,           // 每周更新
    'max_retries' => 5,          // 增加重试次数
    'timeout' => 60,            // 延长超时时间
    'zip_file' => '/custom/path/Unihan.zip',
    'extract_dir' => '/custom/temp/',
    'output_dir' => '/custom/output/',
    'dict_dir' => '/custom/dicts/'
];

$manager = new UnihanDataManager($config);
$manager->updateData(true); // 强制更新
```

## 数据处理流程

### 1. 数据更新流程
```
检查本地缓存 → 需要更新？ → 下载数据 → 提取数据 → 返回更新状态
    ↓              ↓
使用缓存数据     强制更新模式
```

### 2. 分类提取流程
```
确保数据存在 → 创建提取器 → 分类提取 → 返回统计信息
```

### 3. 数据对比流程
```
加载Unihan数据 → 加载当前字典 → 对比差异 → 返回对比结果
```

### 4. 数据验证流程
```
文件完整性检查 → 数据格式验证 → 覆盖度计算 → 返回验证结果
```

### 5. 报告生成流程
```
获取对比结果 → 获取验证结果 → 格式化报告 → 返回报告内容
```

## 配置参数详解

### cache_days
- **类型**: int
- **默认**: 30
- **说明**: 数据缓存有效期（天数）
- **建议**: 生产环境30天，开发环境7天

### max_retries
- **类型**: int
- **默认**: 3
- **说明**: 下载失败时的最大重试次数
- **建议**: 网络不稳定环境可增加到5次

### timeout
- **类型**: int
- **默认**: 30
- **说明**: 下载超时时间（秒）
- **建议**: 慢速网络可增加到60秒

### zip_file
- **类型**: string
- **默认**: `__DIR__ . '/Unihan.zip'`
- **说明**: Unihan.zip文件的保存路径

### extract_dir
- **类型**: string
- **默认**: `__DIR__ . '/temp_unihan'`
- **说明**: 临时解压目录

### output_dir
- **类型**: string
- **默认**: `dirname(__DIR__) . '/data/unihan'`
- **说明**: 输出目录（提取后的字典文件）

### dict_dir
- **类型**: string
- **默认**: `dirname(__DIR__) . '/data'`
- **说明**: 字典文件目录

## 返回结果格式

### 数据更新结果
```php
bool // true表示进行了更新，false表示使用缓存
```

### 分类提取结果
```php
[
    'total_chars' => 12345,              // 总字符数
    'displayable_chars' => 10000,        // 可显示字符数
    'non_displayable_chars' => 2345,     // 不可显示字符数
    'polyphone_chars' => 8486,          // 多音字数量
    'displayable_percentage' => 81.0,    // 可显示字符百分比
    'polyphone_percentage' => 68.7,      // 多音字百分比
    'output_dir' => '/path/to/output'    // 输出目录
]
```

### 数据对比结果
```php
[
    'unihan_total' => 12345,             // Unihan总字符数
    'current_total' => 10000,            // 当前字典字符数
    'new_chars' => ['字1' => [...], ...], // 新增字符
    'missing_chars' => ['字2' => [...], ...], // 缺失字符
    'different_pinyin' => [              // 拼音差异
        '字3' => [
            'unihan' => ['pinyin1', 'pinyin2'],
            'current' => ['pinyin1']
        ]
    ]
]
```

### 数据验证结果
```php
[
    'integrity' => true,     // 文件完整性
    'format' => true,       // 数据格式正确性
    'coverage' => 0.85      // 字符覆盖度（0-1）
]
```

### 数据合并结果
```php
[
    'merged_count' => 100,   // 合并的字符数
    'skipped_count' => 50,   // 跳过的字符数
    'conflicts' => [         // 冲突列表
        [
            'char' => '字',
            'dict_file' => 'common_with_tone.php',
            'unihan_pinyin' => ['pinyin1', 'pinyin2'],
            'existing_pinyin' => ['pinyin1']
        ]
    ],
    'errors' => []           // 错误信息
]
```

## 错误处理

### 异常类型
- `PinyinException::ERROR_DICT_LOAD_FAIL` - 字典加载失败
- `PinyinException::ERROR_FILE_NOT_FOUND` - 文件不存在

### 常见错误场景
1. **网络连接失败** - 下载器重试机制处理
2. **文件权限问题** - 检查目录写入权限
3. **磁盘空间不足** - 确保有足够存储空间
4. **数据格式错误** - 验证器会检测并报告

## 性能优化建议

### 1. 缓存策略优化
- 根据更新频率调整缓存天数
- 开发环境可缩短缓存时间
- 生产环境可延长缓存时间

### 2. 内存使用优化
- 大文件处理时调整PHP内存限制
- 及时释放不再使用的变量
- 使用生成器处理大量数据

### 3. 磁盘I/O优化
- 使用SSD存储提高读写速度
- 合理设置临时目录位置
- 定期清理临时文件

## 扩展和自定义

### 添加自定义验证规则
```php
class CustomUnihanDataManager extends UnihanDataManager
{
    public function validateDict(): array
    {
        $result = parent::validateDict();
        
        // 添加自定义验证规则
        $result['custom_validation'] = $this->customValidation();
        
        return $result;
    }
    
    private function customValidation(): bool
    {
        // 实现自定义验证逻辑
        return true;
    }
}
```

### 自定义报告格式
```php
class CustomUnihanDataManager extends UnihanDataManager
{
    public function generateReport(): string
    {
        $comparison = $this->compareWithCurrentDict();
        $validation = $this->validateDict();
        
        // 自定义报告格式
        $report = $this->formatCustomReport($comparison, $validation);
        
        return $report;
    }
    
    private function formatCustomReport(array $comparison, array $validation): string
    {
        // 实现自定义报告格式
        return "自定义报告内容";
    }
}
```

## 相关类

- `UnihanDownloader` - 数据下载器
- `UnihanExtractor` - 数据提取器
- `UnihanComparator` - 数据对比器
- `UnihanValidator` - 数据验证器
- `UnihanMerger` - 数据合并器

## 版本历史

### v1.0.0
- 初始版本，实现核心管理功能
- 集成所有Unihan处理组件
- 提供完整的API接口

## 技术支持

如有使用问题或需要自定义功能，请参考项目文档或提交Issue。