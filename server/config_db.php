<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 检查安装状态
$installed_file = __DIR__ . '/.installed';
if (!file_exists($installed_file)) {
    echo json_encode([
        'code' => 503,
        'msg' => '系统未安装，请先完成安装',
        'data' => null
    ]);
    exit;
}

// 导入数据库配置
$dbConfig = require_once 'db_config.php';

try {
    // 连接数据库
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 引入商品API处理模块
    require_once 'shop_handler.php';
    // 引入支付API处理模块
    require_once 'pay_handler.php';
    
    // 检查是否是API请求
    $apiPath = isset($_GET['api_path']) ? $_GET['api_path'] : '';
    
    // 检查是否是商品相关的API请求
    if (!empty($apiPath) && strpos($apiPath, '/shop/') !== false) {
        // 获取GET参数（排除api_path参数）
        $params = $_GET;
        unset($params['api_path']);
        
        // 处理商品API请求
        $result = handleShopApi($pdo, $apiPath, $_SERVER['REQUEST_METHOD'], $params);
        
        // 返回结果
        echo json_encode($result);
        exit;
    }
    
    // 检查是否是支付相关的API请求
    if (!empty($apiPath) && strpos($apiPath, '/pay/') !== false) {
        // 获取POST数据或GET参数
        $params = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $params = $input ?: [];
        } else {
            $params = $_GET;
            unset($params['api_path']);
        }
        
        // 处理支付API请求
        $result = handlePayApi($pdo, $apiPath, $_SERVER['REQUEST_METHOD'], $params);
        
        // 返回结果
        echo json_encode($result);
        exit;
    }
    
    // 处理POST请求（登录相关API）
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            responseJson(400, '请求数据格式错误');
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'wx_login':
                $result = handleWxLogin($pdo, $input);
                responseJson($result['code'], $result['msg'], $result['data']);
                break;
                
            case 'bind_phone':
                $result = handleBindPhone($pdo, $input);
                responseJson($result['code'], $result['msg'], $result['data']);
                break;
                
            case 'get_user_profile':
                $result = handleGetUserProfile($pdo, $input);
                responseJson($result['code'], $result['msg'], $result['data']);
                break;
                
            case 'logout':
                $result = handleLogout($pdo, $input);
                responseJson($result['code'], $result['msg'], $result['data']);
                break;
                
            default:
                responseJson(400, '未知的操作类型');
        }
        
        exit;
    }
    
    // 处理GET请求（配置获取）
    $pageName = isset($_GET['page']) ? $_GET['page'] : '';
    
    // 从数据库获取配置数据
    if (!empty($pageName)) {
        // 查询特定页面的配置
        $stmt = $pdo->prepare("
            SELECT page_name, config_key, config_value, parent_key 
            FROM configs 
            WHERE page_name = ?
            ORDER BY parent_key ASC, config_key ASC
        ");
        $stmt->execute([$pageName]);
    } else {
        // 查询所有配置
        $stmt = $pdo->prepare("
            SELECT page_name, config_key, config_value, parent_key 
            FROM configs 
            ORDER BY page_name ASC, parent_key ASC, config_key ASC
        ");
        $stmt->execute();
    }
    
    // 获取结果
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 构建嵌套配置结构
    $configs = [];
    
    // 处理查询结果，构建嵌套配置
    foreach ($results as $row) {
        $pageKey = $row['page_name'];
        $configKey = $row['config_key'];
        $configValue = $row['config_value'];
        $parentKey = $row['parent_key'];
        
        // 修正特殊字段类型 - 需要强制为字符串的字段
        $forceStringKeys = ['switch', 'userId'];
        $isSpecialKey = in_array($configKey, $forceStringKeys);
        
        // 处理数据类型
        if ($isSpecialKey) {
            // 特殊键保持为字符串格式
            $configValue = (string) $configValue;
        } elseif ($configValue === 'true') {
            $configValue = true;
        } elseif ($configValue === 'false') {
            $configValue = false;
        } elseif (is_numeric($configValue)) {
            // 尝试转换为数字
            if (strpos($configValue, '.') !== false) {
                $configValue = (float) $configValue;
            } else {
                $configValue = (int) $configValue;
            }
        }
        
        // 创建页面节点
        if (!isset($configs[$pageKey])) {
            $configs[$pageKey] = [];
        }
        
        // 处理嵌套键
        if ($parentKey) {
            $keys = explode('.', $parentKey);
            $current = &$configs[$pageKey];
            
            foreach ($keys as $key) {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
            
            // 设置值
            $current[$configKey] = $configValue;
        } else {
            // 顶级键
            $configs[$pageKey][$configKey] = $configValue;
        }
    }
    
    // 最后检查一次特殊键名，确保它们是字符串格式
    if (!empty($configs['zhanli']['switch'])) {
        $configs['zhanli']['switch'] = (string) $configs['zhanli']['switch'];
    }
    
    // 返回特定页面或所有配置
    if (!empty($pageName) && isset($configs[$pageName])) {
        echo json_encode([
            'code' => 0,
            'msg' => 'success',
            'data' => $configs[$pageName]
        ]);
    } else if (!empty($pageName) && !isset($configs[$pageName])) {
        // 请求的页面不存在
        echo json_encode([
            'code' => 1,
            'msg' => 'page not found',
            'data' => null
        ]);
    } else {
        // 返回所有配置
        echo json_encode([
            'code' => 0,
            'msg' => 'success',
            'data' => $configs
        ]);
    }
    
} catch (PDOException $e) {
    // 数据库错误
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode([
        'code' => 500,
        'msg' => 'database error',
        'data' => null
    ]);
}

// 登录相关处理函数

function handleWxLogin($pdo, $input) {
    $appId = $input['appId'] ?? '';
    $code = $input['code'] ?? '';
    $userInfo = $input['userInfo'] ?? [];
    
    if (empty($appId) || empty($code)) {
        return [
            'code' => 400,
            'msg' => '参数错误',
            'data' => null
        ];
    }
    
    // 获取小程序配置
    $appConfig = getAppConfig($pdo, $appId);
    if (!$appConfig) {
        return [
            'code' => 404,
            'msg' => '小程序配置不存在',
            'data' => null
        ];
    }
    
    if (!$appConfig['login_enabled']) {
        return [
            'code' => 403,
            'msg' => '登录功能未启用',
            'data' => null
        ];
    }
    
    // 调用微信API获取openid和session_key
    $wxResult = getWxOpenId($code, $appConfig['app_id'], $appConfig['app_secret']);
    if (!$wxResult) {
        return [
            'code' => 400,
            'msg' => '微信登录失败，请重试',
            'data' => null
        ];
    }
    
    $openid = $wxResult['openid'];
    $sessionKey = $wxResult['session_key'];
    
    try {
        // 检查用户是否存在
        $stmt = $pdo->prepare("SELECT * FROM users WHERE app_id = ? AND openid = ?");
        $stmt->execute([$appId, $openid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // 创建新用户
            $stmt = $pdo->prepare("
                INSERT INTO users (app_id, openid, session_key, nickname, avatar_url, last_login_time) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $appId, 
                $openid, 
                $sessionKey,
                $userInfo['nickName'] ?? '微信用户',
                $userInfo['avatarUrl'] ?? ''
            ]);
            
            $userId = $pdo->lastInsertId();
        } else {
            // 更新现有用户
            $userId = $user['id'];
            $stmt = $pdo->prepare("
                UPDATE users 
                SET session_key = ?, nickname = ?, avatar_url = ?, last_login_time = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([
                $sessionKey,
                $userInfo['nickName'] ?? $user['nickname'],
                $userInfo['avatarUrl'] ?? $user['avatar_url'],
                $userId
            ]);
        }
        
        // 生成登录令牌
        $token = generateToken($pdo, $userId, $appId);
        
        // 获取用户信息
        $userInfo = getUserById($pdo, $userId);
        
        return [
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $userInfo['id'],
                    'nickname' => $userInfo['nickname'],
                    'avatar_url' => $userInfo['avatar_url'],
                    'phone' => $userInfo['phone'] ?? '',
                    'is_phone_verified' => (bool)($userInfo['is_phone_verified'] ?? false)
                ]
            ]
        ];
        
    } catch (Exception $e) {
        error_log("登录处理错误: " . $e->getMessage());
        return [
            'code' => 500,
            'msg' => '登录处理失败',
            'data' => null
        ];
    }
}

function handleBindPhone($pdo, $input) {
    $token = $input['token'] ?? '';
    $encryptedData = $input['encryptedData'] ?? '';
    $iv = $input['iv'] ?? '';
    
    if (empty($token) || empty($encryptedData) || empty($iv)) {
        return [
            'code' => 400,
            'msg' => '参数错误',
            'data' => null
        ];
    }
    
    // 验证令牌
    $session = validateToken($pdo, $token);
    if (!$session) {
        return [
            'code' => 401,
            'msg' => '登录已过期',
            'data' => null
        ];
    }
    
    // 获取用户信息以确定appid
    $user = getUserById($pdo, $session['user_id']);
    if (!$user) {
        return [
            'code' => 404,
            'msg' => '用户不存在',
            'data' => null
        ];
    }
    
    // 获取appid配置
    $appConfig = getAppConfig($pdo, $user['app_id']);
    if (!$appConfig) {
        return [
            'code' => 400,
            'msg' => '小程序配置不存在',
            'data' => null
        ];
    }
    
    // 解密微信手机号
    $phone = decryptWxPhone($session['session_key'], $encryptedData, $iv, $appConfig['app_id']);
    if (!$phone) {
        return [
            'code' => 400,
            'msg' => '手机号解密失败',
            'data' => null
        ];
    }
    
    try {
        // 更新用户手机号
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, is_phone_verified = 1 WHERE id = ?");
        $stmt->execute([$phone, $session['user_id']]);
        
        return [
            'code' => 200,
            'msg' => '手机号绑定成功',
            'data' => [
                'phone' => $phone
            ]
        ];
        
    } catch (Exception $e) {
        error_log("手机号绑定错误: " . $e->getMessage());
        return [
            'code' => 500,
            'msg' => '绑定失败',
            'data' => null
        ];
    }
}

function handleGetUserProfile($pdo, $input) {
    $token = $input['token'] ?? '';
    
    if (empty($token)) {
        return [
            'code' => 400,
            'msg' => '参数错误',
            'data' => null
        ];
    }
    
    // 验证令牌
    $session = validateToken($pdo, $token);
    if (!$session) {
        return [
            'code' => 401,
            'msg' => '登录已过期',
            'data' => null
        ];
    }
    
    // 获取用户信息
    $userInfo = getUserById($pdo, $session['user_id']);
    if (!$userInfo) {
        return [
            'code' => 404,
            'msg' => '用户不存在',
            'data' => null
        ];
    }
    
    return [
        'code' => 200,
        'msg' => '获取成功',
        'data' => [
            'id' => $userInfo['id'],
            'nickname' => $userInfo['nickname'],
            'avatar_url' => $userInfo['avatar_url'],
            'phone' => $userInfo['phone'] ?? '',
            'is_phone_verified' => (bool)($userInfo['is_phone_verified'] ?? false)
        ]
    ];
}

function handleLogout($pdo, $input) {
    $token = $input['token'] ?? '';
    
    if (empty($token)) {
        return [
            'code' => 400,
            'msg' => '参数错误',
            'data' => null
        ];
    }
    
    try {
        // 删除会话
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
        $stmt->execute([$token]);
        
        return [
            'code' => 200,
            'msg' => '退出成功',
            'data' => null
        ];
        
    } catch (Exception $e) {
        error_log("退出登录错误: " . $e->getMessage());
        return [
            'code' => 500,
            'msg' => '退出失败',
            'data' => null
        ];
    }
}

// 辅助函数
function getAppConfig($pdo, $appId) {
    // 从miniprogram_config表中获取小程序配置
    $stmt = $pdo->prepare("SELECT * FROM miniprogram_config WHERE app_id = ? AND is_active = 1");
    $stmt->execute([$appId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return [
            'app_id' => $result['app_id'],
            'app_secret' => $result['app_secret'],
            'login_enabled' => (bool)$result['login_enabled'],
            'app_name' => $result['app_name'] ?? ''
        ];
    }
    
    // 如果没有配置，返回null表示配置不存在
    return null;
}

function generateToken($pdo, $userId, $appId) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 3600); // 7天过期

    // 删除旧的会话
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND app_id = ?");
    $stmt->execute([$userId, $appId]);

    // 创建新会话
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, app_id, token, expires_at)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $appId, $token, $expiresAt]);

    return $token;
}

