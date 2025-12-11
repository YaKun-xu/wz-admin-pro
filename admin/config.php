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
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config'])) {
    try {
        $pdo->beginTransaction();
        $updated_count = 0;
        
        foreach ($_POST['config'] as $key => $value) {
            // 解析配置键，格式：page_name.config_key 或 page_name.config_key.parent_key
            // 注意：parent_key可能包含点号（如swiperList.0），需要合并所有剩余部分
            $parts = explode('.', $key);
            if (count($parts) >= 2) {
                $page_name = $parts[0];
                $config_key = $parts[1];
                $parent_key = null;
                
                // 如果有第三部分，说明有parent_key，需要合并所有剩余部分
                if (count($parts) >= 3) {
                    $parent_key = implode('.', array_slice($parts, 2));
                }
                
                $value = trim($value);
                
                // 更新配置，根据是否有parent_key来区分
                if ($parent_key !== null) {
                    $stmt = $pdo->prepare("UPDATE configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE page_name = ? AND config_key = ? AND parent_key = ?");
                    if ($stmt->execute([$value, $page_name, $config_key, $parent_key])) {
                        $updated_count++;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE page_name = ? AND config_key = ? AND (parent_key IS NULL OR parent_key = '')");
                    if ($stmt->execute([$value, $page_name, $config_key])) {
                        $updated_count++;
                    }
                }
            }
        }
        
        if ($updated_count === 0) {
            throw new Exception('没有配置项被更新');
        }
        
        $pdo->commit();
        $success = "配置保存成功！共更新 {$updated_count} 项配置。";
        
        // 保存成功后，重新获取配置数据以显示最新值
        $stmt = $pdo->query("SELECT * FROM configs ORDER BY id ASC");
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $page_configs = [];
        foreach ($configs as $config) {
            $page_name = $config['page_name'];
            // 修正拼写错误
            if (strtolower($page_name) === 'idnex') {
                $page_name = 'index';
            }
            $page_configs[$page_name][] = $config;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = '保存失败：' . htmlspecialchars($e->getMessage());
    }
}

// 获取配置数据（如果保存时已经获取过，则不再重复获取）
if (!isset($configs) || empty($configs)) {
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
        if (!isset($error)) {
            $error = '获取配置失败：' . $e->getMessage();
        }
    }
}

// 页面配置定义 - 基于实际数据
$page_definitions = [
    'index' => [
        'name' => '首页配置',
        'icon' => 'bi-house',
        'description' => '小程序首页相关配置'
    ],
    'zhanli' => [
        'name' => '战力查询',
        'icon' => 'bi-search',
        'description' => '战力查询页面配置'
    ],
    'my' => [
        'name' => '个人中心',
        'icon' => 'bi-person',
        'description' => '个人中心页面配置'
    ],
    'about' => [
        'name' => '关于页面',
        'icon' => 'bi-info-circle',
        'description' => '关于我们页面配置'
    ],
    'settings' => [
        'name' => '设置页面',
        'icon' => 'bi-gear',
        'description' => '设置页面配置'
    ],
    'order' => [
        'name' => '订单页面',
        'icon' => 'bi-receipt',
        'description' => '订单相关配置'
    ],
    'tongyong' => [
        'name' => '通用配置',
        'icon' => 'bi-tools',
        'description' => '通用服务配置'
    ],
    'rename' => [
        'name' => '改名页面',
        'icon' => 'bi-pencil-square',
        'description' => '改名服务页面配置'
    ]
];

