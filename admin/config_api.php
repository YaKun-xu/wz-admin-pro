<?php
session_start();
require_once '../server/db_config.php';

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => '未授权访问']);
    exit;
}

// 数据库连接
$config = require '../server/db_config.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password']
);

$action = $_GET['action'] ?? '';

if ($action === 'get_configs') {
    $page = $_GET['page'] ?? '';
    
    if (empty($page)) {
        echo json_encode(['error' => '缺少页面参数']);
        exit;
    }
    
    $sql = "SELECT * FROM configs WHERE page_name = ? ORDER BY config_key";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$page]);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $configs]);
    exit;
}

if ($action === 'update_config') {
    $config_key = $_POST['config_key'] ?? '';
    $config_value = $_POST['config_value'] ?? '';
    
    if (empty($config_key)) {
        echo json_encode(['error' => '缺少配置键']);
        exit;
    }
    
    $sql = "UPDATE configs SET config_value = ?, updated_at = NOW() WHERE config_key = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$config_value, $config_key]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '配置更新成功']);
    } else {
        echo json_encode(['error' => '配置更新失败']);
    }
    exit;
}

echo json_encode(['error' => '无效的操作']);
?>
