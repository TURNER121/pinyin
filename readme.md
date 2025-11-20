# 汉字转拼音工具

[![License](https://poser.pugx.org/tekintian/pinyin/license)](https://packagist.org/packages/tekintian/pinyin)
[![Latest Stable Version](https://poser.pugx.org/tekintian/pinyin/v/stable)](https://packagist.org/packages/tekintian/pinyin)
[![Total Downloads](https://poser.pugx.org/tekintian/pinyin/downloads)](https://packagist.org/packages/tekintian/pinyin)

一个功能强大的汉字转拼音工具，支持自定义映射、特殊字符处理、自动学习功能和多音字处理。基于权威的Unihan数据库，提供完整的汉字拼音转换解决方案。

## ✨ 核心特性

- **🔤 精准转换** - 支持常用字和生僻字的准确拼音转换
- **🧠 智能学习** - 自动识别并记忆生僻字的拼音，支持自学习字典合并
- **🎛️ 多模式处理** - 三种特殊字符处理模式：`keep`/`delete`/`replace`
- **🔀 多音字识别** - 基于上下文的多音字智能处理
- **📚 完整字典系统** - 多级字典优先级，支持懒加载和内存优化
- **⚡ 高性能** - 多级缓存机制，支持高频字符快速转换
- **🔧 灵活配置** - 丰富的配置选项，满足不同使用场景
- **🔄 后台任务** - 完整的后台任务管理系统，支持守护进程模式
- **🌐 Unihan集成** - 基于Unicode权威数据库的拼音数据

## 📋 快速开始

### 安装

使用Composer安装：

```bash
composer require tekintian/pinyin
```

### 基本使用

```php
use tekintian\pinyin\PinyinConverter;

// 创建实例
$pinyinConverter = new PinyinConverter();

// 基本转换
$pinyin = $pinyinConverter->convert('你好，世界！');
echo $pinyin; // 输出: ni hao shi jie

// 保留声调
$pinyinWithTone = $pinyinConverter->convert('你好，世界！', ' ', true);
echo $pinyinWithTone; // 输出: nǐ hǎo shì jiè

// 生成URL Slug
$slug = $pinyinConverter->getUrlSlug('你好，世界！');
echo $slug; // 输出: ni-hao-shi-jie
```

## 🏗️ 项目架构

### 字典优先级系统

从高到低依次为：
1. **custom_xxx** - 自定义字典（用户自定义映射）
2. **polyphone_xxx** - 多音字规则字典
3. **common_xxx** - 常用字典
4. **rare_xxx** - 生僻字字典
5. **unihan** - Unihan字典（基于权威Unicode数据库）

### 核心模块

- **`src/PinyinConverter.php`** - 主转换器类，提供核心转换功能
- **`src/BackgroundTaskManager.php`** - 后台任务管理器
- **`bin/task_runner.php`** - 统一任务运行器（支持守护进程模式）
- **`unicode/`** - Unihan数据管理和处理工具
- **`tools/`** - 辅助工具集（自动获取、测试、解析等）

## 🚀 高级功能

### 后台任务管理

```bash
# 守护进程模式（推荐生产环境）
php bin/task_runner.php -m daemon -i 60

# 批量处理模式
php bin/task_runner.php -m batch -b 100 -l 500

# 一次性执行模式
php bin/task_runner.php -m once
```

### Unihan数据管理

```php
use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 更新Unihan数据
$updated = $manager->updateData();

// 生成数据报告
$report = $manager->generateReport();
```

### 特殊字符处理

```php
// keep模式：保留所有特殊字符
$result = $converter->convert('你好$世界', ' ', false, 'keep');

// delete模式：删除非安全字符
$result = $converter->convert('你好$世界', ' ', false, 'delete');

// replace模式：自定义替换
$result = $converter->convert('你好$世界', ' ', false, [
    'mode' => 'replace',
    'map' => ['$' => 'dollar']
]);
```

## 📊 数据统计

### Unihan字典覆盖

| 区块 | 字符数量 | 说明 |
|------|----------|------|
| CJK基本汉字 | 20,924 | 常用汉字 |
| CJK扩展A区 | 5,786 | 扩展A区生僻字 |
| CJK扩展B区 | 14,614 | 扩展B区生僻字 |
| CJK扩展C区 | 506 | 扩展C区生僻字 |
| CJK扩展D区 | 222 | 扩展D区生僻字 |
| CJK扩展E区 | 5,762 | 扩展E区生僻字 |
| CJK扩展F区 | 7,473 | 扩展F区生僻字 |
| CJK扩展G区 | 4,939 | 扩展G区生僻字 |

**总计**: 超过 60,000 个汉字的完整拼音覆盖

## 📚 详细文档

- [📖 项目架构和使用指南](docs/项目架构和使用指南.md) - 完整的项目架构说明和使用方法
- [🔧 工具使用手册](docs/工具使用手册.md) - 所有工具的使用方法和示例
- [📊 数据字典说明](docs/数据字典说明.md) - 字典系统的详细说明
- [🔄 后台任务管理](docs/BackgroundTaskManager.md) - 后台任务系统的使用指南
- [🌐 Unihan数据处理](docs/UnihanDataManager.md) - Unihan数据管理工具说明

## ⚙️ 配置选项

```php
$config = [
    'dict' => [
        // 字典文件路径配置
    ],
    'dict_loading' => [
        'strategy' => 'both',        // 字典加载策略
        'lazy_loading' => true,    // 懒加载开关
        'preload_priority' => ['custom', 'common'], // 预加载优先级
    ],
    'custom_dict_persistence' => [
        'enable' => true,           // 自定义字典持久化
        'delay_write' => true,      // 延迟写入
        'auto_save_interval' => 300  // 自动保存间隔（秒）
    ]
];

$pinyinConverter = new PinyinConverter($config);
```

## 🧪 测试

本项目所有测试通过 `run_tests.sh` 脚本统一管理，支持多种测试类型和参数选项：

```bash
# 基本使用 - 运行所有测试
./run_tests.sh

./run_tests.sh all                    # 运行所有测试
./run_tests.sh unit --coverage        # 运行单元测试并生成覆盖率
./run_tests.sh fast                   # 运行快速测试
./run_tests.sh basic                  # 运行基础转换测试

```

### 测试类型说明

该测试脚本包含以下测试类型：
- **基础转换测试** - 验证常用汉字和词组的拼音转换正确性
- **多音字测试** - 测试多音字在不同上下文中的正确识别
- **特殊字符测试** - 验证各种特殊字符处理模式的正确性
- **自定义字典测试** - 测试自定义映射规则的生效情况
- **边界条件测试** - 测试空字符串、超长字符串等边界情况

### 测试报告

运行测试后，将生成 Markdown 格式的测试报告，包含详细的测试结果统计和潜在问题分析。

### 其他测试方式

```bash
# 快速测试
php tools/quick_pinyin_test.php

# 通过Composer运行测试
composer test
```

详细测试文档请参考 [测试指南.md](docs/测试指南.md)


## 🔄 开发计划

✅ **已完成功能**
- 基础汉字转拼音功能
- 多音字处理
- 自学习字典系统
- 后台任务管理
- Unihan数据集成
- 完整的工具集

🔜 **计划功能**
- 更智能的多音字上下文识别
- 分布式任务处理支持
- Web界面管理工具
- 更多拼音数据源集成

## 🤝 贡献指南

欢迎贡献代码！请阅读：
- [贡献指南](CONTRIBUTING.md)
- [代码规范](docs/代码规范.md)
- [版本历史](CHANGELOG.md)

## 📄 许可证

本项目使用MIT许可证，详情请查看[LICENSE](LICENSE)文件。

## 🔗 相关链接

- [Unicode Unihan数据库](https://www.unicode.org/charts/unihan.html)
- [Composer包页面](https://packagist.org/packages/tekintian/pinyin)
- [GitHub仓库](https://github.com/tekintian/pinyin)
- [软件定制开发](https://dev.tekin.cn)

---

**版本**: 1.0.0  
**最后更新**: 2025-11-16  
**维护者**: tekintian  https://dev.tekin.cn