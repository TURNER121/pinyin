# Unihan目录中文件的详细介绍这些文件的作用、内容和用法：


最新官方pinyin 字典元素数据
https://unicode.org/Public/UCD/latest/ucd/Unihan.zip

https://unicode.org/Public/UCD/latest/ucd/UCD.zip

github自动任务工作流 
.github/workflows/update-unihan.yml


~~~sh
# 更新数据库
php unicode/extract_unihan.php --update --force

# 提取数据
php unicode/extract_unihan.php --extract
~~~


Unihan数据管理工具,包含以下核心类：

## 主要类结构

### 1. **UnihanDataManager** (主管理器)
- 统一管理Unihan数据的获取、更新、验证和对比
- 提供简洁的API接口

### 2. **UnihanDownloader** (下载器)
- 负责智能下载和缓存管理
- 支持重试机制和超时控制
- 自动检查文件过期状态

### 3. **UnihanExtractor** (提取器)
- 从Unihan.zip中提取拼音数据
- 按CJK区块分类保存数据
- 生成完整的拼音字典

### 4. **UnihanComparator** (对比器)
- 对比Unihan数据与当前字典数据
- 识别新增字符和拼音差异

### 5. **UnihanValidator** (验证器)
- 验证字典数据的完整性和格式
- 计算字符覆盖度

## 主要功能

### 1. **智能数据更新**
```php
$manager = new UnihanDataManager();
$updated = $manager->updateData($force); // 自动检查缓存，过期则下载
```

### 2. **数据对比分析**
```php
$comparison = $manager->compareWithCurrentDict();
// 返回新增字符、缺失字符、拼音差异等
```

### 3. **完整性验证**
```php
$validation = $manager->validateDict();
// 检查文件完整性、数据格式、覆盖度
```

### 4. **报告生成**
```php
$report = $manager->generateReport();
// 生成详细的对比和验证报告
```

## 使用示例

### 基本使用
```php
<?php
require_once 'UnihanDataManager.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 更新数据（智能缓存）
$updated = $manager->updateData();

// 生成对比报告
$report = $manager->generateReport();
echo $report;
```

### 强制更新
```php
// 强制重新下载数据
$manager->updateData(true);
```

### 详细分析
```php
// 获取详细对比结果
$comparison = $manager->compareWithCurrentDict();
print_r($comparison);

// 验证数据完整性
$validation = $manager->validateDict();
print_r($validation);
```

## 优势特点

1. **模块化设计**：每个类职责单一，易于维护和扩展
2. **智能缓存**：自动管理数据更新，避免不必要的网络请求
3. **错误处理**：完善的异常处理和重试机制
4. **详细报告**：提供全面的数据对比和验证信息
5. **配置灵活**：支持自定义缓存时间、重试次数等参数

提供了更强大的数据管理功能，特别适合用于持续维护和验证拼音字典数据。



## 文件概览

`unicode/Unihan` 目录包含 8 个文件，都是 Unicode 联盟提供的汉字数据库文件（Unihan Database），版本为 Unicode 17.0.0（2025年7月24日）。

## 各文件详细说明

### 1. **Unihan_Readings.txt** (8.43 MB)
**作用**: 汉字读音数据
**包含字段**:
- `kMandarin`: 普通话拼音
- `kCantonese`: 粤语拼音
- `kJapanese`: 日语读音
- `kKorean`: 韩语读音
- `kVietnamese`: 越南语读音
- `kHanyuPinyin`: 汉语大字典拼音
- `kDefinition`: 英文定义
- `kFanqie`: 反切注音

**使用场景**: 汉字转拼音、多语言读音查询

### 2. **Unihan_DictionaryIndices.txt** (10.78 MB)
**作用**: 字典索引数据
**包含字段**:
- `kHanYu`: 汉语大字典索引
- `kKangXi`: 康熙字典索引
- `kMorohashi`: 诸桥辙次大汉和辞典索引
- `kNelson`: 纳尔逊日汉字典索引

**使用场景**: 字典查询、汉字研究

### 3. **Unihan_DictionaryLikeData.txt** (3.88 MB)
**作用**: 字典类数据
**包含字段**:
- `kCangjie`: 仓颉输入法编码
- `kFourCornerCode`: 四角号码
- `kGradeLevel`: 汉字教育等级
- `kPhonetic`: 语音编码

**使用场景**: 输入法开发、汉字排序

### 4. **Unihan_IRGSources.txt** (12.73 MB)
**作用**: 国际汉字认同表（IRG）数据
**包含字段**:
- `kIRG_GSource`: 中国大陆源
- `kIRG_JSource`: 日本源
- `kIRG_KSource`: 韩国源
- `kIRG_TSource`: 台湾源
- `kTotalStrokes`: 总笔画数
- `kRSUnicode`: Unicode 部首笔画

**使用场景**: 汉字标准化、跨地区汉字映射

### 5. **Unihan_OtherMappings.txt** (3.47 MB)
**作用**: 其他编码映射
**包含字段**:
- `kBigFive`: 大五码
- `kGB0`-`kGB8`: 国标码
- `kJIS0213`: JIS X 0213编码
- `kTGH`: 通用规范汉字表

**使用场景**: 编码转换、字符集兼容

### 6. **Unihan_RadicalStrokeCounts.txt** (586.8 KB)
**作用**: 部首笔画计数
**包含字段**:
- `kRSAdobe_Japan1_6`: Adobe-Japan1-6 部首笔画

**使用场景**: 汉字检索、字典排序

### 7. **Unihan_Variants.txt** (659.34 KB)
**作用**: 汉字变体关系
**包含字段**:
- `kSimplifiedVariant`: 简化字变体
- `kTraditionalVariant`: 繁体字变体
- `kSemanticVariant`: 语义变体
- `kSpoofingVariant`: 混淆变体

**使用场景**: 简繁转换、汉字规范化

### 8. **Unihan_NumericValues.txt** (4.65 KB)
**作用**: 汉字数字值
**包含字段**:
- `kPrimaryNumeric`: 主要数值
- `kAccountingNumeric`: 会计数值
- `kOtherNumeric`: 其他数值

**使用场景**: 数字转换、财务应用

## 数据格式说明

所有文件都采用相同的格式：
```
U+3400\tkFieldName\tvalue
```
- `U+3400`: Unicode 码点
- `kFieldName`: 字段名称
- `value`: 字段值

## 使用建议

对于你的拼音项目，**Unihan_Readings.txt** 是最重要的文件，因为它包含：
- 标准普通话拼音 (`kMandarin`)
- 多种权威拼音数据源
- 英文定义帮助理解汉字含义

你可以使用这些数据来：
1. 构建高质量的汉字-拼音映射表
2. 处理多音字（文件包含多个读音）
3. 验证拼音准确性
4. 扩展多语言支持

这些数据是 Unicode 联盟维护的权威汉字数据库，非常适合用于拼音转换库的开发。
