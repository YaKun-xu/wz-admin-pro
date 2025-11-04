<?php

/**
 * 支付处理模块
 */

require_once 'db_config.php';

// 处理支付相关API请求
function handlePayApi($pdo, $path, $method = 'GET', $data = []) {
    
    switch ($path) {
        case '/pay/create-order':
            return handleCreateOrder($pdo, $data);
            
        case '/pay/wx-pay':
            return handleWxPay($pdo, $data);
            
        case '/pay/order-status':
            return handleOrderStatus($pdo, $data);
            
        case '/pay/user-orders':
            return handleUserOrders($pdo, $data);
            
        case '/pay/confirm-payment':
            return handleConfirmPayment($pdo, $data);
            
        case '/pay/cancel-order':
            return handleCancelOrder($pdo, $data);
            
        default:
            if (preg_match('/^\/pay\/orders\/(\d+)$/', $path, $matches)) {
                return handleGetOrderDetail($pdo, $matches[1]);
            }
            
            return [
                'code' => 404,
                'message' => '支付接口不存在',
                'data' => null
            ];
    }
}

// 创建订单
function handleCreateOrder($pdo, $data) {
    try {
        $token = $data['token'] ?? '';
        $productId = $data['product_id'] ?? 0;
        
        if (empty($token) || empty($productId)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        // 获取商品信息
        $stmt = $pdo->prepare("SELECT * FROM shop_products WHERE id = ? AND status = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return [
                'code' => 404,
                'message' => '商品不存在或已下架',
                'data' => null
            ];
        }
        
        // 获取用户信息
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$session['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 生成订单号
        $orderNo = 'WZ' . date('YmdHis') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // 创建订单（移除了quantity, user_phone, user_note字段）
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_no, user_id, app_id, product_id, product_title, 
                product_price, total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $orderNo,
            $session['user_id'],
            $session['app_id'],
            $productId,
            $product['title'],
            $product['price'],
            $product['price'] // total_amount = product_price (数量固定为1)
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        return [
            'code' => 200,
            'message' => '订单创建成功',
            'data' => [
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'total_amount' => $product['price'],
                'product_title' => $product['title']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("创建订单失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '订单创建失败',
            'data' => null
        ];
    }
}

// 微信支付
function handleWxPay($pdo, $data) {
    try {
        $orderId = $data['order_id'] ?? 0;
        $token = $data['token'] ?? '';
        
        if (empty($orderId) || empty($token)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $session['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'code' => 404,
                'message' => '订单不存在',
                'data' => null
            ];
        }
        
        // 检查订单状态 - 如果订单已支付，返回特殊错误码
        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            return [
                'code' => 409, // 使用409 Conflict状态码表示订单状态冲突
                'message' => '该订单已支付，无需重复支付',
                'data' => [
                    'order_status' => $order['status'],
                    'paid_at' => $order['paid_at']
                ]
            ];
        }
        
        if ($order['status'] !== 'pending') {
            return [
                'code' => 400,
                'message' => '订单状态不正确，无法支付',
                'data' => [
                    'order_status' => $order['status']
                ]
            ];
        }
        
        // 获取支付配置
        $payConfig = getPayConfig($pdo, $session['app_id']);
        if (!$payConfig || !$payConfig['pay_enabled']) {
            return [
                'code' => 403,
                'message' => '支付功能未启用',
                'data' => null
            ];
        }
        
        // 调用微信支付统一下单
        $payResult = createWxPayOrder($payConfig, $order, $session['openid']);
        
        if ($payResult['success']) {
            $payData = $payResult['data'];
            
            return [
                'code' => 200,
                'message' => '支付参数获取成功',
                'data' => $payData
            ];
        } else {
            // 处理微信支付返回的具体错误
            $errorCode = $payResult['error_code'] ?? '';
            $errorMessage = $payResult['error_message'] ?? '支付参数获取失败';
            
            if ($errorCode === 'ORDERPAID') {
                // 如果微信返回订单已支付，更新本地订单状态
                $stmt = $pdo->prepare("UPDATE orders SET status = 'paid', paid_at = NOW() WHERE id = ?");
                $stmt->execute([$orderId]);
                
                return [
                    'code' => 409,
                    'message' => '该订单已支付，无需重复支付',
                    'data' => [
                        'order_status' => 'paid'
                    ]
                ];
            }
            
            return [
                'code' => 500,
                'message' => $errorMessage,
                'data' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("微信支付失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '支付调用失败',
            'data' => null
        ];
    }
}

// 获取订单状态
function handleOrderStatus($pdo, $data) {
    try {
        $orderId = $data['order_id'] ?? 0;
        $token = $data['token'] ?? '';
        
        if (empty($orderId) || empty($token)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $session['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'code' => 404,
                'message' => '订单不存在',
                'data' => null
            ];
        }
        
        return [
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'order_id' => $order['id'],
                'order_no' => $order['order_no'],
                'status' => $order['status'],
                'total_amount' => $order['total_amount'],
                'paid_at' => $order['paid_at']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("获取订单状态失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '获取订单状态失败',
            'data' => null
        ];
    }
}

// 获取订单详情
function handleGetOrderDetail($pdo, $orderId) {
    try {
        // 这里暂时不验证用户，实际应用中需要验证用户权限
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'code' => 404,
                'message' => '订单不存在',
                'data' => null
            ];
        }
        
        return [
            'code' => 200,
            'message' => '获取成功',
            'data' => $order
        ];
        
    } catch (Exception $e) {
        error_log("获取订单详情失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '获取订单详情失败',
            'data' => null
        ];
    }
}

// 获取用户订单列表
function handleUserOrders($pdo, $data) {
    try {
        $token = $data['token'] ?? '';
        $page = intval($data['page'] ?? 1);
        $pageSize = intval($data['page_size'] ?? 10);
        $status = $data['status'] ?? ''; // 新增状态过滤参数
        
        if (empty($token)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        $offset = ($page - 1) * $pageSize;
        
        // 构建查询条件
        $whereConditions = ['o.user_id = ?'];
        $queryParams = [$session['user_id']];
        
        if (!empty($status)) {
            $whereConditions[] = 'o.status = ?';
            $queryParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 获取订单列表，包含商品图片
        $stmt = $pdo->prepare("
            SELECT 
                o.id, o.order_no, o.user_id, o.product_id, o.product_title, o.product_price,
                o.total_amount, o.status, o.created_at, o.paid_at, o.card_key, o.card_key_id,
                p.cover_image, p.description as product_description, p.product_type
            FROM orders o
            LEFT JOIN shop_products p ON o.product_id = p.id
            WHERE {$whereClause}
            ORDER BY o.created_at DESC 
            LIMIT " . $pageSize . " OFFSET " . $offset . "
        ");
        $stmt->execute($queryParams);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取总数
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders o WHERE {$whereClause}");
        $countStmt->execute($queryParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'orders' => $orders,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]
        ];
        
    } catch (Exception $e) {
        error_log("获取用户订单失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '获取订单列表失败',
            'data' => null
        ];
    }
}

// 确认支付（用于开发环境或前端支付成功后确认）
function handleConfirmPayment($pdo, $data) {
    try {
        $orderId = $data['order_id'] ?? 0;
        $token = $data['token'] ?? '';
        
        if (empty($orderId) || empty($token)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        // 获取订单信息
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $session['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'code' => 404,
                'message' => '订单不存在',
                'data' => null
            ];
        }
        
        // 如果订单已经是已支付状态，直接返回成功
        if ($order['status'] === 'paid') {
            return [
                'code' => 200,
                'message' => '订单已确认支付',
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'paid'
                ]
            ];
        }
        
        // 更新订单状态为已支付
        // 注意：transaction_id 只有微信支付回调时才设置为真实的微信订单号
        // 前端确认支付不设置 transaction_id，保持为 NULL
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'paid', 
                paid_at = NOW(), 
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // 检查商品是否为卡密类型，如果是则自动分配卡密
        $productResult = assignCardKeyIfNeeded($pdo, $order);
        
        // 更新商品销量（数量固定为1）
        $stmt = $pdo->prepare("UPDATE shop_products SET sales = sales + 1 WHERE id = ?");
        $stmt->execute([$order['product_id']]);
        
        return [
            'code' => 200,
            'message' => '支付确认成功',
            'data' => [
                'order_id' => $orderId,
                'status' => 'paid',
                'card_key' => $productResult['card_key'] ?? null,
                'is_card_product' => $productResult['is_card_product'] ?? false
            ]
        ];
        
    } catch (Exception $e) {
        error_log("确认支付失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '确认支付失败',
            'data' => null
        ];
    }
}

// 验证支付token
function validatePayToken($pdo, $token) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.openid 
        FROM user_sessions s 
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 获取支付配置
function getPayConfig($pdo, $appId) {
    $stmt = $pdo->prepare("SELECT * FROM miniprogram_config WHERE app_id = ? AND is_active = 1");
    $stmt->execute([$appId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 创建微信支付订单
function createWxPayOrder($payConfig, $order, $openid) {
    try {
        $appId = $payConfig['app_id'];
        $mchId = $payConfig['mch_id'];
        $payKey = $payConfig['pay_key'];
        $notifyUrl = $payConfig['pay_notify_url'];
        
        // 统一下单参数
        $params = [
            'appid' => $appId,
            'mch_id' => $mchId,
            'nonce_str' => generateNonceStr(),
            'body' => $order['product_title'],
            'out_trade_no' => $order['order_no'],
            'total_fee' => intval($order['total_amount'] * 100), // 转为分
            'spbill_create_ip' => getClientIp(),
            'notify_url' => $notifyUrl,
            'trade_type' => 'JSAPI',
            'openid' => $openid
        ];
        
        // 生成签名
        $params['sign'] = generateWxPaySign($params, $payKey);
        
        // 转换为XML
        $xml = arrayToXml($params);
        
        // 调用微信支付API
        $response = httpPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = xmlToArray($response);
        
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            // 生成小程序支付参数
            $timeStamp = time();
            $nonceStr = generateNonceStr();
            $package = 'prepay_id=' . $result['prepay_id'];
            
            $payParams = [
                'appId' => $appId,
                'timeStamp' => $timeStamp,
                'nonceStr' => $nonceStr,
                'package' => $package,
                'signType' => 'MD5'
            ];
            
            $payParams['paySign'] = generateWxPaySign($payParams, $payKey);
            $payParams['prepay_id'] = $result['prepay_id'];
            
            return ['success' => true, 'data' => $payParams];
        } else {
            error_log('微信支付下单失败: ' . json_encode($result));
            
            // 处理不同的错误情况
            $errorCode = $result['err_code'] ?? $result['return_code'] ?? 'UNKNOWN';
            $errorMessage = $result['err_code_des'] ?? $result['return_msg'] ?? '支付参数获取失败';
            
            return [
                'success' => false, 
                'error_code' => $errorCode, 
                'error_message' => $errorMessage,
                'raw_result' => $result
            ];
        }
        
    } catch (Exception $e) {
        error_log('微信支付异常: ' . $e->getMessage());
        return [
            'success' => false, 
            'error_code' => 'EXCEPTION', 
            'error_message' => '支付调用失败: ' . $e->getMessage()
        ];
    }
}

// 生成随机字符串
function generateNonceStr($length = 32) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

// 生成微信支付签名
function generateWxPaySign($params, $key) {
    ksort($params);
    $string = '';
    foreach ($params as $k => $v) {
        if ($k != 'sign' && $v != '') {
            $string .= $k . '=' . $v . '&';
        }
    }
    $string .= 'key=' . $key;
    return strtoupper(md5($string));
}

// 数组转XML
function arrayToXml($array) {
    $xml = '<xml>';
    foreach ($array as $key => $value) {
        $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    }
    $xml .= '</xml>';
    return $xml;
}

// XML转数组
function xmlToArray($xml) {
    $array = [];
    $tmp = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($tmp !== false) {
        $array = json_decode(json_encode($tmp), true);
    }
    return $array;
}

// HTTP POST请求
function httpPost($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return $response;
    } else {
        throw new Exception('HTTP请求失败: ' . $httpCode);
    }
}

// 取消订单
function handleCancelOrder($pdo, $data) {
    try {
        $token = $data['token'] ?? '';
        $orderId = $data['order_id'] ?? '';
        
        if (empty($orderId) || empty($token)) {
            return [
                'code' => 400,
                'message' => '参数错误',
                'data' => null
            ];
        }
        
        // 验证用户token
        $session = validatePayToken($pdo, $token);
        if (!$session) {
            return [
                'code' => 401,
                'message' => '登录已过期',
                'data' => null
            ];
        }
        
        // 检查订单是否存在且属于当前用户
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $session['user_id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'code' => 404,
                'message' => '订单不存在',
                'data' => null
            ];
        }
        
        // 检查订单是否可以取消（只有pending状态才能取消）
        if ($order['status'] !== 'pending') {
            return [
                'code' => 400,
                'message' => '该订单不能取消',
                'data' => null
            ];
        }
        
        // 更新订单状态为cancelled
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        return [
            'code' => 200,
            'message' => '订单已取消',
            'data' => [
                'order_id' => $orderId,
                'status' => 'cancelled'
            ]
        ];
        
    } catch (Exception $e) {
        error_log("取消订单失败: " . $e->getMessage());
        return [
            'code' => 500,
            'message' => '取消订单失败',
            'data' => null
        ];
    }
}

// 获取客户端IP
function getClientIp() {
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// 自动分配卡密（如果商品是卡密类型）
function assignCardKeyIfNeeded($pdo, $order) {
    try {
        // 检查商品是否为卡密类型
        $stmt = $pdo->prepare("SELECT product_type FROM shop_products WHERE id = ?");
        $stmt->execute([$order['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || $product['product_type'] != 2) {
            // 不是卡密商品，返回普通商品标识
            return [
                'is_card_product' => false,
                'card_key' => null
            ];
        }
        
        // 是卡密商品，分配卡密
        return assignCardKeyToOrder($pdo, $order);
        
    } catch (Exception $e) {
        error_log("卡密分配检查失败: " . $e->getMessage());
        return [
            'is_card_product' => false,
            'card_key' => null,
            'error' => $e->getMessage()
        ];
    }
}

// 为订单分配卡密
function assignCardKeyToOrder($pdo, $order) {
    try {
        // 开启事务确保数据一致性
        $pdo->beginTransaction();
        
        // 获取可用的卡密（加锁防止并发问题）
        $stmt = $pdo->prepare("
            SELECT id, card_key 
            FROM shop_card_keys 
            WHERE product_id = ? AND status = 0 
            ORDER BY id ASC 
            LIMIT 1 
            FOR UPDATE
        ");
        $stmt->execute([$order['product_id']]);
        $cardKey = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cardKey) {
            $pdo->rollback();
            return [
                'is_card_product' => true,
                'card_key' => null,
                'error' => '该商品暂无可用卡密，请联系客服'
            ];
        }
        
        // 更新订单，添加卡密信息
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET card_key = ?, card_key_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$cardKey['card_key'], $cardKey['id'], $order['id']]);
        
        // 更新卡密状态为已使用
        $stmt = $pdo->prepare("
            UPDATE shop_card_keys 
            SET status = 1 
            WHERE id = ?
        ");
        $stmt->execute([$cardKey['id']]);
        
        // 提交事务
        $pdo->commit();
        
        return [
            'is_card_product' => true,
            'card_key' => $cardKey['card_key'],
            'card_key_id' => $cardKey['id']
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("卡密分配失败: " . $e->getMessage());
        return [
            'is_card_product' => true,
            'card_key' => null,
            'error' => '卡密分配失败: ' . $e->getMessage()
        ];
    }
}

// 查询卡密库存
function getCardKeyStock($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as available_count,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as used_count
            FROM shop_card_keys 
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("查询卡密库存失败: " . $e->getMessage());
        return null;
    }
}

?> 