// 智能判断字段类型的函数
function detectFieldType($config_item, $page_name = '') {
    $config_key = strtolower($config_item['config_key']);
    $config_value = $config_item['config_value'];
    $notes = $config_item['notes'] ?? '';
    
    // 特殊字段处理：switch 字段（联系客服按钮）
    if ($config_key === 'switch') {
        if ($page_name === 'zhanli') {
            // 战力查询：0=图片, 1=小程序, 2=小程序客服, 3=企业微信客服
            return [
                'type' => 'select',
                'options' => ['0' => '图片', '1' => '小程序', '2' => '小程序客服', '3' => '企业微信客服'],
                'label' => $config_item['notes'] ?? '联系客服按钮'
            ];
        } elseif ($page_name === 'my') {
            // 个人中心：0=图片, 1=小程序客服, 2=企业微信客服
            return [
                'type' => 'select',
                'options' => ['0' => '图片', '1' => '小程序客服', '2' => '企业微信客服'],
                'label' => $config_item['notes'] ?? '联系客服按钮'
            ];
        }
    }
    
    // 1. 检查 notes 中是否包含类型定义（格式：类型:select|选项:0:关闭,1:开启）
    $type_pos = strpos($notes, '类型:');
    if ($type_pos !== false) {
        $type_str = substr($notes, $type_pos + 3);
        $type_end = strpos($type_str, '|');
        $type = $type_end !== false ? trim(substr($type_str, 0, $type_end)) : trim($type_str);
        
        $options = [];
        $opt_pos = strpos($notes, '选项:');
        if ($opt_pos !== false) {
            $opt_str = substr($notes, $opt_pos + 3);
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
            return [
                'type' => 'select',
                'options' => $options,
                'label' => $notes
            ];
        }
    }
    
    // 2. 检查 notes 中是否包含选项格式（如：0/1/2/3  图片/小程序/原生客服/企业微信）
    $parts = explode(' ', $notes);
    if (count($parts) >= 2) {
        $first_part = trim($parts[0]);
        if (strpos($first_part, '/') !== false && is_numeric(str_replace('/', '', $first_part))) {
            $values = explode('/', $first_part);
            $all_numeric = true;
            foreach ($values as $v) {
                if (!is_numeric(trim($v))) {
                    $all_numeric = false;
                    break;
                }
            }
            
            if ($all_numeric && count($values) >= 2) {
                $labels_str = implode(' ', array_slice($parts, 1));
                $labels = strpos($labels_str, '/') !== false ? explode('/', $labels_str) : explode(' ', str_replace('  ', ' ', $labels_str));
                $labels = array_values(array_filter(array_map('trim', $labels)));
                
                if (count($values) === count($labels)) {
                    $options = [];
                    foreach ($values as $idx => $val) {
                        $options[trim($val)] = $labels[$idx];
                    }
                    return [
                        'type' => 'select',
                        'options' => $options,
                        'label' => $notes
                    ];
                }
            }
        }
    }
    
    // 3. 根据 config_value 的值判断是否为 select（布尔值判断）
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
            return [
                'type' => 'select',
                'options' => $options,
                'label' => $config_item['notes'] ?? $config_item['config_key']
            ];
        }
    }
    
    // 4. 默认返回 text
    return [
        'type' => 'text',
        'label' => $config_item['notes'] ?? $config_item['config_key'],
        'help' => ''
    ];
}

// 配置字段定义 - 基于实际数据
// ===========================================
// 按页面分类，方便查找和维护
// ===========================================

