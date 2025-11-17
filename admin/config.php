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
                
                // 更新配置，根据是否有parent_key来区分
                if ($parent_key !== null) {
                    $stmt = $pdo->prepare("UPDATE configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE page_name = ? AND config_key = ? AND parent_key = ?");
                    $stmt->execute([$value, $page_name, $config_key, $parent_key]);
                } else {
                    $stmt = $pdo->prepare("UPDATE configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE page_name = ? AND config_key = ? AND (parent_key IS NULL OR parent_key = '')");
                    $stmt->execute([$value, $page_name, $config_key]);
                }
            }
        }
        
        $pdo->commit();
        $success = '配置保存成功！';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = '保存失败：' . $e->getMessage();
    }
}

// 获取配置数据
$configs = [];
$page_configs = [];

try {
    $stmt = $pdo->query("SELECT * FROM configs ORDER BY page_name, config_key");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 按页面分组
    foreach ($configs as $config) {
        $page_configs[$config['page_name']][] = $config;
    }
} catch (Exception $e) {
    $error = '获取配置失败：' . $e->getMessage();
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

// 配置字段定义 - 基于实际数据
// ===========================================
// 按页面分类，方便查找和维护
// ===========================================

$field_definitions = [
    // ===========================================
    // index - 首页配置
    // ===========================================
    'index_noticeContent' => ['label' => '首页配置 - 公告内容', 'type' => 'textarea', 'help' => '页面公告内容'],
    'index_qrcodeImage' => ['label' => '首页配置 - 客服二维码', 'type' => 'url', 'help' => '客服二维码'],

    'index_appId' => ['label' => '首页配置 - 小程序AppID', 'type' => 'text', 'help' => '个人版小程序需要填写'],
    'index_path' => ['label' => '首页配置 - 页面路径', 'type' => 'text', 'help' => '个人版小程序需要填写'],

    'index_rewardedVideoAd' => ['label' => '首页配置 - 激励视频广告', 'type' => 'text', 'help' => '激励视频广告单元ID'],
    'index_videoAdunit' => ['label' => '首页配置 - 视频广告单元', 'type' => 'text', 'help' => '视频广告单元ID'],
    
    'index_wxAdEnabled' => ['label' => '首页配置 - 广告开关', 'type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启'], 'help' => '查询是否启用强制广告'],
    'index_qykefu' => ['label' => '首页配置 - 企业客服开关', 'type' => 'select', 'options' => ['0' => '关闭', '1' => '开启'], 'help' => '企业客服功能开关'],
    'index_dyAdEnabled_adConfig' => ['label' => '首页配置 - 抖音广告开关', 'type' => 'select', 'options' => ['false' => '关闭', 'true' => '开启'], 'help' => '抖音广告开关'],
    
    // ===========================================
    // zhanli - 战力查询页面配置
    // ===========================================
    // swiperList.0 相关字段
    'zhanli_type_swiperList_0' => ['label' => '战力查询 - 轮播图类型(第1项)', 'type' => 'select', 'options' => ['link' => '图片', 'ad' => '广告'], 'help' => '轮播图第一项的内容类型', 'readonly' => true, 'fixed_value' => 'link'],
    'zhanli_image_swiperList_0' => ['label' => '战力查询 - 轮播图图片(第1项)', 'type' => 'url', 'help' => '轮播图第一项的图片地址'],
    'zhanli_appId_swiperList_0' => ['label' => '战力查询 - 轮播图小程序AppID(第1项)', 'type' => 'text', 'help' => '轮播图第一项的小程序AppID'],
    'zhanli_path_swiperList_0' => ['label' => '战力查询 - 轮播图页面路径(第1项)', 'type' => 'text', 'help' => '轮播图第一项的页面路径'],
    'zhanli_target_swiperList_0' => ['label' => '战力查询 - 轮播图跳转目标(第1项)', 'type' => 'select', 'options' => ['miniProgram' => '小程序', 'h5' => 'H5页面'], 'help' => '轮播图第一项的跳转目标'],

    // swiperList.1 相关字段
    'zhanli_type_swiperList_1' => ['label' => '战力查询 - 轮播图类型(第2项)', 'type' => 'select', 'options' => ['link' => '链接', 'ad' => '广告'], 'help' => '轮播图第二项的内容类型', 'readonly' => true, 'fixed_value' => 'ad'],
    'zhanli_swiperAdId_adInfo' => ['label' => '战力查询 - 轮播广告ID', 'type' => 'text', 'help' => '轮播广告单元ID'],

    'zhanli_weburl' => ['label' => '战力查询 - 网页链接', 'type' => 'url', 'help' => '外部网页链接'],
    'zhanli_qrcodeImage' => ['label' => '战力查询 - 客服二维码', 'type' => 'url', 'help' => '联系客服按钮'],
    'zhanli_switch' => ['label' => '战力查询 - 模式切换', 'type' => 'select', 'options' => ['0' => '客服模式', '1' => '小程序模式'], 'help' => '联系客服按钮功能'],
    'zhanli_ddappId' => ['label' => '战力查询 - 联系客服小程序ID', 'type' => 'text', 'help' => '联系客服小程序AppID'],
    'zhanli_ddpath' => ['label' => '战力查询 - 联系客服路径', 'type' => 'text', 'help' => '联系客服小程序页面路径'],
    'zhanli_weidianId_miniProgram' => ['label' => '战力查询 - 改战区小程序ID', 'type' => 'text', 'help' => '改战区按钮跳转'],
    'zhanli_weidianUrl_miniProgram' => ['label' => '战力查询 - 改战区小程序路径', 'type' => 'text', 'help' => '改战区按钮跳转'],

    'zhanli_bottomAdId_adInfo' => ['label' => '战力查询 - 底部广告ID', 'type' => 'text', 'help' => '底部广告单元ID'],
    'zhanli_interstitialAdUnitId_adInfo' => ['label' => '战力查询 - 插屏广告单元', 'type' => 'text', 'help' => '插屏广告单元ID'],
    
    // ===========================================
    // my - 个人中心页面配置
    // ===========================================
    'my_avatar_userInfo' => ['label' => '个人中心 - 头像', 'type' => 'url', 'help' => '未登录用户默认头像'],
    'my_nickname_userInfo' => ['label' => '个人中心 - 昵称', 'type' => 'text', 'help' => '未登录用户默认昵称'],
    'my_userId_userInfo' => ['label' => '个人中心 - 用户ID', 'type' => 'text', 'help' => '未登录用户默认ID'],
    'my_qrcodeImage' => ['label' => '个人中心 - 客服二维码', 'type' => 'url', 'help' => '客服二维码地址'],
    'my_qykefu' => ['label' => '个人中心 - 企业客服开关', 'type' => 'select', 'options' => ['0' => '关闭', '1' => '开启'], 'help' => '企业客服功能开关'],
    'my_gzhewm' => ['label' => '个人中心 - 公众号二维码', 'type' => 'url', 'help' => '公众号二维码图片'],

    'my_path_config_about' => ['label' => '个人中心 - 关于我们路径', 'type' => 'text', 'help' => '个人版小程序需要填写'],
    'my_appId_config_miniProgram' => ['label' => '个人中心 - 商城小程序AppID', 'type' => 'text', 'help' => '个人版小程序需要填写'],
    'my_buyPath_config_miniProgram' => ['label' => '个人中心 - 商城商品页面路径', 'type' => 'text', 'help' => '购买页面路径'],
    'my_orderPath_config_miniProgram' => ['label' => '个人中心 - 商城订单页面路径', 'type' => 'text', 'help' => '订单页面路径'],

    'my_buyUrl_config_h5' => ['label' => '个人中心 - h5购买链接', 'type' => 'url', 'help' => 'H5购买页面链接'],
    'my_orderUrl_config_h5' => ['label' => '个人中心 - h5订单链接', 'type' => 'url', 'help' => 'H5订单页面链接'],

    'my_ddappId_config_miniProgram' => ['label' => '个人中心 - 代打小程序AppID', 'type' => 'text', 'help' => '代打单独跳转小程序'],
    'my_ddpath_config_miniProgram' => ['label' => '个人中心 - 代打页面路径', 'type' => 'text', 'help' => '代打小程序路径'],

    
    'my_nativeAdunit' => ['label' => '个人中心 - 原生广告单元', 'type' => 'text', 'help' => '原生广告单元ID'],
    
    // ===========================================
    // about - 关于页面配置
    // ===========================================
    'about_wechat_contactInfo' => ['label' => '关于页面 - 微信号', 'type' => 'text', 'help' => '客服微信号'],
    'about_publicAccount_contactInfo' => ['label' => '关于页面 - 公众号', 'type' => 'text', 'help' => '公众号名称'],
    'about_templateId_adInfo' => ['label' => '关于页面 - 广告ID', 'type' => 'text', 'help' => '广告模板ID'],
    
    // ===========================================
    // settings - 设置页面配置
    // ===========================================
    'settings_avatar_userInfo' => ['label' => '设置页面 - 头像', 'type' => 'url', 'help' => '用户头像地址'],
    'settings_nickname_userInfo' => ['label' => '设置页面 - 昵称', 'type' => 'text', 'help' => '用户昵称'],
    'settings_unitId_adInfo' => ['label' => '设置页面 - 广告单元ID', 'type' => 'text', 'help' => '广告单元标识'],
    
    // ===========================================
    // order - 订单页面配置
    // ===========================================
    'order_videoTutorialUrl' => ['label' => '订单页面 - 视频教程链接', 'type' => 'url', 'help' => '视频教程地址'],
    
    // ===========================================
    // tongyong - 通用配置
    // ===========================================
    'tongyong_workTime_serviceInfo' => ['label' => '通用配置 - 工作时间', 'type' => 'text', 'help' => '客服工作时间'],
    'tongyong_remark_serviceInfo' => ['label' => '通用配置 - 备注说明', 'type' => 'text', 'help' => '服务备注说明'],
    
    // ===========================================
    // rename - 改名页面配置
    // ===========================================
    'rename_notice' => ['label' => '改名页面 - 公告内容', 'type' => 'textarea', 'help' => '改名服务公告内容'],
    'rename_appId_shop' => ['label' => '改名页面 - 小程序AppID', 'type' => 'text', 'help' => '改名小程序AppID'],
    'rename_path_shop' => ['label' => '改名页面 - 页面路径', 'type' => 'text', 'help' => '改名小程序页面路径'],
    'rename_text1_text_0' => ['label' => '改名页面 - 文本1', 'type' => 'text', 'help' => '第一项说明文本'],
    'rename_text2_text_1' => ['label' => '改名页面 - 文本2', 'type' => 'text', 'help' => '第二项说明文本'],
    'rename_text3_text_2' => ['label' => '改名页面 - 文本3', 'type' => 'text', 'help' => '第三项说明文本'],
    'rename_text4_text_3' => ['label' => '改名页面 - 文本4', 'type' => 'text', 'help' => '第四项说明文本'],
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
                                        $page_configs_data = $page_configs[$page_key] ?? [];
                                        
                                        // 按照 $field_definitions 中定义的顺序排序
                                        $field_order = array_keys($field_definitions);
                                        
                                        // 构建一个函数来获取字段在排序中的位置
                                        $getFieldPosition = function($config_item, $page_key) use ($field_order, $field_definitions) {
                                            // 构建带页面前缀的键名，如果有parent_key也包含
                                            $key = $page_key . '_' . $config_item['config_key'];
                                            
                                            // 如果有parent_key，尝试带parent_key的键名
                                            if (!empty($config_item['parent_key'])) {
                                                $parent_suffix = str_replace('.', '_', $config_item['parent_key']);
                                                $key_with_parent = $key . '_' . $parent_suffix;
                                                if (isset($field_definitions[$key_with_parent])) {
                                                    $key = $key_with_parent;
                                                }
                                            }
                                            
                                            $pos = array_search($key, $field_order);
                                            return $pos !== false ? $pos : PHP_INT_MAX;
                                        };
                                        
                                        usort($page_configs_data, function($a, $b) use ($getFieldPosition, $page_key) {
                                            $pos_a = $getFieldPosition($a, $page_key);
                                            $pos_b = $getFieldPosition($b, $page_key);
                                            
                                            // 如果字段在定义中，按照定义顺序排序
                                            if ($pos_a !== PHP_INT_MAX && $pos_b !== PHP_INT_MAX) {
                                                return $pos_a <=> $pos_b;
                                            }
                                            // 如果只有一个在定义中，定义的排在前面
                                            if ($pos_a !== PHP_INT_MAX) return -1;
                                            if ($pos_b !== PHP_INT_MAX) return 1;
                                            // 如果都不在定义中，按字母顺序
                                            return strcmp($a['config_key'], $b['config_key']);
                                        });
                                        
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
                                            $field_def = $field_definitions[$field_key] ?? [
                                                'label' => $config_item['config_key'] . (!empty($config_item['parent_key']) ? ' (' . $config_item['parent_key'] . ')' : ''),
                                                'type' => 'text',
                                                'help' => ''
                                            ];
                                            
                                            // 直接使用字段定义中的label（已经包含页面前缀）
                                            $display_label = $field_def['label'];
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
