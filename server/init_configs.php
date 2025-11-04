<?php
/**
 * 初始化configs表和数据
 * 根据数据库字段说明创建configs表并插入默认配置
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
    // 创建configs表
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS `configs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `page_name` varchar(50) NOT NULL COMMENT '页面名称',
        `config_key` varchar(100) NOT NULL COMMENT '配置键名',
        `config_value` text COMMENT '配置值',
        `parent_key` varchar(100) DEFAULT NULL COMMENT '父级配置键',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_config` (`page_name`, `config_key`),
        KEY `idx_page_name` (`page_name`),
        KEY `idx_parent_key` (`parent_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='全局配置表';
    ";
    
    $pdo->exec($create_table_sql);
    echo "✅ configs表创建成功\n";
    
    // 清空现有数据
    $pdo->exec("DELETE FROM configs");
    echo "✅ 清空现有配置数据\n";
    
    // 插入默认配置数据
    $default_configs = [
        // 首页配置
        ['index', 'site_name', '王者荣耀查战力系统', null],
        ['index', 'site_description', '专业的王者荣耀战力查询平台', null],
        ['index', 'logo_url', '', null],
        ['index', 'footer_text', '© 2024 王者荣耀查战力系统', null],
        
        // 服务信息配置
        ['serviceInfo', 'workTime', '9:00-23:00', 'serviceInfo'],
        ['serviceInfo', 'contact_phone', '400-123-4567', 'serviceInfo'],
        ['serviceInfo', 'contact_email', 'admin@example.com', 'serviceInfo'],
        ['serviceInfo', 'service_desc', '专业、快速、安全的战力查询服务', 'serviceInfo'],
        
        // 支付配置
        ['payment', 'wechat_pay_enabled', '1', 'payment'],
        ['payment', 'alipay_enabled', '1', 'payment'],
        ['payment', 'min_pay_amount', '0.01', 'payment'],
        ['payment', 'max_pay_amount', '1000.00', 'payment'],
        
        // 系统设置
        ['system', 'maintenance_mode', '0', 'system'],
        ['system', 'max_upload_size', '10', 'system'],
        ['system', 'cache_time', '3600', 'system'],
        ['system', 'debug_mode', '0', 'system'],
        
        // SEO设置
        ['seo', 'meta_title', '王者荣耀查战力 - 专业战力查询平台', 'seo'],
        ['seo', 'meta_keywords', '王者荣耀,战力查询,英雄战力,段位查询', 'seo'],
        ['seo', 'meta_description', '专业的王者荣耀战力查询平台，快速查询英雄战力、段位信息', 'seo'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO configs (page_name, config_key, config_value, parent_key) VALUES (?, ?, ?, ?)");
    
    foreach ($default_configs as $config_data) {
        $stmt->execute($config_data);
    }
    
    echo "✅ 默认配置数据插入成功\n";
    echo "✅ 共插入 " . count($default_configs) . " 条配置记录\n";
    
    // 显示配置概览
    $stmt = $pdo->query("SELECT page_name, COUNT(*) as count FROM configs GROUP BY page_name ORDER BY page_name");
    $page_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 配置页面概览:\n";
    foreach ($page_counts as $page) {
        echo "  - {$page['page_name']}: {$page['count']} 项配置\n";
    }
    
    echo "\n🎉 configs表初始化完成！\n";
    
} catch (Exception $e) {
    echo "❌ 初始化失败: " . $e->getMessage() . "\n";
    exit(1);
}
?>
