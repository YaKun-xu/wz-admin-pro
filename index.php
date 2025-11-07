<?php
session_start();

// 检查安装状态
require_once __DIR__ . '/server/check_install.php';
checkInstallation();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>王者荣耀查战力后台管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* 强制刷新缓存 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
            overflow-x: hidden;
        }

        /* 背景动画效果 */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            max-width: 900px;
            padding: 3rem 2rem;
            position: relative;
        }

        .hero-content::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            right: -50px;
            bottom: -50px;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
            border-radius: 30px;
            z-index: -1;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 50%, #c7d2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.5)); }
            to { filter: drop-shadow(0 0 30px rgba(118, 75, 162, 0.7)); }
        }

        .hero-subtitle {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
            letter-spacing: 1px;
        }

        .hero-description {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn {
            border-radius: 50px;
            font-weight: 600;
            padding: 1.2rem 2.5rem;
            font-size: 1.1rem;
            margin: 0.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.6);
            color: white;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        .features {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 6rem 0;
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(180deg, #1a1a2e 0%, transparent 100%);
        }

        .feature-card {
            text-align: center;
            padding: 3rem 2rem;
            border-radius: 20px;
            background: white;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(102, 126, 234, 0.2);
        }

        .feature-icon {
            font-size: 3.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }

        .feature-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1a1a2e;
        }

        .feature-description {
            color: #64748b;
            line-height: 1.7;
            font-size: 1rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 3rem;
        }

        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .footer-content {
            position: relative;
            z-index: 2;
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.4rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
        }

        /* 滚动动画 */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- 英雄区域 -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <i class="bi bi-trophy-fill me-3"></i>
                王者荣耀查战力
            </h1>
            <p class="hero-subtitle">专业后台管理系统</p>
            <p class="hero-description">
                提供完整的用户管理、商品管理、订单管理、教程管理等功能<br>
                支持数据统计、系统配置、FAQ管理等高级功能
            </p>
            <div>
                <a href="admin/login.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    进入管理系统
                </a>
                <a href="#features" class="btn btn-outline-light">
                    <i class="bi bi-info-circle me-2"></i>
                    了解更多
                </a>
            </div>
        </div>
    </section>

    <!-- 功能特色 -->
    <section id="features" class="features">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2 class="section-title fade-in">系统功能</h2>
                    <p class="section-subtitle fade-in">强大的后台管理功能，让您轻松管理业务</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h3 class="feature-title">用户管理</h3>
                        <p class="feature-description">
                            完整的用户信息管理，支持用户注册、登录、权限控制等功能
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-bag-fill"></i>
                        </div>
                        <h3 class="feature-title">商品管理</h3>
                        <p class="feature-description">
                            商品信息管理，支持商品分类、库存管理、价格设置等功能
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                        <h3 class="feature-title">订单管理</h3>
                        <p class="feature-description">
                            订单处理流程管理，支持订单状态跟踪、支付管理等功能
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <h3 class="feature-title">教程管理</h3>
                        <p class="feature-description">
                            教程内容管理，支持教程分类、内容编辑、发布管理等功能
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-question-circle-fill"></i>
                        </div>
                        <h3 class="feature-title">FAQ管理</h3>
                        <p class="feature-description">
                            常见问题管理，支持问题分类、答案编辑、排序管理等功能
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <h3 class="feature-title">系统配置</h3>
                        <p class="feature-description">
                            系统参数配置，支持网站设置、支付配置、邮件设置等功能
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="footer-content">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-3">
                            <i class="bi bi-trophy-fill me-2"></i>
                            王者荣耀查战力后台管理系统
                        </h5>
                        <p class="mb-0">
                            &copy; <?php echo date('Y'); ?> 王者荣耀查战力后台管理系统. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 滚动动画
        function handleScrollAnimation() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        }

        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', function() {
            handleScrollAnimation();
        });

        // 滚动时执行
        window.addEventListener('scroll', handleScrollAnimation);

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
