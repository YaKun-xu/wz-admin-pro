<?php
/**
 * 安装检测函数
 * 检查系统是否已安装，如果未安装则跳转到安装程序
 */

function checkInstallation() {
    // 检查安装标记文件
    $installed_file = __DIR__ . '/.installed';
    $db_config_file = __DIR__ . '/db_config.php';
    
    // 如果安装标记文件不存在，且不在安装目录中，则跳转到安装程序
    if (!file_exists($installed_file)) {
        // 检查是否已经在安装目录中
        $current_path = $_SERVER['PHP_SELF'] ?? '';
        $install_path = '/install/';
        
        // 如果不在安装目录中，跳转到安装程序
        if (strpos($current_path, $install_path) === false) {
            // 构建安装程序URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_path = dirname(dirname($current_path));
            $base_path = rtrim($base_path, '/');
            
            // 如果根路径是 /，则安装路径为 /install/
            if ($base_path === '' || $base_path === '/') {
                $install_url = $protocol . '://' . $host . '/install/';
            } else {
                $install_url = $protocol . '://' . $host . $base_path . '/install/';
            }
            
            // 跳转到安装程序
            header('Location: ' . $install_url);
            exit;
        }
    }
    
    // 如果数据库配置文件不存在，也跳转到安装程序
    if (!file_exists($db_config_file)) {
        $current_path = $_SERVER['PHP_SELF'] ?? '';
        $install_path = '/install/';
        
        if (strpos($current_path, $install_path) === false) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base_path = dirname(dirname($current_path));
            $base_path = rtrim($base_path, '/');
            
            if ($base_path === '' || $base_path === '/') {
                $install_url = $protocol . '://' . $host . '/install/';
            } else {
                $install_url = $protocol . '://' . $host . $base_path . '/install/';
            }
            
            header('Location: ' . $install_url);
            exit;
        }
    }
    
    // 如果已安装，进一步验证数据库连接
    if (file_exists($installed_file) && file_exists($db_config_file)) {
        try {
            $config = require $db_config_file;
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
                $config['username'],
                $config['password']
            );
            
            // 检查关键表是否存在
            $tables = ['admin_users', 'users', 'configs'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() === 0) {
                    // 表不存在，可能是数据库被清空，需要重新安装
                    $current_path = $_SERVER['PHP_SELF'] ?? '';
                    $install_path = '/install/';
                    
                    if (strpos($current_path, $install_path) === false) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $base_path = dirname(dirname($current_path));
                        $base_path = rtrim($base_path, '/');
                        
                        if ($base_path === '' || $base_path === '/') {
                            $install_url = $protocol . '://' . $host . '/install/';
                        } else {
                            $install_url = $protocol . '://' . $host . $base_path . '/install/';
                        }
                        
                        header('Location: ' . $install_url);
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            // 数据库连接失败，可能是配置错误，需要重新安装
            $current_path = $_SERVER['PHP_SELF'] ?? '';
            $install_path = '/install/';
            
            if (strpos($current_path, $install_path) === false) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $base_path = dirname(dirname($current_path));
                $base_path = rtrim($base_path, '/');
                
                if ($base_path === '' || $base_path === '/') {
                    $install_url = $protocol . '://' . $host . '/install/';
                } else {
                    $install_url = $protocol . '://' . $host . $base_path . '/install/';
                }
                
                header('Location: ' . $install_url);
                exit;
            }
        }
    }
    
    return true;
}

