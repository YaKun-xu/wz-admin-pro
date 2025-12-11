<?php

/**
 * 微信支付回调处理
 */

require_once 'db_config.php';

header('Content-Type: text/xml; charset=utf-8');

try {
    // 获取回调数据
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        responseError('回调数据为空');
    }
    
    // 解析XML数据
    $data = xmlToArray($input);
    
    if (!$data) {
        responseError('XML解析失败');
    }
    
    // 记录回调日志
    error_log('微信支付回调数据: ' . json_encode($data));
    
    // 验证回调签名
    if (!verifyNotifySign($data)) {
        responseError('签名验证失败');
    }
    
    // 检查支付结果
    if ($data['return_code'] !== 'SUCCESS' || $data['result_code'] !== 'SUCCESS') {
        responseError('支付失败: ' . ($data['err_code_des'] ?? '未知错误'));
    }
    
    // 获取订单号和交易号
    $orderNo = $data['out_trade_no'];
    $transactionId = $data['transaction_id'];
    $totalFee = $data['total_fee']; // 分
    
    // 连接数据库
    $dbConfig = require_once 'db_config.php';
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 查询订单
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_no = ?");
    $stmt->execute([$orderNo]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        responseError('订单不存在');
    }
    
    // 检查订单状态
    if ($order['status'] === 'paid') {
        // 订单已处理，直接返回成功
        responseSuccess();
    }
    
    // 检查金额是否一致
    if (intval($order['total_amount'] * 100) !== intval($totalFee)) {
        responseError('金额不匹配');
    }
    
    // 更新订单状态
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'paid', 
            transaction_id = ?, 
            paid_at = NOW(),
            updated_at = NOW()
        WHERE order_no = ?
    ");
    $stmt->execute([$transactionId, $orderNo]);
    
    // 这里可以添加其他业务逻辑，如：
    // - 发送订单确认短信/邮件
    // - 更新商品销量
    // - 记录支付日志等
    
    // 更新商品销量（数量固定为1）
    $stmt = $pdo->prepare("UPDATE shop_products SET sales = sales + 1 WHERE id = ?");
    $stmt->execute([$order['product_id']]);
    
    // 记录支付成功日志
    error_log("订单支付成功: {$orderNo}, 交易号: {$transactionId}, 金额: {$totalFee}分");
    
    // 返回成功响应
    responseSuccess();
    
} catch (Exception $e) {
    error_log('支付回调处理失败: ' . $e->getMessage());
    responseError('系统错误');
}

// 验证回调签名
function verifyNotifySign($data) {
    try {
        $sign = $data['sign'];
        unset($data['sign']);
        
        // 获取支付配置
        $dbConfig = require 'db_config.php';
        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password']
        );
        
        // 根据订单号查找支付配置
        $orderNo = $data['out_trade_no'];
        $stmt = $pdo->prepare("
            SELECT mc.pay_key 
            FROM orders o 
            LEFT JOIN miniprogram_config mc ON o.app_id = mc.app_id 
            WHERE o.order_no = ?
        ");
        $stmt->execute([$orderNo]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            return false;
        }
        
        $payKey = $config['pay_key'];
        
        // 生成签名
        ksort($data);
        $string = '';
        foreach ($data as $k => $v) {
            if ($v != '') {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string .= 'key=' . $payKey;
        
        $generatedSign = strtoupper(md5($string));
        
        return $generatedSign === $sign;
        
    } catch (Exception $e) {
        error_log('签名验证异常: ' . $e->getMessage());
        return false;
    }
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

// 返回成功响应
function responseSuccess() {
    $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    echo $xml;
    exit;
}

// 返回错误响应
function responseError($message) {
    $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[' . $message . ']]></return_msg></xml>';
    echo $xml;
    exit;
}

?> 