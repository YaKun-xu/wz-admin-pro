<?php
session_start();
require_once '../server/db_config.php';

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 数据库连接
$config = require '../server/db_config.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password'],
    $config['options']
);

$message = '';
$messageType = '';

// 处理表单提交
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $card_keys_text = trim($_POST['card_keys'] ?? '');
        
        if (empty($card_keys_text) || $product_id <= 0) {
            $message = '商品ID和卡密内容不能为空';
            $messageType = 'danger';
        } else {
            try {
                // 验证是否为卡密商品
                $stmt = $pdo->prepare("SELECT product_type FROM shop_products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$product) {
                    $message = '选择的商品不存在';
                    $messageType = 'danger';
                } elseif ($product['product_type'] != 2) {
                    $message = '只能为卡密商品添加卡密';
                    $messageType = 'danger';
                } else {
                    // 解析多行卡密，每行一个
                    $card_keys = array_filter(array_map('trim', explode("\n", $card_keys_text)), function($key) {
                        return !empty($key);
                    });
                    
                    if (empty($card_keys)) {
                        $message = '请输入至少一个卡密';
                        $messageType = 'danger';
                    } else {
                        $pdo->beginTransaction();
                        
                        $success_count = 0;
                        $duplicate_count = 0;
                        $errors = [];
                        
                        // 批量插入卡密
                        $insert_stmt = $pdo->prepare("INSERT INTO shop_card_keys (product_id, card_key, status) VALUES (?, ?, 0)");
                        $check_stmt = $pdo->prepare("SELECT id FROM shop_card_keys WHERE card_key = ?");
                        
                        foreach ($card_keys as $card_key) {
                            $card_key = trim($card_key);
                            if (empty($card_key)) continue;
                            
                            // 检查是否已存在
                            $check_stmt->execute([$card_key]);
                            $existing = $check_stmt->fetch();
                            
                            if ($existing) {
                                $duplicate_count++;
                            } else {
                                try {
                                    $insert_stmt->execute([$product_id, $card_key]);
                                    $success_count++;
                                } catch (PDOException $e) {
                                    if ($e->getCode() == 23000) {
                                        $duplicate_count++;
                                    } else {
                                        $errors[] = $card_key . ': ' . $e->getMessage();
                                    }
                                }
                            }
                        }
                        
                        $pdo->commit();
                        
                        // 生成结果消息
                        $message_parts = [];
                        if ($success_count > 0) {
                            $message_parts[] = "成功添加 {$success_count} 个卡密";
                        }
                        if ($duplicate_count > 0) {
                            $message_parts[] = "跳过 {$duplicate_count} 个重复卡密";
                        }
                        if (!empty($errors)) {
                            $message_parts[] = "失败 " . count($errors) . " 个卡密";
                        }
                        
                        $message = implode('，', $message_parts);
                        $messageType = $success_count > 0 ? 'success' : 'danger';
                    }
                }
            } catch (PDOException $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e->getCode() == 23000) {
                    $message = '部分卡密已存在，请检查后重试';
                } else {
                    $message = '添加失败：' . $e->getMessage();
                }
                $messageType = 'danger';
            } catch (Exception $e) {
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = '添加失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $card_key = trim($_POST['card_key'] ?? '');
        
        if (empty($card_key)) {
            $message = '卡密内容不能为空';
            $messageType = 'danger';
        } else {
            try {
                // 检查卡密是否与其他记录重复（排除当前记录）
                $stmt = $pdo->prepare("SELECT id FROM shop_card_keys WHERE card_key = ? AND id != ?");
                $stmt->execute([$card_key, $id]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $message = '该卡密已被其他记录使用，请使用其他卡密';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare("UPDATE shop_card_keys SET card_key = ? WHERE id = ?");
                    $stmt->execute([$card_key, $id]);
                    $message = '卡密更新成功';
                    $messageType = 'success';
                }
            } catch (PDOException $e) {
                // 捕获唯一约束冲突错误
                if ($e->getCode() == 23000) {
                    $message = '该卡密已被其他记录使用，请使用其他卡密';
                } else {
                    $message = '更新失败：' . $e->getMessage();
                }
                $messageType = 'danger';
            } catch (Exception $e) {
                $message = '更新失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM shop_card_keys WHERE id = ?");
            $stmt->execute([$id]);
            $message = '卡密删除成功';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 获取筛选条件
$filter_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$filter_status = isset($_GET['status']) ? intval($_GET['status']) : -1; // -1表示全部，0未使用，1已使用
$search_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // 每页显示数量
$offset = ($page - 1) * $limit;

// 获取卡密列表
$cardkeys = [];
$products = [];
$totalCardkeys = 0;
$totalPages = 1;

try {
    // 构建查询条件
    $whereConditions = [];
    $queryParams = [];
    
    // 商品筛选
    if ($filter_product_id > 0) {
        $whereConditions[] = "ck.product_id = ?";
        $queryParams[] = $filter_product_id;
    }
    
    // 状态筛选
    if ($filter_status >= 0) {
        $whereConditions[] = "ck.status = ?";
        $queryParams[] = $filter_status;
    }
    
    // 关键词搜索（搜索卡密内容）
    if (!empty($search_keyword)) {
        $whereConditions[] = "ck.card_key LIKE ?";
        $queryParams[] = '%' . $search_keyword . '%';
    }
    
    // 构建WHERE子句
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 获取总数
    $countSql = "SELECT COUNT(*) FROM shop_card_keys ck LEFT JOIN shop_products p ON ck.product_id = p.id {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($queryParams);
    $totalCardkeys = $countStmt->fetchColumn();
    $totalPages = ceil($totalCardkeys / $limit);
    
    // 执行查询（分页）
    $sql = "SELECT ck.*, p.title as product_title 
            FROM shop_card_keys ck 
            LEFT JOIN shop_products p ON ck.product_id = p.id 
            {$whereClause}
            ORDER BY ck.id DESC
            LIMIT ? OFFSET ?";
    
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $cardkeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 只获取卡密商品（product_type = 2）
    $stmt = $pdo->query("SELECT * FROM shop_products WHERE product_type = 2 ORDER BY title");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = '获取数据失败：' . $e->getMessage();
    $messageType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>卡密管理 - 王者荣耀查战力后台管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/table-enhanced.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content-wrapper {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .content-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .alert {
            border-radius: 15px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-unused {
            background: #d1fae5;
            color: #065f46;
        }

        .status-used {
            background: #fee2e2;
            color: #991b1b;
        }

        .pagination {
            justify-content: center;
        }

        .page-link {
            border-radius: 10px;
            margin: 0 2px;
            border: none;
            color: #667eea;
            padding: 0.5rem 1rem;
        }

        .page-link:hover {
            background: #667eea;
            color: white;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="main-content">
            <!-- 页面标题 -->
            <div class="page-header">
                <h1>
                    <i class="bi bi-key me-3"></i>卡密管理
                </h1>
                <p>管理商品卡密，添加、编辑、删除卡密</p>
            </div>

            <!-- 消息提示 -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 筛选和搜索 -->
            <div class="content-card">
                <h3 class="mb-3">
                    <i class="bi bi-funnel me-2"></i>筛选和搜索
                </h3>
                <form method="GET" id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-box-seam me-1"></i>商品筛选
                        </label>
                        <select class="form-select" name="product_id" id="filter_product_id">
                            <option value="0">全部商品</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>" <?php echo $filter_product_id == $product['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">
                            <i class="bi bi-toggle-on me-1"></i>状态筛选
                        </label>
                        <select class="form-select" name="status" id="filter_status">
                            <option value="-1" <?php echo $filter_status == -1 ? 'selected' : ''; ?>>全部状态</option>
                            <option value="0" <?php echo $filter_status == 0 ? 'selected' : ''; ?>>未使用</option>
                            <option value="1" <?php echo $filter_status == 1 ? 'selected' : ''; ?>>已使用</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-search me-1"></i>搜索卡密
                        </label>
                        <input type="text" class="form-control" name="keyword" id="search_keyword" 
                               value="<?php echo htmlspecialchars($search_keyword); ?>" 
                               placeholder="输入卡密内容进行搜索">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="w-100">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>搜索
                            </button>
                        </div>
                    </div>
                    <?php if ($filter_product_id > 0 || $filter_status >= 0 || !empty($search_keyword)): ?>
                    <div class="col-12">
                        <a href="cardkeys.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-1"></i>清除筛选
                        </a>
                        <span class="text-muted ms-2">
                            <i class="bi bi-info-circle me-1"></i>当前筛选：共 <?php echo $totalCardkeys; ?> 条记录
                        </span>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- 卡密列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>卡密列表
                        <?php if ($filter_product_id > 0 || $filter_status >= 0 || !empty($search_keyword)): ?>
                            <small class="text-muted">(已筛选)</small>
                        <?php endif; ?>
                    </h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle me-2"></i>添加卡密
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>商品名称</th>
                                <th>卡密内容</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cardkeys)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox me-2"></i>暂无数据
                                        <?php if ($filter_product_id > 0 || $filter_status >= 0 || !empty($search_keyword)): ?>
                                            <br><small>请尝试调整筛选条件</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cardkeys as $cardkey): ?>
                                    <tr>
                                        <td><?php echo $cardkey['id']; ?></td>
                                        <td><?php echo htmlspecialchars($cardkey['product_title'] ?? '未知商品'); ?></td>
                                        <td>
                                            <code><?php 
                                                $card_key_display = htmlspecialchars($cardkey['card_key']);
                                                // 如果有关键词搜索，高亮显示
                                                if (!empty($search_keyword)) {
                                                    $card_key_display = str_ireplace($search_keyword, '<mark>' . htmlspecialchars($search_keyword) . '</mark>', $card_key_display);
                                                }
                                                echo $card_key_display;
                                            ?></code>
                                        </td>
                                    <td>
                                        <span class="status-badge <?php echo $cardkey['status'] ? 'status-used' : 'status-unused'; ?>">
                                            <?php echo $cardkey['status'] ? '已使用' : '未使用'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary me-1" onclick="editCardKey(<?php echo htmlspecialchars(json_encode($cardkey)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCardKey(<?php echo $cardkey['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="卡密分页" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            共 <?php echo $totalCardkeys; ?> 条记录，第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页
                        </div>
                        <ul class="pagination mb-0">
                            <?php 
                            // 构建查询参数字符串
                            $queryParams = [];
                            if ($filter_product_id > 0) $queryParams['product_id'] = $filter_product_id;
                            if ($filter_status >= 0) $queryParams['status'] = $filter_status;
                            if (!empty($search_keyword)) $queryParams['keyword'] = $search_keyword;
                            $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>">
                                        <i class="bi bi-chevron-left"></i> 上一页
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>">
                                        下一页 <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 添加卡密模态框 -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>批量添加卡密
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-box-seam me-1"></i>选择商品 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="product_id" id="add_product_id" required>
                                <option value="">请选择卡密商品</option>
                                <?php if (empty($products)): ?>
                                    <option value="" disabled>暂无卡密商品，请先在商品管理中创建卡密商品</option>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>只显示卡密商品（product_type = 2）
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-key me-1"></i>卡密内容 <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="card_keys" id="add_card_keys" rows="10" required placeholder="请输入卡密，每行一个&#10;例如：&#10;card_key_001&#10;card_key_002&#10;card_key_003" style="font-family: monospace; font-size: 0.9rem;"></textarea>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>每行输入一个卡密，系统会自动识别并批量添加。重复的卡密将被自动跳过。
                            </small>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb me-1"></i>提示：可以直接从Excel或其他文件复制多行内容粘贴到此处
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>批量添加
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑卡密模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑卡密
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-key me-1"></i>卡密内容 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="card_key" id="edit_card_key" required placeholder="请输入卡密内容">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>保存修改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    确定要删除这个卡密吗？此操作不可撤销。
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCardKey(cardkey) {
            document.getElementById('edit_id').value = cardkey.id;
            document.getElementById('edit_card_key').value = cardkey.card_key;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteCardKey(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // 重置添加表单
        const addModal = document.getElementById('addModal');
        if (addModal) {
            addModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('addForm').reset();
            });
        }

        // 自动隐藏提示消息
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // 搜索框支持回车键搜索
        const searchKeyword = document.getElementById('search_keyword');
        if (searchKeyword) {
            searchKeyword.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('filterForm').submit();
                }
            });
        }

        // 筛选条件改变时自动提交（可选，如果不需要可以注释掉）
        // const filterProduct = document.getElementById('filter_product_id');
        // const filterStatus = document.getElementById('filter_status');
        // if (filterProduct) {
        //     filterProduct.addEventListener('change', function() {
        //         document.getElementById('filterForm').submit();
        //     });
        // }
        // if (filterStatus) {
        //     filterStatus.addEventListener('change', function() {
        //         document.getElementById('filterForm').submit();
        //     });
        // }
    </script>
</body>
</html>
