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
        $image_url = trim($_POST['image_url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (empty($product_id) || empty($image_url)) {
            $message = '请选择商品并输入图片URL';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO shop_product_images (product_id, image_url, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$product_id, $image_url, $sort_order]);
                $message = '图片添加成功';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '添加失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        
        if (empty($id) || empty($product_id) || empty($image_url)) {
            $message = '请填写完整信息';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE shop_product_images SET product_id = ?, image_url = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$product_id, $image_url, $sort_order, $id]);
                $message = '图片更新成功';
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
            $stmt = $pdo->prepare("DELETE FROM shop_product_images WHERE id = ?");
            $stmt->execute([$id]);
            $message = '图片删除成功';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($action === 'set_main') {
        $id = intval($_POST['id'] ?? 0);
        $product_id = intval($_POST['product_id'] ?? 0);
        
        // 检查是否是 AJAX 请求
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        try {
            $pdo->beginTransaction();
            
            // 将该商品的所有图片排序设为较大值
            $stmt = $pdo->prepare("UPDATE shop_product_images SET sort_order = sort_order + 1000 WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            // 将当前图片设为最小排序（主图）
            $stmt = $pdo->prepare("UPDATE shop_product_images SET sort_order = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            $message = '主图设置成功';
            $messageType = 'success';
            
            // 如果是 AJAX 请求，返回 JSON
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $message]);
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '设置失败：' . $e->getMessage();
            $messageType = 'danger';
            
            // 如果是 AJAX 请求，返回 JSON
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }
    }
    
    if ($action === 'update_sort_order') {
        $id = intval($_POST['id'] ?? 0);
        $sort_order = intval($_POST['sort_order'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE shop_product_images SET sort_order = ? WHERE id = ?");
            $stmt->execute([$sort_order, $id]);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => '排序更新成功']);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
            exit;
        }
    }
}

// 获取筛选条件
$filter_product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// 分页参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // 每页显示数量
$offset = ($page - 1) * $limit;

// 获取商品图片列表
$images = [];
$products = [];
$totalImages = 0;
$totalPages = 1;

