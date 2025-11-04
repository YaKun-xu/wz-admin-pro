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

// 获取配置
$configs = [];
try {
    $stmt = $pdo->query("SELECT * FROM configs ORDER BY config_key");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // 如果表不存在，使用默认配置
    $configs = [
        ['config_key' => 'site_name', 'config_value' => '王者荣耀查战力系统', 'description' => '网站名称'],
        ['config_key' => 'site_description', 'config_value' => '专业的王者荣耀战力查询平台', 'description' => '网站描述'],
        ['config_key' => 'contact_email', 'config_value' => 'admin@example.com', 'description' => '联系邮箱'],
        ['config_key' => 'contact_phone', 'config_value' => '400-123-4567', 'description' => '联系电话'],
        ['config_key' => 'wechat_pay_enabled', 'config_value' => '1', 'description' => '微信支付开关'],
        ['config_key' => 'alipay_enabled', 'config_value' => '1', 'description' => '支付宝开关'],
        ['config_key' => 'maintenance_mode', 'config_value' => '0', 'description' => '维护模式'],
        ['config_key' => 'max_upload_size', 'config_value' => '10', 'description' => '最大上传大小(MB)'],
    ];
}

// 处理保存
if ($_POST) {
    try {
        foreach ($_POST['config'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO configs (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $success = '配置保存成功！';
    } catch (Exception $e) {
        $error = '保存失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统配置 - 王者荣耀查战力后台管理系统</title>
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
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

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
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

        .config-section {
            margin-bottom: 2rem;
        }

        .config-section h5 {
            color: #495057;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
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
                    <i class="bi bi-gear me-3"></i>系统配置
                </h1>
                <p>管理系统基本配置信息</p>
            </div>

            <!-- 消息提示 -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 配置表单 -->
            <div class="content-card">
                <form method="POST">
                    <!-- 基本设置 -->
                    <div class="config-section">
                        <h5><i class="bi bi-info-circle me-2"></i>基本设置</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">网站名称</label>
                                    <input type="text" class="form-control" name="config[site_name]" 
                                           value="<?php echo htmlspecialchars($configs['site_name'] ?? '王者荣耀查战力系统'); ?>">
                                    <div class="form-text">显示在网站标题和页面头部</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">网站描述</label>
                                    <input type="text" class="form-control" name="config[site_description]" 
                                           value="<?php echo htmlspecialchars($configs['site_description'] ?? '专业的王者荣耀战力查询平台'); ?>">
                                    <div class="form-text">用于SEO和页面描述</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 联系信息 -->
                    <div class="config-section">
                        <h5><i class="bi bi-telephone me-2"></i>联系信息</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">联系邮箱</label>
                                    <input type="email" class="form-control" name="config[contact_email]" 
                                           value="<?php echo htmlspecialchars($configs['contact_email'] ?? 'admin@example.com'); ?>">
                                    <div class="form-text">用于接收系统通知和用户反馈</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">联系电话</label>
                                    <input type="text" class="form-control" name="config[contact_phone]" 
                                           value="<?php echo htmlspecialchars($configs['contact_phone'] ?? '400-123-4567'); ?>">
                                    <div class="form-text">客服联系电话</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 支付设置 -->
                    <div class="config-section">
                        <h5><i class="bi bi-credit-card me-2"></i>支付设置</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">微信支付</label>
                                    <select class="form-select" name="config[wechat_pay_enabled]">
                                        <option value="1" <?php echo ($configs['wechat_pay_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                        <option value="0" <?php echo ($configs['wechat_pay_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                    </select>
                                    <div class="form-text">是否启用微信支付功能</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">支付宝</label>
                                    <select class="form-select" name="config[alipay_enabled]">
                                        <option value="1" <?php echo ($configs['alipay_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>启用</option>
                                        <option value="0" <?php echo ($configs['alipay_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>禁用</option>
                                    </select>
                                    <div class="form-text">是否启用支付宝功能</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 系统设置 -->
                    <div class="config-section">
                        <h5><i class="bi bi-gear me-2"></i>系统设置</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">维护模式</label>
                                    <select class="form-select" name="config[maintenance_mode]">
                                        <option value="0" <?php echo ($configs['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>正常模式</option>
                                        <option value="1" <?php echo ($configs['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>维护模式</option>
                                    </select>
                                    <div class="form-text">维护模式下只有管理员可以访问</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">最大上传大小 (MB)</label>
                                    <input type="number" class="form-control" name="config[max_upload_size]" 
                                           value="<?php echo htmlspecialchars($configs['max_upload_size'] ?? '10'); ?>" min="1" max="100">
                                    <div class="form-text">限制文件上传的最大大小</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 提交按钮 -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                            <i class="bi bi-arrow-clockwise me-2"></i>重置
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>保存配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            if (confirm('确定要重置所有配置吗？')) {
                location.reload();
            }
        }

        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('请填写所有必填字段');
            }
        });
    </script>
</body>
</html>
