# UnihanDownloader 类文档

## 概述

`UnihanDownloader` 类负责从Unicode官方网站下载Unihan数据库文件。它是Unihan数据处理流程的第一步，确保获取最新的Unihan数据用于后续的拼音提取。

## 主要功能

### 1. 智能下载检查
- 检查本地文件是否存在
- 验证文件是否过期
- 支持强制下载模式

### 2. 可靠下载机制
- 支持重试机制（最大重试次数可配置）
- 超时控制
- 错误处理和异常捕获

### 3. 文件信息管理
- 文件大小格式化显示
- 文件修改时间跟踪
- 缓存过期策略

## 类结构

### 常量定义

```php
const REMOTE_URL = 'https://unicode.org/Public/UCD/latest/ucd/Unihan.zip';
```
Unihan数据的官方下载地址。

### 属性

#### 配置参数
```php
private $config
```
包含以下配置项：
- `zip_file`: Unihan.zip文件路径
- `cache_days`: 缓存天数（默认30天）
- `max_retries`: 最大重试次数（默认3次）
- `timeout`: 下载超时时间（默认30秒）

### 公共方法

#### 构造函数
```php
public function __construct(array $config)
```
初始化配置参数。

#### 下载检查
```php
public function shouldDownload(bool $force = false): bool
```
检查是否需要下载Unihan数据。

#### 下载执行
```php
public function download(): bool
```
执行Unihan数据的下载操作。

### 私有方法

#### 文件信息获取
```php
private function getFileInfo(string $filePath): string
```
获取文件的详细信息（大小、修改时间等）。

#### 字节格式化
```php
private function formatBytes($bytes, $precision = 2): string
```
将字节数格式化为易读的格式（KB、MB、GB等）。

## 使用示例

### 基本使用
```php
<?php
require_once 'vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDownloader;

$config = [
    'zip_file' => __DIR__ . '/Unihan.zip',
    'cache_days' => 30,
    'max_retries' => 3,
    'timeout' => 30
];

$downloader = new UnihanDownloader($config);

// 检查是否需要下载
if ($downloader->shouldDownload()) {
    echo "需要下载Unihan数据...\n";
    
    // 执行下载
    if ($downloader->download()) {
        echo "下载成功！\n";
    } else {
        echo "下载失败！\n";
    }
} else {
    echo "使用本地缓存数据\n";
}
```

### 通过UnihanDataManager使用
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

## 下载策略

### 1. 缓存检查逻辑
```php
// 文件不存在 → 需要下载
// 强制下载模式 → 需要下载  
// 文件过期 → 需要下载
// 其他情况 → 使用缓存
```

### 2. 重试机制
- 使用指数退避算法：等待时间 = 2^attempt 秒
- 最大重试次数可配置
- 每次重试后等待时间加倍

### 3. 超时控制
- 默认30秒超时
- 可配置超时时间
- 避免长时间等待网络问题

## 配置参数详解

### zip_file
- **类型**: string
- **默认**: `__DIR__ . '/Unihan.zip'`
- **说明**: Unihan.zip文件的本地保存路径

### cache_days
- **类型**: int
- **默认**: 30
- **说明**: 缓存有效期（天数），超过此天数后需要重新下载

### max_retries
- **类型**: int
- **默认**: 3
- **说明**: 下载失败时的最大重试次数

### timeout
- **类型**: int
- **默认**: 30
- **说明**: 下载超时时间（秒）

## 错误处理

### 异常类型
- `PinyinException::ERROR_DICT_LOAD_FAIL` - 下载失败异常

### 错误场景
1. **网络连接失败** - 重试机制处理
2. **服务器无响应** - 超时机制处理
3. **磁盘空间不足** - 文件写入失败
4. **权限问题** - 无法创建文件

### 错误信息示例
```
下载异常: file_get_contents(): SSL operation failed with code 1
下载异常: file_get_contents(): Failed to enable crypto
下载异常: file_get_contents(https://...): failed to open stream: Operation timed out
```

## 性能优化

### 1. 缓存策略
- 避免重复下载相同数据
- 合理设置缓存天数
- 支持强制更新

### 2. 网络优化
- 使用流上下文配置超时
- 设置合适的User-Agent
- SSL证书验证优化

### 3. 内存优化
- 分块处理大文件
- 及时释放资源

## 安全考虑

### 1. SSL验证
- 默认禁用SSL证书验证（避免证书问题）
- 生产环境建议启用验证

### 2. 文件路径安全
- 验证文件路径有效性
- 防止目录遍历攻击

### 3. 输入验证
- 配置参数类型检查
- 路径规范化处理

## 扩展和自定义

### 自定义下载URL
```php
// 继承并重写REMOTE_URL常量
class CustomUnihanDownloader extends UnihanDownloader
{
    const REMOTE_URL = 'https://custom-mirror.com/Unihan.zip';
}
```

### 自定义重试策略
```php
// 重写download方法实现自定义重试逻辑
public function download(): bool
{
    // 自定义重试逻辑
    // ...
}
```

### 添加下载进度显示
```php
// 使用cURL实现进度回调
private function downloadWithProgress($url, $filePath)
{
    // 实现进度显示
    // ...
}
```

## 注意事项

### 1. 网络环境
- 确保服务器可以访问Unicode官方网站
- 防火墙和代理设置可能影响下载
- 考虑使用镜像站点提高下载速度

### 2. 文件权限
- 确保有写入权限到目标目录
- 临时文件需要足够的磁盘空间
- 文件锁可能影响并发下载

### 3. 版本控制
- Unihan数据会定期更新
- 建议定期检查并更新数据
- 版本变更可能影响拼音提取结果

### 4. 错误处理
- 下载失败时应提供清晰的错误信息
- 重试机制可以处理临时网络问题
- 永久性错误需要人工干预

## 相关类

- `UnihanDataManager` - 数据管理器，协调下载和提取流程
- `UnihanExtractor` - 数据提取器，处理下载的Unihan数据
- `UnihanValidator` - 数据验证器，验证下载数据的完整性

## 版本历史

### v1.0.0
- 初始版本，实现基本下载功能
- 支持缓存检查和重试机制
- 集成到Unihan数据处理流程中

## 技术支持

如有下载问题或需要自定义功能，请参考项目文档或提交Issue。