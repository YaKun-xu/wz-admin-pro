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

// 处理保存配置
if ($_POST) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['config'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE website_configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE config_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $pdo->commit();
        $success = '网站信息保存成功！';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = '保存失败：' . $e->getMessage();
    }
}

// 获取网站配置数据
$website_configs = [];
$config_groups = [];

try {
    $stmt = $pdo->query("SELECT * FROM website_configs ORDER BY category, sort_order");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按分类分组
    foreach ($configs as $config) {
        $config_groups[$config['category']][] = $config;
        $website_configs[$config['config_key']] = $config['config_value'];
    }
} catch (Exception $e) {
    $error = '获取配置失败：' . $e->getMessage();
}

// 页面配置定义 - 按功能分组
$page_definitions = [
    'basic' => [
        'name' => '基本信息',
        'icon' => 'bi-info-circle',
        'description' => '网站基本信息和SEO配置'
    ],
    'contact' => [
        'name' => '联系信息',
        'icon' => 'bi-telephone',
        'description' => '客服联系方式和时间'
    ],
    'company' => [
        'name' => '公司信息',
        'icon' => 'bi-building',
        'description' => '公司基本信息和备案信息'
    ],
    'legal' => [
        'name' => '法律条款',
        'icon' => 'bi-shield-check',
        'description' => '隐私政策、服务条款等法律文档'
    ]
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站信息 - 王者荣耀查战力后台管理系统</title>
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

        .config-tabs {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .nav-pills .nav-link {
            border-radius: 15px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: #f8f9fa;
            color: #495057;
        }

        .nav-pills .nav-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .nav-pills .nav-link .badge {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #495057;
        }

        .nav-pills .nav-link.active .badge {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white;
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

        .tab-content {
            min-height: 400px;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .config-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid #667eea;
        }

        .config-item .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .config-item .form-control,
        .config-item .form-select {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
            
            .config-grid {
                grid-template-columns: 1fr;
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
                    <i class="bi bi-globe me-3"></i>网站信息
                </h1>
                <p>配置网站基本信息、联系方式、公司信息等</p>
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

            <!-- 配置标签页 -->
            <div class="config-tabs">
                <ul class="nav nav-pills" id="configTabs" role="tablist">
                    <?php foreach ($page_definitions as $page_key => $page_info): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $page_key === 'basic' ? 'active' : ''; ?>" 
                                    id="<?php echo $page_key; ?>-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#<?php echo $page_key; ?>" 
                                    type="button" 
                                    role="tab">
                                <i class="<?php echo $page_info['icon']; ?> me-2"></i>
                                <?php echo $page_info['name']; ?>
                                <span class="badge bg-secondary ms-2"><?php echo count($config_groups[$page_key] ?? []); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 配置表单 -->
            <div class="content-card">
                <form method="POST" id="websiteForm">
                    <div class="tab-content" id="configTabsContent">
                        <?php foreach ($page_definitions as $page_key => $page_info): ?>
                            <div class="tab-pane fade <?php echo $page_key === 'basic' ? 'show active' : ''; ?>" 
                                 id="<?php echo $page_key; ?>" 
                                 role="tabpanel">
                                
                                <div class="config-section">
                                    <h5>
                                        <i class="<?php echo $page_info['icon']; ?> me-2"></i>
                                        <?php echo $page_info['name']; ?>
                                    </h5>
                                    <p class="text-muted mb-4"><?php echo $page_info['description']; ?></p>
                                    
                                    <div class="config-grid">
                                        <?php 
                                        $page_configs_data = $config_groups[$page_key] ?? [];
                                        foreach ($page_configs_data as $config_item): 
                                        ?>
                                            <div class="config-item">
                                                <label class="form-label">
                                                    <?php echo htmlspecialchars($config_item['config_label'] ?? $config_item['config_key']); ?>
                                                    <?php if ($config_item['is_required']): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <?php if ($config_item['config_type'] === 'textarea'): ?>
                                                    <textarea class="form-control" 
                                                              name="config[<?php echo $config_item['config_key']; ?>]"
                                                              rows="3"
                                                              <?php echo $config_item['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($config_item['config_value']); ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?php echo $config_item['config_type']; ?>" 
                                                           class="form-control" 
                                                           name="config[<?php echo $config_item['config_key']; ?>]"
                                                           value="<?php echo htmlspecialchars($config_item['config_value']); ?>"
                                                           <?php echo $config_item['is_required'] ? 'required' : ''; ?>>
                                                <?php endif; ?>
                                                
                                                <?php if ($config_item['help_text']): ?>
                                                    <div class="form-text"><?php echo htmlspecialchars($config_item['help_text']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 提交按钮 -->
                    <div class="text-end mt-4">
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

        // 标签页切换动画
        const tabButtons = document.querySelectorAll('[data-bs-toggle="pill"]');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function (event) {
                const targetPane = document.querySelector(event.target.getAttribute('data-bs-target'));
                targetPane.style.opacity = '0';
                targetPane.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    targetPane.style.transition = 'all 0.3s ease';
                    targetPane.style.opacity = '1';
                    targetPane.style.transform = 'translateY(0)';
                }, 50);
            });
        });

        // 表单验证
        document.getElementById('websiteForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
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
