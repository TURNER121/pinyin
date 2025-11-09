# Changelog

所有对该项目的重要更改都会记录在此文件中。

## [Unreleased]

## [1.0.0] - 2023-xx-xx

### Added
- 初始版本发布
- 实现基本的汉字转拼音功能
- 支持自定义映射
- 支持特殊字符处理
- 支持自动学习功能

## [1.1.0] - 2023-xx-xx

### Added
- 添加多字词语替换功能
- 添加URL友好的slug生成
- 增加自学习字典合并策略

### Fixed
- 修复特殊字符处理失效问题
- 修复参数传递不灵活问题

## [1.2.0] - 2023-xx-xx

### Added
- 添加Contract接口定义
- 添加异常处理类
- 添加工具类

### Changed
- 优化目录结构
- 完善文档

## [1.2.1] - 2023-xx-xx

### Fixed
- 修复特殊字符处理逻辑，确保replace模式正常工作
- 修复拼音转换缓存机制

## 版本号规范

本项目遵循[语义化版本控制](https://semver.org/lang/zh-CN/)：

- **MAJOR**: 不兼容的API更改
- **MINOR**: 向下兼容的功能性新增
- **PATCH**: 向下兼容的问题修正

[Unreleased]: https://github.com/tekintian/pinyin/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/tekintian/pinyin/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/tekintian/pinyin/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/tekintian/pinyin/releases/tag/v1.0.0