<?php
session_start();
require_once '../server/db_config.php';

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// 初始化统计数据
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'new_users_today' => 0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'paid_orders' => 0,
    'processing_orders' => 0,
    'completed_orders' => 0,
    'cancelled_orders' => 0,
    'refunded_orders' => 0,
    'total_products' => 0,
    'active_products' => 0,
    'total_tutorials' => 0,
    'total_faqs' => 0,
    'total_revenue' => 0,
    'today_revenue' => 0,
    'monthly_revenue' => 0
];

$user_growth = [];
$revenue_trend = [];
$popular_products = [];
$db_error = false;

// 尝试连接数据库
try {
    $config = require '../server/db_config.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password']
    );
    
    // 获取用户统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['active_users'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_users_today'] = $stmt->fetchColumn();
    
    // 获取订单统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $stats['total_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'paid'");
    $stats['paid_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
    $stats['processing_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
    $stats['completed_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'cancelled'");
    $stats['cancelled_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'refunded'");
    $stats['refunded_orders'] = $stmt->fetchColumn();
    
    // 获取商品统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM shop_products");
    $stats['total_products'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM shop_products WHERE status = 1");
    $stats['active_products'] = $stmt->fetchColumn();
    
    // 获取教程和FAQ统计
    $stmt = $pdo->query("SELECT COUNT(*) FROM shop_tutorials");
    $stats['total_tutorials'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM shop_faqs");
    $stats['total_faqs'] = $stmt->fetchColumn();
    
    // 获取收入统计（包括所有已支付订单：paid、processing、completed）
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'processing', 'completed')");
    $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'processing', 'completed') AND DATE(created_at) = CURDATE()");
    $stats['today_revenue'] = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'processing', 'completed') AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
    
    // 获取用户增长趋势（最近7天）
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as users 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $user_growth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 获取收入趋势（最近7天）
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(total_amount) as revenue 
        FROM orders 
        WHERE status IN ('paid', 'processing', 'completed') AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $revenue_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 获取热门商品
    $stmt = $pdo->query("
        SELECT p.title, p.sales, p.price 
        FROM shop_products p 
        WHERE p.status = 1 
        ORDER BY p.sales DESC 
        LIMIT 5
    ");
    $popular_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    echo "<!-- 数据库错误: " . $e->getMessage() . " -->";
    $db_error = true;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仪表盘 - 王者荣耀查战力后台管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content-wrapper {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .main-content {
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stats-card:hover {
            transform: translateY(-3px);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1a1a2e;
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table th {
            border: none;
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .error-alert {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content-wrapper">
        <div class="main-content">
            <!-- 页面标题 -->
            <div class="page-header">
                <h1>
                    <i class="bi bi-speedometer2 me-3"></i>仪表盘
                </h1>
                <p>系统概览和关键指标监控</p>
            </div>

            <?php if ($db_error): ?>
                <!-- 数据库连接错误提示 -->
                <div class="alert error-alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>数据库连接失败</strong> - 无法获取实时数据。请检查数据库配置和连接状态。
                </div>
            <?php else: ?>
                <!-- 数据库连接成功提示 -->
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>数据库连接成功</strong> - 正在显示实时数据。
                </div>
            <?php endif; ?>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stats-number text-primary"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stats-label">总用户数</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="stats-number text-success"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stats-label">总订单数</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                            <i class="bi bi-box"></i>
                        </div>
                        <div class="stats-number text-warning"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stats-label">商品总数</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stats-number text-danger">¥<?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stats-label">总收入</div>
                    </div>
                </div>
            </div>

            <!-- 详细统计 -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-info"><?php echo number_format($stats['active_users']); ?></div>
                        <div class="stats-label">活跃用户（7天）</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-success"><?php echo number_format($stats['new_users_today']); ?></div>
                        <div class="stats-label">今日新用户</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-warning"><?php echo number_format($stats['pending_orders']); ?></div>
                        <div class="stats-label">待处理订单</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-primary">¥<?php echo number_format($stats['today_revenue'], 2); ?></div>
                        <div class="stats-label">今日收入</div>
                    </div>
                </div>
            </div>

            <?php if (!$db_error): ?>
                <!-- 图表区域 -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-graph-up me-2"></i>用户增长趋势
                            </div>
                            <?php if (!empty($user_growth)): ?>
                                <div style="height: 200px; max-height: 200px;">
                                    <canvas id="userGrowthChart" style="max-height: 200px;"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-graph-up"></i>
                                    <p>暂无用户增长数据</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-pie-chart me-2"></i>订单状态分布
                            </div>
                            <?php if ($stats['total_orders'] > 0): ?>
                                <div style="height: 200px; max-height: 200px;">
                                    <canvas id="orderStatusChart" style="max-height: 200px;"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-pie-chart"></i>
                                    <p>暂无订单数据</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 收入趋势和热门商品 -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-3">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-currency-dollar me-2"></i>收入趋势
                            </div>
                            <?php if (!empty($revenue_trend)): ?>
                                <div style="height: 200px; max-height: 200px;">
                                    <canvas id="revenueChart" style="max-height: 200px;"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-currency-dollar"></i>
                                    <p>暂无收入数据</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="chart-card">
                            <div class="chart-title">
                                <i class="bi bi-fire me-2"></i>热门商品
                            </div>
                            <?php if (!empty($popular_products)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($popular_products as $index => $product): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($product['title']); ?></div>
                                            <small class="text-muted">销量: <?php echo $product['sales']; ?></small>
                                        </div>
                                        <span class="badge bg-primary">¥<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-fire"></i>
                                    <p>暂无商品数据</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (!$db_error): ?>
        // 用户增长趋势图表
        <?php if (!empty($user_growth)): ?>
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthData = <?php echo json_encode(array_column($user_growth, 'users')); ?>;
        const maxUsers = Math.max(...userGrowthData, 0);
        // 计算Y轴最大值：向上取整到10的倍数，只留最小空间
        const yAxisMax = Math.ceil((maxUsers + 1) / 10) * 10;
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($user_growth, 'date')); ?>,
                datasets: [{
                    label: '新增用户',
                    data: userGrowthData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: yAxisMax,
                        ticks: {
                            stepSize: 10,
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // 订单状态分布图表
        <?php if ($stats['total_orders'] > 0): ?>
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['待支付', '已支付', '处理中', '已完成', '已取消', '已退款'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending_orders']; ?>,
                        <?php echo $stats['paid_orders']; ?>,
                        <?php echo $stats['processing_orders']; ?>,
                        <?php echo $stats['completed_orders']; ?>,
                        <?php echo $stats['cancelled_orders']; ?>,
                        <?php echo $stats['refunded_orders']; ?>
                    ],
                    backgroundColor: [
                        '#94a3b8', // 待支付 - 灰色
                        '#3b82f6', // 已支付 - 蓝色
                        '#f59e0b', // 处理中 - 橙色
                        '#10b981', // 已完成 - 绿色
                        '#ef4444', // 已取消 - 红色
                        '#8b5cf6'  // 已退款 - 紫色
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>

        // 收入趋势图表
        <?php if (!empty($revenue_trend)): ?>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($revenue_trend, 'date')); ?>,
                datasets: [{
                    label: '收入',
                    data: <?php echo json_encode(array_column($revenue_trend, 'revenue')); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10b981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
