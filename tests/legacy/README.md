# PinyinConverter 测试套件

## 文件说明

- `UnitTest.php` - 完整的单元测试套件，覆盖所有核心功能和边界场景
- `PressureTest.php` - 压力测试套件，包含性能测试和内存泄漏检测
- `README.md` - 本文件，测试套件说明

## 快速开始

### 运行所有测试
```bash
# 使用便捷脚本
./run_tests.sh all

# 或手动运行
./vendor/bin/phpunit tests/legacy/UnitTest.php
php tests/legacy/PressureTest.php
```

### 运行特定测试
```bash
# 单元测试
./vendor/bin/phpunit tests/legacy/UnitTest.php --filter testBasicConversion

# 压力测试
php tests/legacy/PressureTest.php

# 内存泄漏检测
./run_tests.sh memory
```

## 测试覆盖范围

### 单元测试
- ✅ 基础拼音转换功能
- ✅ 特殊字符处理
- ✅ 多音字处理
- ✅ 自定义字典功能
- ✅ URL Slug 生成
- ✅ 批量转换
- ✅ 搜索功能
- ✅ 缓存管理
- ✅ 异常处理
- ✅ 边界场景（空字符串、超长文本、生僻字等）

### 压力测试
- ✅ 单线程性能测试（1000次迭代）
- ✅ 内存泄漏检测（2000-10000次迭代）
- ✅ 批量转换测试（500-2000条数据）
- ✅ 边界场景测试
- ✅ 极端长度文本测试

## 性能基准

| 测试类型 | 目标性能 | 警告阈值 |
|---------|----------|----------|
| 单线程转换 | ≥ 500次/秒 | 300-500次/秒 |
| 批量转换 | ≥ 1000条/秒 | 500-1000条/秒 |
| 内存增长 | < 10MB/万次 | 10-20MB/万次 |

## CI/CD 集成

测试已集成到 GitHub Actions，每次代码提交自动运行：
- 多 PHP 版本测试（7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3）
- 完整覆盖 composer.json 中声明的所有支持版本
- 单元测试
- 压力测试
- 内存泄漏检测
- 代码风格检查
- 静态分析

## 详细文档

- [单元测试文档](../docs/单元测试文档.md)
- [压力测试文档](../docs/压力测试文档.md)
- [测试指南](../docs/测试指南.md)

## 问题报告

如发现测试问题，请：
1. 检查环境配置
2. 查看测试日志
3. 提交 GitHub Issue

## 维护说明

- 定期更新测试用例
- 监控测试执行时间
- 维护测试数据
- 更新性能基准