<?php
namespace tekintian\pinyin;

/**
 * 合并拼音字典
 * 配置crontab：
 * # 每天凌晨2点执行
 * 0 2 * * * /usr/bin/php /path/to/merge_pinyin_dict.php >> /path/to/merge_log.txt 2>&1
 *
 */
require 'PinyinConverter.php';
$converter = new PinyinConverter();
$result = $converter->executeMerge();
// 打印合并结果（仅在调试模式下输出）
pinyin_debug("合并结果：" . json_encode($result, JSON_UNESCAPED_UNICODE), 'info');