function validateToken($pdo, $token) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.session_key 
        FROM user_sessions s 
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 微信数据解密函数
function decryptWxPhone($sessionKey, $encryptedData, $iv, $appId = null) {
    // 检查参数是否为空
    if (empty($sessionKey) || empty($encryptedData) || empty($iv)) {
        error_log("手机号解密失败: 参数为空 - sessionKey: " . ($sessionKey ? 'exists' : 'empty') . 
                  ", encryptedData: " . ($encryptedData ? 'exists' : 'empty') . 
                  ", iv: " . ($iv ? 'exists' : 'empty'));
        return false;
    }
    
    // 检查是否为测试session_key
    if (strpos($sessionKey, 'test_session_key_') === 0) {
        error_log("手机号解密失败: 使用的是测试session_key，无法解密真实数据");
        // 在开发环境下返回模拟手机号
        return '138****' . rand(1000, 9999);
    }
    
    try {
        // 对称解密使用的算法为 AES-128-CBC，数据采用PKCS#7填充
        $sessionKeyDecoded = base64_decode($sessionKey);
        $encryptedDataDecoded = base64_decode($encryptedData);
        $ivDecoded = base64_decode($iv);
        
        if ($sessionKeyDecoded === false || $encryptedDataDecoded === false || $ivDecoded === false) {
            error_log("手机号解密失败: base64解码失败");
            return false;
        }
        
        $decrypted = openssl_decrypt($encryptedDataDecoded, 'AES-128-CBC', $sessionKeyDecoded, OPENSSL_RAW_DATA, $ivDecoded);
        
        if ($decrypted === false) {
            error_log("手机号解密失败: openssl_decrypt返回false，可能是session_key过期或无效");
            return false;
        }
        
        $decryptedData = json_decode($decrypted, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("手机号解密失败: JSON解析错误 - " . json_last_error_msg());
            return false;
        }
        
        // 检查水印（如果提供了appId）
        if ($appId && isset($decryptedData['watermark']) && isset($decryptedData['watermark']['appid'])) {
            if ($decryptedData['watermark']['appid'] !== $appId) {
                error_log("手机号解密失败: 水印验证失败，期望appid: {$appId}，实际appid: " . $decryptedData['watermark']['appid']);
                return false;
            }
        }
        
        // 验证手机号字段是否存在
        if (!isset($decryptedData['phoneNumber'])) {
            error_log("手机号解密失败: 解密数据中没有phoneNumber字段，数据内容: " . json_encode($decryptedData));
            return false;
        }
        
        $phoneNumber = $decryptedData['phoneNumber'];
        error_log("手机号解密成功: " . $phoneNumber);
        
        return $phoneNumber;
        
    } catch (Exception $e) {
        error_log("手机号解密异常: " . $e->getMessage());
        return false;
    }
}

// 调用微信接口获取openid和session_key
function getWxOpenId($code, $appId, $appSecret) {
    $url = "https://api.weixin.qq.com/sns/jscode2session";
    $params = [
        'appid' => $appId,
        'secret' => $appSecret,
        'js_code' => $code,
        'grant_type' => 'authorization_code'
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => 'User-Agent: Mozilla/5.0'
        ]
    ]);
    
    $response = file_get_contents($url . '?' . http_build_query($params), false, $context);
    
    if ($response === false) {
        error_log('微信API调用失败: ' . error_get_last()['message']);
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['errcode']) && $result['errcode'] !== 0) {
        error_log('微信API返回错误: ' . json_encode($result));
        return false;
    }
    
    return $result;
}

function responseJson($code, $message, $data = null) {
    $response = [
        'code' => $code,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
?>