<?php
/**
 * 文件路径检查工具
 * 访问: http://你的域名/check_files.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>文件路径检查工具</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .info { background: #fff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #333; color: white; }
    </style>
</head>
<body>
    <h1>文件路径检查工具</h1>
    
    <div class="info">
        <h3>当前环境信息</h3>
        <p><strong>PHP 版本:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>当前文件:</strong> <?php echo __FILE__; ?></p>
        <p><strong>当前目录:</strong> <?php echo __DIR__; ?></p>
        <p><strong>工作目录:</strong> <?php echo getcwd(); ?></p>
    </div>

    <h3>关键文件检查</h3>
    <table>
        <tr>
            <th>文件路径</th>
            <th>状态</th>
            <th>实际路径</th>
            <th>可读</th>
        </tr>
        <?php
        $files = [
            // 数据库配置
            'server/db_config.php' => 'server/db_config.php',
            '../server/db_config.php' => '../server/db_config.php',
            __DIR__ . '/server/db_config.php' => '__DIR__ . "/server/db_config.php"',
            
            // 管理后台文件
            'admin/index.php' => 'admin/index.php',
            'admin/login.php' => 'admin/login.php',
            'admin/sidebar.php' => 'admin/sidebar.php',
            
            // 服务器端文件
            'server/login_api.php' => 'server/login_api.php',
            'server/pay_handler.php' => 'server/pay_handler.php',
            'server/shop_handler.php' => 'server/shop_handler.php',
            
            // 静态资源
            'assets/css/admin.css' => 'assets/css/admin.css',
            'assets/js/admin.js' => 'assets/js/admin.js',
        ];
        
        foreach ($files as $path => $label) {
            $exists = file_exists($path);
            $realpath = $exists ? realpath($path) : '不存在';
            $readable = $exists ? (is_readable($path) ? '是' : '否') : '-';
            
            echo "<tr>";
            echo "<td><code>{$label}</code></td>";
            echo "<td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ 存在' : '✗ 不存在') . "</td>";
            echo "<td><code>{$realpath}</code></td>";
            echo "<td>{$readable}</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h3>从 admin 目录检查（相对路径）</h3>
    <table>
        <tr>
            <th>文件路径</th>
            <th>状态</th>
            <th>实际路径</th>
        </tr>
        <?php
        $adminFiles = [
            '../server/db_config.php',
            '../assets/css/admin.css',
            'sidebar.php',
            'index.php',
        ];
        
        // 模拟在 admin 目录
        $adminDir = __DIR__ . '/admin';
        if (is_dir($adminDir)) {
            foreach ($adminFiles as $file) {
                $fullPath = $adminDir . '/' . str_replace('../', '', $file);
                if (strpos($file, '../') === 0) {
                    $fullPath = __DIR__ . '/' . str_replace('../', '', $file);
                }
                
                $exists = file_exists($fullPath);
                $realpath = $exists ? realpath($fullPath) : '不存在';
                
                echo "<tr>";
                echo "<td><code>admin/{$file}</code></td>";
                echo "<td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ 存在' : '✗ 不存在') . "</td>";
                echo "<td><code>{$realpath}</code></td>";
                echo "</tr>";
            }
        }
        ?>
    </table>

    <h3>路径测试</h3>
    <div class="info">
        <?php
        $testPaths = [
            'server/db_config.php',
            '../server/db_config.php',
            './server/db_config.php',
            __DIR__ . '/server/db_config.php',
        ];
        
        echo "<h4>测试不同的路径写法:</h4>";
        foreach ($testPaths as $path) {
            $exists = file_exists($path);
            $realpath = $exists ? realpath($path) : '不存在';
            echo "<p>";
            echo "<code>{$path}</code> - ";
            echo "<span class='" . ($exists ? 'success' : 'error') . "'>";
            echo $exists ? "✓ 找到: {$realpath}" : "✗ 不存在";
            echo "</span>";
            echo "</p>";
        }
        ?>
    </div>

    <h3>建议</h3>
    <div class="info">
        <p><strong>如果文件路径有问题，建议：</strong></p>
        <ol>
            <li>使用绝对路径：<code>require_once __DIR__ . '/../server/db_config.php';</code></li>
            <li>检查文件权限：<code>chmod 644 server/db_config.php</code></li>
            <li>确认文件确实存在于服务器上</li>
            <li>检查 Web 服务器的文档根目录配置</li>
        </ol>
    </div>
</body>
</html>

