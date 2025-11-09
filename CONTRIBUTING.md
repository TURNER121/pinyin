# 贡献指南

感谢您考虑为这个项目做出贡献！以下是一些指导原则，帮助您顺利参与项目开发。

## 开发环境设置

1. 克隆仓库
```bash
git clone https://github.com/tekintian/pinyin.git
cd pinyin
```

2. 安装依赖
```bash
composer install
```

3. 运行测试
```bash
composer test
```

## 代码规范

- 遵循PSR-12编码规范
- 使用PHPDoc为类和方法添加文档注释
- 确保所有新增功能都有相应的测试用例

## 提交流程

1. 创建新分支
```bash
git checkout -b feature/your-feature-name
```

2. 编写代码并提交
```bash
git add .
git commit -m "描述你的修改"
```

3. 推送到远程仓库
```bash
git push origin feature/your-feature-name
```

4. 创建Pull Request

## 报告问题

如果您发现任何问题，请在GitHub上创建Issue，并提供以下信息：

- 问题的详细描述
- 复现步骤
- 预期行为
- 实际行为
- 环境信息（PHP版本、操作系统等）

## 提交Bug修复

1. 确保您的代码修复了问题
2. 添加测试用例验证修复
3. 更新文档（如果需要）

## 提交新功能

1. 确保新功能符合项目的整体设计
2. 添加完整的测试用例
3. 更新README.md文档，描述新功能的使用方法

## 代码审查

所有提交的代码都会经过审查，请耐心等待审查结果，并根据反馈进行必要的修改。

再次感谢您的贡献！