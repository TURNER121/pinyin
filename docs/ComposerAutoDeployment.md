# Composer 自动部署词库

## 概述

拼音转换库支持在 Composer 安装/更新时自动部署词库文件，提供了灵活的部署策略和环境变量管理。

## 自动部署机制

### 触发时机

- `composer install` - 首次安装时自动部署
- `composer update` - 更新包时自动部署
- `composer run-script deploy-dicts` - 手动触发部署

### 部署策略

当目标目录已存在时，提供以下处理选项：

1. **跳过部署** - 使用现有词库，仅更新环境变量
2. **覆盖部署** - 备份现有词库后覆盖
3. **更换目录** - 指定新的部署目录

## 使用方法

### 1. 标准安装（交互式）

```bash
composer install
```

首次安装时会进入交互模式：
- 提示输入词库目录路径
- 检查目录存在性并提供处理选项
- 自动创建 `.env` 文件并设置环境变量

### 2. 静默安装（非交互式）

```bash
# 跳过现有词库
composer run-script deploy-dicts-silent

# 强制覆盖现有词库
composer run-script deploy-dicts-force

# 部署到指定目录
composer run-script deploy-dicts-to -- --target=/custom/path
```

### 3. 环境变量配置

#### 自动配置
部署脚本会自动更新 `.env` 文件：
```env
PINYIN_DICT_ROOT_PATH=/path/to/dictionaries
```

#### 手动配置
```bash
# 设置自定义词库路径
export PINYIN_DICT_ROOT_PATH=/shared/dicts

# 跳过自动部署
export PINYIN_SKIP_AUTO_DEPLOY=1
```

## 部署场景

### 开发环境

```bash
# 使用默认配置
composer install

# 词库将部署到项目根目录的 data 文件夹
# .env 文件自动创建
```

### 生产环境

```bash
# 设置生产环境标识，跳过自动部署
export COMPOSER_PROD_INSTALL=1
composer install --no-dev

# 或手动部署到共享位置
composer run-script deploy-dicts-to -- --target=/shared/dicts
```

### CI/CD 环境

```yaml
# GitHub Actions 示例
- name: Deploy dictionaries
  run: |
    composer run-script deploy-dicts-silent -- --target=${{ github.workspace }}/data
    
# 或使用环境变量
- name: Setup environment
  run: |
    echo "PINYIN_DICT_ROOT_PATH=${{ github.workspace }}/data" >> .env
    echo "PINYIN_SKIP_AUTO_DEPLOY=1" >> .env
```

### Docker 部署

```dockerfile
# Dockerfile
COPY . /app
WORKDIR /app

# 部署词库到容器内
RUN composer install && \
    composer run-script deploy-dicts-silent -- --target=/app/data

# 或挂载外部词库
VOLUME ["/shared/dicts"]
ENV PINYIN_DICT_ROOT_PATH=/shared/dicts
RUN PINYIN_SKIP_AUTO_DEPLOY=1 composer install
```

## 高级配置

### 环境变量说明

| 变量名 | 说明 | 默认值 |
|--------|------|--------|
| `PINYIN_DICT_ROOT_PATH` | 字典根路径 | `项目根目录/data` |
| `PINYIN_SKIP_AUTO_DEPLOY` | 跳过自动部署 | `0` |
| `CI` | CI 环境标识 | 未设置 |
| `COMPOSER_PROD_INSTALL` | 生产环境标识 | 未设置 |

### 配置文件

部署配置保存在 `.pinyin-dict-config.json`：
```json
{
  "target_dir": "/path/to/dictionaries",
  "env_var": "/path/to/dictionaries",
  "strategy": "skip",
  "last_deploy": "2025-11-20 12:00:00"
}
```

### .env 文件模板

参考 `.env.example` 文件：
```env
# 拼音转换库环境变量配置
PINYIN_DICT_ROOT_PATH=/path/to/your/dictionaries
PINYIN_SKIP_AUTO_DEPLOY=0
```

## 故障排除

### 常见问题

1. **权限错误**
   ```bash
   # 确保目标目录有写权限
   chmod 755 /path/to/dictionaries
   ```

2. **路径不存在**
   ```bash
   # 创建目标目录
   mkdir -p /path/to/dictionaries
   ```

3. **跳过部署**
   ```bash
   # 检查环境变量
   env | grep PINYIN
   
   # 手动触发部署
   composer run-script deploy-dicts
   ```

### 调试模式

```bash
# 查看详细输出
composer run-script deploy-dicts -v

# 检查配置
cat .pinyin-dict-config.json
cat .env
```

## 最佳实践

### 1. 开发环境
- 使用交互式部署，便于配置
- 将 `.env` 文件添加到 `.gitignore`
- 记录部署配置到文档

### 2. 生产环境
- 使用环境变量或配置文件
- 设置 `PINYIN_SKIP_AUTO_DEPLOY=1` 避免意外覆盖
- 定期备份词库文件

### 3. 团队协作
- 统一词库版本和路径
- 使用相对路径配置
- 在 CI/CD 中自动化部署

### 4. 容器化部署
- 使用卷挂载共享词库
- 设置适当的环境变量
- 考虑词库文件大小和性能

## 相关命令

```bash
# 查看所有可用脚本
composer run-script --list

# 手动部署词库
composer run-script deploy-dicts

# 静默部署
composer run-script deploy-dicts-silent

# 强制覆盖部署
composer run-script deploy-dicts-force

# 部署到指定目录
composer run-script deploy-dicts-to -- --target=/custom/path
```

---

通过这套自动部署机制，可以大大简化拼音转换库的部署流程，提高开发效率和部署一致性。

