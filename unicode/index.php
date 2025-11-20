<?php
require_once 'UnihanDataManager.php';
require_once 'UnihanDownloader.php';
require_once 'UnihanExtractor.php';
require_once 'UnihanComparator.php';
require_once 'UnihanValidator.php';

use tekintian\pinyin\unicode\UnihanDataManager;

$manager = new UnihanDataManager();

// 智能更新数据
$updated = $manager->updateData();

// 对比分析
$comparison = $manager->compareWithCurrentDict();

// 验证完整性
$validation = $manager->validateDict();

// 生成报告
$report = $manager->generateReport();
echo $report;
