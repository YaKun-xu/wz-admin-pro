<?php
/**
 * 初始化网站信息配置数据
 * 创建website_configs表并添加默认数据
 */

require_once 'db_config.php';

$config = require 'db_config.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password'],
    $config['options']
);

try {
    echo "🔧 创建网站信息配置表\n\n";
    
    // 创建website_configs表
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS `website_configs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `config_key` varchar(100) NOT NULL COMMENT '配置键名',
        `config_value` text COMMENT '配置值',
        `config_type` varchar(50) DEFAULT 'text' COMMENT '配置类型',
        `is_required` tinyint(1) DEFAULT 0 COMMENT '是否必填',
        `help_text` text COMMENT '帮助说明',
        `category` varchar(50) DEFAULT 'basic' COMMENT '配置分类',
        `sort_order` int(11) DEFAULT 0 COMMENT '排序',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_key` (`config_key`),
        KEY `idx_category` (`category`),
        KEY `idx_sort` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站信息配置表';
    ";
    
    $pdo->exec($create_table_sql);
    echo "✅ website_configs表创建成功\n";
    
    // 清空现有数据
    $pdo->exec("DELETE FROM website_configs");
    echo "✅ 清空现有配置数据\n";
    
    // 插入默认配置数据
    $website_configs = [
        // 基本信息
        ['site_name', '王者荣耀查战力系统', 'text', 1, '显示在网站标题和页面头部', 'basic', 1],
        ['site_description', '专业的王者荣耀战力查询平台，提供准确的英雄战力数据查询服务，支持多服务器查询，让您轻松了解自己的游戏实力。', 'textarea', 1, '用于SEO和页面描述', 'basic', 2],
        ['site_keywords', '王者荣耀,战力查询,英雄战力,段位查询,游戏数据,王者荣耀助手', 'text', 0, 'SEO关键词，用逗号分隔', 'basic', 3],
        ['site_logo', 'https://cdn.yixinzy.cn/logo/wangzhe-logo.png', 'url', 0, '网站Logo图片地址', 'basic', 4],
        ['site_favicon', 'https://cdn.yixinzy.cn/favicon/wangzhe.ico', 'url', 0, '网站favicon图标地址', 'basic', 5],
        ['version', 'v2.1.0', 'text', 0, '当前系统版本号', 'basic', 6],
        
        // 联系信息
        ['contact_email', 'support@wangzhe.com', 'email', 1, '客服联系邮箱', 'contact', 1],
        ['contact_phone', '400-888-9999', 'tel', 1, '客服联系电话', 'contact', 2],
        ['contact_wechat', 'wangzhe_support', 'text', 0, '客服微信号', 'contact', 3],
        ['contact_qq', '888888888', 'text', 0, '客服QQ号', 'contact', 4],
        ['service_time', '7×24小时在线服务，节假日正常服务', 'text', 0, '客服服务时间', 'contact', 5],
        
        // 公司信息
        ['company_name', '王者荣耀查战力科技有限公司', 'text', 0, '公司全称', 'company', 1],
        ['company_address', '北京市朝阳区建国路88号SOHO现代城A座1001室', 'text', 0, '公司详细地址', 'company', 2],
        ['icp_number', '京ICP备2024000001号-1', 'text', 0, 'ICP备案号', 'company', 3],
        ['beian_number', '京公网安备11010502012345号', 'text', 0, '公安备案号', 'company', 4],
        ['copyright', '© 2024 王者荣耀查战力系统 版权所有 | 京ICP备2024000001号-1', 'text', 0, '版权声明', 'company', 5],
        
        // 法律条款
        ['privacy_policy', '我们非常重视您的隐私保护。本隐私政策详细说明了我们如何收集、使用、存储和保护您的个人信息。我们承诺按照相关法律法规要求，采取相应的安全保护措施，保护您的个人信息安全。', 'textarea', 0, '隐私保护政策内容', 'legal', 1],
        ['terms_of_service', '欢迎使用王者荣耀查战力系统！使用本服务即表示您同意遵守以下条款和条件。请仔细阅读本服务条款，特别是限制责任和争议解决条款。如果您不同意本条款的任何内容，请不要使用我们的服务。', 'textarea', 0, '服务使用条款', 'legal', 2],
        ['about_us', '我们是一家专注于游戏数据查询服务的科技公司，致力于为玩家提供准确、及时的游戏数据查询服务。我们的团队由资深游戏开发者和数据分析师组成，拥有丰富的游戏行业经验和技术实力。', 'textarea', 0, '公司介绍和业务说明', 'legal', 3],
        ['maintenance_notice', '系统将定期进行维护升级，维护期间服务可能暂时中断。我们会提前24小时在官网和APP内发布维护公告，请各位用户合理安排使用时间。维护完成后，所有功能将恢复正常。', 'textarea', 0, '系统维护时的公告内容', 'legal', 4]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO website_configs (config_key, config_value, config_type, is_required, help_text, category, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($website_configs as $config_data) {
        $stmt->execute($config_data);
    }
    
    echo "✅ 网站信息配置数据插入成功\n";
    echo "✅ 共插入 " . count($website_configs) . " 条配置记录\n\n";
    
    // 显示配置概览
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM website_configs GROUP BY category ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 配置分类统计:\n";
    foreach ($categories as $category) {
        echo "  - {$category['category']}: {$category['count']} 项配置\n";
    }
    
    echo "\n🎉 网站信息配置表初始化完成！\n";
    
} catch (Exception $e) {
    echo "❌ 初始化失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>
