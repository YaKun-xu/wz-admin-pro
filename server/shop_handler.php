<?php

/**
 * 商品API处理模块
 */

// 统一响应函数
function shopResponseJson($code, $message, $data = null) {
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// 处理商品相关API请求
function handleShopApi($pdo, $path, $method = 'GET', $data = []) {
    
    // 路由分发
    switch ($path) {
        case '/shop/categories':
            return handleGetCategories($pdo);
            
        case '/shop/products':
            return handleGetProducts($pdo, $data);
            
        case '/shop/config/shop_index':
            return handleGetShopConfig($pdo, 'shop_index');
            
        default:
            // 处理动态路由
            if (preg_match('/^\/shop\/products\/(\d+)$/', $path, $matches)) {
                return handleGetProductDetail($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/images$/', $path, $matches)) {
                return handleGetProductImages($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/steps$/', $path, $matches)) {
                return handleGetModifySteps($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/tutorials$/', $path, $matches)) {
                return handleGetTutorials($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/videos$/', $path, $matches)) {
                return handleGetVideos($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/faqs$/', $path, $matches)) {
                return handleGetFaqs($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/products\/(\d+)\/notices$/', $path, $matches)) {
                return handleGetPurchaseNotices($pdo, $matches[1]);
            }
            if (preg_match('/^\/shop\/config\/(.+)$/', $path, $matches)) {
                return handleGetShopConfig($pdo, $matches[1]);
            }
            
            shopResponseJson(404, '接口不存在');
    }
}

// 获取商品分类列表
function handleGetCategories($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, sort_order, status 
            FROM shop_categories 
            WHERE status = 1 
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 转换数据类型
        foreach ($categories as &$category) {
            $category['id'] = (int)$category['id'];
            $category['sort_order'] = (int)$category['sort_order'];
            $category['status'] = (int)$category['status'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $categories
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取分类失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取商品列表
function handleGetProducts($pdo, $params = []) {
    try {
        $categoryId = isset($params['category_id']) ? (int)$params['category_id'] : 0;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $pageSize = isset($params['page_size']) ? (int)$params['page_size'] : 10;
        $keyword = isset($params['keyword']) ? trim($params['keyword']) : '';
        
        $offset = ($page - 1) * $pageSize;
        
        // 构建查询条件
        $whereConditions = ["p.status = 1"];
        $queryParams = [];
        
        if ($categoryId > 0) {
            $whereConditions[] = "p.category_id = ?";
            $queryParams[] = $categoryId;
        }
        
        if (!empty($keyword)) {
            $whereConditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $queryParams[] = "%{$keyword}%";
            $queryParams[] = "%{$keyword}%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 查询商品列表 - 直接拼接LIMIT避免占位符问题
        $sql = "
            SELECT 
                p.id, p.category_id, p.product_type, p.title, p.description, p.price,
                p.cover_image, p.sales, p.sort_order, p.status, p.created_at,
                c.name as category_name
            FROM shop_products p
            LEFT JOIN shop_categories c ON p.category_id = c.id
            WHERE {$whereClause}
            ORDER BY p.sort_order ASC, p.id ASC
            LIMIT {$offset}, {$pageSize}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryParams);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 处理数据类型
        foreach ($products as &$product) {
            $product['id'] = (int)$product['id'];
            $product['category_id'] = (int)$product['category_id'];
            $product['product_type'] = (int)$product['product_type'];
            $product['price'] = (float)$product['price'];
            $product['sales'] = (int)$product['sales'];
            $product['sort_order'] = (int)$product['sort_order'];
            $product['status'] = (int)$product['status'];
        }
        
        // 查询总数
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM shop_products p
            WHERE {$whereClause}
        ");
        $countParams = array_slice($queryParams, 0, -2); // 移除LIMIT和OFFSET参数
        $countStmt->execute($countParams);
        $total = (int)$countStmt->fetchColumn();
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'list' => $products,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'total_pages' => ceil($total / $pageSize)
            ]
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取商品列表失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取商品详情
function handleGetProductDetail($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.*, c.name as category_name
            FROM shop_products p
            LEFT JOIN shop_categories c ON p.category_id = c.id
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return [
                'code' => 404,
                'message' => '商品不存在',
                'data' => null
            ];
        }
        
        // 处理数据类型
        $product['id'] = (int)$product['id'];
        $product['category_id'] = (int)$product['category_id'];
        $product['product_type'] = (int)$product['product_type'];
        $product['price'] = (float)$product['price'];
        $product['sales'] = (int)$product['sales'];
        $product['sort_order'] = (int)$product['sort_order'];
        $product['status'] = (int)$product['status'];
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $product
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取商品详情失败: ' . $e->getMessage(),
            'data' => null
        ];
    }
}

// 获取商品图片
function handleGetProductImages($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, product_id, image_url, sort_order
            FROM shop_product_images
            WHERE product_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($images as &$image) {
            $image['id'] = (int)$image['id'];
            $image['product_id'] = (int)$image['product_id'];
            $image['sort_order'] = (int)$image['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $images
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取商品图片失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取修改步骤
function handleGetModifySteps($pdo, $productId) {
    try {
        // 只查询特定商品的数据，如果没有就返回空
        $stmt = $pdo->prepare("
            SELECT id, product_id, step_number, title, description, note, icon, sort_order
            FROM shop_modify_steps
            WHERE product_id = ? AND status = 1
            ORDER BY sort_order ASC, step_number ASC
        ");
        $stmt->execute([$productId]);
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($steps as &$step) {
            $step['id'] = (int)$step['id'];
            $step['product_id'] = $step['product_id'] ? (int)$step['product_id'] : null;
            $step['step_number'] = (int)$step['step_number'];
            $step['sort_order'] = (int)$step['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $steps
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取修改步骤失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取商城配置
function handleGetShopConfig($pdo, $configType) {
    try {
        // 从数据库获取配置数据
        $stmt = $pdo->prepare("
            SELECT page_name, config_key, config_value, parent_key 
            FROM configs 
            WHERE page_name = ?
            ORDER BY parent_key ASC, config_key ASC
        ");
        $stmt->execute([$configType]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 构建嵌套配置结构
        $configs = [];
        
        foreach ($results as $row) {
            $configKey = $row['config_key'];
            $configValue = $row['config_value'];
            $parentKey = $row['parent_key'];
            
            // 修正特殊字段类型
            $forceStringKeys = ['switch', 'userId'];
            $isSpecialKey = in_array($configKey, $forceStringKeys);
            
            // 处理数据类型
            if ($isSpecialKey) {
                $configValue = (string) $configValue;
            } elseif ($configValue === 'true') {
                $configValue = true;
            } elseif ($configValue === 'false') {
                $configValue = false;
            } elseif (is_numeric($configValue)) {
                if (strpos($configValue, '.') !== false) {
                    $configValue = (float) $configValue;
                } else {
                    $configValue = (int) $configValue;
                }
            }
            
            // 处理嵌套键
            if ($parentKey) {
                $keys = explode('.', $parentKey);
                $current = &$configs;
                
                foreach ($keys as $key) {
                    if (!isset($current[$key]) || !is_array($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }
                
                $current[$configKey] = $configValue;
            } else {
                $configs[$configKey] = $configValue;
            }
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $configs
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取商城配置失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取图文教程
function handleGetTutorials($pdo, $productId) {
    try {
        // 只查询特定商品的数据，如果没有就返回空
        $stmt = $pdo->prepare("
            SELECT id, product_id, title, content, image_url, sort_order
            FROM shop_tutorials
            WHERE product_id = ? AND status = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tutorials as &$tutorial) {
            $tutorial['id'] = (int)$tutorial['id'];
            $tutorial['product_id'] = $tutorial['product_id'] ? (int)$tutorial['product_id'] : null;
            $tutorial['sort_order'] = (int)$tutorial['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $tutorials
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取图文教程失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取视频教程
function handleGetVideos($pdo, $productId) {
    try {
        // 只查询特定商品的数据，如果没有就返回空
        $stmt = $pdo->prepare("
            SELECT id, product_id, title, description, video_url, sort_order
            FROM shop_videos
            WHERE product_id = ? AND status = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($videos as &$video) {
            $video['id'] = (int)$video['id'];
            $video['product_id'] = $video['product_id'] ? (int)$video['product_id'] : null;
            $video['sort_order'] = (int)$video['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $videos
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取视频教程失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取常见问题
function handleGetFaqs($pdo, $productId) {
    try {
        // 只查询特定商品的数据，如果没有就返回空
        $stmt = $pdo->prepare("
            SELECT id, product_id, question, answer, sort_order
            FROM shop_faqs
            WHERE product_id = ? AND status = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($faqs as &$faq) {
            $faq['id'] = (int)$faq['id'];
            $faq['product_id'] = $faq['product_id'] ? (int)$faq['product_id'] : null;
            $faq['sort_order'] = (int)$faq['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $faqs
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取常见问题失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

// 获取购买须知
function handleGetPurchaseNotices($pdo, $productId) {
    try {
        // 只查询特定商品的数据，如果没有就返回空
        $stmt = $pdo->prepare("
            SELECT id, product_id, content, sort_order
            FROM shop_purchase_notices
            WHERE product_id = ? AND status = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$productId]);
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notices as &$notice) {
            $notice['id'] = (int)$notice['id'];
            $notice['product_id'] = $notice['product_id'] ? (int)$notice['product_id'] : null;
            $notice['sort_order'] = (int)$notice['sort_order'];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $notices
        ];
    } catch (Exception $e) {
        return [
            'code' => 500,
            'message' => '获取购买须知失败: ' . $e->getMessage(),
            'data' => []
        ];
    }
}

?> 