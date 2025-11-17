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
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $product_type = intval($_POST['product_type'] ?? 1); // 默认普通商品
        $status = intval($_POST['status'] ?? 1); // 默认上架
        $sort_order = intval($_POST['sort_order'] ?? 0); // 排序权重
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($title) || $price <= 0) {
            $message = '商品标题和价格不能为空';
            $messageType = 'danger';
        } else {
            try {
                // 插入商品（只设置封面图片，不自动插入到商品图片表）
                $stmt = $pdo->prepare("INSERT INTO shop_products (title, description, price, category_id, product_type, status, sort_order, cover_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $price, $category_id, $product_type, $status, $sort_order, $image_url]);
                $product_id = $pdo->lastInsertId();
                
                $message = '商品添加成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '添加失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $product_type = intval($_POST['product_type'] ?? 1);
        $status = intval($_POST['status'] ?? 1);
        $sort_order = intval($_POST['sort_order'] ?? 0); // 排序权重
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($title) || $price <= 0) {
            $message = '商品标题和价格不能为空';
            $messageType = 'danger';
        } else {
            try {
                // 更新商品（只更新封面图片，不自动更新商品图片表）
                $stmt = $pdo->prepare("UPDATE shop_products SET title = ?, description = ?, price = ?, category_id = ?, product_type = ?, status = ?, sort_order = ?, cover_image = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $description, $price, $category_id, $product_type, $status, $sort_order, $image_url, $id]);
                
                $message = '商品更新成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '更新失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM shop_products WHERE id = ?");
            $stmt->execute([$id]);
            $message = '商品删除成功';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // 获取当前状态
            $stmt = $pdo->prepare("SELECT status FROM shop_products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // 切换状态：1变0，0变1
                $new_status = $product['status'] == 1 ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE shop_products SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                $message = $new_status == 1 ? '商品已上架' : '商品已下架';
                $messageType = 'success';
            } else {
                $message = '商品不存在';
                $messageType = 'danger';
            }
        } catch (Exception $e) {
            $message = '操作失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($action === 'update_sort_order') {
        $id = intval($_POST['id'] ?? 0);
        $sort_order = intval($_POST['sort_order'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE shop_products SET sort_order = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$sort_order, $id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '排序权重更新成功']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
}

// 获取商品列表
$products = [];
$categories = [];

// 处理获取图片请求
if (isset($_GET['action']) && $_GET['action'] === 'get_image') {
    $id = intval($_GET['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("SELECT image_url FROM shop_product_images WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1");
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode(['image_url' => $image['image_url'] ?? '']);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['image_url' => '']);
        exit;
    }
}

// 处理获取商品图片列表请求
if (isset($_GET['action']) && $_GET['action'] === 'get_product_images') {
    $id = intval($_GET['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("SELECT id, image_url, sort_order FROM shop_product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'images' => $images]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'images' => [], 'message' => $e->getMessage()]);
        exit;
    }
}

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // 每页显示数量
$offset = ($page - 1) * $limit;

try {
    // 获取总数
    $countSql = "SELECT COUNT(*) FROM shop_products p LEFT JOIN shop_categories c ON p.category_id = c.id";
    $countStmt = $pdo->query($countSql);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);
    
    // 获取分页数据
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, 
               (SELECT image_url FROM shop_product_images WHERE product_id = p.id AND sort_order = 0 LIMIT 1) as main_image,
               p.cover_image,
               CASE p.product_type 
                   WHEN 1 THEN '普通商品' 
                   WHEN 2 THEN '卡密商品' 
                   ELSE '未知' 
               END as product_type_name
        FROM shop_products p 
        LEFT JOIN shop_categories c ON p.category_id = c.id 
        ORDER BY p.status DESC, p.sort_order ASC, p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM shop_categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = '获取数据失败：' . $e->getMessage();
    $messageType = 'danger';
    $totalProducts = 0;
    $totalPages = 1;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品管理 - 王者荣耀查战力后台管理系统</title>
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

        .image-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
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
                    <i class="bi bi-box-seam me-3"></i>商品管理
                </h1>
                <p>管理商品信息，添加、编辑、删除商品</p>
            </div>

            <!-- 消息提示 -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 商品列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>商品列表
                    </h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle me-2"></i>添加商品
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>主图</th>
                                <th>封面</th>
                                <th>标题</th>
                                <th>分类</th>
                                <th>类型</th>
                                <th>价格</th>
                                <th>排序权重</th>
                                <th>状态</th>
                                <th>描述</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                 alt="主图" 
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                                 onclick="previewImage('<?php echo htmlspecialchars($product['main_image']); ?>')"
                                                 title="主图">
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">无主图</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['cover_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['cover_image']); ?>" 
                                                 alt="封面" 
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                                 onclick="previewImage('<?php echo htmlspecialchars($product['cover_image']); ?>')"
                                                 title="封面">
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">无封面</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? '未分类'); ?></td>
                                    <td>
                                        <?php if ($product['product_type'] == 1): ?>
                                            <span class="badge bg-primary">普通商品</span>
                                        <?php elseif ($product['product_type'] == 2): ?>
                                            <span class="badge bg-success">卡密商品</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">未知</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>¥<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <input type="number" 
                                               class="form-control form-control-sm sort-order-input" 
                                               style="width: 80px; display: inline-block;"
                                               value="<?php echo intval($product['sort_order'] ?? 0); ?>" 
                                               data-id="<?php echo $product['id']; ?>"
                                               onchange="updateSortOrder(<?php echo $product['id']; ?>, this.value)">
                                    </td>
                                    <td>
                                        <?php if (($product['status'] ?? 1) == 1): ?>
                                            <span class="badge bg-success" style="cursor: pointer; transition: all 0.3s ease;" 
                                                  onclick="toggleStatus(<?php echo $product['id']; ?>, <?php echo ($product['status'] ?? 1); ?>)" 
                                                  onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)';" 
                                                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';" 
                                                  title="点击切换为下架">
                                                <i class="bi bi-check-circle me-1"></i>上架
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="cursor: pointer; transition: all 0.3s ease;" 
                                                  onclick="toggleStatus(<?php echo $product['id']; ?>, <?php echo ($product['status'] ?? 1); ?>)" 
                                                  onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)';" 
                                                  onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';" 
                                                  title="点击切换为上架">
                                                <i class="bi bi-x-circle me-1"></i>下架
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['description'] ?? ''); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary me-1" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="商品分页" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            共 <?php echo $totalProducts; ?> 条记录，第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页
                        </div>
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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

    <!-- 添加商品模态框 -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>添加商品
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-tag me-1"></i>商品标题 <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="title" id="add_title" required placeholder="请输入商品标题">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-currency-yen me-1"></i>价格 <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="price" id="add_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-folder me-1"></i>分类 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="category_id" id="add_category_id" required>
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-box-seam me-1"></i>商品类型 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="product_type" id="add_product_type" required>
                                    <option value="1" selected>普通商品</option>
                                    <option value="2">卡密商品</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>普通商品：手动处理订单；卡密商品：自动发放卡密
                                </small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>上架状态 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="status" id="add_status" required>
                                    <option value="1" selected>上架</option>
                                    <option value="0">下架</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>上架后商品将显示在小程序中，下架后用户无法购买
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-sort-numeric-down me-1"></i>排序权重
                                </label>
                                <input type="number" class="form-control" name="sort_order" id="add_sort_order" value="0" min="0" placeholder="数字越小越靠前">
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>数字越小，商品在列表中越靠前显示
                                </small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>描述
                            </label>
                            <textarea class="form-control" name="description" id="add_description" rows="3" placeholder="请输入商品描述（可选）"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>封面图片URL
                            </label>
                            <input type="url" class="form-control" name="image_url" id="add_image_url" placeholder="https://example.com/image.jpg">
                            <small class="form-text text-muted">
                                <i class="bi bi-link-45deg me-1"></i>封面图片用于商品列表展示，请输入图片的完整URL地址。主图请在商品添加后通过"商品图片管理"功能单独设置。
                            </small>
                            <div id="add_image_preview" class="mt-3" style="display: none;">
                                <div class="border rounded p-3" style="background: #f8f9fa;">
                                    <p class="mb-2 text-muted small">封面图片预览：</p>
                                    <img id="add_image_preview_img" src="" alt="预览" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>添加商品
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑商品模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑商品
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-tag me-1"></i>商品标题 <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="title" id="edit_title" required placeholder="请输入商品标题">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-currency-yen me-1"></i>价格 <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-folder me-1"></i>分类 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-box-seam me-1"></i>商品类型 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="product_type" id="edit_product_type" required>
                                    <option value="1">普通商品</option>
                                    <option value="2">卡密商品</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>普通商品：手动处理订单；卡密商品：自动发放卡密
                                </small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>上架状态 <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="status" id="edit_status" required>
                                    <option value="1">上架</option>
                                    <option value="0">下架</option>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>上架后商品将显示在小程序中，下架后用户无法购买
                                </small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-sort-numeric-down me-1"></i>排序权重
                                </label>
                                <input type="number" class="form-control" name="sort_order" id="edit_sort_order" value="0" min="0" placeholder="数字越小越靠前">
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>数字越小，商品在列表中越靠前显示
                                </small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>描述
                            </label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" placeholder="请输入商品描述（可选）"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>封面图片URL
                            </label>
                            <div class="input-group">
                                <input type="url" class="form-control" name="image_url" id="edit_image_url" placeholder="https://example.com/image.jpg">
                                <button type="button" class="btn btn-outline-secondary" onclick="loadProductImages()" title="从商品图片中选择">
                                    <i class="bi bi-images"></i> 选择图片
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                <i class="bi bi-link-45deg me-1"></i>封面图片用于商品列表展示，请输入图片的完整URL地址
                            </small>
                            <div id="edit_image_preview" class="mt-3" style="display: none;">
                                <div class="border rounded p-3" style="background: #f8f9fa;">
                                    <p class="mb-2 text-muted small">封面图片预览：</p>
                                    <img id="edit_image_preview_img" src="" alt="预览" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 商品图片管理区域 -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">
                                    <i class="bi bi-images me-1"></i>商品图片管理
                                </label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadProductImages()">
                                        <i class="bi bi-arrow-clockwise me-1"></i>刷新
                                    </button>
                                    <a href="product_images.php?product_id=" id="edit_manage_images_link" class="btn btn-sm btn-outline-info" target="_blank">
                                        <i class="bi bi-gear me-1"></i>管理图片
                                    </a>
                                </div>
                            </div>
                            <div id="edit_product_images_list" class="border rounded p-3" style="background: #f8f9fa; min-height: 100px;">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-hourglass-split me-2"></i>加载中...
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>主图：商品图片表中 sort_order = 0 的图片（通过"商品图片管理"功能设置） | 封面：商品表的 cover_image 字段（通过上方"封面图片URL"设置）。主图和封面是独立的，可以分别设置。
                            </small>
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
                    确定要删除这个商品吗？此操作不可撤销。
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

    <!-- 上下架确认模态框 -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" id="toggleStatusHeader" style="border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" id="toggleStatusTitle" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi" id="toggleStatusIcon"></i>
                        <span id="toggleStatusText"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p class="mb-0" id="toggleStatusMessage" style="font-size: 1rem; line-height: 1.6;"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>取消
                    </button>
                    <form method="POST" style="display: inline;" id="toggleStatusForm">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="id" id="toggle_status_id">
                        <button type="submit" class="btn" id="toggleStatusBtn">
                            <i class="bi me-2" id="toggleStatusBtnIcon"></i>
                            <span id="toggleStatusBtnText"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 图片预览模态框 -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">图片预览</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="preview_image" src="" alt="预览" style="max-width: 100%; max-height: 70vh; border-radius: 8px;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_title').value = product.title;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_product_type').value = product.product_type || 1;
            document.getElementById('edit_status').value = product.status ?? 1;
            document.getElementById('edit_sort_order').value = product.sort_order ?? 0;
            document.getElementById('edit_description').value = product.description || '';
            
            // 直接使用封面图片
            if (product.cover_image) {
                document.getElementById('edit_image_url').value = product.cover_image;
                updateImagePreview(product.cover_image, 'edit_image_preview');
            } else {
                document.getElementById('edit_image_url').value = '';
                document.getElementById('edit_image_preview').style.display = 'none';
            }
            
            // 更新管理图片链接
            const manageLink = document.getElementById('edit_manage_images_link');
            if (manageLink) {
                manageLink.href = 'product_images.php?product_id=' + product.id;
            }
            
            // 加载商品图片列表
            loadProductImages();
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function loadProductImages() {
            const productId = document.getElementById('edit_id').value;
            if (!productId) {
                document.getElementById('edit_product_images_list').innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-exclamation-circle me-2"></i>请先选择商品</div>';
                return;
            }
            
            const imagesList = document.getElementById('edit_product_images_list');
            imagesList.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split me-2"></i>加载中...</div>';
            
            fetch('products.php?action=get_product_images&id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.images && data.images.length > 0) {
                        let html = '<div class="row g-2">';
                        data.images.forEach((img, index) => {
                            const isMain = img.sort_order == 0;
                            html += `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card position-relative" style="border: ${isMain ? '2px solid #28a745' : '1px solid #dee2e6'};">
                                        ${isMain ? '<span class="badge bg-success position-absolute top-0 start-0 m-1">主图</span>' : ''}
                                        <img src="${img.image_url}" class="card-img-top" style="height: 120px; object-fit: cover; cursor: pointer;" 
                                             onclick="previewImage('${img.image_url}')" 
                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3E图片加载失败%3C/text%3E%3C/svg%3E'">
                                        <div class="card-body p-2">
                                            <div class="btn-group w-100" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="setAsCover('${img.image_url}')" 
                                                        title="设为封面">
                                                    <i class="bi bi-image"></i>
                                                </button>
                                                ${!isMain ? `<button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="setAsMain(${img.id}, ${productId})" 
                                                        title="设为主图">
                                                    <i class="bi bi-star"></i>
                                                </button>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        imagesList.innerHTML = html;
                    } else {
                        imagesList.innerHTML = `
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-image me-2"></i>暂无图片
                                <br>
                                <small><a href="product_images.php?product_id=${productId}" target="_blank" class="text-decoration-none">点击添加图片</a></small>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('加载图片失败:', error);
                    imagesList.innerHTML = '<div class="text-center text-danger py-3"><i class="bi bi-exclamation-triangle me-2"></i>加载失败，请刷新重试</div>';
                });
        }
        
        function setAsCover(imageUrl) {
            document.getElementById('edit_image_url').value = imageUrl;
            updateImagePreview(imageUrl, 'edit_image_preview');
            // 显示提示
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>已选择为封面图片，请点击"保存修改"按钮保存
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => alertDiv.remove(), 3000);
        }
        
        function setAsMain(imageId, productId) {
            if (!confirm('确定要将此图片设为主图吗？')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'set_main');
            formData.append('id', imageId);
            formData.append('product_id', productId);
            
            fetch('product_images.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 重新加载图片列表
                    loadProductImages();
                    // 显示成功提示
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>${data.message || '主图设置成功'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    setTimeout(() => alertDiv.remove(), 3000);
                } else {
                    alert('设置失败：' + (data.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('设置主图失败:', error);
                alert('设置失败，请重试');
            });
        }
        
        function updateSortOrder(id, sortOrder) {
            const formData = new FormData();
            formData.append('action', 'update_sort_order');
            formData.append('id', id);
            formData.append('sort_order', sortOrder);
            
            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 显示成功提示
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    
                    // 3秒后自动移除
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 3000);
                    
                    // 刷新页面以更新排序
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    alert('更新失败：' + data.message);
                }
            })
            .catch(error => {
                console.error('更新排序权重失败:', error);
                alert('更新失败，请重试');
            });
        }

        function previewImage(imageUrl) {
            document.getElementById('preview_image').src = imageUrl;
            new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
        }

        function updateImagePreview(imageUrl, previewId) {
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId + '_img');
            if (imageUrl && imageUrl.trim() !== '') {
                previewImg.src = imageUrl;
                preview.style.display = 'block';
                // 图片加载错误处理
                previewImg.onerror = function() {
                    preview.innerHTML = '<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>图片加载失败，请检查URL是否正确</div>';
                };
            } else {
                preview.style.display = 'none';
            }
        }

        // 添加商品表单图片预览
        document.addEventListener('DOMContentLoaded', function() {
            const addImageInput = document.getElementById('add_image_url');
            if (addImageInput) {
                addImageInput.addEventListener('input', function() {
                    updateImagePreview(this.value.trim(), 'add_image_preview');
                });
            }
            
            // 重置添加表单
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('addForm').reset();
                    document.getElementById('add_image_preview').style.display = 'none';
                });
            }

            // 编辑商品表单图片预览
            const editImageInput = document.getElementById('edit_image_url');
            if (editImageInput) {
                editImageInput.addEventListener('input', function() {
                    updateImagePreview(this.value.trim(), 'edit_image_preview');
                });
            }
        });

        function deleteProduct(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function toggleStatus(id, currentStatus) {
            const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
            const isUp = currentStatus == 1;
            
            // 设置标题和图标
            const header = document.getElementById('toggleStatusHeader');
            const title = document.getElementById('toggleStatusTitle');
            const icon = document.getElementById('toggleStatusIcon');
            const message = document.getElementById('toggleStatusMessage');
            const btn = document.getElementById('toggleStatusBtn');
            const btnIcon = document.getElementById('toggleStatusBtnIcon');
            const btnText = document.getElementById('toggleStatusBtnText');
            const form = document.getElementById('toggleStatusForm');
            
            if (isUp) {
                // 下架操作
                header.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
                icon.className = 'bi bi-arrow-down-circle me-2';
                title.innerHTML = '<i class="bi bi-arrow-down-circle me-2"></i>确认下架';
                message.textContent = '确定要将商品下架吗？下架后商品将不再显示在小程序中，用户将无法购买此商品。';
                btn.className = 'btn btn-warning';
                btnIcon.className = 'bi bi-arrow-down-circle';
                btnText.textContent = '确认下架';
            } else {
                // 上架操作
                header.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                icon.className = 'bi bi-arrow-up-circle me-2';
                title.innerHTML = '<i class="bi bi-arrow-up-circle me-2"></i>确认上架';
                message.textContent = '确定要将商品上架吗？上架后商品将显示在小程序中，用户将可以购买此商品。';
                btn.className = 'btn btn-success';
                btnIcon.className = 'bi bi-arrow-up-circle';
                btnText.textContent = '确认上架';
            }
            
            document.getElementById('toggle_status_id').value = id;
            modal.show();
        }

        // 自动隐藏提示消息
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
