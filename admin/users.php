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
$searchType = $_GET['search_type'] ?? 'all'; // 搜索类型：all, id, openid, phone, nickname
$whereClause = '';
$params = [];

if (!empty($search)) {
    switch ($searchType) {
        case 'id':
            // 搜索ID（精确匹配）
            $whereClause = "WHERE u.id = ?";
            $params = [intval($search)];
            break;
        case 'openid':
            // 搜索OpenID（模糊匹配）
            $whereClause = "WHERE u.openid LIKE ?";
            $params = ["%$search%"];
            break;
        case 'phone':
            // 搜索手机号（模糊匹配）
            $whereClause = "WHERE u.phone LIKE ?";
            $params = ["%$search%"];
            break;
        case 'nickname':
            // 搜索用户昵称（模糊匹配）
            $whereClause = "WHERE u.nickname LIKE ?";
            $params = ["%$search%"];
            break;
        case 'all':
        default:
            // 搜索所有字段（ID精确匹配，其他模糊匹配）
            $whereClause = "WHERE u.id = ? OR u.nickname LIKE ? OR u.phone LIKE ? OR u.openid LIKE ?";
            $searchTerm = "%$search%";
            $id = intval($search);
            $params = [$id, $searchTerm, $searchTerm, $searchTerm];
            break;
    }
}

// 分页
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 获取总数（使用与列表查询相同的表别名）
$countSql = "SELECT COUNT(*) FROM users u $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();

$totalPages = ceil($totalUsers / $limit);

// 处理删除操作
$message = '';
$messageType = '';

