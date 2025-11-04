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
$status = $_GET['status'] ?? '';
$whereClause = '';
$params = [];

$conditions = [];
$user_id = $_GET['user_id'] ?? '';

if (!empty($search)) {
    $conditions[] = "(o.order_no LIKE ? OR u.nickname LIKE ? OR o.product_title LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($user_id)) {
    $conditions[] = "o.user_id = ?";
    $params[] = intval($user_id);
}

if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(' AND ', $conditions);
}

// 分页
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 处理订单操作
$message = '';
$messageType = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $id]);
            $message = '订单状态更新成功';
            $messageType = 'success';
            
            // 刷新页面
            header('Location: orders.php?' . http_build_query($_GET));
            exit;
        } catch (Exception $e) {
            $message = '状态更新失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $message = '订单删除成功';
            $messageType = 'success';
            
            // 刷新页面
            header('Location: orders.php?' . http_build_query($_GET));
            exit;
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 获取总数（关联用户表）
$countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();

$totalPages = ceil($totalOrders / $limit);

// 获取订单列表（关联用户表和小程序配置表）
$sql = "SELECT o.*, u.nickname as user_name, u.avatar_url as user_avatar, m.app_name as miniprogram_name
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN miniprogram_config m ON o.app_id = m.app_id 
        $whereClause 
        ORDER BY o.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单管理 - 王者荣耀查战力后台管理系统</title>
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
                    <i class="bi bi-cart me-3"></i>订单管理
                </h1>
                <p>管理系统订单信息</p>
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
                    <?php if (!empty($user_id)): ?>
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <?php endif; ?>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="搜索订单号、用户名或商品名..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">全部状态</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待支付</option>
                            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>已支付</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>处理中</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>搜索
                            </button>
                            <a href="orders.php<?php echo !empty($user_id) ? '?user_id=' . urlencode($user_id) : ''; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>重置
                            </a>
                            <?php if (!empty($user_id)): ?>
                                <a href="orders.php" class="btn btn-outline-warning">
                                    <i class="bi bi-x-circle me-2"></i>清除用户筛选
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <?php if (!empty($user_id)): ?>
                    <?php
                    // 获取用户信息
                    $userStmt = $pdo->prepare("SELECT nickname, avatar_url FROM users WHERE id = ?");
                    $userStmt->execute([$user_id]);
                    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="mt-3 p-3 bg-info bg-opacity-10 rounded border border-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-funnel-fill text-info me-2"></i>
                            <span class="text-info fw-bold">当前筛选：用户ID #<?php echo $user_id; ?></span>
                            <?php if ($userInfo): ?>
                                <span class="ms-2">
                                    <?php if (!empty($userInfo['avatar_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($userInfo['avatar_url']); ?>" alt="头像" class="rounded-circle me-1" width="20" height="20" style="object-fit: cover;">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($userInfo['nickname'] ?? '未知用户'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 订单列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        订单列表
                        <?php if (!empty($search) || !empty($status)): ?>
                            <span class="badge badge-info ms-2">搜索结果: <?php echo $totalOrders; ?> 个</span>
                        <?php else: ?>
                            <span class="badge badge-info ms-2">共 <?php echo $totalOrders; ?> 个订单</span>
                        <?php endif; ?>
                    </h5>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">暂无订单数据</h4>
                        <p class="text-muted"><?php echo (!empty($search) || !empty($status)) ? '没有找到匹配的订单' : '系统中还没有订单'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>订单号</th>
                                    <th>用户ID</th>
                                    <th>商品</th>
                                    <th>金额</th>
                                    <th>状态</th>
                                    <th>支付方式</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['order_no'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td>
                                        <a href="?user_id=<?php echo $order['user_id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>" 
                                           class="text-decoration-none fw-bold text-primary" 
                                           title="点击查看该用户的所有订单">
                                            #<?php echo $order['user_id']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['product_title'] ?? '未知商品'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">¥<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($order['status'] ?? '') {
                                            case 'pending':
                                                $statusClass = 'badge-warning';
                                                $statusText = '待支付';
                                                break;
                                            case 'paid':
                                                $statusClass = 'badge-info';
                                                $statusText = '已支付';
                                                break;
                                            case 'processing':
                                                $statusClass = 'badge-secondary';
                                                $statusText = '处理中';
                                                break;
                                            case 'completed':
                                                $statusClass = 'badge-success';
                                                $statusText = '已完成';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge-danger';
                                                $statusText = '已取消';
                                                break;
                                            default:
                                                $statusClass = 'badge-secondary';
                                                $statusText = '未知';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $paymentMethod = $order['pay_method'] ?? 'wxpay';
                                        $paymentNames = [
                                            'wxpay' => '微信支付',
                                            'alipay' => '支付宝',
                                            'bank' => '银行卡'
                                        ];
                                        $paymentIcons = [
                                            'wxpay' => 'bi bi-wechat',
                                            'alipay' => 'bi bi-credit-card',
                                            'bank' => 'bi bi-bank'
                                        ];
                                        $icon = $paymentIcons[$paymentMethod] ?? 'bi bi-question-circle';
                                        $name = $paymentNames[$paymentMethod] ?? '未知';
                                        ?>
                                        <i class="<?php echo $icon; ?> me-1"></i>
                                        <?php echo $name; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                                    title="查看详情">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['status']); ?>', '<?php echo htmlspecialchars($order['order_no']); ?>')" 
                                                    title="修改状态">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_no']); ?>')" 
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
                    <nav aria-label="订单分页">
                        <ul class="pagination">
                            <?php 
                            $queryParams = [];
                            if (!empty($search)) {
                                $queryParams['search'] = $search;
                            }
                            if (!empty($status)) {
                                $queryParams['status'] = $status;
                            }
                            if (!empty($user_id)) {
                                $queryParams['user_id'] = $user_id;
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

    <!-- 订单详情模态框 -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-receipt me-2"></i>订单详情
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;" id="orderDetailContent">
                    <!-- 内容将通过JavaScript动态填充 -->
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>关闭
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 修改订单状态模态框 -->
    <div class="modal fade" id="editOrderStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi bi-pencil-square me-2"></i>修改订单状态
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editOrderStatusForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="edit_order_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">订单号</label>
                            <input type="text" class="form-control" id="edit_order_no" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">订单状态 <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="edit_order_status" required>
                                <option value="pending">待支付</option>
                                <option value="paid">已支付</option>
                                <option value="processing">处理中</option>
                                <option value="completed">已完成</option>
                                <option value="cancelled">已取消</option>
                                <option value="refunded">已退款</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
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
    <div class="modal fade" id="deleteOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi bi-exclamation-triangle me-2"></i>确认删除
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p class="mb-0" id="deleteOrderMessage"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>取消
                    </button>
                    <form method="POST" style="display: inline;" id="deleteOrderForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_order_id">
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
        function viewOrder(order) {
            const content = document.getElementById('orderDetailContent');
            const statusNames = {
                'pending': '待支付',
                'paid': '已支付',
                'processing': '处理中',
                'completed': '已完成',
                'cancelled': '已取消',
                'refunded': '已退款'
            };
            const statusBadges = {
                'pending': 'badge-warning',
                'paid': 'badge-info',
                'processing': 'badge-secondary',
                'completed': 'badge-success',
                'cancelled': 'badge-danger',
                'refunded': 'badge-danger'
            };
            const paymentNames = {
                'wxpay': '微信支付',
                'alipay': '支付宝',
                'bank': '银行卡'
            };
            
            content.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">订单号</small>
                            <strong>${escapeHtml(order.order_no || '')}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">订单状态</small>
                            <span class="badge ${statusBadges[order.status] || 'badge-secondary'}">${statusNames[order.status] || '未知'}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">用户</small>
                            <strong>${escapeHtml(order.user_name || '未知用户')}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">商品名称</small>
                            <strong>${escapeHtml(order.product_title || '')}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">商品价格</small>
                            <strong>¥${parseFloat(order.product_price || 0).toFixed(2)}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">订单总金额</small>
                            <strong class="text-success">¥${parseFloat(order.total_amount || 0).toFixed(2)}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">支付方式</small>
                            <strong>${paymentNames[order.pay_method] || order.pay_method || '未知'}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">所属小程序</small>
                            <strong>${order.miniprogram_name ? escapeHtml(order.miniprogram_name) : (order.app_id ? escapeHtml(order.app_id) : '未知')}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">创建时间</small>
                            <strong>${order.created_at ? formatDateTime(order.created_at) : '未知'}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">支付时间</small>
                            <strong>${order.paid_at ? formatDateTime(order.paid_at) : '<span class="text-muted">未支付</span>'}</strong>
                        </div>
                    </div>
                    ${order.transaction_id ? `
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">交易号</small>
                            <small class="text-break">${escapeHtml(order.transaction_id)}</small>
                        </div>
                    </div>
                    ` : ''}
                    ${order.card_key ? `
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">卡密</small>
                            <code class="text-break">${escapeHtml(order.card_key)}</code>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
        }

        function editOrderStatus(id, currentStatus, orderNo) {
            document.getElementById('edit_order_id').value = id;
            document.getElementById('edit_order_no').value = orderNo;
            document.getElementById('edit_order_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('editOrderStatusModal')).show();
        }

        function deleteOrder(id, orderNo) {
            document.getElementById('delete_order_id').value = id;
            document.getElementById('deleteOrderMessage').textContent = 
                `确定要删除订单 "${orderNo}" 吗？此操作不可撤销，将永久删除该订单的所有数据。`;
            new bootstrap.Modal(document.getElementById('deleteOrderModal')).show();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDateTime(dateString) {
            if (!dateString) return '未知';
            try {
                const date = new Date(dateString.replace(/-/g, '/'));
                if (isNaN(date.getTime())) {
                    return dateString;
                }
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            } catch (e) {
                return dateString;
            }
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
