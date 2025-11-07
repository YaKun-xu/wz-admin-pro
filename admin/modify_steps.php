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
        $product_id = $product_id === '' ? null : intval($product_id);
        $step_number = intval($_POST['step_number'] ?? 1);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $note = $_POST['note'] ?? '';
        $icon = $_POST['icon'] ?? '';
        $sort_order = $_POST['sort_order'] ?? 0;
        $status = $_POST['status'] ?? 1;
        
        $sql = "INSERT INTO shop_modify_steps (product_id, step_number, title, description, note, icon, sort_order, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $step_number, $title, $description, $note, $icon, $sort_order, $status]);
        
        header('Location: modify_steps.php?success=1');
        exit;
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $product_id = $_POST['product_id'] ?? null;
        $product_id = $product_id === '' ? null : intval($product_id);
        $step_number = intval($_POST['step_number'] ?? 1);
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $note = $_POST['note'] ?? '';
        $icon = $_POST['icon'] ?? '';
        $sort_order = $_POST['sort_order'] ?? 0;
        $status = $_POST['status'] ?? 1;
        
        $sql = "UPDATE shop_modify_steps SET product_id = ?, step_number = ?, title = ?, description = ?, note = ?, icon = ?, sort_order = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product_id, $step_number, $title, $description, $note, $icon, $sort_order, $status, $id]);
        
        header('Location: modify_steps.php?success=1');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        $sql = "DELETE FROM shop_modify_steps WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header('Location: modify_steps.php?success=1');
        exit;
    }
}

// 搜索功能
$search = $_GET['search'] ?? '';
$whereClause = '';
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE s.title LIKE ? OR s.description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
}

// 分页
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 获取总数
$countSql = "SELECT COUNT(*) FROM shop_modify_steps s $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalSteps = $countStmt->fetchColumn();

$totalPages = ceil($totalSteps / $limit);

