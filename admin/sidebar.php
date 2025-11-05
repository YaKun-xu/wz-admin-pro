<?php
// 获取当前页面文件名
$current_page = basename($_SERVER['PHP_SELF']);

// 获取管理员头像
$admin_avatar = $_SESSION['admin_avatar'] ?? '';
?>
<style>
/* 侧边栏样式 */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-content {
    padding: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}

.sidebar-header .logo {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.sidebar-header .logo i {
    font-size: 2rem;
    margin-right: 0.5rem;
}

.sidebar-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
}

.sidebar .nav {
    flex: 1;
    padding: 1rem 0;
}

.sidebar .nav-item {
    margin: 0.2rem 1rem;
}

.sidebar .nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 500;
}

.sidebar .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar .nav-link i {
    font-size: 1.1rem;
    margin-right: 0.75rem;
    width: 20px;
    text-align: center;
}

.sidebar .nav-link span {
    flex: 1;
}

.nav-indicator {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    opacity: 0;
    transition: all 0.3s ease;
}

.sidebar .nav-link.active .nav-indicator {
    opacity: 1;
    background: white;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: auto;
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    overflow: hidden;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar i {
    font-size: 1.5rem;
    color: white;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-footer .btn {
    width: 100%;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    padding: 0.5rem;
}

/* 移动端菜单切换按钮 */
.sidebar-toggle {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 0.75rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.sidebar-toggle i {
    font-size: 1.2rem;
}

/* 主内容区域 */
.main-content-wrapper {
    margin-left: 280px;
    min-height: 100vh;
    transition: all 0.3s ease;
}

/* 移动端响应式 */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .main-content-wrapper {
        margin-left: 0;
    }
    
    .main-content-wrapper.sidebar-open {
        margin-left: 0;
    }
}

/* 滚动条样式 */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>

<nav class="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-shield-lock-fill"></i>
                <span>后台管理</span>
            </div>
            <div class="sidebar-subtitle">王者荣耀查战力</div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>仪表盘</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>商品管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                    <i class="bi bi-folder"></i>
                    <span>分类管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="cardkeys.php" class="nav-link <?php echo $current_page === 'cardkeys.php' ? 'active' : ''; ?>">
                    <i class="bi bi-key"></i>
                    <span>卡密管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>订单管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>用户管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="faqs.php" class="nav-link <?php echo $current_page === 'faqs.php' ? 'active' : ''; ?>">
                    <i class="bi bi-question-circle"></i>
                    <span>FAQ管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="tutorials.php" class="nav-link <?php echo $current_page === 'tutorials.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book"></i>
                    <span>教程管理</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="miniprogram.php" class="nav-link <?php echo $current_page === 'miniprogram.php' ? 'active' : ''; ?>">
                    <i class="bi bi-phone"></i>
                    <span>小程序配置</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="config.php" class="nav-link <?php echo $current_page === 'config.php' ? 'active' : ''; ?>">
                    <i class="bi bi-gear"></i>
                    <span>系统配置</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="website.php" class="nav-link <?php echo $current_page === 'website.php' ? 'active' : ''; ?>">
                    <i class="bi bi-globe"></i>
                    <span>网站信息</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($admin_avatar)): ?>
                        <img src="<?php echo htmlspecialchars($admin_avatar); ?>" 
                             alt="头像" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <i class="bi bi-person-circle" style="display: none;"></i>
                    <?php else: ?>
                        <i class="bi bi-person-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理员'); ?></div>
                    <div class="user-role">系统管理员</div>
                </div>
            </div>
            
            <div class="d-flex flex-column gap-2">
                <a href="profile.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-person me-1"></i>个人资料
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>退出登录
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- 移动端菜单切换按钮 -->
<button class="sidebar-toggle d-lg-none" id="sidebarToggle">
    <i class="bi bi-list"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content-wrapper');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
        });
    }
    
    // 点击侧边栏外部关闭菜单
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
                mainContent.classList.remove('sidebar-open');
            }
        }
    });
    
    // 窗口大小改变时的处理
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
            mainContent.classList.remove('sidebar-open');
        }
    });
});
</script>
