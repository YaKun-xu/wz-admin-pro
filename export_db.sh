#!/bin/bash

# 数据库配置
DB_NAME="zhanli"
DB_USER="root"
OUTPUT_FILE="sql_export_$(date +%Y%m%d_%H%M%S).sql"

echo "=========================================="
echo "开始导出数据库"
echo "=========================================="

# 检查 mysqldump 是否可用
if ! command -v mysqldump &> /dev/null; then
    echo "错误: 未找到 mysqldump 命令"
    echo "请确保已安装 MySQL 并添加到 PATH"
    exit 1
fi

# 提示输入 MySQL root 密码
echo ""
echo "请输入 MySQL root 密码（如果 root 没有密码，直接按回车）:"
read -s MYSQL_PASSWORD

# 导出数据库
echo ""
echo "正在导出数据库 $DB_NAME 到 $OUTPUT_FILE..."
if [ -z "$MYSQL_PASSWORD" ]; then
    mysqldump -u $DB_USER \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --default-character-set=utf8mb4 \
        $DB_NAME > "$OUTPUT_FILE" 2>&1
else
    mysqldump -u $DB_USER -p"$MYSQL_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --default-character-set=utf8mb4 \
        $DB_NAME > "$OUTPUT_FILE" 2>&1
fi

if [ $? -eq 0 ]; then
    # 检查文件大小
    FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "✓ 数据库导出成功"
    echo ""
    echo "=========================================="
    echo "导出完成！"
    echo "文件名: $OUTPUT_FILE"
    echo "文件大小: $FILE_SIZE"
    echo "=========================================="
else
    echo "✗ 数据库导出失败，请检查错误信息"
    exit 1
fi