// 获取步骤列表
$sql = "SELECT s.*, p.title as product_name 
        FROM shop_modify_steps s 
        LEFT JOIN shop_products p ON s.product_id = p.id 
        $whereClause 
        ORDER BY s.sort_order ASC, s.step_number ASC, s.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>修改步骤教程管理 - 王者荣耀查战力后台管理系统</title>
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

        .step-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .description-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .note-preview {
            max-width: 200px;
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
                    <i class="bi bi-list-ol me-3"></i>修改步骤教程管理
                </h1>
                <p>管理修改步骤教程内容</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>操作成功！
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-list-ol fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo number_format($totalSteps); ?></h3>
                                <p class="text-muted mb-0">总步骤数</p>
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
                                <h3 class="mb-0"><?php echo number_format(count(array_filter($steps, function($s) { return $s['status']; }))); ?></h3>
                                <p class="text-muted mb-0">启用步骤</p>
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
                                <h3 class="mb-0"><?php echo number_format(count(array_filter($steps, function($s) { return !$s['status']; }))); ?></h3>
                                <p class="text-muted mb-0">禁用步骤</p>
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
                        <label for="search" class="form-label">搜索步骤</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="输入标题或描述...">
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                            <i class="bi bi-search me-2"></i>搜索
                        </button>
                    </div>
                </div>
            </div>

            <!-- 步骤列表 -->
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>步骤列表
                    </h5>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStepModal">
                        <i class="bi bi-plus-circle me-2"></i>添加步骤
                    </button>
                </div>

                <?php if (empty($steps)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-list-ol display-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">暂无步骤数据</h4>
                        <p class="text-muted">点击"添加步骤"按钮开始添加</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>步骤</th>
                                    <th>图标</th>
                                    <th>标题</th>
                                    <th>描述预览</th>
                                    <th>注意事项</th>
                                    <th>关联商品</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($steps as $step): ?>
                                <tr>
                                    <td><?php echo $step['id']; ?></td>
                                    <td>
                                        <span class="step-number"><?php echo $step['step_number']; ?></span>
                                    </td>
                                    <td>
                                        <?php if ($step['icon']): ?>
                                            <div class="step-icon">
                                                <i class="<?php echo htmlspecialchars($step['icon']); ?>"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="step-icon">
                                                <i class="bi bi-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($step['title']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-muted description-preview">
                                            <?php echo htmlspecialchars($step['description'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted note-preview">
                                            <?php echo htmlspecialchars($step['note'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($step['product_name']): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($step['product_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">通用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $step['sort_order']; ?></td>
                                    <td>
                                        <span class="badge <?php echo $step['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $step['status'] ? '启用' : '禁用'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($step['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editStep(<?php echo htmlspecialchars(json_encode($step)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteStep(<?php echo $step['id']; ?>)">
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
                    <nav aria-label="步骤分页">
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

    <!-- 添加步骤模态框 -->
    <div class="modal fade" id="addStepModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>添加步骤
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addStepForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="add_product_id" class="form-label fw-bold">
                                <i class="bi bi-box-seam me-1"></i>关联商品
                            </label>
                            <select class="form-select" id="add_product_id" name="product_id">
                                <option value="">通用步骤</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>选择关联商品或留空作为通用步骤
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_step_number" class="form-label fw-bold">
                                    <i class="bi bi-123 me-1"></i>步骤序号 <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="add_step_number" name="step_number" value="1" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_icon" class="form-label fw-bold">
                                    <i class="bi bi-image me-1"></i>步骤图标
                                </label>
                                <input type="text" class="form-control" id="add_icon" name="icon" placeholder="bi bi-check-circle">
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Bootstrap Icons类名，如：bi bi-check-circle
                                </small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_title" class="form-label fw-bold">
                                <i class="bi bi-bookmark me-1"></i>步骤标题 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="add_title" name="title" required placeholder="请输入步骤标题">
                        </div>
                        <div class="mb-3">
                            <label for="add_description" class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>步骤描述 <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="add_description" name="description" rows="4" required placeholder="请输入步骤详细描述"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="add_note" class="form-label fw-bold">
                                <i class="bi bi-exclamation-triangle me-1"></i>注意事项
                            </label>
                            <textarea class="form-control" id="add_note" name="note" rows="3" placeholder="请输入注意事项（可选）"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_sort_order" class="form-label fw-bold">
                                    <i class="bi bi-sort-numeric-down me-1"></i>排序
                                </label>
                                <input type="number" class="form-control" id="add_sort_order" name="sort_order" value="0" placeholder="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_status" class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>状态
                                </label>
                                <select class="form-select" id="add_status" name="status">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>添加步骤
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑步骤模态框 -->
    <div class="modal fade" id="editStepModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑步骤
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_product_id" class="form-label fw-bold">
                                <i class="bi bi-box-seam me-1"></i>关联商品
                            </label>
                            <select class="form-select" id="edit_product_id" name="product_id">
                                <option value="">通用步骤</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>选择关联商品或留空作为通用步骤
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_step_number" class="form-label fw-bold">
                                    <i class="bi bi-123 me-1"></i>步骤序号 <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="edit_step_number" name="step_number" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_icon" class="form-label fw-bold">
                                    <i class="bi bi-image me-1"></i>步骤图标
                                </label>
                                <input type="text" class="form-control" id="edit_icon" name="icon" placeholder="bi bi-check-circle">
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Bootstrap Icons类名，如：bi bi-check-circle
                                </small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_title" class="form-label fw-bold">
                                <i class="bi bi-bookmark me-1"></i>步骤标题 <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_title" name="title" required placeholder="请输入步骤标题">
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label fw-bold">
                                <i class="bi bi-file-text me-1"></i>步骤描述 <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required placeholder="请输入步骤详细描述"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_note" class="form-label fw-bold">
                                <i class="bi bi-exclamation-triangle me-1"></i>注意事项
                            </label>
                            <textarea class="form-control" id="edit_note" name="note" rows="3" placeholder="请输入注意事项（可选）"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_sort_order" class="form-label fw-bold">
                                    <i class="bi bi-sort-numeric-down me-1"></i>排序
                                </label>
                                <input type="number" class="form-control" id="edit_sort_order" name="sort_order" placeholder="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>状态
                                </label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilters() {
            const search = document.getElementById('search').value;
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        function editStep(step) {
            document.getElementById('edit_id').value = step.id;
            document.getElementById('edit_product_id').value = step.product_id || '';
            document.getElementById('edit_step_number').value = step.step_number;
            document.getElementById('edit_title').value = step.title;
            document.getElementById('edit_description').value = step.description || '';
            document.getElementById('edit_note').value = step.note || '';
            document.getElementById('edit_icon').value = step.icon || '';
            document.getElementById('edit_sort_order').value = step.sort_order;
            document.getElementById('edit_status').value = step.status;
            
            new bootstrap.Modal(document.getElementById('editStepModal')).show();
        }

        function deleteStep(id) {
            if (confirm('确定要删除这个步骤吗？此操作不可恢复！')) {
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

        // 重置添加表单
        const addStepModal = document.getElementById('addStepModal');
        if (addStepModal) {
            addStepModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('addStepForm').reset();
            });
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

