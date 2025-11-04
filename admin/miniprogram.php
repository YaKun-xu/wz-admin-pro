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
        $app_name = $_POST['app_name'] ?? '';
        $app_id = $_POST['app_id'] ?? '';
        $app_secret = $_POST['app_secret'] ?? '';
        $is_active = $_POST['is_active'] ?? 0;
        $login_enabled = $_POST['login_enabled'] ?? 0;
        $phone_bind_required = $_POST['phone_bind_required'] ?? 0;
        $mch_id = $_POST['mch_id'] ?? '';
        $pay_key = $_POST['pay_key'] ?? '';
        $pay_enabled = $_POST['pay_enabled'] ?? 0;
        $pay_notify_url = $_POST['pay_notify_url'] ?? '';
        
        $sql = "INSERT INTO miniprogram_config (app_name, app_id, app_secret, is_active, login_enabled, phone_bind_required, mch_id, pay_key, pay_enabled, pay_notify_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$app_name, $app_id, $app_secret, $is_active, $login_enabled, $phone_bind_required, $mch_id, $pay_key, $pay_enabled, $pay_notify_url]);
        
        header('Location: miniprogram.php?success=1');
        exit;
    }
    
    if ($action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $app_name = $_POST['app_name'] ?? '';
        $app_id = $_POST['app_id'] ?? '';
        $app_secret = $_POST['app_secret'] ?? '';
        $is_active = $_POST['is_active'] ?? 0;
        $login_enabled = $_POST['login_enabled'] ?? 0;
        $phone_bind_required = $_POST['phone_bind_required'] ?? 0;
        $mch_id = $_POST['mch_id'] ?? '';
        $pay_key = $_POST['pay_key'] ?? '';
        $pay_enabled = $_POST['pay_enabled'] ?? 0;
        $pay_notify_url = $_POST['pay_notify_url'] ?? '';
        
        $sql = "UPDATE miniprogram_config SET app_name = ?, app_id = ?, app_secret = ?, is_active = ?, login_enabled = ?, phone_bind_required = ?, mch_id = ?, pay_key = ?, pay_enabled = ?, pay_notify_url = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$app_name, $app_id, $app_secret, $is_active, $login_enabled, $phone_bind_required, $mch_id, $pay_key, $pay_enabled, $pay_notify_url, $id]);
        
        header('Location: miniprogram.php?success=1');
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        $sql = "DELETE FROM miniprogram_config WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header('Location: miniprogram.php?success=1');
        exit;
    }
}

