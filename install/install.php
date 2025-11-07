<?php
/**
 * 安装处理程序
 * 执行数据库创建和初始化
 */

session_start();

// 检查是否已安装
$installed_file = '../server/.installed';
if (file_exists($installed_file)) {
    die('系统已安装');
}

// 检查配置
if (!isset($_SESSION['db_config']) || !isset($_SESSION['admin_config'])) {
    header('Location: index.php?step=1');
    exit;
}

$db_config = $_SESSION['db_config'];
$admin_config = $_SESSION['admin_config'];

$errors = [];
$messages = [];

/**
 * 创建数据表
 * @param PDO $pdo 数据库连接
 * @param array $only_tables 如果指定，只创建这些表；如果为null，创建所有表
 */
function createTables($pdo, $only_tables = null) {
    // 定义所有表的创建语句（按依赖顺序）
    $table_definitions = [
        'shop_categories' => "CREATE TABLE IF NOT EXISTS `shop_categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL COMMENT '分类名称',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `app_id` varchar(100) NOT NULL COMMENT '所属小程序AppID',
            `openid` varchar(100) NOT NULL COMMENT '用户openid',
            `unionid` varchar(100) DEFAULT NULL COMMENT '用户unionid',
            `session_key` varchar(100) DEFAULT NULL COMMENT '会话密钥',
            `nickname` varchar(100) DEFAULT NULL COMMENT '用户昵称',
            `avatar_url` varchar(500) DEFAULT NULL COMMENT '头像地址',
            `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
            `is_phone_verified` tinyint(1) DEFAULT '0' COMMENT '手机号是否验证',
            `last_login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user` (`app_id`,`openid`),
            KEY `idx_openid` (`openid`),
            KEY `idx_unionid` (`unionid`),
            KEY `idx_phone` (`phone`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表'",
        
        'shop_products' => "CREATE TABLE IF NOT EXISTS `shop_products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `category_id` int(11) NOT NULL COMMENT '分类ID',
            `product_type` tinyint(4) DEFAULT '1' COMMENT '商品类型：1-普通商品，2-卡密商品',
            `title` varchar(200) NOT NULL COMMENT '商品标题',
            `description` text COMMENT '商品描述',
            `price` decimal(10,2) NOT NULL COMMENT '现价',
            `cover_image` varchar(500) DEFAULT NULL COMMENT '封面图片',
            `sales` int(11) DEFAULT '0' COMMENT '销量',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-上架，0-下架',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_products_category` (`category_id`),
            KEY `idx_products_status` (`status`),
            KEY `idx_products_sort` (`sort_order`),
            KEY `idx_product_type` (`product_type`),
            CONSTRAINT `shop_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `shop_categories` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'admin_users' => "CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `login_name` varchar(50) NOT NULL DEFAULT '',
            `email` varchar(100) DEFAULT NULL,
            `password` varchar(255) NOT NULL,
            `qq` varchar(20) DEFAULT NULL,
            `avatar_url` varchar(500) DEFAULT NULL COMMENT '头像URL',
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'configs' => "CREATE TABLE IF NOT EXISTS `configs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `page_name` varchar(50) NOT NULL COMMENT '页面名称',
            `config_key` varchar(100) NOT NULL COMMENT '配置键',
            `config_value` text COMMENT '配置值',
            `parent_key` varchar(100) DEFAULT NULL COMMENT '父级键',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_config` (`page_name`,`config_key`,`parent_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'miniprogram_config' => "CREATE TABLE IF NOT EXISTS `miniprogram_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `app_name` varchar(100) NOT NULL COMMENT '小程序名称',
            `app_id` varchar(100) NOT NULL COMMENT '小程序AppID',
            `app_secret` varchar(200) NOT NULL COMMENT '小程序AppSecret',
            `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
            `login_enabled` tinyint(1) DEFAULT '1' COMMENT '是否开启登录功能',
            `phone_bind_required` tinyint(1) DEFAULT '1' COMMENT '是否需要绑定手机号:1=需要,0=不需要',
            `mch_id` varchar(32) DEFAULT NULL COMMENT '微信支付商户号',
            `pay_key` varchar(32) DEFAULT NULL COMMENT '微信支付API密钥',
            `pay_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用支付:1-是,0-否',
            `pay_notify_url` varchar(255) DEFAULT NULL COMMENT '支付回调地址',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `app_id` (`app_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小程序配置表'",
        
        'shop_card_keys' => "CREATE TABLE IF NOT EXISTS `shop_card_keys` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL COMMENT '商品ID',
            `card_key` varchar(255) NOT NULL COMMENT '卡密内容',
            `status` tinyint(4) DEFAULT '0' COMMENT '使用状态：0-未使用，1-已使用',
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_card_key` (`card_key`),
            KEY `idx_card_product` (`product_id`),
            KEY `idx_card_status` (`status`),
            CONSTRAINT `shop_card_keys_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_no` varchar(32) NOT NULL COMMENT '订单号',
            `user_id` int(11) NOT NULL COMMENT '用户ID',
            `app_id` varchar(100) NOT NULL COMMENT '小程序ID',
            `product_id` int(11) NOT NULL COMMENT '商品ID',
            `product_title` varchar(200) NOT NULL COMMENT '商品标题',
            `product_price` decimal(10,2) NOT NULL COMMENT '商品价格',
            `total_amount` decimal(10,2) NOT NULL COMMENT '订单总金额',
            `status` enum('pending','paid','processing','completed','cancelled','refunded') DEFAULT 'pending' COMMENT '订单状态',
            `pay_method` varchar(20) DEFAULT 'wxpay' COMMENT '支付方式',
            `transaction_id` varchar(64) DEFAULT NULL COMMENT '微信支付交易号',
            `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
            `card_key` varchar(255) DEFAULT NULL COMMENT '分配的卡密内容',
            `card_key_id` int(11) DEFAULT NULL COMMENT '关联卡密表ID',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_no` (`order_no`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_app_id` (`app_id`),
            KEY `idx_order_no` (`order_no`),
            KEY `idx_status` (`status`),
            KEY `product_id` (`product_id`),
            KEY `idx_orders_card_key_id` (`card_key_id`),
            CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
            CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`),
            CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`card_key_id`) REFERENCES `shop_card_keys` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表'",
        
        'user_sessions' => "CREATE TABLE IF NOT EXISTS `user_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL COMMENT '用户ID',
            `app_id` varchar(100) NOT NULL COMMENT '小程序AppID',
            `token` varchar(200) NOT NULL COMMENT '登录令牌',
            `expires_at` timestamp NOT NULL COMMENT '过期时间',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_token` (`token`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_expires` (`expires_at`),
            CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户会话表'",
        
        'shop_faqs' => "CREATE TABLE IF NOT EXISTS `shop_faqs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用FAQ）',
            `category` varchar(50) DEFAULT NULL COMMENT 'FAQ分类',
            `question` text NOT NULL COMMENT '问题',
            `answer` text NOT NULL COMMENT '答案',
            `tags` json DEFAULT NULL COMMENT '问题标签',
            `view_count` int(11) DEFAULT '0' COMMENT '查看次数',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_faqs_product` (`product_id`),
            CONSTRAINT `shop_faqs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'shop_modify_steps' => "CREATE TABLE IF NOT EXISTS `shop_modify_steps` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用步骤）',
            `step_number` int(11) NOT NULL COMMENT '步骤序号',
            `title` varchar(200) NOT NULL COMMENT '步骤标题',
            `description` text NOT NULL COMMENT '步骤描述',
            `note` text COMMENT '注意事项',
            `icon` varchar(255) DEFAULT NULL COMMENT '步骤图标',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_steps_product` (`product_id`),
            KEY `idx_steps_sort` (`sort_order`),
            CONSTRAINT `shop_modify_steps_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'shop_product_images' => "CREATE TABLE IF NOT EXISTS `shop_product_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL COMMENT '商品ID',
            `image_url` varchar(500) NOT NULL COMMENT '图片URL',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_images_product` (`product_id`),
            CONSTRAINT `shop_product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'shop_tutorials' => "CREATE TABLE IF NOT EXISTS `shop_tutorials` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用教程）',
            `title` varchar(200) NOT NULL COMMENT '教程标题',
            `image_url` varchar(500) DEFAULT NULL COMMENT '教程图片',
            `content` text COMMENT '详细内容',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_tutorials_product` (`product_id`),
            CONSTRAINT `shop_tutorials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'shop_videos' => "CREATE TABLE IF NOT EXISTS `shop_videos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用视频）',
            `title` varchar(200) NOT NULL COMMENT '视频标题',
            `description` text COMMENT '视频描述',
            `video_url` varchar(500) DEFAULT NULL COMMENT '视频链接',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_videos_product` (`product_id`),
            CONSTRAINT `shop_videos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'website_configs' => "CREATE TABLE IF NOT EXISTS `website_configs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `config_key` varchar(100) NOT NULL COMMENT '配置键名',
            `config_label` varchar(100) DEFAULT NULL COMMENT '中文标签',
            `config_value` text COMMENT '配置值',
            `config_type` varchar(50) DEFAULT 'text' COMMENT '配置类型',
            `is_required` tinyint(1) DEFAULT '0' COMMENT '是否必填',
            `help_text` text COMMENT '帮助说明',
            `category` varchar(50) DEFAULT 'basic' COMMENT '配置分类',
            `sort_order` int(11) DEFAULT '0' COMMENT '排序',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_key` (`config_key`),
            KEY `idx_category` (`category`),
            KEY `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='网站信息配置表'"
    ];
    
    // 如果指定了只创建某些表，则过滤
    if ($only_tables !== null) {
        $table_definitions = array_intersect_key($table_definitions, array_flip($only_tables));
    }
    
    // 按顺序创建表
    foreach ($table_definitions as $table_name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // 如果表已存在且不是覆盖模式，跳过
            if (strpos($e->getMessage(), 'already exists') !== false && $only_tables !== null) {
                continue;
            }
            throw $e;
        }
    }
}

