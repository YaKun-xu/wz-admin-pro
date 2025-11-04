<?php
// 开启输出缓冲，确保重定向前没有输出
ob_start();

// 如果会话尚未启动，则启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 清除所有会话数据
$_SESSION = array();

// 如果使用基于 cookie 的会话，删除 cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 清除输出缓冲
ob_end_clean();

// 重定向到登录页面
header('Location: login.php');
exit;
?>