// 获取小程序配置列表
$sql = "SELECT * FROM miniprogram_config ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$miniprograms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小程序配置 - 王者荣耀查战力后台管理系统</title>
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

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
                    <i class="bi bi-phone me-3"></i>小程序配置
                </h1>
                <p>管理多个小程序的配置信息</p>
            </div>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-phone fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo count($miniprograms); ?></h3>
                                <p class="text-muted mb-0">小程序总数</p>
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
                                <h3 class="mb-0"><?php echo count(array_filter($miniprograms, function($m) { return $m['is_active']; })); ?></h3>
                                <p class="text-muted mb-0">启用小程序</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-person-check fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo count(array_filter($miniprograms, function($m) { return $m['login_enabled']; })); ?></h3>
                                <p class="text-muted mb-0">开启登录</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="bi bi-credit-card fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?php echo count(array_filter($miniprograms, function($m) { return $m['pay_enabled']; })); ?></h3>
                                <p class="text-muted mb-0">开启支付</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 小程序配置列表 -->
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>小程序配置列表
                    </h5>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMiniprogramModal">
                        <i class="bi bi-plus-circle me-2"></i>添加小程序
                    </button>
                </div>

                <?php if (empty($miniprograms)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-phone display-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">暂无小程序配置</h4>
                        <p class="text-muted">点击"添加小程序"按钮开始配置</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>小程序名称</th>
                                    <th>AppID</th>
                                    <th>状态</th>
                                    <th>登录</th>
                                    <th>支付</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($miniprograms as $miniprogram): ?>
                                <tr>
                                    <td><?php echo $miniprogram['id']; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($miniprogram['app_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($miniprogram['app_id']); ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($miniprogram['app_id']); ?></code>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $miniprogram['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $miniprogram['is_active'] ? '启用' : '禁用'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $miniprogram['login_enabled'] ? 'badge-info' : 'badge-danger'; ?>">
                                            <?php echo $miniprogram['login_enabled'] ? '开启' : '关闭'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $miniprogram['pay_enabled'] ? 'badge-warning' : 'badge-danger'; ?>">
                                            <?php echo $miniprogram['pay_enabled'] ? '开启' : '关闭'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($miniprogram['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm me-2" onclick="editMiniprogram(<?php echo htmlspecialchars(json_encode($miniprogram)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteMiniprogram(<?php echo $miniprogram['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 添加小程序模态框 -->
    <div class="modal fade" id="addMiniprogramModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-plus-circle me-2"></i>添加小程序配置
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addMiniprogramForm">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_app_name" class="form-label fw-bold">
                                    <i class="bi bi-app me-1"></i>小程序名称 <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="add_app_name" name="app_name" required placeholder="请输入小程序名称">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_app_id" class="form-label fw-bold">
                                    <i class="bi bi-key me-1"></i>AppID <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="add_app_id" name="app_id" required placeholder="请输入AppID">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add_app_secret" class="form-label fw-bold">
                                <i class="bi bi-lock me-1"></i>AppSecret <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="add_app_secret" name="app_secret" required placeholder="请输入AppSecret">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="add_is_active" class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>是否启用
                                </label>
                                <select class="form-select" id="add_is_active" name="is_active">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="add_login_enabled" class="form-label fw-bold">
                                    <i class="bi bi-person-check me-1"></i>是否开启登录
                                </label>
                                <select class="form-select" id="add_login_enabled" name="login_enabled">
                                    <option value="1">开启</option>
                                    <option value="0">关闭</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="add_phone_bind_required" class="form-label fw-bold">
                                    <i class="bi bi-phone me-1"></i>是否要求绑定手机
                                </label>
                                <select class="form-select" id="add_phone_bind_required" name="phone_bind_required">
                                    <option value="0">不要求</option>
                                    <option value="1">要求</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_mch_id" class="form-label fw-bold">
                                    <i class="bi bi-wallet2 me-1"></i>微信支付商户号
                                </label>
                                <input type="text" class="form-control" id="add_mch_id" name="mch_id" placeholder="请输入商户号">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_pay_key" class="form-label fw-bold">
                                    <i class="bi bi-key-fill me-1"></i>微信支付密钥
                                </label>
                                <input type="text" class="form-control" id="add_pay_key" name="pay_key" placeholder="请输入支付密钥">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="add_pay_enabled" class="form-label fw-bold">
                                    <i class="bi bi-credit-card me-1"></i>是否开启支付
                                </label>
                                <select class="form-select" id="add_pay_enabled" name="pay_enabled">
                                    <option value="0">关闭</option>
                                    <option value="1">开启</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="add_pay_notify_url" class="form-label fw-bold">
                                    <i class="bi bi-link-45deg me-1"></i>支付回调地址
                                </label>
                                <input type="url" class="form-control" id="add_pay_notify_url" name="pay_notify_url" placeholder="https://example.com/pay_notify.php">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e9ecef; padding: 1.5rem 2rem; border-radius: 0 0 20px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>取消
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>添加小程序
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑小程序模态框 -->
    <div class="modal fade" id="editMiniprogramModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0; border: none; padding: 1.5rem 2rem;">
                    <h5 class="modal-title text-white" style="font-size: 1.5rem; font-weight: 700;">
                        <i class="bi bi-pencil-square me-2"></i>编辑小程序配置
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body" style="padding: 2rem;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_app_name" class="form-label fw-bold">
                                    <i class="bi bi-app me-1"></i>小程序名称 <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="edit_app_name" name="app_name" required placeholder="请输入小程序名称">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_app_id" class="form-label fw-bold">
                                    <i class="bi bi-key me-1"></i>AppID <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="edit_app_id" name="app_id" required placeholder="请输入AppID">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_app_secret" class="form-label fw-bold">
                                <i class="bi bi-lock me-1"></i>AppSecret <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="edit_app_secret" name="app_secret" required placeholder="请输入AppSecret">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_is_active" class="form-label fw-bold">
                                    <i class="bi bi-toggle-on me-1"></i>是否启用
                                </label>
                                <select class="form-select" id="edit_is_active" name="is_active">
                                    <option value="1">启用</option>
                                    <option value="0">禁用</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_login_enabled" class="form-label fw-bold">
                                    <i class="bi bi-person-check me-1"></i>是否开启登录
                                </label>
                                <select class="form-select" id="edit_login_enabled" name="login_enabled">
                                    <option value="1">开启</option>
                                    <option value="0">关闭</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_phone_bind_required" class="form-label fw-bold">
                                    <i class="bi bi-phone me-1"></i>是否要求绑定手机
                                </label>
                                <select class="form-select" id="edit_phone_bind_required" name="phone_bind_required">
                                    <option value="0">不要求</option>
                                    <option value="1">要求</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_mch_id" class="form-label fw-bold">
                                    <i class="bi bi-wallet2 me-1"></i>微信支付商户号
                                </label>
                                <input type="text" class="form-control" id="edit_mch_id" name="mch_id" placeholder="请输入商户号">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_pay_key" class="form-label fw-bold">
                                    <i class="bi bi-key-fill me-1"></i>微信支付密钥
                                </label>
                                <input type="text" class="form-control" id="edit_pay_key" name="pay_key" placeholder="请输入支付密钥">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_pay_enabled" class="form-label fw-bold">
                                    <i class="bi bi-credit-card me-1"></i>是否开启支付
                                </label>
                                <select class="form-select" id="edit_pay_enabled" name="pay_enabled">
                                    <option value="0">关闭</option>
                                    <option value="1">开启</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_pay_notify_url" class="form-label fw-bold">
                                    <i class="bi bi-link-45deg me-1"></i>支付回调地址
                                </label>
                                <input type="url" class="form-control" id="edit_pay_notify_url" name="pay_notify_url" placeholder="https://example.com/pay_notify.php">
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
        function editMiniprogram(miniprogram) {
            document.getElementById('edit_id').value = miniprogram.id;
            document.getElementById('edit_app_name').value = miniprogram.app_name;
            document.getElementById('edit_app_id').value = miniprogram.app_id;
            document.getElementById('edit_app_secret').value = miniprogram.app_secret;
            document.getElementById('edit_is_active').value = miniprogram.is_active;
            document.getElementById('edit_login_enabled').value = miniprogram.login_enabled;
            document.getElementById('edit_phone_bind_required').value = miniprogram.phone_bind_required;
            document.getElementById('edit_mch_id').value = miniprogram.mch_id;
            document.getElementById('edit_pay_key').value = miniprogram.pay_key;
            document.getElementById('edit_pay_enabled').value = miniprogram.pay_enabled;
            document.getElementById('edit_pay_notify_url').value = miniprogram.pay_notify_url;
            
            new bootstrap.Modal(document.getElementById('editMiniprogramModal')).show();
        }

        function deleteMiniprogram(id) {
            if (confirm('确定要删除这个小程序配置吗？此操作不可恢复！')) {
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
        const addMiniprogramModal = document.getElementById('addMiniprogramModal');
        if (addMiniprogramModal) {
            addMiniprogramModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('addMiniprogramForm').reset();
            });
        }
    </script>
</body>
</html>
