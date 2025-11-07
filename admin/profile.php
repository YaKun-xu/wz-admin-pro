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

$message = '';
$messageType = '';

// 处理表单提交
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $login_name = trim($_POST['login_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $qq = trim($_POST['qq'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        
        if (empty($username) || empty($login_name)) {
            $message = '用户名和登录账号不能为空';
            $messageType = 'danger';
        } else {
            try {
                // 检查用户名是否已被其他管理员使用
                $checkUsernameSql = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
                $checkUsernameStmt = $pdo->prepare($checkUsernameSql);
                $checkUsernameStmt->execute([$username, $_SESSION['admin_id']]);
                
                // 检查登录账号是否已被其他管理员使用（如果login_name字段存在）
                $checkLoginNameSql = "SELECT id FROM admin_users WHERE login_name = ? AND id != ?";
                $checkLoginNameStmt = $pdo->prepare($checkLoginNameSql);
                $checkLoginNameStmt->execute([$login_name, $_SESSION['admin_id']]);
                
                if ($checkUsernameStmt->fetch()) {
                    $message = '用户名已被其他管理员使用';
                    $messageType = 'danger';
                } elseif ($checkLoginNameStmt->fetch()) {
                    $message = '登录账号已被其他管理员使用';
                    $messageType = 'danger';
                } else {
                    // 更新个人信息（根据实际表结构）
                    $updateSql = "UPDATE admin_users SET username = ?, login_name = ?, email = ?, qq = ?, avatar_url = ? WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([$username, $login_name, $email ?: null, $qq ?: null, $avatar_url ?: null, $_SESSION['admin_id']]);
                    
                    // 更新session中的用户名
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_avatar'] = $avatar_url;
                    
                    $message = '个人信息更新成功';
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = '更新失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = '所有密码字段都不能为空';
            $messageType = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = '新密码和确认密码不匹配';
            $messageType = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = '新密码长度至少6位';
            $messageType = 'danger';
        } else {
            try {
                // 获取当前用户信息
                $userSql = "SELECT password FROM admin_users WHERE id = ?";
                $userStmt = $pdo->prepare($userSql);
                $userStmt->execute([$_SESSION['admin_id']]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    $message = '用户不存在';
                    $messageType = 'danger';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $message = '当前密码错误';
                    $messageType = 'danger';
                } else {
                    // 更新密码
                    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateSql = "UPDATE admin_users SET password = ? WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                    
                    $message = '密码修改成功';
                    $messageType = 'success';
                }
            } catch (Exception $e) {
                $message = '密码修改失败：' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// 获取当前管理员信息
try {
    $adminSql = "SELECT * FROM admin_users WHERE id = ?";
    $adminStmt = $pdo->prepare($adminSql);
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        // 如果数据库中没有记录，使用默认信息
        $admin = [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'] ?? 'admin',
            'login_name' => $_SESSION['admin_username'] ?? 'admin',
            'email' => '',
            'qq' => '',
            'avatar_url' => $_SESSION['admin_avatar'] ?? ''
        ];
    }
} catch (Exception $e) {
    // 数据库连接失败，使用默认信息
    $admin = [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'login_name' => $_SESSION['admin_username'] ?? 'admin',
        'email' => '',
        'qq' => '',
        'avatar_url' => $_SESSION['admin_avatar'] ?? ''
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人信息管理 - 王者荣耀查战力后台管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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

        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-avatar i {
            font-size: 3rem;
            color: white;
        }

        .profile-info {
            margin-bottom: 1.5rem;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            color: #718096;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #718096;
        }

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .section-title {
            color: #2d3748;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #667eea;
        }

        .form-floating {
            position: relative;
        }

        .form-floating .form-control {
            height: calc(3.5rem + 2px);
            line-height: 1.25;
            padding: 1rem 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-floating label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 0.75rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
            color: #718096;
        }

        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }

        .field-description {
            font-size: 0.875rem;
            color: #718096;
            margin-top: 0.5rem;
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

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #059669; width: 100%; }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            overflow: hidden;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-preview i {
            font-size: 2rem;
            color: #6c757d;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .profile-stats {
                flex-direction: column;
                gap: 0.5rem;
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
                    <i class="bi bi-person-circle me-3"></i>个人信息管理
                </h1>
                <p>管理您的个人资料和账户设置</p>
            </div>

            <!-- 消息提示 -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- 个人信息卡片 -->
                <div class="col-lg-4 mb-4">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <?php if (!empty($admin['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($admin['avatar_url']); ?>" 
                                     alt="头像" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="bi bi-person-circle" style="display: none;"></i>
                            <?php else: ?>
                                <i class="bi bi-person-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?php echo htmlspecialchars($admin['username']); ?></div>
                            <div class="profile-role">系统管理员</div>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <div class="stat-number">正常</div>
                                    <div class="stat-label">账户状态</div>
                                </div>
                                <?php if (!empty($admin['email'])): ?>
                                <div class="stat-item">
                                    <div class="stat-number" style="font-size: 0.9rem;"><?php echo htmlspecialchars($admin['email']); ?></div>
                                    <div class="stat-label">邮箱</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 个人信息编辑 -->
                <div class="col-lg-8 mb-4">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="bi bi-person me-2"></i>个人信息
                        </h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                        <label for="username">用户名</label>
                                    </div>
                                    <div class="field-description">显示名称，用于界面显示</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="login_name" name="login_name" 
                                               value="<?php echo htmlspecialchars($admin['login_name']); ?>" required>
                                        <label for="login_name">登录账号</label>
                                    </div>
                                    <div class="field-description">登录时使用的账号名</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                                        <label for="email">邮箱地址</label>
                                    </div>
                                    <div class="field-description">可选，用于联系和通知</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="qq" name="qq" 
                                               value="<?php echo htmlspecialchars($admin['qq'] ?? ''); ?>">
                                        <label for="qq">QQ号码</label>
                                    </div>
                                    <div class="field-description">可选，用于联系</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-floating">
                                        <input type="url" class="form-control" id="avatar_url" name="avatar_url" 
                                               value="<?php echo htmlspecialchars($admin['avatar_url'] ?? ''); ?>"
                                               placeholder="https://example.com/avatar.jpg">
                                        <label for="avatar_url">头像链接</label>
                                    </div>
                                    <div class="field-description">头像图片的URL地址</div>
                                </div>
                            </div>
                            
                            <!-- 头像预览 -->
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">头像预览</label>
                                    <div class="avatar-preview" id="avatarPreview">
                                        <?php if (!empty($admin['avatar_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($admin['avatar_url']); ?>" 
                                                 alt="头像预览" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <i class="bi bi-person-circle" style="display: none;"></i>
                                        <?php else: ?>
                                            <i class="bi bi-person-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="field-description">输入头像URL后会自动预览</div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>保存修改
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 密码修改 -->
            <div class="row">
                <div class="col-12">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="bi bi-shield-lock me-2"></i>修改密码
                        </h3>
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <label for="current_password">当前密码</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <label for="new_password">新密码</label>
                                    </div>
                                    <div class="password-strength">
                                        <div class="strength-bar">
                                            <div class="strength-fill" id="strengthBar"></div>
                                        </div>
                                        <small class="text-muted" id="strengthText">密码强度</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-floating">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <label for="confirm_password">确认新密码</label>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-key me-2"></i>修改密码
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 头像预览功能
        document.getElementById('avatar_url').addEventListener('input', function() {
            const url = this.value.trim();
            const preview = document.getElementById('avatarPreview');
            
            if (url) {
                const img = preview.querySelector('img');
                const icon = preview.querySelector('i');
                
                if (img) {
                    img.src = url;
                    img.style.display = 'block';
                    icon.style.display = 'none';
                } else {
                    preview.innerHTML = `<img src="${url}" alt="头像预览" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><i class="bi bi-person-circle" style="display: none;"></i>`;
                }
            } else {
                preview.innerHTML = '<i class="bi bi-person-circle"></i>';
            }
        });

        // 密码强度检测
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let strengthLabel = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'strength-fill';
            
            if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
                strengthLabel = '弱';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-fair');
                strengthLabel = '一般';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-good');
                strengthLabel = '良好';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthLabel = '强';
            }
            
            strengthText.textContent = `密码强度: ${strengthLabel}`;
        });

        // 表单验证
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('新密码和确认密码不匹配');
                return;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('新密码长度至少6位');
                return;
            }
        });
    </script>
</body>
</html>
