# PHP 技术栈说明

## 📋 项目使用的 PHP 技术

### 1. **PHP 版本要求**
- **最低版本**: PHP 7.0+（推荐 PHP 7.4+ 或 PHP 8.0+）
- **项目类型**: 原生 PHP（未使用框架）
- **架构**: MVC 风格的后台管理系统

### 2. **核心 PHP 功能**

#### 数据库操作
- **PDO (PHP Data Objects)**: 用于数据库连接和操作
- **MySQL**: 数据库类型
- **字符集**: UTF-8 (utf8mb4)

```php
// 示例：使用PDO连接MySQL
$pdo = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}",
    $config['username'],
    $config['password']
);
```

#### 会话管理
- **session_start()**: 管理用户登录状态
- **$_SESSION**: 存储管理员登录信息

#### 密码加密
- **password_hash()**: 密码加密（使用 bcrypt 算法）
- **password_verify()**: 密码验证

```php
// 密码加密
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 密码验证
if (password_verify($password, $hashedPassword)) {
    // 登录成功
}
```

#### 数据加密/解密
- **openssl_decrypt()**: 微信小程序数据解密（AES-128-CBC）
- **base64_decode()**: Base64 解码

#### JSON 处理
- **json_encode()**: 将数组转换为 JSON
- **json_decode()**: 将 JSON 转换为数组

#### 其他功能
- **file_get_contents()**: 调用微信 API
- **http_build_query()**: 构建查询字符串
- **date()**: 日期时间处理
- **number_format()**: 数字格式化

### 3. **必需的 PHP 扩展**

| 扩展名 | 用途 | 是否必需 |
|--------|------|---------|
| **PDO** | 数据库操作 | ✅ 必需 |
| **pdo_mysql** | MySQL 数据库支持 | ✅ 必需 |
| **openssl** | 微信数据解密 | ✅ 必需 |
| **json** | JSON 数据处理 | ✅ 必需 |
| **session** | 会话管理 | ✅ 必需 |
| **mbstring** | 多字节字符串处理 | ✅ 推荐 |
| **curl** | HTTP 请求（可选） | ⚠️ 可选 |

### 4. **项目结构**

```
项目目录/
├── admin/              # 管理后台
│   ├── index.php      # 仪表盘
│   ├── login.php      # 登录页面
│   ├── products.php   # 商品管理
│   └── ...
├── server/            # 服务器端API
│   ├── db_config.php  # 数据库配置
│   ├── login_api.php  # 登录API
│   ├── pay_handler.php # 支付处理
│   └── ...
└── assets/           # 静态资源
    ├── css/
    ├── js/
    └── image/
```

### 5. **数据库**

- **数据库类型**: MySQL / MariaDB
- **字符集**: utf8mb4
- **连接方式**: PDO
- **最低版本**: MySQL 5.7+ 或 MariaDB 10.2+

### 6. **前端技术**

- **Bootstrap 5.1.3**: UI 框架
- **Chart.js**: 图表库（用于数据可视化）
- **Bootstrap Icons**: 图标库
- **原生 JavaScript**: 前端交互

### 7. **部署要求**

#### Web 服务器
- **Apache** 或 **Nginx**
- 支持 PHP-FPM 或 mod_php
- URL 重写功能（可选）

#### PHP 配置建议
```ini
; php.ini 推荐配置
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
date.timezone = Asia/Shanghai
```

### 8. **检查 PHP 环境**

创建 `phpinfo.php` 文件检查环境：
```php
<?php
phpinfo();
?>
```

访问 `http://你的域名/phpinfo.php` 查看 PHP 配置信息。

### 9. **常见问题排查**

#### 问题1: 数据库连接失败
- 检查 `server/db_config.php` 配置是否正确
- 确认 MySQL 服务是否运行
- 检查数据库用户权限

#### 问题2: 微信数据解密失败
- 确认 `openssl` 扩展已启用
- 检查 PHP 版本是否支持

#### 问题3: 会话无法保存
- 检查 `session.save_path` 权限
- 确认 `session` 扩展已启用

### 10. **推荐 PHP 版本**

- **开发环境**: PHP 7.4 或 PHP 8.0
- **生产环境**: PHP 8.0+（性能更好，安全性更高）

### 11. **项目特点**

✅ **优点**:
- 原生 PHP，无需学习框架
- 代码结构清晰，易于维护
- 轻量级，性能好
- 适合中小型项目

⚠️ **注意事项**:
- 需要手动处理安全性（XSS、SQL注入等）
- 需要自己组织代码结构
- 没有框架提供的便利功能

### 12. **安全检查清单**

- [ ] 使用 PDO 预处理语句（防止 SQL 注入）✅
- [ ] 使用 `htmlspecialchars()` 防止 XSS ✅
- [ ] 密码使用 `password_hash()` 加密 ✅
- [ ] 敏感信息不在代码中硬编码 ✅
- [ ] 使用 HTTPS 传输敏感数据（生产环境）

