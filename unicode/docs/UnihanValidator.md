# UnihanValidator 类文档

## 概述

`UnihanValidator` 类负责验证Unihan数据和当前字典数据的完整性和正确性。它提供了全面的数据质量检查，确保拼音转换系统的稳定运行。

## 主要功能

### 1. 数据完整性验证
- 检查必需的文件是否存在
- 验证字典文件的可访问性
- 确保数据提取流程的完整性

### 2. 数据格式验证
- 检查字典数据的结构正确性
- 验证拼音数据的格式规范
- 确保字符编码的一致性

### 3. 覆盖度计算
- 计算当前字典对Unihan数据的覆盖比例
- 评估字典数据的完备性
- 提供数据质量量化指标

### 4. 错误检测与报告
- 识别数据质量问题
- 提供详细的验证报告
- 支持问题定位和修复

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

#### 数据验证
```php
public function validate(): array
```
执行全面的数据验证。

### 私有方法

#### 文件完整性检查
```php
private function checkFileIntegrity(): bool
```
检查必需的文件是否存在。

#### 数据格式检查
```php
private function checkDataFormat(): bool
```
验证字典数据的格式正确性。

#### 覆盖度计算
```php
private function calculateCoverage(): float
```
计算当前字典对Unihan数据的覆盖比例。

#### 当前字典加载
```php
private function loadCurrentDict(): array
```
加载当前字典数据用于验证。

## 使用示例

### 基本验证操作
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanValidator;

$config = [
    'output_dir' => __DIR__ . '/data/unihan',
    'dict_dir' => __DIR__ . '/data'
];

$validator = new UnihanValidator($config);
$result = $validator->validate();

print_r($result);
```

### 通过UnihanDataManager使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$result = $manager->validateDict();

print_r($result);
```

### 生成验证报告
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$report = $manager->generateReport();

echo $report;

// 保存报告到文件
file_put_contents('unihan_validation_report.md', $report);
```

### 自动化验证脚本
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanValidator;

function validateUnihanData() {
    $validator = new UnihanValidator();
    $result = $validator->validate();
    
    if (!$result['integrity']) {
        throw new Exception("文件完整性验证失败");
    }
    
    if (!$result['format']) {
        throw new Exception("数据格式验证失败");
    }
    
    if ($result['coverage'] < 0.8) {
        echo "警告: 字典覆盖度较低: " . round($result['coverage'] * 100, 2) . "%\n";
    }
    
    return $result;
}

$validation = validateUnihanData();
echo "数据验证通过!\n";
```

## 验证流程

### 1. 文件完整性检查
```
检查必需文件 → 验证文件可访问性 → 返回完整性状态
```

### 2. 数据格式验证
```
加载字典数据 → 验证数据结构 → 检查拼音格式 → 返回格式状态
```

### 3. 覆盖度计算
```
加载Unihan数据 → 加载当前字典 → 计算共同字符 → 返回覆盖比例
```

### 4. 结果汇总
```
汇总验证结果 → 生成详细报告 → 返回验证状态
```

## 验证标准详解

### 1. 文件完整性标准
必需的文件包括：
- `data/unihan/all_unihan_pinyin.php` - 完整Unihan字典
- `data/common_with_tone.php` - 常用字带声调字典
- `data/rare_with_tone.php` - 生僻字带声调字典

### 2. 数据格式标准
- **字典结构**: 必须是有效的PHP数组
- **字符编码**: 必须使用UTF-8编码
- **拼音格式**: 必须是有效的拼音字符串数组
- **数据一致性**: 相同字符在不同字典中的格式必须一致

### 3. 覆盖度计算标准
```php
覆盖度 = 共同字符数 / Unihan总字符数
```

- **优秀**: 覆盖度 ≥ 90%
- **良好**: 覆盖度 ≥ 80%
- **一般**: 覆盖度 ≥ 70%
- **需要改进**: 覆盖度 < 70%

## 验证结果格式

### 1. 完整验证结果
```php
[
    'integrity' => true,     // 文件完整性
    'format' => true,       // 数据格式正确性
    'coverage' => 0.85      // 字符覆盖度（0-1）
]
```

### 2. 详细验证报告
通过`UnihanDataManager::generateReport()`生成的报告包含：

```markdown
# Unihan数据验证报告

生成时间: 2025-11-11 18:16:00

## 验证结果摘要
- 文件完整性: ✓ 通过
- 数据格式正确性: ✓ 通过  
- 字符覆盖度: 85.0%

## 详细验证信息
- Unihan总字符数: 12345
- 当前字典字符数: 10000
- 共同字符数: 10500
- 覆盖比例: 85.0%

## 建议
- 当前字典覆盖度良好，可以满足大多数使用场景
- 建议定期更新Unihan数据以保持最新
- 考虑合并新增字符以提高覆盖度
```

