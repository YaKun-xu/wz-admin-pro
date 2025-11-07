<?php
// 获取当前页面文件名
$current_page = basename($_SERVER['PHP_SELF']);

// 获取管理员头像
$admin_avatar = $_SESSION['admin_avatar'] ?? '';
?>
<style>
/* 侧边栏样式 */
.sidebar {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-content {
    padding: 0;
    min-height: 100%;
    display: flex;
    flex-direction: column;
}

.sidebar .nav {
    flex: 1;
    padding: 1.5rem 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar .nav-item {
    margin: 0.5rem 1rem;
}

.sidebar .nav-link {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 500;
    font-size: 0.9rem;
}

.sidebar .nav-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.sidebar .nav-link[style*="cursor: default"]:hover {
    transform: none;
}

.sidebar .nav-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar .nav-link i {
    font-size: 0.95rem;
    margin-right: 0.6rem;
    width: 18px;
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

/* 二级菜单样式 */
.submenu {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0 0;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 0.6rem 0;
}

.submenu li {
    margin: 0.2rem 0;
}

.submenu-link {
    display: flex;
    align-items: center;
    padding: 0.6rem 1rem 0.6rem 2.8rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    border-radius: 0;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    position: relative;
}

.submenu-link:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
    padding-left: 3.2rem;
}

.submenu-link.active {
    color: white;
    background: rgba(255, 255, 255, 0.15);
    font-weight: 600;
}

.submenu-link.active::before {
    content: '';
    position: absolute;
    left: 0.8rem;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 4px;
    background: white;
    border-radius: 50%;
}

.submenu-link i {
    font-size: 0.8rem;
    margin-right: 0.6rem;
    width: 14px;
    text-align: center;
}

.sidebar-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    margin-top: auto;
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.6rem;
    overflow: hidden;
    flex-shrink: 0;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar i {
    font-size: 1.2rem;
    color: white;
}

.user-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.user-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.15rem;
}

.user-name {
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
    min-width: 0;
}

.logout-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    font-size: 0.85rem;
    width: 24px;
    height: 24px;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.2s ease;
    margin-left: 0.5rem;
    flex-shrink: 0;
    background: rgba(239, 68, 68, 0.1);
    line-height: 1;
}

.logout-btn:hover {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(255, 255, 255, 0.8);
}

.logout-btn i {
    display: inline-block;
    line-height: 1;
    vertical-align: middle;
}

.user-role {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-footer .btn {
    width: 100%;
    margin-bottom: 0.4rem;
    font-size: 0.75rem;
    padding: 0.35rem;
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
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>仪表盘</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>
            
            <!-- 商品管理 -->
            <li class="nav-item">
                <ul class="submenu">
                    <li>
                        <a href="products.php" class="submenu-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                            <i class="bi bi-list-ul"></i>
                            <span>商品列表</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="submenu-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                            <i class="bi bi-folder"></i>
                            <span>商品分类</span>
                        </a>
                    </li>
                    <li>
                        <a href="cardkeys.php" class="submenu-link <?php echo $current_page === 'cardkeys.php' ? 'active' : ''; ?>">
                            <i class="bi bi-key"></i>
                            <span>卡密管理</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- 用户系统 -->
            <li class="nav-item">
                <ul class="submenu">
                    <li>
                        <a href="users.php" class="submenu-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                            <i class="bi bi-person-lines-fill"></i>
                            <span>用户列表</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php" class="submenu-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                            <i class="bi bi-receipt"></i>
                            <span>订单列表</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- 教程管理 -->
            <li class="nav-item">
                <ul class="submenu">
                    <li>
                        <a href="tutorials.php" class="submenu-link <?php echo $current_page === 'tutorials.php' ? 'active' : ''; ?>">
                            <i class="bi bi-file-text"></i>
                            <span>图文教程</span>
                        </a>
                    </li>
                    <li>
                        <a href="video_tutorials.php" class="submenu-link <?php echo $current_page === 'video_tutorials.php' ? 'active' : ''; ?>">
                            <i class="bi bi-camera-video"></i>
                            <span>视频教程</span>
                        </a>
                    </li>
                    <li>
                        <a href="modify_steps.php" class="submenu-link <?php echo $current_page === 'modify_steps.php' ? 'active' : ''; ?>">
                            <i class="bi bi-list-ol"></i>
                            <span>修改步骤教程</span>
                        </a>
                    </li>
                    <li>
                        <a href="faqs.php" class="submenu-link <?php echo $current_page === 'faqs.php' ? 'active' : ''; ?>">
                            <i class="bi bi-question-circle"></i>
                            <span>问答教程</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- 小程序配置 -->
            <li class="nav-item">
                <ul class="submenu">
                    <li>
                        <a href="miniprogram.php" class="submenu-link <?php echo $current_page === 'miniprogram.php' ? 'active' : ''; ?>">
                            <i class="bi bi-app"></i>
                            <span>小程序列表</span>
                        </a>
                    </li>
                    <li>
                        <a href="config.php" class="submenu-link <?php echo $current_page === 'config.php' ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i>
                            <span>系统配置</span>
                        </a>
                    </li>
                </ul>
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
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <div class="user-name-row">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? '管理员'); ?></div>
                        <a href="logout.php" class="logout-btn" title="退出登录">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </div>
                    <div class="user-role">系统管理员</div>
                </div>
            </div>
            
            <div class="d-flex flex-column gap-2">
                <a href="website.php" class="btn btn-outline-light btn-sm <?php echo $current_page === 'website.php' ? 'active' : ''; ?>">
                    <i class="bi bi-globe me-1"></i>网站信息
                </a>
                <a href="profile.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-person me-1"></i>个人资料
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
// 等待Bootstrap JS加载完成
(function() {
    function initSidebar() {
        // 检查Bootstrap是否已加载
        if (typeof bootstrap === 'undefined') {
            setTimeout(initSidebar, 100);
            return;
        }
        
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
        
        // 侧边栏功能已简化，移除折叠相关代码
    }
    
    // DOM加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
</script>
