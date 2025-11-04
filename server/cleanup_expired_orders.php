<?php
/**
 * 自动清理过期的待支付订单
 * 
 * 使用方法：
 * 1. 直接运行：php cleanup_expired_orders.php
 * 2. Cron定时任务（推荐）：
 *    crontab -e 添加: 每10分钟执行一次
 *    命令示例: /usr/bin/php /path/to/your/project/server/cleanup_expired_orders.php
 * 
 * 配置说明：
 * - EXPIRE_MINUTES: 订单过期时间（分钟）  
 * - 可以根据需要调整过期时间
 */

// 设置错误报告（仅在开发环境使用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 引入数据库配置
$dbConfig = require_once 'db_config.php';

// 配置项
define('EXPIRE_MINUTES', 10); // 订单过期时间（分钟），可根据需要调整

// 记录日志函数
function writeLog($message) {
    $logFile = __DIR__ . '/cleanup.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    echo $logMessage; // 也输出到控制台
}

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    writeLog("=== 开始清理过期订单任务 ===");

    // 计算过期时间点
    $expireTime = date('Y-m-d H:i:s', time() - (EXPIRE_MINUTES * 60));
    writeLog("过期时间设置：" . EXPIRE_MINUTES . " 分钟，过期时间点：{$expireTime}");

    // 查询过期的待支付订单
    $selectSql = "
        SELECT id, order_no, user_id, product_title, total_amount, created_at 
        FROM orders 
        WHERE status = 'pending' 
        AND created_at < :expire_time
        ORDER BY created_at ASC
    ";

    $selectStmt = $pdo->prepare($selectSql);
    $selectStmt->bindParam(':expire_time', $expireTime);
    $selectStmt->execute();

    $expiredOrders = $selectStmt->fetchAll();
    $totalCount = count($expiredOrders);

    if ($totalCount == 0) {
        writeLog("没有找到过期的待支付订单");
        writeLog("=== 清理任务结束 ===");
        exit(0);
    }

    writeLog("找到 {$totalCount} 个过期的待支付订单");

    // 开始事务
    $pdo->beginTransaction();

    // 删除过期订单
    $deleteSql = "DELETE FROM orders WHERE status = 'pending' AND created_at < :expire_time";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->bindParam(':expire_time', $expireTime);
    $deleteStmt->execute();

    $deletedCount = $deleteStmt->rowCount();

    // 提交事务
    $pdo->commit();

    // 记录删除的订单信息
    writeLog("成功删除 {$deletedCount} 个过期订单：");
    foreach ($expiredOrders as $order) {
        writeLog("- 订单号：{$order['order_no']}, 用户ID：{$order['user_id']}, 商品：{$order['product_title']}, 金额：￥{$order['total_amount']}, 创建时间：{$order['created_at']}");
    }

    writeLog("=== 清理任务完成 ===");

} catch (PDOException $e) {
    // 数据库错误，回滚事务
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $errorMsg = "数据库错误：" . $e->getMessage();
    writeLog("❌ " . $errorMsg);
    
    // 可以选择发送邮件通知管理员
    error_log($errorMsg);
    exit(1);

} catch (Exception $e) {
    // 其他错误
    $errorMsg = "系统错误：" . $e->getMessage();
    writeLog("❌ " . $errorMsg);
    error_log($errorMsg);
    exit(1);
}

// 清理旧日志文件（保留最近7天的日志）
function cleanupOldLogs() {
    $logFile = __DIR__ . '/cleanup.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        
        // 只保留最近1000行日志
        if (count($lines) > 1000) {
            $recentLines = array_slice($lines, -1000);
            file_put_contents($logFile, implode("\n", $recentLines));
            writeLog("日志文件已清理，只保留最近1000行记录");
        }
    }
}

// 执行日志清理（每天执行一次）
$lastCleanupFile = __DIR__ . '/last_cleanup.txt';
$today = date('Y-m-d');

if (!file_exists($lastCleanupFile) || file_get_contents($lastCleanupFile) !== $today) {
    cleanupOldLogs();
    file_put_contents($lastCleanupFile, $today);
}

?> 