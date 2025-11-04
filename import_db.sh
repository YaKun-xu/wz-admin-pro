#!/bin/bash

# 数据库名称
DB_NAME="zhanli"
SQL_FILE="sql.sql"

echo "=========================================="
echo "开始创建数据库并导入数据"
echo "=========================================="

# 检查 MySQL 是否可用
if ! command -v mysql &> /dev/null; then
    echo "错误: 未找到 mysql 命令"
    echo "请确保已安装 MySQL 并添加到 PATH"
    exit 1
fi

# 提示输入 MySQL root 密码
echo ""
echo "请输入 MySQL root 密码（如果 root 没有密码，直接按回车）:"
read -s MYSQL_PASSWORD

# 创建数据库
echo ""
echo "正在创建数据库 $DB_NAME..."
if [ -z "$MYSQL_PASSWORD" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
else
    mysql -u root -p"$MYSQL_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1
fi

if [ $? -eq 0 ]; then
    echo "✓ 数据库 $DB_NAME 创建成功"
else
    echo "✗ 数据库创建失败，请检查 MySQL 连接和权限"
    exit 1
fi

# 导入 SQL 文件
echo ""
echo "正在导入 SQL 文件..."
if [ -z "$MYSQL_PASSWORD" ]; then
    mysql -u root $DB_NAME < "$SQL_FILE" 2>&1
else
    mysql -u root -p"$MYSQL_PASSWORD" $DB_NAME < "$SQL_FILE" 2>&1
fi

if [ $? -eq 0 ]; then
    echo "✓ SQL 文件导入成功"
    echo ""
    echo "=========================================="
    echo "数据库 $DB_NAME 创建并导入完成！"
    echo "=========================================="
else
    echo "✗ SQL 文件导入失败，请检查错误信息"
    exit 1
fi

