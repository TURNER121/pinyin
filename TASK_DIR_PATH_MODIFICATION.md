# Task Directory Path Modification

## 修改概述

本次修改将 `task_dir` 配置从硬编码的绝对路径改为相对路径，使其支持动态路径解析，与字典根路径配置保持一致。

## 修改内容

### 1. PinyinConverter.php
- **修改前**: `'task_dir' => __DIR__ . '/../data/tasks/'`
- **修改后**: `'task_dir' => 'tasks/' // 任务存储目录（相对于字典根路径）`
- **新增功能**: 在创建 BackgroundTaskManager 时传递字典根路径配置

### 2. BackgroundTaskManager.php
- **修改前**: 硬编码路径 `'task_dir' => __DIR__ . '/../data/tasks/'`
- **修改后**: 相对路径 `'task_dir' => 'tasks/'` + 动态路径解析
- **新增方法**: `getDictPath()` - 用于解析相对路径为完整路径
- **新增配置**: `dict_root_path` - 字典根路径配置

### 3. 其他硬编码路径修复
修复了 BackgroundTaskManager.php 中的其他硬编码路径：
- `isCommonHanChar()` 方法中的常用字典路径
- `addToDictFile()` 方法中的字典文件路径
- `fetchFromUnihanDict()` 方法中的 Unihan 字典路径
- `removeCharFromNotFound()` 方法中的未找到字符文件路径

### 4. PinyinConverter.php 中的其他修复
- `queryUnihanForPinyin()` 方法中的 Unihan 字典路径

## 环境变量支持

修改后的配置支持通过 `PINYIN_DICT_ROOT_PATH` 环境变量自定义字典根路径：

```bash
export PINYIN_DICT_ROOT_PATH=/custom/path/to/dicts
```

## 路径解析逻辑

```php
private function getDictPath(string $relativePath): string
{
    return $this->config['dict_root_path'] . '/' . ltrim($relativePath, '/\\');
}
```

## 配置示例

### 默认配置
```php
'background_tasks' => [
    'enable' => true,
    'task_dir' => 'tasks/', // 相对于字典根路径
    'max_concurrent' => 3,
    // ...
]
```

### 使用环境变量
```php
// 环境变量: PINYIN_DICT_ROOT_PATH=/shared/dicts
// 最终 task_dir 路径: /shared/dicts/tasks/
```

## 文档更新

更新了以下文档：
- `docs/BackgroundTaskManager.md` - 更新配置参数说明
- `docs/BackgroundTaskManager_Deployment.md` - 更新配置示例

## 兼容性

- ✅ 向后兼容：现有代码无需修改
- ✅ 环境变量支持：灵活的部署配置
- ✅ 相对路径：支持包部署场景

## 测试验证

创建了测试脚本验证：
- 环境变量加载正确性
- 路径解析逻辑正确性
- 目录创建和访问正常

## 部署注意事项

1. 确保字典根路径下的 `tasks/` 目录有适当的写权限
2. 如使用环境变量，确保目标路径存在且可访问
3. 在 Composer 包部署场景中，可将字典文件部署到共享位置

## 相关文件

- `src/PinyinConverter.php` - 主要配置和逻辑
- `src/BackgroundTaskManager.php` - 后台任务管理器
- `docs/BackgroundTaskManager.md` - 配置文档
- `docs/BackgroundTaskManager_Deployment.md` - 部署文档