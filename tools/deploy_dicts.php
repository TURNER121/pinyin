#!/usr/bin/env php
<?php
/**
 * 拼音字典部署工具
 * 
 * 用于将字典文件从项目目录复制到指定的目标目录，
 * 便于作为包使用时统一管理字典文件。
 * 
 * 使用方法:
 * php tools/deploy_dicts.php [目标目录]
 * 
 * 示例:
 * php tools/deploy_dicts.php /usr/local/share/pinyin-data
 * php tools/deploy_dicts.php ./dict_data
 */

// 检查是否在项目根目录运行
if (!file_exists(__DIR__ . '/../src/PinyinConverter.php')) {
    echo "错误: 请在项目根目录运行此脚本\n";
    exit(1);
}

$sourceDir = getenv('PINYIN_DICT_ROOT_PATH') ?: __DIR__ . '/../data';
$targetDir = $argv[1] ?? null;

if (!$targetDir) {
    echo "使用方法: php tools/deploy_dicts.php <目标目录>\n";
    echo "示例: php tools/deploy_dicts.php /usr/local/share/pinyin-data\n";
    echo "示例: php tools/deploy_dicts.php ./dict_data\n";
    exit(1);
}

// 标准化路径
$targetDir = rtrim($targetDir, '/\\');

echo "开始部署拼音字典文件...\n";
echo "源目录: {$sourceDir}\n";
echo "目标目录: {$targetDir}\n\n";

// 检查源目录是否存在
if (!is_dir($sourceDir)) {
    echo "错误: 源目录不存在: {$sourceDir}\n";
    exit(1);
}

// 创建目标目录
if (!is_dir($targetDir)) {
    echo "创建目标目录: {$targetDir}\n";
    if (!mkdir($targetDir, 0755, true)) {
        echo "错误: 无法创建目标目录: {$targetDir}\n";
        exit(1);
    }
}

// 需要复制的文件和目录
$items = [
    'common_with_tone.php',
    'common_no_tone.php',
    'rare_with_tone.php',
    'rare_no_tone.php',
    'custom_with_tone.php',
    'custom_no_tone.php',
    'self_learn_with_tone.php',
    'self_learn_no_tone.php',
    'polyphone_rules.php',
    'char_frequency.php',
    'unihan/',
    'diy/',
    'backup/'
];

$successCount = 0;
$failCount = 0;

foreach ($items as $item) {
    $sourcePath = $sourceDir . '/' . $item;
    $targetPath = $targetDir . '/' . $item;
    
    if (is_dir($sourcePath)) {
        // 复制目录
        echo "复制目录: {$item}\n";
        $result = copyDirectory($sourcePath, $targetPath);
    } elseif (file_exists($sourcePath)) {
        // 复制文件
        echo "复制文件: {$item}\n";
        $result = copyFile($sourcePath, $targetPath);
    } else {
        echo "跳过不存在的项: {$item}\n";
        continue;
    }
    
    if ($result) {
        $successCount++;
    } else {
        $failCount++;
        echo "  错误: 复制失败\n";
    }
}

echo "\n部署完成!\n";
echo "成功: {$successCount} 项\n";
echo "失败: {$failCount} 项\n";

if ($failCount > 0) {
    echo "\n警告: 有 {$failCount} 项复制失败，请检查权限和路径\n";
    exit(1);
}

// 生成环境变量配置提示
echo "\n环境变量配置提示:\n";
echo "export PINYIN_DICT_ROOT_PATH=\"{$targetDir}\"\n";
echo "\n或者将以下内容添加到 .env 文件:\n";
echo "PINYIN_DICT_ROOT_PATH={$targetDir}\n";

/**
 * 递归复制目录
 */
function copyDirectory($source, $target) {
    if (!is_dir($target)) {
        if (!mkdir($target, 0755, true)) {
            return false;
        }
    }
    
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $sourcePath = $source . '/' . $file;
        $targetPath = $target . '/' . $file;
        
        if (is_dir($sourcePath)) {
            if (!copyDirectory($sourcePath, $targetPath)) {
                return false;
            }
        } else {
            if (!copyFile($sourcePath, $targetPath)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * 复制文件
 */
function copyFile($source, $target) {
    $targetDir = dirname($target);
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            return false;
        }
    }
    
    return copy($source, $target);
}