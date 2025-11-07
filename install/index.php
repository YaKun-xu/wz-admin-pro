<?php
/**
 * 安装程序主页面
 * 王者荣耀查战力系统安装向导
 */

session_start();

// 检查是否已安装
$installed_file = '../server/.installed';
if (file_exists($installed_file)) {
    die('系统已安装，如需重新安装，请先删除 server/.installed 文件');
}

$step = $_GET['step'] ?? 1;
$step = (int)$step;

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        // 测试数据库连接
        $db_host = $_POST['host'] ?? 'localhost';
        $db_port = $_POST['port'] ?? 3306;
        $db_name = $_POST['database'] ?? '';
        $db_user = $_POST['username'] ?? 'root';
        $db_pass = $_POST['password'] ?? '';
        
        try {
            // 先尝试连接 MySQL（不指定数据库）
            $test_pdo = new PDO(
                "mysql:host={$db_host};port={$db_port};charset=utf8mb4",
                $db_user,
                $db_pass
            );
            $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 测试是否可以创建数据库
            $test_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // 保存数据库配置
            $_SESSION['db_config'] = [
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'username' => $db_user,
                'password' => $db_pass,
                'charset' => 'utf8mb4'
            ];
            
            // 直接设置为覆盖模式（清空数据库）
            $_SESSION['overwrite_db'] = true;
            unset($_SESSION['show_db_confirm']);
            
            header('Location: index.php?step=3');
            exit;
        } catch (PDOException $e) {
            $_SESSION['db_error'] = '数据库连接失败: ' . $e->getMessage();
            header('Location: index.php?step=2');
            exit;
        }
    } elseif ($step === 3) {
        // 验证密码确认
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        if ($password !== $password_confirm) {
            $_SESSION['install_error'] = '两次输入的密码不一致';
            header('Location: index.php?step=3');
            exit;
        }
        
        // 创建管理员账号
        $_SESSION['admin_config'] = [
            'username' => $_POST['username'] ?? 'admin',
            'login_name' => $_POST['login_name'] ?? 'admin',
            'password' => $password
        ];
        header('Location: install.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 王者荣耀查战力系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .install-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .install-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .install-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .install-body {
            padding: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-item::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        .step-item:last-child::after {
            display: none;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .step-item.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step-item.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step-title {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .step-item.active .step-title {
            color: #667eea;
            font-weight: 600;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn {
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .check-item {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-item.success {
            background: #d4edda;
            color: #155724;
        }
        .check-item.error {
            background: #f8d7da;
            color: #721c24;
        }
        .check-item.warning {
            background: #fff3cd;
            color: #856404;
        }
        .modal {
            z-index: 1050;
        }
        .modal-backdrop {
            z-index: 1040;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.show {
            display: block !important;
        }
        .modal-header.bg-warning {
            background-color: #ffc107;
            color: #000;
        }
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }
        .modal-footer .btn {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="bi bi-gear-fill me-2"></i>系统安装向导</h1>
            <p>王者荣耀查战力系统</p>
        </div>
        
        <div class="install-body">
            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?>">
                    <div class="step-number">1</div>
                    <div class="step-title">环境检查</div>
                </div>
                <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <div class="step-number">2</div>
                    <div class="step-title">数据库配置</div>
                </div>
                <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <div class="step-number">3</div>
                    <div class="step-title">管理员账号</div>
                </div>
                <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <div class="step-number">4</div>
                    <div class="step-title">完成安装</div>
                </div>
            </div>

            <!-- 步骤内容 -->
            <?php if ($step === 1): ?>
                <!-- 步骤1: 环境检查 -->
                <?php
                $php_version = PHP_VERSION;
                $php_ok = version_compare($php_version, '7.4.0', '>=');
                
                $extensions = [
                    'pdo' => extension_loaded('pdo'),
                    'pdo_mysql' => extension_loaded('pdo_mysql'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                    'json' => extension_loaded('json')
                ];
                
                $writable_dirs = [
                    '../server' => is_writable('../server'),
                    '../admin' => is_writable('../admin')
                ];
                
                $all_ok = $php_ok && !in_array(false, $extensions) && !in_array(false, $writable_dirs);
                ?>
                
                <h3 class="mb-4"><i class="bi bi-check-circle me-2"></i>环境检查</h3>
                
                <div class="check-item <?php echo $php_ok ? 'success' : 'error'; ?>">
                    <span><i class="bi bi-<?php echo $php_ok ? 'check' : 'x'; ?>-circle me-2"></i>PHP 版本: <?php echo $php_version; ?></span>
                    <span><?php echo $php_ok ? '✓ 通过' : '✗ 需要 PHP 7.4+'; ?></span>
                </div>
                
                <?php foreach ($extensions as $ext => $loaded): ?>
                <div class="check-item <?php echo $loaded ? 'success' : 'error'; ?>">
                    <span><i class="bi bi-<?php echo $loaded ? 'check' : 'x'; ?>-circle me-2"></i>PHP 扩展: <?php echo $ext; ?></span>
                    <span><?php echo $loaded ? '✓ 已安装' : '✗ 未安装'; ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php foreach ($writable_dirs as $dir => $writable): ?>
                <div class="check-item <?php echo $writable ? 'success' : 'error'; ?>">
                    <span><i class="bi bi-<?php echo $writable ? 'check' : 'x'; ?>-circle me-2"></i>目录权限: <?php echo $dir; ?></span>
                    <span><?php echo $writable ? '✓ 可写' : '✗ 不可写'; ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="mt-4 text-end">
                    <?php if ($all_ok): ?>
                        <a href="index.php?step=2" class="btn btn-primary">
                            下一步 <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>请先解决上述问题</button>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($step === 2): ?>
                <!-- 步骤2: 数据库配置 -->
                <h3 class="mb-4"><i class="bi bi-database me-2"></i>数据库配置</h3>
                
                <?php if (isset($_SESSION['db_error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['db_error']); unset($_SESSION['db_error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="dbConfigForm">
                    <div class="mb-3">
                        <label class="form-label">数据库主机</label>
                        <input type="text" class="form-control" name="host" id="db_host" value="<?php echo htmlspecialchars($_SESSION['db_config']['host'] ?? 'localhost'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库端口</label>
                        <input type="number" class="form-control" name="port" id="db_port" value="<?php echo htmlspecialchars($_SESSION['db_config']['port'] ?? '3306'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库名称</label>
                        <input type="text" class="form-control" name="database" id="db_database" value="<?php echo htmlspecialchars($_SESSION['db_config']['database'] ?? 'zhanli'); ?>" required>
                        <small class="text-muted">如果数据库不存在，安装程序会自动创建</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库用户名</label>
                        <input type="text" class="form-control" name="username" id="db_username" value="<?php echo htmlspecialchars($_SESSION['db_config']['username'] ?? 'root'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库密码</label>
                        <input type="password" class="form-control" name="password" id="db_password" value="">
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="index.php?step=1" class="btn btn-secondary">上一步</a>
                        <button type="submit" class="btn btn-primary">
                            下一步 <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step === 3): ?>
                <!-- 步骤3: 管理员账号 -->
                <h3 class="mb-4"><i class="bi bi-person-plus me-2"></i>创建管理员账号</h3>
                
                <?php if (isset($_SESSION['install_error'])): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($_SESSION['install_error']); unset($_SESSION['install_error']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">用户名</label>
                        <input type="text" class="form-control" name="username" value="管理员" required>
                        <small class="text-muted">显示在系统中的名称</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">登录账号</label>
                        <input type="text" class="form-control" name="login_name" value="admin" required>
                        <small class="text-muted">用于登录的账号</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">登录密码</label>
                        <input type="password" class="form-control" name="password" value="admin123" required>
                        <small class="text-muted">建议使用强密码</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">确认密码</label>
                        <input type="password" class="form-control" name="password_confirm" value="admin123" required>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="index.php?step=2" class="btn btn-secondary">上一步</a>
                        <button type="submit" class="btn btn-primary">
                            开始安装 <i class="bi bi-play-fill ms-2"></i>
                        </button>
                    </div>
                </form>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 密码确认验证
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]')?.value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]')?.value;
            
            if (password && passwordConfirm && password !== passwordConfirm) {
                e.preventDefault();
                alert('两次输入的密码不一致！');
                return false;
            }
        });
        
        // 页面加载时恢复表单数据和弹窗状态
        window.addEventListener('DOMContentLoaded', function() {
            // 恢复表单数据（从 session 或 sessionStorage）
            const dbConfigForm = document.getElementById('dbConfigForm');
            if (dbConfigForm) {
                // 优先使用 PHP session 中的数据
                <?php if (isset($_SESSION['db_config'])): ?>
                const sessionData = {
                    host: '<?php echo htmlspecialchars($_SESSION['db_config']['host'] ?? 'localhost', ENT_QUOTES); ?>',
                    port: '<?php echo htmlspecialchars($_SESSION['db_config']['port'] ?? '3306', ENT_QUOTES); ?>',
                    database: '<?php echo htmlspecialchars($_SESSION['db_config']['database'] ?? 'zhanli', ENT_QUOTES); ?>',
                    username: '<?php echo htmlspecialchars($_SESSION['db_config']['username'] ?? 'root', ENT_QUOTES); ?>'
                };
                if (document.getElementById('db_host')) document.getElementById('db_host').value = sessionData.host;
                if (document.getElementById('db_port')) document.getElementById('db_port').value = sessionData.port;
                if (document.getElementById('db_database')) document.getElementById('db_database').value = sessionData.database;
                if (document.getElementById('db_username')) document.getElementById('db_username').value = sessionData.username;
                <?php else: ?>
                // 如果没有 session 数据，尝试从 sessionStorage 恢复
                const dbConfigBackup = sessionStorage.getItem('db_config_backup');
                if (dbConfigBackup) {
                    try {
                        const formData = JSON.parse(dbConfigBackup);
                        if (document.getElementById('db_host')) document.getElementById('db_host').value = formData.host || 'localhost';
                        if (document.getElementById('db_port')) document.getElementById('db_port').value = formData.port || '3306';
                        if (document.getElementById('db_database')) document.getElementById('db_database').value = formData.database || 'zhanli';
                        if (document.getElementById('db_username')) document.getElementById('db_username').value = formData.username || 'root';
                        // 密码不恢复，安全考虑
                    } catch (e) {
                        console.error('恢复表单数据失败:', e);
                    }
                }
                <?php endif; ?>
            }
        });
    </script>
</body>
</html>

