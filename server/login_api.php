<?php
header('Content-Type: application/json; charset=utf-8');
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
        'message' => '系统未安装，请先完成安装',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'db_config.php';

class LoginAPI {
    private $pdo;
    
    public function __construct() {
        $config = require 'db_config.php';
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", 
                $config['username'], 
                $config['password']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->response(500, '数据库连接失败');
        }
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'wx_login':
                if ($method === 'POST') {
                    $this->wxLogin();
                } else {
                    $this->response(405, '方法不允许');
                }
                break;
                
            case 'bind_phone':
                if ($method === 'POST') {
                    $this->bindPhone();
                } else {
                    $this->response(405, '方法不允许');
                }
                break;
                
            case 'get_user_profile':
                if ($method === 'POST') {
                    $this->getUserProfile();
                } else {
                    $this->response(405, '方法不允许');
                }
                break;
                
            case 'config':
                if ($method === 'GET') {
                    $this->getLoginConfig();
                } else {
                    $this->response(405, '方法不允许');
                }
                break;
                
            case 'getAllConfigs':
                if ($method === 'GET') {
                    $this->getAllConfigs();
                } else {
                    $this->response(405, '方法不允许');
                }
                break;
                
            default:
                $this->response(404, '接口不存在');
        }
    }
    
    // 微信登录
    private function wxLogin() {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $appId = $input['appId'] ?? '';
        $userInfo = $input['userInfo'] ?? [];
        
        if (empty($code) || empty($appId)) {
            $this->response(400, '参数不完整');
        }
        
        // 获取小程序配置
        $appConfig = $this->getAppConfig($appId);
        if (!$appConfig) {
            $this->response(400, '小程序配置不存在');
        }
        
        if (!$appConfig['login_enabled']) {
            $this->response(400, '登录功能未开启');
        }
        
        // 调用微信接口获取 openid
        $wxResult = $this->getWxOpenId($code, $appConfig['app_id'], $appConfig['app_secret']);
        if (!$wxResult) {
            $this->response(400, '微信登录失败');
        }
        
        // 创建或更新用户
        $user = $this->createOrUpdateUser($appId, $wxResult, $userInfo);
        
        // 生成登录令牌
        $token = $this->generateToken($user['id'], $appId);
        
        $this->response(200, '登录成功', [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar_url' => $user['avatar_url'],
                'phone' => $user['phone'],
                'is_phone_verified' => $user['is_phone_verified']
            ]
        ]);
    }
    
    // 绑定手机号
    private function bindPhone() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $encryptedData = $input['encryptedData'] ?? '';
        $iv = $input['iv'] ?? '';
        
        if (empty($token) || empty($encryptedData) || empty($iv)) {
            $this->response(400, '参数不完整');
        }
        
        // 验证令牌
        $session = $this->validateToken($token);
        if (!$session) {
            $this->response(401, '登录已过期');
        }
        
        // 获取用户信息
        $user = $this->getUserById($session['user_id']);
        if (!$user) {
            $this->response(404, '用户不存在');
        }
        
        // 解密手机号（这里需要实现微信数据解密）
        $phoneData = $this->decryptWxData($encryptedData, $iv, $user['session_key']);
        if (!$phoneData) {
            $this->response(400, '手机号解密失败');
        }
        
        // 更新用户手机号
        $this->updateUserPhone($user['id'], $phoneData['phoneNumber']);
        
        $this->response(200, '手机号绑定成功', [
            'phone' => $phoneData['phoneNumber']
        ]);
    }
    
    // 获取用户信息
    private function getUserProfile() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        
        if (empty($token)) {
            $this->response(400, '缺少令牌');
        }
        
        $session = $this->validateToken($token);
        if (!$session) {
            $this->response(401, '登录已过期');
        }
        
        $user = $this->getUserById($session['user_id']);
        if (!$user) {
            $this->response(404, '用户不存在');
        }
        
        $this->response(200, '获取成功', [
            'id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar_url' => $user['avatar_url'],
            'phone' => $user['phone'],
            'is_phone_verified' => $user['is_phone_verified'],
            'gender' => $user['gender'],
            'country' => $user['country'],
            'province' => $user['province'],
            'city' => $user['city']
        ]);
    }
    
    // 获取登录配置
    private function getLoginConfig() {
        $appId = $_GET['appId'] ?? '';
        
        if (empty($appId)) {
            $this->response(400, '缺少AppID');
        }
        
        $config = $this->getAppConfig($appId);
        if (!$config) {
            $this->response(404, '小程序配置不存在');
        }
        
        // 确保数据类型正确 - 数据库中0表示不需要，1表示需要
        $phoneBindRequired = (int)$config['phone_bind_required'];
        
        $this->response(200, '获取成功', [
            'login_enabled' => (int)$config['login_enabled'],
            'phone_bind_required' => $phoneBindRequired, // 返回整数 0 或 1
            'app_name' => $config['app_name']
        ]);
    }
    
    // 获取所有可用的小程序配置
    private function getAllConfigs() {
        try {
            $stmt = $this->pdo->prepare("SELECT app_id, app_name, login_enabled, is_active FROM miniprogram_config ORDER BY is_active DESC, id ASC");
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($configs)) {
                $this->response(404, '没有找到任何小程序配置');
            }
            
            // 转换数据类型确保一致性
            foreach ($configs as &$config) {
                $config['login_enabled'] = (bool)$config['login_enabled'];
                $config['is_active'] = (int)$config['is_active'];
            }
            
            $this->response(200, '获取成功', $configs);
            
        } catch (Exception $e) {
            error_log("获取所有配置失败: " . $e->getMessage());
            $this->response(500, '获取配置失败');
        }
    }
    
    // 获取小程序配置
    private function getAppConfig($appId) {
        $stmt = $this->pdo->prepare("SELECT * FROM miniprogram_config WHERE app_id = ? AND is_active = 1");
        $stmt->execute([$appId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 调用微信接口获取 openid
    private function getWxOpenId($code, $appId, $appSecret) {
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
    
    // 创建或更新用户
    private function createOrUpdateUser($appId, $wxResult, $userInfo) {
        $openid = $wxResult['openid'];
        $sessionKey = $wxResult['session_key'];
        $unionid = $wxResult['unionid'] ?? null;
        
        // 检查用户是否存在
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE app_id = ? AND openid = ?");
        $stmt->execute([$appId, $openid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // 更新用户信息
            $stmt = $this->pdo->prepare("
                UPDATE users SET 
                    session_key = ?, unionid = ?, nickname = ?, avatar_url = ?, 
                    gender = ?, country = ?, province = ?, city = ?, last_login_time = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $sessionKey, $unionid, $userInfo['nickName'] ?? $user['nickname'],
                $userInfo['avatarUrl'] ?? $user['avatar_url'], $userInfo['gender'] ?? $user['gender'],
                $userInfo['country'] ?? $user['country'], $userInfo['province'] ?? $user['province'],
                $userInfo['city'] ?? $user['city'], $user['id']
            ]);
            
            return $this->getUserById($user['id']);
        } else {
            // 创建新用户
            $stmt = $this->pdo->prepare("
                INSERT INTO users (app_id, openid, unionid, session_key, nickname, avatar_url, 
                                 gender, country, province, city, last_login_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $appId, $openid, $unionid, $sessionKey, $userInfo['nickName'] ?? '',
                $userInfo['avatarUrl'] ?? '', $userInfo['gender'] ?? 0,
                $userInfo['country'] ?? '', $userInfo['province'] ?? '', $userInfo['city'] ?? ''
            ]);
            
            return $this->getUserById($this->pdo->lastInsertId());
        }
    }
    
    // 生成登录令牌
    private function generateToken($userId, $appId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 3600); // 7天过期
        
        // 删除旧的会话
        $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND app_id = ?");
        $stmt->execute([$userId, $appId]);
        
        // 创建新会话
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (user_id, app_id, token, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $appId, $token, $expiresAt]);
        
        return $token;
    }
    
    // 验证令牌
    private function validateToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM user_sessions 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 根据ID获取用户
    private function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // 更新用户手机号
    private function updateUserPhone($userId, $phone) {
        $stmt = $this->pdo->prepare("
            UPDATE users SET phone = ?, is_phone_verified = 1 WHERE id = ?
        ");
        $stmt->execute([$phone, $userId]);
    }
    
    // 解密微信数据
    private function decryptWxData($encryptedData, $iv, $sessionKey) {
        // 检查参数是否为空
        if (empty($sessionKey) || empty($encryptedData) || empty($iv)) {
            error_log("手机号解密失败: 参数为空");
            return false;
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
            
            // 验证手机号字段是否存在
            if (!isset($decryptedData['phoneNumber'])) {
                error_log("手机号解密失败: 解密数据中没有phoneNumber字段");
                return false;
            }
            
            return $decryptedData;
            
        } catch (Exception $e) {
            error_log("手机号解密异常: " . $e->getMessage());
            return false;
        }
    }
    
    private function response($code, $message, $data = null) {
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
}

$api = new LoginAPI();
$api->handleRequest();
?>