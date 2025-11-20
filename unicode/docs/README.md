# Unihan数据处理系统文档索引

## 概述

本目录包含Unihan数据处理系统的完整文档，涵盖了数据下载、提取、验证、对比和合并等所有功能模块。

## 文档列表

### 核心类文档

| 文档名称 | 类名 | 主要功能 | 文件大小 |
|---------|------|----------|----------|
| [UnihanDataManager.md](UnihanDataManager.md) | `UnihanDataManager` | 统一管理Unihan数据处理流程 | 9.36 KB |
| [UnihanDownloader.md](UnihanDownloader.md) | `UnihanDownloader` | 从Unicode官网下载Unihan数据 | 6.11 KB |
| [UnihanExtractor.md](UnihanExtractor.md) | `UnihanExtractor` | 提取并分类Unihan拼音数据 | 9.39 KB |
| [UnihanMerger.md](UnihanMerger.md) | `UnihanMerger` | 合并Unihan数据到PinyinConverter字典 | 10.38 KB |
| [UnihanComparator.md](UnihanComparator.md) | `UnihanComparator` | 对比Unihan数据与当前字典差异 | 7.99 KB |
| [UnihanValidator.md](UnihanValidator.md) | `UnihanValidator` | 验证Unihan数据的完整性和正确性 | 9.80 KB |

### 其他文档

| 文档名称 | 描述 | 文件大小 |
|---------|------|----------|
| [Unihan数据提取工具.md](Unihan数据提取工具.md) | 早期版本的提取工具说明 | 4.07 KB |

## 快速开始

### 1. 数据更新流程
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

### 2. 完整数据处理
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 1. 更新数据
$manager->updateData();

// 2. 分类提取
$extractResult = $manager->extractWithClassification();

// 3. 数据对比
$comparison = $manager->compareWithCurrentDict();

// 4. 数据验证
$validation = $manager->validateDict();

// 5. 生成报告
$report = $manager->generateReport();

// 6. 合并数据
$mergeResult = $manager->mergeToPinyinConverter([
    'unihan_files' => ['displayable_unihan_pinyin.php'],
    'target_dict' => 'rare_with_tone.php'
]);
```

## 文档结构

每个文档都包含以下标准章节：

### 1. 概述
- 类的功能和用途介绍
- 主要特性说明

### 2. 类结构
- 属性定义和说明
- 公共方法和私有方法详解

### 3. 使用示例
- 基本使用示例
- 高级用法示例
- 配置参数说明

### 4. 技术细节
- 算法原理说明
- 数据处理流程
- 性能优化建议

### 5. 错误处理
- 常见错误场景
- 异常处理机制
- 调试技巧

### 6. 扩展和自定义
- 继承和重写示例
- 自定义功能实现
- 最佳实践建议

## 类关系图

```
UnihanDataManager (管理器)
    ├── UnihanDownloader (下载器)
    ├── UnihanExtractor (提取器)
    ├── UnihanComparator (对比器)
    ├── UnihanValidator (验证器)
    └── UnihanMerger (合并器)
```

## 数据处理流程

```
下载数据 → 提取拼音 → 分类字符 → 验证数据 → 对比差异 → 合并更新
    ↓          ↓          ↓          ↓          ↓          ↓
Downloader  Extractor  Classifier  Validator  Comparator  Merger
```

## 配置参数说明

所有类都使用统一的配置参数结构：

```php
$config = [
    'cache_days' => 30,           // 缓存天数
    'max_retries' => 3,          // 最大重试次数
    'timeout' => 30,            // 超时时间（秒）
    'zip_file' => '/path/to/Unihan.zip',      // Unihan文件路径
    'extract_dir' => '/path/to/temp',         // 临时解压目录
    'output_dir' => '/path/to/output',        // 输出目录
    'dict_dir' => '/path/to/dicts'            // 字典目录
];
```

## 常见问题

### 1. 如何自定义配置？
参考各文档中的"配置参数详解"章节。

### 2. 如何处理数据冲突？
参考[UnihanMerger.md](UnihanMerger.md)中的冲突处理机制。

### 3. 如何验证数据质量？
参考[UnihanValidator.md](UnihanValidator.md)中的验证标准。

### 4. 如何扩展功能？
参考各文档中的"扩展和自定义"章节。

## 版本信息

### 当前版本
- **系统版本**: v1.0.0
- **PHP要求**: 7.4+
- **依赖扩展**: mbstring, ZipArchive

### 更新历史
- **2024-01-15**: 创建完整文档体系
- **2024-01-10**: 统一类架构设计
- **2024-01-05**: 初始功能实现

## 技术支持

如有问题或建议，请：

1. 首先查阅相关类的详细文档
2. 检查常见问题部分
3. 查看代码示例和配置说明
4. 如仍有问题，提交Issue并提供详细描述

## 贡献指南

欢迎贡献文档改进：

1. 确保文档结构符合标准格式
2. 提供清晰的使用示例
3. 包含必要的技术细节
4. 验证所有代码示例的正确性
5. 保持文档的时效性和准确性

---

*最后更新: 2024-01-15*