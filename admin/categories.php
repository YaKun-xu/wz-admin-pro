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
    $config['password']
);

// 搜索功能
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE name LIKE ?";
    $params = ["%$search%"];
}

// 分页
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 获取总数
$countSql = "SELECT COUNT(*) FROM shop_categories $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCategories = $countStmt->fetchColumn();

$totalPages = ceil($totalCategories / $limit);

// 处理表单提交
$message = '';
$messageType = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        
        if (empty($name)) {
            $message = '分类名称不能为空';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO shop_categories (name, sort_order, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $sort_order, $status]);
                $message = '分类添加成功';
                $messageType = 'success';
                
                // 刷新页面
                header('Location: categories.php?' . http_build_query($_GET));
                exit;
            } catch (Exception $e) {
                $message = '添加失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $status = intval($_POST['status'] ?? 1);
        
        if (empty($name)) {
            $message = '分类名称不能为空';
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE shop_categories SET name = ?, sort_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $sort_order, $status, $id]);
                $message = '分类更新成功';
                $messageType = 'success';
                
                // 刷新页面
                header('Location: categories.php?' . http_build_query($_GET));
                exit;
            } catch (Exception $e) {
                $message = '更新失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // 检查是否有商品使用此分类
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM shop_products WHERE category_id = ?");
            $checkStmt->execute([$id]);
            $productCount = $checkStmt->fetchColumn();
            
            if ($productCount > 0) {
                $message = '删除失败：该分类下还有 ' . $productCount . ' 个商品，请先处理这些商品';
                $messageType = 'danger';
            } else {
                $stmt = $pdo->prepare("DELETE FROM shop_categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = '分类删除成功';
                $messageType = 'success';
                
                // 刷新页面
                header('Location: categories.php?' . http_build_query($_GET));
                exit;
            }
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 获取分类列表
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM shop_products WHERE category_id = c.id) as product_count
        FROM shop_categories c 
        $whereClause 
        ORDER BY c.sort_order ASC, c.id ASC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 王者荣耀查战力后台管理系统</title>
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

        .search-form {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }

        .table tbody td {
            padding: 1rem;
            border-color: #f8f9fa;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }

        .badge-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }

        .pagination {
            justify-content: center;
        }

        .page-link {
            border-radius: 10px;
            margin: 0 2px;
            border: none;
            color: #667eea;
        }

        .page-link:hover {
            background: #667eea;
            color: white;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
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
                    <i class="bi bi-folder me-3"></i>分类管理
                </h1>
                <p>管理商品分类信息</p>
            </div>
            
            <!-- 消息提示 -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- 搜索表单 -->
            <div class="search-form">
                <form method="GET" class="row g-3">
                    <div class="col-md-9">
                        <input type="text" class="form-control" name="search" id="search_input" 
                               placeholder="请输入分类名称搜索..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>搜索
                            </button>
                            <a href="categories.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>重置
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 分类列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        分类列表
                        <?php if (!empty($search)): ?>
                            <span class="badge badge-info ms-2">搜索结果: <?php echo $totalCategories; ?> 个</span>
                        <?php else: ?>
                            <span class="badge badge-info ms-2">共 <?php echo $totalCategories; ?> 个分类</span>
                        <?php endif; ?>
                    </h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle me-2"></i>添加分类
                    </button>
                </div>

                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder-x display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">暂无分类数据</h4>
                        <p class="text-muted"><?php echo !empty($search) ? '没有找到匹配的分类' : '系统中还没有分类，请添加分类'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>分类名称</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>商品数量</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td><?php echo $category['sort_order']; ?></td>
                                    <td>
                                        <?php if (($category['status'] ?? 1) == 1): ?>
                                            <span class="badge badge-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">禁用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $category['product_count'] ?? 0; ?> 个</span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($category['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                    title="编辑">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['product_count'] ?? 0; ?>)" 
                                                    title="删除">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="分类分页">
                        <ul class="pagination">
                            <?php 
                            $queryParams = [];
                            if (!empty($search)) {
                                $queryParams['search'] = $search;
                            }
                            $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                            ?>
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $queryString; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $queryString; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryString; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 添加分类模态框 -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>添加分类
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tag me-1"></i>分类名称 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="name" id="add_name" required placeholder="请输入分类名称">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-down me-1"></i>排序顺序
                            </label>
                            <input type="number" class="form-control" name="sort_order" id="add_sort_order" value="0" min="0" placeholder="数字越小越靠前">
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>数字越小，排序越靠前，默认为0
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-toggle-on me-1"></i>状态 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="status" id="add_status" required>
                                <option value="1" selected>启用</option>
                                <option value="0">禁用</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>禁用后，该分类将不会在小程序中显示
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>添加分类
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑分类模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑分类
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tag me-1"></i>分类名称 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="name" id="edit_name" required placeholder="请输入分类名称">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-sort-numeric-down me-1"></i>排序顺序
                            </label>
                            <input type="number" class="form-control" name="sort_order" id="edit_sort_order" value="0" min="0" placeholder="数字越小越靠前">
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>数字越小，排序越靠前，默认为0
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-toggle-on me-1"></i>状态 <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="1">启用</option>
                                <option value="0">禁用</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>禁用后，该分类将不会在小程序中显示
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
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi bi-exclamation-triangle me-2"></i>确认删除
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p class="mb-0" id="deleteMessage"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>取消
                    </button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>确认删除
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_sort_order').value = category.sort_order || 0;
            document.getElementById('edit_status').value = category.status ?? 1;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteCategory(id, name, productCount) {
            document.getElementById('delete_id').value = id;
            if (productCount > 0) {
                document.getElementById('deleteMessage').innerHTML = 
                    `确定要删除分类 "<strong>${escapeHtml(name)}</strong>" 吗？<br><br>` +
                    `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>该分类下还有 ${productCount} 个商品，删除分类前请先处理这些商品。</span>`;
            } else {
                document.getElementById('deleteMessage').innerHTML = 
                    `确定要删除分类 "<strong>${escapeHtml(name)}</strong>" 吗？此操作不可撤销。`;
            }
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 重置添加表单
        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function() {
                    document.getElementById('addForm').reset();
                    document.getElementById('add_sort_order').value = 0;
                    document.getElementById('add_status').value = 1;
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

