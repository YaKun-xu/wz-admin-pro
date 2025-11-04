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
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($title) || $price <= 0) {
            $message = '商品标题和价格不能为空';
            $messageType = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 插入商品
                $stmt = $pdo->prepare("INSERT INTO shop_products (title, description, price, category_id, product_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $description, $price, $category_id, $product_type]);
                $product_id = $pdo->lastInsertId();
                
                // 如果有图片URL，插入图片
                if (!empty($image_url)) {
                    $stmt = $pdo->prepare("INSERT INTO shop_product_images (product_id, image_url, sort_order, created_at) VALUES (?, ?, 0, NOW())");
                    $stmt->execute([$product_id, $image_url]);
                }
                
                $pdo->commit();
                $message = '商品添加成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
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
        $image_url = trim($_POST['image_url'] ?? '');
        
        if (empty($title) || $price <= 0) {
            $message = '商品标题和价格不能为空';
            $messageType = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 更新商品
                $stmt = $pdo->prepare("UPDATE shop_products SET title = ?, description = ?, price = ?, category_id = ?, product_type = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $description, $price, $category_id, $product_type, $id]);
                
                // 处理图片
                if (!empty($image_url)) {
                    // 检查是否已有图片
                    $stmt = $pdo->prepare("SELECT id FROM shop_product_images WHERE product_id = ? LIMIT 1");
                    $stmt->execute([$id]);
                    $existing_image = $stmt->fetch();
                    
                    if ($existing_image) {
                        // 更新现有图片
                        $stmt = $pdo->prepare("UPDATE shop_product_images SET image_url = ? WHERE id = ?");
                        $stmt->execute([$image_url, $existing_image['id']]);
                    } else {
                        // 插入新图片
                        $stmt = $pdo->prepare("INSERT INTO shop_product_images (product_id, image_url, sort_order, created_at) VALUES (?, ?, 0, NOW())");
                        $stmt->execute([$id, $image_url]);
                    }
                }
                
                $pdo->commit();
                $message = '商品更新成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
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

try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name, 
               (SELECT image_url FROM shop_product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) as cover_image,
               CASE p.product_type 
                   WHEN 1 THEN '普通商品' 
                   WHEN 2 THEN '卡密商品' 
                   ELSE '未知' 
               END as product_type_name
        FROM shop_products p 
        LEFT JOIN shop_categories c ON p.category_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT * FROM shop_categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                <th>ID</th>
                                <th>图片</th>
                                <th>标题</th>
                                <th>分类</th>
                                <th>类型</th>
                                <th>价格</th>
                                <th>描述</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['id']; ?></td>
                                    <td>
                                        <?php if (!empty($product['cover_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['cover_image']); ?>" 
                                                 alt="商品图片" 
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                                                 onclick="previewImage('<?php echo htmlspecialchars($product['cover_image']); ?>')">
                                        <?php else: ?>
                                            <span class="text-muted">无图片</span>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>描述
                            </label>
                            <textarea class="form-control" name="description" id="add_description" rows="3" placeholder="请输入商品描述（可选）"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>商品图片URL
                            </label>
                            <input type="url" class="form-control" name="image_url" id="add_image_url" placeholder="https://example.com/image.jpg">
                            <small class="form-text text-muted">
                                <i class="bi bi-link-45deg me-1"></i>请输入图片的完整URL地址
                            </small>
                            <div id="add_image_preview" class="mt-3" style="display: none;">
                                <div class="border rounded p-3" style="background: #f8f9fa;">
                                    <p class="mb-2 text-muted small">图片预览：</p>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>描述
                            </label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" placeholder="请输入商品描述（可选）"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>商品图片URL
                            </label>
                            <input type="url" class="form-control" name="image_url" id="edit_image_url" placeholder="https://example.com/image.jpg">
                            <small class="form-text text-muted">
                                <i class="bi bi-link-45deg me-1"></i>请输入图片的完整URL地址
                            </small>
                            <div id="edit_image_preview" class="mt-3" style="display: none;">
                                <div class="border rounded p-3" style="background: #f8f9fa;">
                                    <p class="mb-2 text-muted small">图片预览：</p>
                                    <img id="edit_image_preview_img" src="" alt="预览" style="max-width: 100%; max-height: 300px; border-radius: 8px; border: 1px solid #dee2e6;">
                                </div>
                            </div>
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
            document.getElementById('edit_description').value = product.description || '';
            
            // 获取商品图片
            fetch('products.php?action=get_image&id=' + product.id)
                .then(response => response.json())
                .then(data => {
                    if (data.image_url) {
                        document.getElementById('edit_image_url').value = data.image_url;
                        updateImagePreview(data.image_url, 'edit_image_preview');
                    } else {
                        document.getElementById('edit_image_url').value = '';
                        document.getElementById('edit_image_preview').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('获取图片失败:', error);
                });
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
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
