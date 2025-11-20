# UnihanExtractor 类文档

## 概述

`UnihanExtractor` 类是一个功能强大的Unihan数据库拼音提取工具，专门用于从Unicode的Unihan数据库提取汉字拼音信息，并进行智能分类和处理。

## 主要功能

### 1. 拼音数据提取
- 从Unihan_Readings.txt文件中提取拼音信息
- 支持多种拼音字段格式解析
- 智能处理多音字情况

### 2. 字符智能分类
- **可正常显示字符**：CJK基本汉字、扩展A区、兼容汉字等
- **无法正常显示字符**：CJK扩展B-G区、生僻字、异体字等

### 3. 多音字处理
- 自动识别多音字（拼音数量大于1的字符）
- 多音字字典分类保存
- 保持多音字信息的完整性

### 4. 数据组织
- 按CJK区块分类保存数据
- 生成完整的拼音字典文件
- 提供详细的统计信息

## 类结构

### 属性

#### 配置参数
```php
private $config
```
包含以下配置项：
- `zip_file`: Unihan.zip文件路径
- `extract_dir`: 临时解压目录
- `output_dir`: 输出目录
- `cache_days`: 缓存天数
- `max_retries`: 最大重试次数
- `timeout`: 下载超时时间

#### Unicode范围定义
```php
private $displayableRanges = [
    [0x4E00, 0x9FFF],   // CJK基本汉字
    [0x3400, 0x4DBF],   // CJK扩展A
    [0xF900, 0xFAFF],   // CJK兼容汉字
    [0x2F00, 0x2FDF],   // 康熙部首
    [0x2E80, 0x2EFF],   // CJK部首补充
    [0x31C0, 0x31EF],   // CJK笔画
    [0x2FF0, 0x2FFF],   // 汉字结构描述字符
    [0x3000, 0x303F],   // CJK符号和标点
    [0x3200, 0x32FF],   // 带圈字母数字
    [0x3300, 0x33FF],   // CJK兼容
    [0xFE30, 0xFE4F],   // CJK兼容形式
    [0x1F200, 0x1F2FF], // 带圈补充符号
];

private $nonDisplayableRanges = [
    [0x20000, 0x2A6DF], // CJK扩展B
    [0x2A700, 0x2B73F], // CJK扩展C
    [0x2B740, 0x2B81F], // CJK扩展D
    [0x2B820, 0x2CEAF], // CJK扩展E
    [0x2CEB0, 0x2EBEF], // CJK扩展F
    [0x30000, 0x3134F], // CJK扩展G
    [0x31350, 0x323AF], // CJK扩展H
    [0x2EBF0, 0x2EE5F], // CJK扩展I
];
```

### 公共方法

#### 构造函数
```php
public function __construct(array $config)
```
初始化配置参数。

#### 提取数据
```php
public function extract(): void
```
提取Unihan数据（调用extractWithClassification的别名）。

#### 分类提取
```php
public function extractWithClassification(): array
```
提取Unihan数据并进行分类，返回详细的统计信息。

### 私有方法

#### 数据提取和分类
```php
private function extractAndClassifyPinyinData(string $extractDir, string $outputDir): array
```
核心的数据提取和分类逻辑。

#### 文件解析
```php
private function parseReadingsFile(string $filePath): array
```
解析Unihan_Readings.txt文件。

#### 拼音处理
```php
private function processPinyinData(array $charData): array
```
处理拼音数据并生成字典。

#### 字符分类
```php
private function classifyCharacters(array $pinyinDict): array
```
将字符分类为可显示和不可显示两类。

#### 拼音提取
```php
private function extractPinyins(array $data): array
```
从字符数据中提取拼音信息。

#### 字段解析
```php
private function parsePinyinField(string $field, string $value): array
```
解析特定拼音字段的格式。

#### 字典保存
```php
private function saveDictFile(array $dict, string $filePath, string $name, int $count): void
```
保存字典文件到指定路径。

#### 多音字处理
```php
private function savePolyphoneDict(array $pinyinDict, string $outputDir): void
```
处理并保存多音字字典。

## 使用示例

### 基本使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanExtractor;

$config = [
    'zip_file' => __DIR__ . '/Unihan.zip',
    'extract_dir' => __DIR__ . '/temp_unihan',
    'output_dir' => __DIR__ . '/data/unihan'
];

$extractor = new UnihanExtractor($config);
$result = $extractor->extractWithClassification();

print_r($result);
```

### 通过UnihanDataManager使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();
$result = $manager->extractWithClassification();

print_r($result);
```

## 输出文件

### 主要字典文件

#### 分类字典
- `displayable_unihan_pinyin.php` - 可正常显示的汉字拼音字典
- `non_displayable_unihan_pinyin.php` - 无法正常显示的汉字拼音字典

#### 多音字字典
- `displayable_polyphone_unihan_pinyin.php` - 可正常显示的多音字字典
- `non_displayable_polyphone_unihan_pinyin.php` - 无法正常显示的多音字字典
- `polyphone_unihan_pinyin.php` - 完整多音字字典（兼容性）

#### 完整字典
- `all_unihan_pinyin.php` - 完整Unihan拼音字典

