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
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password']
);

// 处理表单提交
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = $_POST['product_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $content = $_POST['content'] ?? '';
        $sort_order = $_POST['sort_order'] ?? 0;
        $status = $_POST['status'] ?? 1;
        
        $sql = "INSERT INTO shop_tutorials (product_id, title, image_url, content, sort_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $title, $image_url, $content, $sort_order, $status]);
        
        header('Location: tutorials.php?success=1');
        exit;
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $product_id = $_POST['product_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $content = $_POST['content'] ?? '';
        $sort_order = $_POST['sort_order'] ?? 0;
        $status = $_POST['status'] ?? 1;
        
        $sql = "UPDATE shop_tutorials SET product_id = ?, title = ?, image_url = ?, content = ?, sort_order = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $title, $image_url, $content, $sort_order, $status, $id]);
        
        header('Location: tutorials.php?success=1');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        $sql = "DELETE FROM shop_tutorials WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header('Location: tutorials.php?success=1');
        exit;
    }
}

// 搜索功能
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE t.title LIKE ? OR t.content LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
}

// 分页
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 获取总数
$countSql = "SELECT COUNT(*) FROM shop_tutorials t $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalTutorials = $countStmt->fetchColumn();

$totalPages = ceil($totalTutorials / $limit);

// 获取教程列表
$sql = "SELECT t.*, p.title as product_name 
        FROM shop_tutorials t 
        LEFT JOIN shop_products p ON t.product_id = p.id 
        $whereClause 
        ORDER BY t.sort_order ASC, t.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取商品列表
$products_sql = "SELECT * FROM shop_products ORDER BY title";
$products_stmt = $pdo->query($products_sql);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教程管理 - 王者荣耀查战力后台管理系统</title>
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

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .tutorial-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .content-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
                    <i class="bi bi-book me-3"></i>教程管理
                </h1>
                <p>管理图文教程内容</p>
            </div>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-book fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo number_format($totalTutorials); ?></h3>
                                <p class="text-muted mb-0">总教程数</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo number_format(count(array_filter($tutorials, function($t) { return $t['status']; }))); ?></h3>
                                <p class="text-muted mb-0">启用教程</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-pause-circle fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo number_format(count(array_filter($tutorials, function($t) { return !$t['status']; }))); ?></h3>
                                <p class="text-muted mb-0">禁用教程</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-box-seam fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo count($products); ?></h3>
                                <p class="text-muted mb-0">关联商品</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 筛选和搜索 -->
            <div class="filter-card">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-3">
                        <label for="search" class="form-label">搜索教程</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="输入标题或内容...">
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="bi bi-search me-2"></i>搜索
                        </button>
                    </div>
                </div>
            </div>

            <!-- 教程列表 -->
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>教程列表
                    </h5>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTutorialModal">
                        <i class="bi bi-plus-circle me-2"></i>添加教程
                    </button>
                </div>

                <?php if (empty($tutorials)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-book display-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">暂无教程数据</h4>
                        <p class="text-muted">点击"添加教程"按钮开始添加</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>封面</th>
                                    <th>标题</th>
                                    <th>内容预览</th>
                                    <th>关联商品</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tutorials as $tutorial): ?>
                                <tr>
                                    <td><?php echo $tutorial['id']; ?></td>
                                    <td>
                                        <?php if ($tutorial['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($tutorial['image_url']); ?>" class="tutorial-image" alt="教程封面">
                                        <?php else: ?>
                                            <div class="tutorial-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($tutorial['title']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-muted content-preview">
                                            <?php echo htmlspecialchars($tutorial['content']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($tutorial['product_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($tutorial['product_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">通用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $tutorial['sort_order']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $tutorial['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $tutorial['status'] ? '启用' : '禁用'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($tutorial['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editTutorial(<?php echo htmlspecialchars(json_encode($tutorial)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteTutorial(<?php echo $tutorial['id']; ?>)">
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
                    <nav aria-label="教程分页">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">上一页</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">下一页</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 添加教程模态框 -->
    <div class="modal fade" id="addTutorialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">添加教程</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="add_product_id" class="form-label">关联商品</label>
                            <select class="form-select" id="add_product_id" name="product_id">
                                <option value="">通用教程</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_title" class="form-label">标题</label>
                            <input type="text" class="form-control" id="add_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_image_url" class="form-label">图片URL</label>
                            <input type="url" class="form-control" id="add_image_url" name="image_url" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label for="add_content" class="form-label">详细内容</label>
                            <textarea class="form-control" id="add_content" name="content" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_sort_order" class="form-label">排序</label>
                                <input type="number" class="form-control" id="add_sort_order" name="sort_order" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_status" class="form-label">状态</label>
                                <select class="form-select" id="add_status" name="status">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">添加教程</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑教程模态框 -->
    <div class="modal fade" id="editTutorialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑教程</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_product_id" class="form-label">关联商品</label>
                            <select class="form-select" id="edit_product_id" name="product_id">
                                <option value="">通用教程</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">标题</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image_url" class="form-label">图片URL</label>
                            <input type="url" class="form-control" id="edit_image_url" name="image_url" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">详细内容</label>
                            <textarea class="form-control" id="edit_content" name="content" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_sort_order" class="form-label">排序</label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">状态</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-warning">更新教程</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilters() {
            const search = document.getElementById('search').value;
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        function editTutorial(tutorial) {
            document.getElementById('edit_id').value = tutorial.id;
            document.getElementById('edit_product_id').value = tutorial.product_id || '';
            document.getElementById('edit_title').value = tutorial.title;
            document.getElementById('edit_image_url').value = tutorial.image_url || '';
            document.getElementById('edit_content').value = tutorial.content;
            document.getElementById('edit_sort_order').value = tutorial.sort_order;
            document.getElementById('edit_status').value = tutorial.status;
            
            new bootstrap.Modal(document.getElementById('editTutorialModal')).show();
        }

        function deleteTutorial(id) {
            if (confirm('确定要删除这个教程吗？此操作不可恢复！')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // 回车键搜索
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>
