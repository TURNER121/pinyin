# `PinyinConverter`工具的单元测试
以下是针对`PinyinConverter`工具的单元测试（基于PHPUnit）和压力测试代码，覆盖核心功能验证、优先级机制、性能稳定性等场景。




### 一、单元测试代码（`PinyinConverterTest.php`）
需依赖`PHPUnit`，测试前确保已安装：`composer require --dev phpunit/phpunit`

./vendor/bin/phpunit tests/PinyinConverterTest.php



### 二、压力测试代码（`pressure_test.php`）
模拟高并发请求，测试工具在大量转换任务下的性能和稳定性。

./vendor/bin/phpunit tests/test_actual_results.php




### 三、测试说明

#### 单元测试
1. **测试范围**：覆盖基础转换、字典优先级、多字匹配、自学习、多音字规则、特殊字符、缓存等核心功能。
2. **环境隔离**：使用临时目录存储字典文件，避免测试污染真实数据。
3. **执行方式**：  
   在项目根目录运行：./vendor/bin/phpunit tests/PinyinConverterTest.php


#### 压力测试
1. **测试目标**：验证工具在高并发场景下的性能（响应时间、吞吐量）和稳定性（无崩溃、内存泄漏）。
2. **配置说明**：  
   - `concurrency`：并发进程数（根据服务器性能调整，建议从10开始）。  
   - `requests_per_process`：每个进程的请求数，控制总请求量。  
   - `test_texts`：模拟真实业务的文本库，包含技术热词。
3. **执行方式**：  
   `php pressure_test.php`  
   （需PHP支持`pcntl`扩展，用于创建多进程）。
4. **关键指标**：  
   - 平均单请求耗时：反映响应速度，建议<50ms。  
   - 吞吐量：每秒处理的请求数，越高性能越好。  
   - 最大耗时：检测是否存在极端延迟（可能由IO或缓存失效导致）。


### 四、扩展建议
1. 单元测试可补充边界场景（超长文本、全生僻字、空字符串等）。
2. 压力测试可添加内存使用监控（`memory_get_usage()`），检测内存泄漏。
3. 结合CI/CD工具（如GitHub Actions），实现每次代码提交自动运行单元测试。



