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
if ($_POST && isset($_POST['save_config'])) {
    try {
        $pdo->beginTransaction();
        $updated_count = 0;
        
        // 使用ID来更新，确保唯一性
        if (isset($_POST['config_ids']) && is_array($_POST['config_ids'])) {
            foreach ($_POST['config_ids'] as $id => $form_name) {
                if (isset($_POST['config'][$form_name])) {
                    $value = trim($_POST['config'][$form_name]);
                    $id = (int)$id; // 确保ID是整数，防止SQL注入
                    
                    // 验证ID是否存在
                    $check_stmt = $pdo->prepare("SELECT id FROM configs WHERE id = ?");
                    $check_stmt->execute([$id]);
                    if ($check_stmt->fetch()) {
                        $stmt = $pdo->prepare("UPDATE configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$value, $id]);
                        $updated_count++;
                    }
                }
            }
        }
        
        $pdo->commit();
        $success = "配置保存成功！共更新 {$updated_count} 项配置。";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = '保存失败：' . htmlspecialchars($e->getMessage());
    }
}

// 获取配置数据
$configs = [];
$page_configs = [];

try {
    // 按ID排序
    $stmt = $pdo->query("SELECT * FROM configs ORDER BY id ASC");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按页面分组，保持ID顺序
    // 自动修正拼写错误：将 'idnex' 合并到 'index'
    foreach ($configs as $config) {
        $page_name = $config['page_name'];
        // 修正拼写错误
        if (strtolower($page_name) === 'idnex') {
            $page_name = 'index';
        }
        $page_configs[$page_name][] = $config;
    }
} catch (Exception $e) {
    $error = '获取配置失败：' . $e->getMessage();
}

// 页面配置定义 - 从数据库动态获取，但保持分类默认配置
$page_definitions = [];

// 页面分类默认配置（保持不变）
$default_page_config = [
    'index' => ['name' => '首页配置', 'icon' => 'bi-house', 'description' => '小程序首页相关配置'],
    'zhanli' => ['name' => '战力查询', 'icon' => 'bi-search', 'description' => '战力查询页面配置'],
    'my' => ['name' => '个人中心', 'icon' => 'bi-person', 'description' => '个人中心页面配置'],
    'about' => ['name' => '关于页面', 'icon' => 'bi-info-circle', 'description' => '关于我们页面配置'],
    'settings' => ['name' => '设置页面', 'icon' => 'bi-gear', 'description' => '设置页面配置'],
    'order' => ['name' => '订单页面', 'icon' => 'bi-receipt', 'description' => '订单相关配置'],
    'tongyong' => ['name' => '通用配置', 'icon' => 'bi-tools', 'description' => '通用服务配置'],
    'rename' => ['name' => '改名页面', 'icon' => 'bi-pencil-square', 'description' => '改名服务页面配置']
];

// 从配置数据中获取所有页面（确保所有有数据的页面都能显示）
$all_page_names = array_keys($page_configs);

// 为每个有配置数据的页面设置定义
foreach ($all_page_names as $page_name) {
    $page_definitions[$page_name] = $default_page_config[$page_name] ?? [
        'name' => $page_name,
        'icon' => 'bi-gear',
        'description' => ''
    ];
}

// 智能判断字段类型的函数
function detectFieldType($config_item) {
    $config_key = strtolower($config_item['config_key']);
    $config_value = $config_item['config_value'];
    $notes = $config_item['notes'] ?? '';
    $page_name = $config_item['page_name'] ?? '';
    
    // 0. 特殊字段处理：switch 字段（联系客服按钮）
    if ($config_key === 'switch') {
        if ($page_name === 'zhanli') {
            // 战力查询：0=图片, 1=小程序, 2=小程序客服, 3=企业微信客服
            return [
                'type' => 'select',
                'options' => [
                    '0' => '图片',
                    '1' => '小程序',
                    '2' => '小程序客服',
                    '3' => '企业微信客服'
                ]
            ];
        } elseif ($page_name === 'my') {
            // 个人中心：0=图片, 1=小程序客服, 2=企业微信客服
            return [
                'type' => 'select',
                'options' => [
                    '0' => '图片',
                    '1' => '小程序客服',
                    '2' => '企业微信客服'
                ]
            ];
        }
    }
    
    // 1. 检查 notes 中是否包含类型定义（格式：类型:select|选项:0:关闭,1:开启）
    $type_pos = strpos($notes, '类型:');
    if ($type_pos !== false) {
        $type_str = substr($notes, $type_pos + 3); // 跳过 "类型:"
        $type_end = strpos($type_str, '|');
        if ($type_end !== false) {
            $type = trim(substr($type_str, 0, $type_end));
        } else {
            $type = trim($type_str);
        }
        
        $options = [];
        // 解析选项
        $opt_pos = strpos($notes, '选项:');
        if ($opt_pos !== false) {
            $opt_str = substr($notes, $opt_pos + 3); // 跳过 "选项:"
            $opt_end = strpos($opt_str, '|');
            if ($opt_end !== false) {
                $opt_str = substr($opt_str, 0, $opt_end);
            }
            $opt_pairs = explode(',', $opt_str);
            foreach ($opt_pairs as $pair) {
                if (strpos($pair, ':') !== false) {
                    list($val, $label) = explode(':', $pair, 2);
                    $options[trim($val)] = trim($label);
                }
            }
        }
        
        if ($type === 'select' && !empty($options)) {
            return ['type' => 'select', 'options' => $options];
        }
    }
    
    // 2. 检查 notes 中是否包含选项格式（如：0/1/2/3  图片/小程序/原生客服/企业微信）
    // 检查是否包含 "数字/数字" 的格式
    $parts = explode(' ', $notes);
    if (count($parts) >= 2) {
        $first_part = trim($parts[0]);
        // 检查第一部分是否是数字/数字的格式
        if (strpos($first_part, '/') !== false && is_numeric(str_replace('/', '', $first_part))) {
            $values = explode('/', $first_part);
            // 检查是否都是数字
            $all_numeric = true;
            foreach ($values as $v) {
                if (!is_numeric(trim($v))) {
                    $all_numeric = false;
                    break;
                }
            }
            
            if ($all_numeric && count($values) >= 2) {
                $labels_str = implode(' ', array_slice($parts, 1));
                
                // 尝试用斜杠分割标签
                if (strpos($labels_str, '/') !== false) {
                    $labels = explode('/', $labels_str);
                } else {
                    // 用多个空格分割
                    $labels = [];
                    $temp = str_replace('  ', ' ', $labels_str); // 替换多个空格为单个
                    $labels = explode(' ', $temp);
                }
                
                // 清理空白字符
                $labels = array_map('trim', $labels);
                $labels = array_filter($labels); // 移除空元素
                $labels = array_values($labels); // 重新索引
                
                // 确保值和标签数量匹配
                if (count($values) === count($labels)) {
                    $options = [];
                    foreach ($values as $idx => $val) {
                        $options[trim($val)] = $labels[$idx];
                    }
                    return ['type' => 'select', 'options' => $options];
                }
            }
        }
    }
    
    // 3. 根据 config_value 的值判断是否为 select（仅保留 true/false 等布尔值判断）
    if (in_array($config_value, ['true', 'false', '0', '1', 'yes', 'no', 'on', 'off'])) {
        $options = [];
        if (in_array($config_value, ['true', 'false'])) {
            $options = ['false' => '关闭', 'true' => '开启'];
        } elseif (in_array($config_value, ['0', '1'])) {
            $options = ['0' => '关闭', '1' => '开启'];
        } elseif (in_array($config_value, ['yes', 'no'])) {
            $options = ['no' => '否', 'yes' => '是'];
        } elseif (in_array($config_value, ['on', 'off'])) {
            $options = ['off' => '关闭', 'on' => '开启'];
        }
        if (!empty($options)) {
            return ['type' => 'select', 'options' => $options];
        }
    }
    
    // 4. 默认返回 text
    return ['type' => 'text'];
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
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .config-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .config-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .config-item .config-key {
            font-size: 0.75rem;
            color: #6c757d;
            font-family: monospace;
            margin-top: 0.25rem;
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
        @media (max-width: 1400px) {
            .config-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .config-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
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
                    <i class="bi bi-gear me-3"></i>系统配置
                </h1>
                <p>管理小程序各页面配置信息，基于现有数据库数据</p>
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
                            <button class="nav-link <?php echo $page_key === 'index' ? 'active' : ''; ?>" 
                                    id="<?php echo $page_key; ?>-tab" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#<?php echo $page_key; ?>" 
                                    type="button" 
                                    role="tab"
                                    data-page-key="<?php echo htmlspecialchars($page_key); ?>">
                                <i class="<?php echo $page_info['icon']; ?> me-2"></i>
                                <?php echo $page_info['name']; ?>
                                <span class="badge bg-secondary ms-2"><?php echo count($page_configs[$page_key] ?? []); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 配置表单 -->
            <div class="content-card">
                <form method="POST" id="configForm">
                    <div class="tab-content" id="configTabsContent">
                        <?php foreach ($page_definitions as $page_key => $page_info): ?>
                            <div class="tab-pane fade <?php echo $page_key === 'index' ? 'show active' : ''; ?>" 
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
                                        // 获取当前页面的配置，已经按ID排序
                                        $page_configs_data = $page_configs[$page_key] ?? [];
                                        
                                        foreach ($page_configs_data as $config_item): 
                                            // 使用notes作为显示名称，如果没有notes则使用config_key
                                            $display_label = !empty($config_item['notes']) ? $config_item['notes'] : $config_item['config_key'];
                                            
                                            // 智能判断字段类型（完全基于数据库）
                                            $field_def = detectFieldType($config_item);
                                        ?>
                                            <div class="config-item">
                                                <label class="form-label">
                                                    <?php echo htmlspecialchars($display_label); ?>
                                                    <?php if (!empty($config_item['config_key'])): ?>
                                                        <span class="config-key d-block">键名: <?php echo htmlspecialchars($config_item['config_key']); ?></span>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <?php 
                                                // 构建表单name，如果有parent_key则包含在name中
                                                $form_name = $page_key . '.' . $config_item['config_key'];
                                                if (!empty($config_item['parent_key'])) {
                                                    $form_name .= '.' . $config_item['parent_key'];
                                                }
                                                // 使用ID作为唯一标识
                                                $config_id = $config_item['id'];
                                                ?>
                                                <!-- 隐藏字段：保存ID和表单名的映射 -->
                                                <input type="hidden" name="config_ids[<?php echo $config_id; ?>]" value="<?php echo htmlspecialchars($form_name); ?>">
                                                
                                                <?php if ($field_def['type'] === 'select' && !empty($field_def['options'])): ?>
                                                    <select class="form-select" 
                                                            name="config[<?php echo htmlspecialchars($form_name); ?>]">
                                                        <?php foreach ($field_def['options'] as $value => $label): ?>
                                                            <option value="<?php echo htmlspecialchars($value); ?>" 
                                                                    <?php echo $config_item['config_value'] == $value ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif ($field_def['type'] === 'textarea'): ?>
                                                    <textarea class="form-control" 
                                                              name="config[<?php echo htmlspecialchars($form_name); ?>]"
                                                              rows="3"><?php echo htmlspecialchars($config_item['config_value']); ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?php echo htmlspecialchars($field_def['type']); ?>" 
                                                           class="form-control" 
                                                           name="config[<?php echo htmlspecialchars($form_name); ?>]"
                                                           value="<?php echo htmlspecialchars($config_item['config_value']); ?>">
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
                        <button type="submit" name="save_config" class="btn btn-primary" id="saveBtn">
                            <i class="bi bi-check-lg me-2"></i>保存配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 表单提交确认
        document.getElementById('configForm').addEventListener('submit', function(e) {
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>保存中...';
        });

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
        
        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 检查是否有未保存的更改（可选功能）
            const form = document.getElementById('configForm');
            let formChanged = false;
            
            form.addEventListener('change', function() {
                formChanged = true;
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = '您有未保存的更改，确定要离开吗？';
                }
            });
        });
    </script>
</body>
</html>
