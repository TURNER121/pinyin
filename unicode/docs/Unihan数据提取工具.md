
# Unihan数据提取工具

php extract_unihan.php --extract


Unihan数据提取器 支持将可正常显示和无法正常显示的字符分别提取出来。


## 主要功能特点

### 1. **智能字符分类**
- **可正常显示的字符**：CJK基本汉字、扩展A区、兼容汉字等
- **无法正常显示的字符**：CJK扩展B-G区、生僻字、代理对字符

### 2. **分类标准**
```php
// 可正常显示的范围
$displayableRanges = [
    [0x4E00, 0x9FFF],   // CJK基本汉字
    [0x3400, 0x4DBF],   // CJK扩展A
    [0xF900, 0xFAFF],   // CJK兼容汉字
    [0x2F800, 0x2FA1F]  // CJK兼容扩展
];

// 无法正常显示的范围  
$nonDisplayableRanges = [
    [0x20000, 0x2A6DF], // CJK扩展B
    [0x2A700, 0x2B73F], // CJK扩展C
    [0x2B740, 0x2B81F], // CJK扩展D
    [0x2B820, 0x2CEAF], // CJK扩展E
    [0x2CEB0, 0x2EBEF], // CJK扩展F
    [0x30000, 0x3134F], // CJK扩展G
    [0x31350, 0x323AF]  // CJK扩展H
];
```

### 3. **生成的文件**
- `displayable_unihan_pinyin.php` - 可正常显示的汉字拼音字典
- `non_displayable_unihan_pinyin.php` - 无法正常显示的汉字拼音字典
- `all_unihan_pinyin.php` - 完整Unihan拼音字典
- 按CJK区块分类的字典文件

### 4. **使用示例**
```php
<?php
require_once 'UnihanExtractorEnhanced.php';

use tekintian\pinyin\unicode\UnihanExtractorEnhanced;

$config = [
    'zip_file' => __DIR__ . '/Unihan.zip',
    'extract_dir' => __DIR__ . '/temp_unihan',
    'output_dir' => __DIR__ . '/data/unihan'
];

$extractor = new UnihanExtractorEnhanced($config);
$result = $extractor->extractWithClassification();

print_r($result);
```

### 5. **统计信息**
提取完成后会返回详细的统计信息：
- 总字符数
- 可正常显示字符数及百分比
- 无法正常显示字符数及百分比

## 为什么有必要分类？

1. **性能优化**：大多数应用只需要处理可正常显示的字符
2. **兼容性**：避免在无法显示字符的系统上出现问题
3. **存储效率**：分离生僻字可以减小常用字典的大小
4. **用户体验**：确保常用汉字有更好的显示效果

这种分类方式特别适合拼音转换库，可以根据实际需求选择使用完整的字典还是仅使用可正常显示的字符字典。





# 多音字字典

多音字字典的保存逻辑: 多音字字典也会按照可显示和不可显示进行分类，保持与普通拼音字典相同的模式。

## 新的多音字字典结构

### 1. **三个多音字字典文件**

**可正常显示的多音字字典**
- `displayable_polyphone_unihan_pinyin.php`
- 包含所有可正常显示的多音字
- 适用于大多数应用场景

**不可正常显示的多音字字典**  
- `non_displayable_polyphone_unihan_pinyin.php`
- 包含生僻字、异体字等多音字
- 适用于专业字符处理

**完整多音字字典（兼容性）**
- `polyphone_unihan_pinyin.php`
- 包含所有多音字，保持向后兼容

### 2. **分类逻辑**

```php
// 检查字符是否可正常显示
if ($this->isDisplayableChar($char)) {
    $displayablePolyphoneDict[$char] = $pinyins;
} else {
    $nonDisplayablePolyphoneDict[$char] = $pinyins;
}
```

### 3. **优势**

1. **一致性**：多音字字典与普通拼音字典采用相同的分类模式
2. **实用性**：可显示的多音字字典更适用于日常应用
3. **完整性**：不可显示的多音字字典满足专业需求
4. **兼容性**：保留完整的字典文件，确保现有代码不受影响

### 4. **文件结构**

```
data/unihan/
├── displayable_polyphone_unihan_pinyin.php (可显示多音字)
├── non_displayable_polyphone_unihan_pinyin.php (不可显示多音字)
└── polyphone_unihan_pinyin.php (完整多音字 8486个，兼容性)
```

### 5. **使用建议**

- **日常应用**：使用`displayable_polyphone_unihan_pinyin.php`
- **专业处理**：使用`non_displayable_polyphone_unihan_pinyin.php`
- **兼容性**：使用`polyphone_unihan_pinyin.php`

现在重新运行提取工具，多音字字典也会按照可显示和不可显示进行分类，整个Unihan拼音字典系统更加完整和实用！