if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = '用户删除成功';
            $messageType = 'success';
            
            // 删除后重新获取用户列表（避免显示已删除的用户）
            header('Location: users.php?' . http_build_query($_GET));
            exit;
        } catch (Exception $e) {
            $message = '删除失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($action === 'force_logout') {
        try {
            // 删除用户的所有会话记录
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$id]);
            $message = '强制登出成功，该用户的所有登录会话已失效';
            $messageType = 'success';
            
            // 操作后刷新页面
            header('Location: users.php?' . http_build_query($_GET));
            exit;
        } catch (Exception $e) {
            $message = '强制登出失败：' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// 获取用户列表（关联小程序配置表获取小程序名称）
$sql = "SELECT u.*, m.app_name as miniprogram_name 
        FROM users u 
        LEFT JOIN miniprogram_config m ON u.app_id = m.app_id 
        $whereClause 
        ORDER BY u.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 王者荣耀查战力后台管理系统</title>
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
                    <i class="bi bi-people me-3"></i>用户管理
                </h1>
                <p>管理系统用户账户信息</p>
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
                    <div class="col-md-3">
                        <select class="form-select" name="search_type" id="search_type">
                            <option value="all" <?php echo $searchType === 'all' ? 'selected' : ''; ?>>全部字段</option>
                            <option value="id" <?php echo $searchType === 'id' ? 'selected' : ''; ?>>用户ID</option>
                            <option value="openid" <?php echo $searchType === 'openid' ? 'selected' : ''; ?>>OpenID</option>
                            <option value="phone" <?php echo $searchType === 'phone' ? 'selected' : ''; ?>>手机号</option>
                            <option value="nickname" <?php echo $searchType === 'nickname' ? 'selected' : ''; ?>>用户昵称</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" id="search_input" 
                               placeholder="请输入搜索关键词..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>搜索
                            </button>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>重置
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 用户列表 -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        用户列表
                        <?php if (!empty($search)): ?>
                            <span class="badge badge-info ms-2">搜索结果: <?php echo $totalUsers; ?> 个</span>
                        <?php else: ?>
                            <span class="badge badge-info ms-2">共 <?php echo $totalUsers; ?> 个用户</span>
                        <?php endif; ?>
                    </h5>
                </div>

                <?php if (empty($users)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-person-x display-1 text-muted"></i>
                        <h4 class="text-muted mt-3">暂无用户数据</h4>
                        <p class="text-muted"><?php echo !empty($search) ? '没有找到匹配的用户' : '系统中还没有用户注册'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>头像</th>
                                    <th>昵称</th>
                                    <th>手机号</th>
                                    <th>OpenID</th>
                                    <th>注册时间</th>
                                    <th>最后登录</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <?php if (!empty($user['avatar_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="头像" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['nickname'] ?? '未设置'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['phone'])): ?>
                                            <?php echo htmlspecialchars($user['phone']); ?>
                                            <?php if (!empty($user['is_phone_verified']) && $user['is_phone_verified'] == 1): ?>
                                                <span class="badge bg-success ms-1" style="font-size: 0.7rem;">已验证</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">未绑定</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted" title="<?php echo htmlspecialchars($user['openid'] ?? ''); ?>">
                                            <?php echo htmlspecialchars(substr($user['openid'] ?? '', 0, 20)) . (strlen($user['openid'] ?? '') > 20 ? '...' : ''); ?>
                                        </small>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if (!empty($user['last_login_time'])): ?>
                                            <?php echo date('Y-m-d H:i', strtotime($user['last_login_time'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">从未登录</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($user['phone']) && !empty($user['is_phone_verified']) && $user['is_phone_verified'] == 1): ?>
                                            <span class="badge badge-success">正常</span>
                                        <?php elseif (!empty($user['phone'])): ?>
                                            <span class="badge badge-warning">待验证</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">未绑定手机</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    title="查看详情">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="orders.php?user_id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="查看该用户的所有订单">
                                                <i class="bi bi-cart"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="forceLogout(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nickname'] ?? '用户'); ?>')" 
                                                    title="强制登出">
                                                <i class="bi bi-box-arrow-right"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nickname'] ?? '用户'); ?>')" 
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
                    <nav aria-label="用户分页">
                        <ul class="pagination">
                            <?php 
                            $queryParams = [];
                            if (!empty($search)) {
                                $queryParams['search'] = $search;
                            }
                            if ($searchType !== 'all') {
                                $queryParams['search_type'] = $searchType;
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

    <!-- 用户详情模态框 -->
    <div class="modal fade" id="userDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-person-circle me-2"></i>用户详情
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;" id="userDetailContent">
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

    <!-- 强制登出确认模态框 -->
    <div class="modal fade" id="forceLogoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi bi-box-arrow-right me-2"></i>确认强制登出
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p class="mb-0" id="forceLogoutMessage"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>取消
                    </button>
                    <form method="POST" style="display: inline;" id="forceLogoutForm">
                        <input type="hidden" name="action" value="force_logout">
                        <input type="hidden" name="id" id="force_logout_user_id">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-box-arrow-right me-2"></i>确认强制登出
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 15px 15px 0 0; border: none; padding: 1.5rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="bi bi-exclamation-triangle me-2"></i>确认删除
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 2rem;">
                    <p class="mb-0" id="deleteUserMessage"></p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem; border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>取消
                    </button>
                    <form method="POST" style="display: inline;" id="deleteUserForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_user_id">
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
        function viewUser(user) {
            const content = document.getElementById('userDetailContent');
            const avatar = user.avatar_url ? 
                `<img src="${escapeHtml(user.avatar_url)}" alt="头像" class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">` : 
                '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 80px; height: 80px;"><i class="bi bi-person" style="font-size: 2rem;"></i></div>';
            
            content.innerHTML = `
                <div class="text-center mb-4">
                    ${avatar}
                    <h4 class="mb-1">${escapeHtml(user.nickname || '未设置')}</h4>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">用户ID</small>
                            <strong>#${user.id}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">手机号</small>
                            <strong>${user.phone ? escapeHtml(user.phone) : '<span class="text-muted">未绑定</span>'}</strong>
                            ${user.is_phone_verified == 1 ? '<span class="badge bg-success ms-2">已验证</span>' : ''}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">OpenID</small>
                            <small class="text-break">${escapeHtml(user.openid || '')}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">UnionID</small>
                            <small class="text-break">${user.unionid ? escapeHtml(user.unionid) : '<span class="text-muted">未设置</span>'}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">注册时间</small>
                            <strong>${user.created_at ? formatDateTime(user.created_at) : '未知'}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">最后登录</small>
                            <strong>${user.last_login_time ? formatDateTime(user.last_login_time) : '<span class="text-muted">从未登录</span>'}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">所属小程序</small>
                            <strong>${user.miniprogram_name ? escapeHtml(user.miniprogram_name) : (user.app_id ? escapeHtml(user.app_id) : '<span class="text-muted">未知</span>')}</strong>
                            ${user.app_id ? `<br><small class="text-muted">AppID: ${escapeHtml(user.app_id)}</small>` : ''}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block mb-1">状态</small>
                            ${user.phone && user.is_phone_verified == 1 ? 
                                '<span class="badge bg-success">正常</span>' : 
                                user.phone ? 
                                '<span class="badge bg-warning">待验证</span>' : 
                                '<span class="badge bg-info">未绑定手机</span>'}
                        </div>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('userDetailModal')).show();
        }

        function forceLogout(id, nickname) {
            document.getElementById('force_logout_user_id').value = id;
            document.getElementById('forceLogoutMessage').textContent = 
                `确定要强制登出用户 "${nickname}" 吗？该用户的所有登录会话将被清除，需要重新登录。`;
            new bootstrap.Modal(document.getElementById('forceLogoutModal')).show();
        }

        function deleteUser(id, nickname) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('deleteUserMessage').textContent = 
                `确定要删除用户 "${nickname}" 吗？此操作不可撤销，将永久删除该用户的所有数据。`;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDateTime(dateString) {
            if (!dateString) return '未知';
            try {
                // 处理MySQL日期时间格式：2025-01-01 12:00:00
                const date = new Date(dateString.replace(/-/g, '/'));
                if (isNaN(date.getTime())) {
                    return dateString; // 如果无法解析，返回原始字符串
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

        // 根据搜索类型动态改变输入框提示
        const searchTypeSelect = document.getElementById('search_type');
        const searchInput = document.getElementById('search_input');
        
        if (searchTypeSelect && searchInput) {
            function updatePlaceholder() {
                const type = searchTypeSelect.value;
                const placeholders = {
                    'all': '请输入搜索关键词（ID、昵称、手机号、OpenID）',
                    'id': '请输入用户ID（精确匹配）',
                    'openid': '请输入OpenID（支持模糊匹配）',
                    'phone': '请输入手机号（支持模糊匹配）',
                    'nickname': '请输入用户昵称（支持模糊匹配）'
                };
                searchInput.placeholder = placeholders[type] || placeholders['all'];
            }
            
            searchTypeSelect.addEventListener('change', updatePlaceholder);
            updatePlaceholder(); // 初始化
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
