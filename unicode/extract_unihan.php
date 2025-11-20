<?php
/**
 * Unihan数据提取工具 - 主入口脚本
 * 使用面向对象的Unihan数据管理器来提取、更新和验证拼音字典数据
 * 
 * 使用方法:
 * php extract_unihan.php [options]
 * 
 * 选项:
 *   --update 或 -u    更新Unihan数据
 *   --force 或 -f     强制重新下载数据
 *   --compare 或 -c   对比当前字典数据
 *   --validate 或 -v  验证字典完整性
 *   --report 或 -r    生成完整报告
 *   --help 或 -h      显示帮助信息
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查是否在命令行运行
if (php_sapi_name() !== 'cli') {
    die("此脚本只能在命令行模式下运行\n");
}

// 加载Composer自动加载器
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // 备用自动加载器
    spl_autoload_register(function ($class) {
        $classFile = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($classFile)) {
            require_once $classFile;
        }
    });
}

// 解析命令行参数
$options = getopt('ufcvrc', ['update', 'force', 'compare', 'validate', 'report', 'extract', 'help']);

// 显示帮助信息
if (isset($options['h']) || isset($options['help']) || empty($options)) {
    showHelp();
    exit(0);
}

try {
    // 配置参数
    $config = [
        'remote_url' => 'https://unicode.org/Public/UCD/latest/ucd/Unihan.zip',
        'zip_file' => __DIR__ . '/Unihan.zip',
        'extract_dir' => __DIR__ . '/temp_unihan',
        'output_dir' => __DIR__ . '/../data/unihan',
        'cache_days' => 30,
        'max_retries' => 3,
        'timeout' => 300
    ];
    
    // 创建数据管理器
    $manager = new tekintian\pinyin\unicode\UnihanDataManager($config);
    
    // 执行相应操作
    if (isset($options['u']) || isset($options['update'])) {
        $force = isset($options['f']) || isset($options['force']);
        echo "开始更新Unihan数据...\n";
        $result = $manager->updateData($force);
        echo "数据更新完成！\n";
        echo "总字符数: " . $result['total_chars'] . "\n";
        echo "输出目录: " . $result['output_dir'] . "\n";
    }
    
    if (isset($options['c']) || isset($options['compare'])) {
        echo "开始对比字典数据...\n";
        $comparison = $manager->compareWithCurrentDict();
        echo "对比完成！\n";
        echo "新增字符: " . $comparison['new_chars_count'] . "\n";
        echo "缺失字符: " . $comparison['missing_chars_count'] . "\n";
        echo "拼音差异: " . $comparison['pinyin_diff_count'] . "\n";
    }
    
    if (isset($options['v']) || isset($options['validate'])) {
        echo "开始验证字典完整性...\n";
        $validation = $manager->validateDict();
        echo "验证完成！\n";
        echo "文件完整性: " . ($validation['file_integrity'] ? '通过' : '失败') . "\n";
        echo "数据格式: " . ($validation['data_format'] ? '通过' : '失败') . "\n";
        echo "字符覆盖度: " . $validation['coverage_percentage'] . "%\n";
    }
    
    if (isset($options['r']) || isset($options['report'])) {
        echo "开始生成完整报告...\n";
        $report = $manager->generateReport();
        echo $report;
    }
    
    // 提取Unihan汉字和拼音字典
    if (isset($options['extract'])) {
        echo "开始提取Unihan汉字和拼音字典...\n";
        $enhancedResult = $manager->extractWithClassification();
        echo "分类完成！\n";
        echo "总字符数: " . $enhancedResult['total_chars'] . "\n";
        echo "可正常显示字符: " . $enhancedResult['displayable_chars'] . " (" . $enhancedResult['displayable_percentage'] . "%)\n";
        echo "无法正常显示字符: " . $enhancedResult['non_displayable_chars'] . "\n";
    }
    
    // 如果没有指定具体操作，执行完整流程
    if (!isset($options['u']) && !isset($options['c']) && !isset($options['v']) && !isset($options['r']) && !isset($options['extract'])) {
        echo "执行完整Unihan数据处理流程...\n\n";
        
        // 1. 更新数据
        echo "=== 步骤1: 更新Unihan数据 ===\n";
        $force = isset($options['f']) || isset($options['force']);
        $updateResult = $manager->updateData($force);
        echo "数据更新完成！总字符数: " . $updateResult['total_chars'] . "\n\n";
        
        // 2. 对比数据
        echo "=== 步骤2: 对比字典数据 ===\n";
        $comparison = $manager->compareWithCurrentDict();
        echo "对比完成！新增字符: " . $comparison['new_chars_count'] . 
             ", 缺失字符: " . $comparison['missing_chars_count'] . "\n\n";
        
        // 3. 验证数据
        echo "=== 步骤3: 验证字典完整性 ===\n";
        $validation = $manager->validateDict();
        echo "验证完成！字符覆盖度: " . $validation['coverage_percentage'] . "%\n\n";
        
        // 4. 使用增强版提取器进行分类
        echo "=== 步骤4: 字符显示分类 ===\n";
        $enhancedResult = $manager->extractWithClassification();
        echo "分类完成！\n";
        echo "可正常显示字符: " . $enhancedResult['displayable_chars'] . " (" . $enhancedResult['displayable_percentage'] . "%)\n";
        echo "无法正常显示字符: " . $enhancedResult['non_displayable_chars'] . "\n\n";
        
        // 5. 生成报告
        echo "=== 步骤5: 生成报告 ===\n";
        $report = $manager->generateReport();
        echo $report;
    }
    
    echo "\nUnihan数据处理完成！\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}

/**
 * 显示帮助信息
 */
function showHelp() {
    echo "Unihan数据提取工具\n";
    echo "===================\n\n";
    echo "使用方法:\n";
    echo "  php extract_unihan.php [选项]\n\n";
    echo "选项:\n";
    echo "  -u, --update      更新Unihan数据\n";
    echo "  -f, --force       强制重新下载数据\n";
    echo "  -c, --compare     对比当前字典数据\n";
    echo "  -v, --validate    验证字典完整性\n";
    echo "  -r, --report      生成完整报告\n";
    echo "  --extract         提取Unihan汉字和拼音字典\n";
    echo "  -h, --help        显示此帮助信息\n\n";
    echo "示例:\n";
    echo "  php extract_unihan.php --update --force    # 强制更新数据\n";
    echo "  php extract_unihan.php --compare          # 对比字典数据\n";
    echo "  php extract_unihan.php --extract         # 提取Unihan汉字和拼音字典\n";
    echo "  php extract_unihan.php                    # 执行完整流程\n";
}