try {
    // 获取所有商品
    $stmt = $pdo->query("SELECT id, title FROM shop_products ORDER BY title");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 构建查询
    $sql = "
        SELECT 
            pi.*,
            p.title as product_title
        FROM shop_product_images pi
        LEFT JOIN shop_products p ON pi.product_id = p.id
    ";
    
    $params = [];
    if ($filter_product_id > 0) {
        $sql .= " WHERE pi.product_id = ?";
        $params[] = $filter_product_id;
    }
    
    // 获取总数
    $countSql = "SELECT COUNT(*) FROM shop_product_images pi LEFT JOIN shop_products p ON pi.product_id = p.id" . ($filter_product_id > 0 ? " WHERE pi.product_id = ?" : "");
    $countStmt = $pdo->prepare($countSql);
    if ($filter_product_id > 0) {
        $countStmt->execute([$filter_product_id]);
    } else {
        $countStmt->execute();
    }
    $totalImages = $countStmt->fetchColumn();
    $totalPages = ceil($totalImages / $limit);
    
    $sql .= " ORDER BY pi.product_id ASC, pi.sort_order ASC, pi.id ASC LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>商品图片管理 - 王者荣耀查战力后台管理系统</title>
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

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
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

        .image-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-thumbnail:hover {
            transform: scale(1.1);
        }

        .badge-main {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
                    <i class="bi bi-images me-3"></i>商品图片管理
                </h1>
                <p>管理商品图片，添加、编辑、删除商品图片</p>
            </div>

            <!-- 消息提示 -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 筛选和操作 -->
            <div class="content-card">
                <div class="filter-section">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-funnel me-1"></i>按商品筛选
                            </label>
                            <select class="form-select" name="product_id" onchange="this.form.submit()">
                                <option value="0">全部商品</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $filter_product_id == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="bi bi-plus-circle me-2"></i>添加图片
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 图片列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>图片列表
                        <span class="badge bg-primary ms-2"><?php echo count($images); ?></span>
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>图片</th>
                                <th>商品</th>
                                <th>图片URL</th>
                                <th>排序</th>
                                <th>是否主图</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($images)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3">暂无图片数据</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($images as $image): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                 alt="商品图片" 
                                                 class="image-thumbnail"
                                                 onclick="previewImage('<?php echo htmlspecialchars($image['image_url']); ?>')"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect width=\'80\' height=\'80\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3E图片加载失败%3C/text%3E%3C/svg%3E'">
                                        </td>
                                        <td><?php echo htmlspecialchars($image['product_title'] ?? '未知商品'); ?></td>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($image['image_url']); ?>">
                                                <?php echo htmlspecialchars($image['image_url']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm sort-order-input" 
                                                   style="width: 80px; display: inline-block;"
                                                   value="<?php echo intval($image['sort_order'] ?? 0); ?>" 
                                                   data-id="<?php echo $image['id']; ?>"
                                                   onchange="updateSortOrder(<?php echo $image['id']; ?>, this.value)">
                                        </td>
                                        <td>
                                            <?php if (($image['sort_order'] ?? 0) == 0): ?>
                                                <span class="badge badge-main">
                                                    <i class="bi bi-star-fill me-1"></i>主图
                                                </span>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="setMainImage(<?php echo $image['id']; ?>, <?php echo $image['product_id']; ?>)">
                                                    <i class="bi bi-star me-1"></i>设为主图
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($image['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-secondary me-1" onclick="editImage(<?php echo htmlspecialchars(json_encode($image)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteImage(<?php echo $image['id']; ?>)">
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
                <nav aria-label="图片分页" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            共 <?php echo $totalImages; ?> 条记录，第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页
                        </div>
                        <ul class="pagination mb-0">
                            <?php 
                            // 构建查询参数字符串
                            $queryString = $filter_product_id > 0 ? '&product_id=' . $filter_product_id : '';
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

    <!-- 添加图片模态框 -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>添加图片
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
                                <option value="">请选择商品</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>图片URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" name="image_url" id="add_image_url" required placeholder="https://example.com/image.jpg">
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-down me-1"></i>排序权重
                            </label>
                            <input type="number" class="form-control" name="sort_order" id="add_sort_order" value="0" min="0" placeholder="数字越小越靠前，0为主图">
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>数字越小，图片在列表中越靠前显示。设为0将作为主图
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>添加图片
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑图片模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑图片
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-box-seam me-1"></i>选择商品 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="product_id" id="edit_product_id" required>
                                <option value="">请选择商品</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-1"></i>图片URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" name="image_url" id="edit_image_url" required placeholder="https://example.com/image.jpg">
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-down me-1"></i>排序权重
                            </label>
                            <input type="number" class="form-control" name="sort_order" id="edit_sort_order" value="0" min="0" placeholder="数字越小越靠前，0为主图">
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>数字越小，图片在列表中越靠前显示。设为0将作为主图
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
                    确定要删除这张图片吗？此操作不可撤销。
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
        function editImage(image) {
            document.getElementById('edit_id').value = image.id;
            document.getElementById('edit_product_id').value = image.product_id;
            document.getElementById('edit_image_url').value = image.image_url;
            document.getElementById('edit_sort_order').value = image.sort_order || 0;
            updateImagePreview(image.image_url, 'edit_image_preview');
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function updateSortOrder(id, sortOrder) {
            const formData = new FormData();
            formData.append('action', 'update_sort_order');
            formData.append('id', id);
            formData.append('sort_order', sortOrder);
            
            fetch('product_images.php', {
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

        function setMainImage(id, productId) {
            if (!confirm('确定要将此图片设为主图吗？')) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="set_main">
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="product_id" value="${productId}">
            `;
            document.body.appendChild(form);
            form.submit();
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

        function deleteImage(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
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