// 配置字段定义 - 基于数据库实际数据，label使用数据库的notes字段
$field_definitions = [
    // ===========================================
    // index - 首页配置
    // ===========================================
    'index_kefu' => ['type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启']],
    'index_jump' => ['type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启']],
    'index_noticeContent' => ['type' => 'textarea'],
    'index_qrcodeImage' => ['type' => 'url'],
    'index_appId' => ['type' => 'text'],
    'index_path' => ['type' => 'text'],
    'index_rewardedVideoAd' => ['type' => 'text'],
    'index_videoAdunit' => ['type' => 'text'],
    'index_wxAdEnabled' => ['type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启']],
    'index_dyAdEnabled_adConfig' => ['type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启']],
    
    // ===========================================
    // zhanli - 战力查询页面配置
    // ===========================================
    'zhanli_weidianId_miniProgram' => ['type' => 'text'],
    'zhanli_weidianUrl_miniProgram' => ['type' => 'text'],
    'zhanli_weburl' => ['type' => 'url'],
    'zhanli_switch' => ['type' => 'select', 'options' => ['0' => '图片', '1' => '小程序', '2' => '小程序客服', '3' => '企业微信客服']],
    'zhanli_qrcodeImage' => ['type' => 'url'],
    'zhanli_ddappId' => ['type' => 'text'],
    'zhanli_ddpath' => ['type' => 'text'],
    'zhanli_qywxid' => ['type' => 'text'],
    'zhanli_qykfurl' => ['type' => 'url'],
    'zhanli_bottomAdId_adInfo' => ['type' => 'text'],
    'zhanli_interstitialAdUnitId_adInfo' => ['type' => 'text'],
    
    // ===========================================
    // my - 个人中心页面配置
    // ===========================================
    'my_switch' => ['type' => 'select', 'options' => ['0' => '图片', '1' => '小程序客服', '2' => '企业微信客服']],
    'my_qrcodeImage' => ['type' => 'url'],
    'my_qywxid' => ['type' => 'text'],
    'my_qykfurl' => ['type' => 'url'],
    'my_gzhewm' => ['type' => 'url'],
    'my_avatar_userInfo' => ['type' => 'url'],
    'my_nickname_userInfo' => ['type' => 'text'],
    'my_userId_userInfo' => ['type' => 'text'],
    'my_appId_config_miniProgram' => ['type' => 'text'],
    'my_orderPath_config_miniProgram' => ['type' => 'text'],
    'my_buyPath_config_miniProgram' => ['type' => 'text'],
    'my_ddappId_config_miniProgram' => ['type' => 'text'],
    'my_ddpath_config_miniProgram' => ['type' => 'text'],
    'my_orderUrl_config_h5' => ['type' => 'url'],
    'my_buyUrl_config_h5' => ['type' => 'url'],
    'my_path_config_about' => ['type' => 'text'],
    'my_nativeAdunit' => ['type' => 'text'],
    
    // ===========================================
    // about - 关于页面配置
    // ===========================================
    'about_wechat_contactInfo' => ['type' => 'text'],
    'about_publicAccount_contactInfo' => ['type' => 'text'],
    'about_templateId_adInfo' => ['type' => 'text'],
    
    // ===========================================
    // settings - 设置页面配置
    // ===========================================
    'settings_avatar_userInfo' => ['type' => 'url'],
    'settings_nickname_userInfo' => ['type' => 'text'],
    'settings_unitId_adInfo' => ['type' => 'text'],
    
    // ===========================================
    // order - 订单页面配置
    // ===========================================
    'order_videoTutorialUrl' => ['type' => 'url'],
    
    // ===========================================
    // tongyong - 通用配置
    // ===========================================
    'tongyong_workTime_serviceInfo' => ['type' => 'text'],
    'tongyong_remark_serviceInfo' => ['type' => 'text'],
    
    // ===========================================
    // rename - 改名页面配置
    // ===========================================
    'rename_notice' => ['type' => 'textarea'],
    'rename_appId_shop' => ['type' => 'text'],
    'rename_path_shop' => ['type' => 'text'],
    'rename_text1_text_0' => ['type' => 'text'],
    'rename_text2_text_1' => ['type' => 'text'],
    'rename_text3_text_2' => ['type' => 'text'],
    'rename_text4_text_3' => ['type' => 'text'],
];
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
                                    role="tab">
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
                                        // 直接使用从数据库获取的数据，已经按ID排序
                                        $page_configs_data = $page_configs[$page_key] ?? [];
                                        
                                        foreach ($page_configs_data as $config_item): 
                                            // 构建带页面前缀的键名来查找字段定义
                                            // 如果有parent_key，也包含在键名中以便区分相同字段名的不同实例
                                            $field_key = $page_key . '_' . $config_item['config_key'];
                                            if (!empty($config_item['parent_key'])) {
                                                // 将parent_key中的点号替换为下划线，避免键名冲突
                                                $parent_suffix = str_replace('.', '_', $config_item['parent_key']);
                                                $field_key_with_parent = $field_key . '_' . $parent_suffix;
                                                // 先尝试带parent_key的键名
                                                if (isset($field_definitions[$field_key_with_parent])) {
                                                    $field_key = $field_key_with_parent;
                                                }
                                            }
                                            
                                            // 如果字段定义中存在，使用定义；否则自动判断
                                            if (isset($field_definitions[$field_key])) {
                                                $field_def = $field_definitions[$field_key];
                                            } else {
                                                // 自动判断字段类型和选项
                                                $field_def = detectFieldType($config_item, $page_key);
                                            }
                                            
                                            // 使用数据库的notes作为显示名称，如果没有notes则使用config_key
                                            $display_label = !empty($config_item['notes']) ? $config_item['notes'] : $config_item['config_key'];
                                        ?>
                                            <div class="config-item">
                                                <label class="form-label">
                                                    <?php echo htmlspecialchars($display_label); ?>
                                                </label>
                                                
                                                <?php 
                                                // 构建表单name，如果有parent_key则包含在name中
                                                $form_name = $page_key . '.' . $config_item['config_key'];
                                                if (!empty($config_item['parent_key'])) {
                                                    $form_name .= '.' . $config_item['parent_key'];
                                                }
                                                ?>
                                                <?php if ($field_def['type'] === 'select'): ?>
                                                    <?php 
                                                    $is_readonly = isset($field_def['readonly']) && $field_def['readonly'] === true;
                                                    $fixed_value = $field_def['fixed_value'] ?? null;
                                                    ?>
                                                    <select class="form-select" 
                                                            name="config[<?php echo htmlspecialchars($form_name); ?>]"
                                                            <?php if ($is_readonly): ?>disabled title="此字段已固定为<?php echo htmlspecialchars($fixed_value ?? '指定值'); ?>，不可修改"<?php endif; ?>>
                                                        <?php foreach ($field_def['options'] as $value => $label): ?>
                                                            <option value="<?php echo $value; ?>" 
                                                                    <?php 
                                                                    if ($is_readonly && $fixed_value !== null) {
                                                                        echo $value == $fixed_value ? 'selected' : '';
                                                                    } else {
                                                                        echo $config_item['config_value'] == $value ? 'selected' : '';
                                                                    }
                                                                    ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if ($is_readonly && $fixed_value !== null): ?>
                                                        <input type="hidden" name="config[<?php echo htmlspecialchars($form_name); ?>]" value="<?php echo htmlspecialchars($fixed_value); ?>">
                                                        <div class="form-text text-muted">
                                                            <i class="bi bi-lock-fill"></i> 此字段已固定为 <?php echo htmlspecialchars($field_def['options'][$fixed_value] ?? $fixed_value); ?>，不可修改
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($field_def['type'] === 'textarea'): ?>
                                                    <textarea class="form-control" 
                                                              name="config[<?php echo htmlspecialchars($form_name); ?>]"
                                                              rows="3"><?php echo htmlspecialchars($config_item['config_value']); ?></textarea>
                                                <?php else: ?>
                                                    <input type="<?php echo $field_def['type']; ?>" 
                                                           class="form-control" 
                                                           name="config[<?php echo htmlspecialchars($form_name); ?>]"
                                                           value="<?php echo htmlspecialchars($config_item['config_value']); ?>">
                                                <?php endif; ?>
                                                
                                                <?php if ($field_def['help']): ?>
                                                    <div class="form-text"><?php echo $field_def['help']; ?></div>
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
    </script>
</body>
</html>
