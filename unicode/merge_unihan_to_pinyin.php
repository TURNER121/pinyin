<?php
require_once __DIR__ . '/../vendor/autoload.php';

use tekintian\pinyin\unicode\UnihanDataManager;

// 配置参数
$dictRootPath = getenv('PINYIN_DICT_ROOT_PATH') ?: __DIR__ . '/../data';
$config = [
    'zip_file' => __DIR__ . '/Unihan/Unihan.zip',
    'extract_dir' => __DIR__ . '/Unihan/extracted',
    'output_dir' => $dictRootPath . '/unihan',
    'dict_dir' => $dictRootPath
];

// 创建数据管理器
$manager = new UnihanDataManager($config);

// 方法1：通过管理器直接调用
echo "方法1：通过UnihanDataManager直接调用合并功能...\n";
try {
    $result = $manager->mergeToPinyinConverter([
        'unihan_files' => ['displayable_unihan_pinyin.php'],
        'target_dict' => 'rare_with_tone.php',
        'check_existing' => true,
        'prompt_conflicts' => true,
        'auto_merge' => false
    ]);
    
    echo "合并完成！结果：\n";
    echo "- 成功合并: {$result['merged_count']} 个字符\n";
    echo "- 跳过: {$result['skipped_count']} 个字符\n";
    echo "- 发现冲突: " . count($result['conflicts']) . " 个\n";
    
    if (!empty($result['errors'])) {
        echo "- 错误: " . implode(', ', $result['errors']) . "\n";
    }
    
} catch (Exception $e) {
    echo "合并失败: " . $e->getMessage() . "\n";
}

// // 方法2：直接使用UnihanMerger类
// echo "\n方法2：直接使用UnihanMerger类...\n";
// try {
//     $merger = $manager->createMerger();
    
//     $result = $merger->mergeToPinyinConverter([
//         'unihan_files' => ['displayable_unihan_pinyin.php'],
//         'target_dict' => 'rare_with_tone.php',
//         'check_existing' => true,
//         'prompt_conflicts' => false,
//         'auto_merge' => true
//     ]);
    
//     echo "直接合并完成！结果：\n";
//     echo "- 成功合并: {$result['merged_count']} 个字符\n";
//     echo "- 跳过: {$result['skipped_count']} 个字符\n";
    
// } catch (Exception $e) {
//     echo "直接合并失败: " . $e->getMessage() . "\n";
// }

// // 批量合并示例
// echo "\n批量合并示例：\n";
// try {
//     $merger = $manager->createMerger();
    
//     // 'unihan_files' => ['displayable_unihan_pinyin.php', 'non_displayable_unihan_pinyin.php'],
//     $result = $merger->mergeToPinyinConverter([
//         'unihan_files' => ['displayable_unihan_pinyin.php'], // 一般情况只用合并常规可见汉字
//         'target_dict' => 'rare_with_tone.php',
//         'check_existing' => true,
//         'prompt_conflicts' => false,
//         'auto_merge' => true
//     ]);
    
//     echo "批量合并完成！结果：\n";
//     echo "- 成功合并: {$result['merged_count']} 个字符\n";
//     echo "- 跳过: {$result['skipped_count']} 个字符\n";
    
// } catch (Exception $e) {
//     echo "批量合并失败: " . $e->getMessage() . "\n";
// }