#### 区块字典
- `cjk_basic.php` - CJK基本汉字
- `cjk_ext_a.php` - CJK扩展A区
- `cjk_ext_b.php` - CJK扩展B区
- `cjk_ext_c.php` - CJK扩展C区
- `cjk_ext_d.php` - CJK扩展D区
- `cjk_ext_e.php` - CJK扩展E区
- `cjk_ext_f.php` - CJK扩展F区
- `cjk_ext_g.php` - CJK扩展G区

### 文件结构示例
```
data/unihan/
├── displayable_unihan_pinyin.php          # 可显示字符字典
├── non_displayable_unihan_pinyin.php      # 不可显示字符字典
├── all_unihan_pinyin.php                  # 完整字典
├── displayable_polyphone_unihan_pinyin.php # 可显示多音字
├── non_displayable_polyphone_unihan_pinyin.php # 不可显示多音字
├── polyphone_unihan_pinyin.php            # 完整多音字
├── cjk_basic.php                          # 基本汉字区块
├── cjk_ext_a.php                          # 扩展A区
├── cjk_ext_b.php                          # 扩展B区
└── ...                                    # 其他区块
```

## 拼音字段处理

### 支持的拼音字段

#### 高优先级字段
- `kMandarin` - 普通话拼音（最高优先级）

#### 其他字段
- `kHanyuPinyin` - 汉语拼音
- `kXHC1983` - 现代汉语词典
- `kTGHZ2013` - 通用规范汉字字典
- `kHanyuPinlu` - 汉语拼音频率
- `kSMSZD2003Readings` - 商务印书社字典

### 拼音提取策略

1. **优先级排序**：kMandarin字段的拼音始终排在数组最前面
2. **多音字处理**：保留所有有效的拼音读音
3. **去重处理**：去除重复拼音，但保持优先级顺序
4. **格式验证**：只保留符合拼音格式的条目

### 字段格式解析

#### kMandarin格式
```
拼音1 拼音2 拼音3
```
示例：`yī yí yǐ yì`

#### kXHC1983/kTGHZ2013格式
```
页码.编号:拼音1,拼音2;页码.编号:拼音3
```
示例：`1234.01:yī,yí;5678.02:yǐ`

#### kHanyuPinyin格式
```
页码.编号:拼音1,拼音2;页码.编号:拼音3
```
示例：`1234.01:yī,yí;5678.02:yǐ`

#### kHanyuPinlu格式
```
拼音1(频率) 拼音2(频率)
```
示例：`yī(123) yí(45)`

#### kSMSZD2003Readings格式
```
拼音1,拼音2,拼音3
```
示例：`yī,yí,yǐ`

## 返回结果格式

```php
[
    'total_chars' => 12345,              // 总字符数
    'displayable_chars' => 10000,        // 可显示字符数
    'non_displayable_chars' => 2345,     // 不可显示字符数
    'polyphone_chars' => 8486,           // 多音字数量
    'displayable_percentage' => 81.0,    // 可显示字符百分比
    'polyphone_percentage' => 68.7,       // 多音字百分比
    'output_dir' => '/path/to/output'     // 输出目录
]
```

## 注意事项

### 1. 依赖要求
- PHP 7.4+ 版本
- mbstring 扩展（用于多字节字符处理）
- ZipArchive 扩展（用于解压文件）

### 2. 文件权限
- 确保有足够的权限创建临时目录和输出文件
- 临时目录会在处理完成后自动清理

### 3. 内存使用
- 处理大量数据时可能需要调整PHP内存限制
- 建议在处理前设置：`ini_set('memory_limit', '512M')`

### 4. 字符显示兼容性
- 可显示字符的定义基于常见字体支持
- 实际显示效果可能因系统和字体而异

### 5. 数据更新
- Unihan数据会定期更新，建议定期重新提取
- 可通过UnihanDataManager的updateData方法检查更新

## 错误处理

类中定义了以下异常情况：

### 文件相关错误
- `Unihan.zip文件无法打开`
- `Unihan_Readings.txt文件不存在`
- `无法创建输出目录`

### 数据处理错误
- `无效的Unicode编码`
- `拼音格式解析失败`

## 性能优化建议

### 1. 批量处理
- 每处理1000个字符输出进度信息
- 可调整进度输出频率

### 2. 内存优化
- 使用生成器处理大文件
- 及时释放不再使用的变量

### 3. 磁盘I/O优化
- 使用SSD存储提高读写速度
- 合理设置临时目录位置

## 扩展和自定义

### 添加新的Unicode范围
```php
// 在构造函数后添加自定义范围
$extractor = new UnihanExtractor($config);
// 添加自定义可显示范围
$extractor->addDisplayableRange(0x10000, 0x10FFFF);
```

### 自定义拼音字段处理
可扩展`parsePinyinField`方法支持新的拼音字段格式。

### 自定义输出格式
可重写`saveDictFile`方法实现自定义字典文件格式。

## 版本历史

### v1.0.0 (当前版本)
- 合并了UnihanExtractorEnhanced的所有功能
- 支持多音字正确处理
- 实现字符智能分类
- 提供详细的统计信息
- 优化拼音提取算法

## 相关类

- `UnihanDataManager` - Unihan数据管理器
- `UnihanDownloader` - Unihan数据下载器
- `UnihanMerger` - Unihan数据合并器
- `UnihanComparator` - Unihan数据比较器

## 技术支持

如有问题或建议，请参考项目文档或提交Issue。