/**
 * 初始化配置数据
 */
function initConfigs($pdo) {
    // 可以在这里添加默认配置数据
    // 例如：
    // $stmt = $pdo->prepare("INSERT INTO configs (page_name, config_key, config_value) VALUES (?, ?, ?)");
    // $stmt->execute(['index', 'noticeContent', '欢迎使用王者荣耀查战力系统']);
}

try {
    // 1. 连接 MySQL（不指定数据库）
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. 创建数据库（如果不存在）
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $messages[] = "数据库创建成功";
    
    // 3. 选择数据库
    $pdo->exec("USE `{$db_config['database']}`");
    
    // 4. 清空数据库并创建数据表
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 获取所有表名
    $tables_stmt = $pdo->query("SHOW TABLES");
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        // 删除所有表
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $messages[] = "已清空数据库（删除 " . count($tables) . " 个现有表）";
    }
    
    // 创建所有表
    createTables($pdo);
    $messages[] = "数据表创建成功";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 5. 初始化配置数据
    initConfigs($pdo);
    $messages[] = "配置数据初始化成功";
    
    // 6. 创建或更新管理员账号（使用 ON DUPLICATE KEY UPDATE 或先检查）
    $hashed_password = password_hash($admin_config['password'], PASSWORD_DEFAULT);
    
    // 先检查账号是否存在
    $check_stmt = $pdo->prepare("SELECT id FROM admin_users WHERE login_name = ?");
    $check_stmt->execute([$admin_config['login_name']]);
    $existing_admin = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // 检查表有哪些字段
    $columns_stmt = $pdo->query("SHOW COLUMNS FROM admin_users");
    $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $has_status = in_array('status', $columns);
    $has_created_at = in_array('created_at', $columns);
    $has_updated_at = in_array('updated_at', $columns);
    
    if ($existing_admin) {
        // 更新现有账号
        $update_fields = ['username = ?', 'password = ?'];
        $update_params = [
            $admin_config['username'],
            $hashed_password
        ];
        
        if ($has_updated_at) {
            $update_fields[] = 'updated_at = NOW()';
        }
        
        $update_sql = "UPDATE admin_users SET " . implode(', ', $update_fields) . " WHERE login_name = ?";
        $update_params[] = $admin_config['login_name'];
        
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute($update_params);
        $messages[] = "管理员账号更新成功";
    } else {
        // 创建新账号
        $fields = ['username', 'login_name', 'password'];
        $placeholders = ['?', '?', '?'];
        $params = [
            $admin_config['username'],
            $admin_config['login_name'],
            $hashed_password
        ];
        
        if ($has_status) {
            $fields[] = 'status';
            $placeholders[] = '?';
            $params[] = 1;
        }
        if ($has_created_at) {
            $fields[] = 'created_at';
            $placeholders[] = 'NOW()';
        }
        if ($has_updated_at) {
            $fields[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }
        
        $insert_sql = "INSERT INTO admin_users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute($params);
        $messages[] = "管理员账号创建成功";
    }
    
    // 7. 保存数据库配置到文件
    // 转义密码中的特殊字符
    $escaped_password = addslashes($db_config['password']);
    $escaped_host = addslashes($db_config['host']);
    $escaped_database = addslashes($db_config['database']);
    $escaped_username = addslashes($db_config['username']);
    
    $config_content = "<?php\nreturn [\n";
    $config_content .= "    'host' => '{$escaped_host}',\n";
    $config_content .= "    'port' => {$db_config['port']},\n";
    $config_content .= "    'database' => '{$escaped_database}',\n";
    $config_content .= "    'username' => '{$escaped_username}',\n";
    $config_content .= "    'password' => '{$escaped_password}',\n";
    $config_content .= "    'charset' => '{$db_config['charset']}',\n";
    $config_content .= "    'options' => [\n";
    $config_content .= "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
    $config_content .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
    $config_content .= "        PDO::ATTR_EMULATE_PREPARES => false,\n";
    $config_content .= "    ]\n";
    $config_content .= "];\n";
    
    // 确保目录存在
    $config_dir = dirname(__FILE__) . '/../server';
    if (!is_dir($config_dir)) {
        mkdir($config_dir, 0755, true);
    }
    
    $result = @file_put_contents($config_dir . '/db_config.php', $config_content);
    if ($result === false) {
        throw new Exception('无法写入配置文件，请检查 server 目录权限');
    }
    $messages[] = "配置文件保存成功";
    
    // 8. 创建安装标记文件
    $installed_dir = dirname(__FILE__) . '/../server';
    if (!is_dir($installed_dir)) {
        mkdir($installed_dir, 0755, true);
    }
    
    $result = @file_put_contents($installed_dir . '/.installed', date('Y-m-d H:i:s'));
    if ($result === false) {
        throw new Exception('无法创建安装标记文件，请检查 server 目录权限');
    }
    $messages[] = "安装完成";
    
    // 清除会话
    session_unset();
    
} catch (Exception $e) {
    $errors[] = "安装失败: " . $e->getMessage();
    // 记录详细错误信息用于调试
    error_log("安装错误: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装结果 - 王者荣耀查战力系统</title>
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
        .result-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        .result-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }
        .result-icon.success {
            background: #d4edda;
            color: #155724;
        }
        .result-icon.error {
            background: #f8d7da;
            color: #721c24;
        }
        .message-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .message-item.success {
            background: #d4edda;
            color: #155724;
        }
        .message-item.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <?php if (empty($errors)): ?>
            <div class="result-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2 class="text-center mb-4">安装成功！</h2>
            
            <div class="mb-4">
                <h5>安装信息：</h5>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item success">
                        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle me-2"></i>管理员账号信息</h6>
                <p class="mb-1"><strong>登录账号：</strong><?php echo htmlspecialchars($admin_config['login_name']); ?></p>
                <p class="mb-0"><strong>密码：</strong><?php echo htmlspecialchars($admin_config['password']); ?></p>
            </div>
            
            <div class="text-center mt-4">
                <a href="../admin/login.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>前往后台登录
                </a>
            </div>
            
            <div class="alert alert-warning mt-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>安全提示：</strong>安装完成后，建议删除或重命名 install 目录。
            </div>
            
        <?php else: ?>
            <div class="result-icon error">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <h2 class="text-center mb-4">安装失败</h2>
            
            <div class="mb-4">
                <?php foreach ($errors as $error): ?>
                    <div class="message-item error">
                        <i class="bi bi-x-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php?step=1" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-2"></i>返回重新安装
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