## 错误处理

### 1. 文件完整性错误
- **必需文件不存在** - 需要先运行数据提取流程
- **文件权限问题** - 检查文件读写权限
- **路径配置错误** - 验证配置文件路径

### 2. 数据格式错误
- **PHP语法错误** - 检查字典文件的PHP语法
- **数组结构错误** - 验证字典数据的结构
- **编码问题** - 确保使用UTF-8编码

### 3. 覆盖度计算错误
- **数据加载失败** - 检查字典文件的可访问性
- **字符编码不一致** - 确保字符编码统一
- **计算逻辑错误** - 验证覆盖度计算算法

## 性能优化建议

### 1. 验证效率优化
- 缓存验证结果避免重复验证
- 分批处理大字典文件
- 使用内存映射提高文件读取速度

### 2. 内存使用优化
- 及时释放临时变量
- 使用生成器处理大量数据
- 优化数据加载策略

### 3. 磁盘I/O优化
- 减少不必要的文件读取
- 使用文件缓存机制
- 合理设置验证频率

## 应用场景

### 1. 系统启动验证
```php
// 在系统启动时验证数据完整性
$validator = new UnihanValidator();
$result = $validator->validate();

if (!$result['integrity'] || !$result['format']) {
    // 数据验证失败，采取相应措施
    Logger::error('Unihan数据验证失败', $result);
    // 触发数据修复流程或使用备用方案
}
```

### 2. 数据更新后验证
```php
// 在数据更新后验证新数据的正确性
$manager = new UnihanDataManager();
$manager->updateData(true); // 强制更新数据

$validation = $manager->validateDict();
if ($validation['integrity'] && $validation['format']) {
    echo "数据更新验证通过!\n";
} else {
    echo "数据更新验证失败，需要手动检查!\n";
}
```

### 3. 定期健康检查
```php
// 定期执行数据健康检查
function performHealthCheck() {
    $validator = new UnihanValidator();
    $result = $validator->validate();
    
    $healthScore = calculateHealthScore($result);
    
    if ($healthScore < 0.7) {
        sendAlert('Unihan数据健康度较低', $result);
    }
    
    return $healthScore;
}

function calculateHealthScore(array $validation): float {
    $score = 0;
    if ($validation['integrity']) $score += 0.4;
    if ($validation['format']) $score += 0.4;
    $score += $validation['coverage'] * 0.2;
    return $score;
}
```

## 扩展和自定义

### 1. 添加自定义验证规则
```php
class CustomUnihanValidator extends UnihanValidator
{
    public function validate(): array
    {
        $result = parent::validate();
        
        // 添加自定义验证规则
        $result['custom_validation'] = $this->customValidation();
        
        return $result;
    }
    
    private function customValidation(): bool
    {
        // 实现自定义验证逻辑
        // 例如：检查特定字符的拼音完整性
        return true;
    }
}
```

### 2. 增强覆盖度计算
```php
class EnhancedUnihanValidator extends UnihanValidator
{
    private function calculateCoverage(): float
    {
        $basicCoverage = parent::calculateCoverage();
        
        // 添加加权覆盖度计算
        $weightedCoverage = $this->calculateWeightedCoverage();
        
        return ($basicCoverage + $weightedCoverage) / 2;
    }
    
    private function calculateWeightedCoverage(): float
    {
        // 实现加权覆盖度计算
        // 例如：常用字符权重更高
        return 0.0;
    }
}
```

### 3. 添加详细错误报告
```php
class DetailedUnihanValidator extends UnihanValidator
{
    private $detailedErrors = [];
    
    public function validate(): array
    {
        $result = parent::validate();
        
        // 添加详细错误信息
        $result['detailed_errors'] = $this->detailedErrors;
        
        return $result;
    }
    
    private function checkFileIntegrity(): bool
    {
        $result = parent::checkFileIntegrity();
        
        if (!$result) {
            // 记录具体的缺失文件
            $this->detailedErrors[] = '缺失文件: ' . implode(', ', $this->getMissingFiles());
        }
        
        return $result;
    }
    
    private function getMissingFiles(): array
    {
        // 实现获取缺失文件列表的逻辑
        return [];
    }
}
```

## 相关类

- `UnihanDataManager` - 数据管理器，提供验证接口
- `UnihanExtractor` - 数据提取器，生成被验证的数据
- `UnihanComparator` - 数据对比器，与验证器协同工作
- `UnihanMerger` - 数据合并器，修复验证发现的问题

## 版本历史

### v1.0.0
- 初始版本，实现基本验证功能
- 支持文件完整性、数据格式和覆盖度验证
- 集成到Unihan数据处理流程

## 技术支持

如有验证问题或需要自定义功能，请参考项目文档或提交Issue。