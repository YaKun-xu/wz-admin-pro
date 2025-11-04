// 后台管理系统JavaScript

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 初始化弹出框
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // 添加页面加载动画
    document.body.classList.add('fade-in');
});

// 通用函数
const AdminUtils = {
    // 显示加载状态
    showLoading: function(element) {
        element.innerHTML = '<span class="loading"></span> 处理中...';
        element.disabled = true;
    },

    // 隐藏加载状态
    hideLoading: function(element, originalText) {
        element.innerHTML = originalText;
        element.disabled = false;
    },

    // 显示成功消息
    showSuccess: function(message) {
        this.showAlert(message, 'success');
    },

    // 显示错误消息
    showError: function(message) {
        this.showAlert(message, 'danger');
    },

    // 显示警告消息
    showWarning: function(message) {
        this.showAlert(message, 'warning');
    },

    // 显示信息消息
    showInfo: function(message) {
        this.showAlert(message, 'info');
    },

    // 显示警告框
    showAlert: function(message, type) {
        const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // 自动隐藏
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                const alert = new bootstrap.Alert(alertElement);
                alert.close();
            }
        }, 5000);
    },

    // 创建警告容器
    createAlertContainer: function() {
        const container = document.createElement('div');
        container.id = 'alert-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
        return container;
    },

    // 获取警告图标
    getAlertIcon: function(type) {
        const icons = {
            'success': 'check-circle-fill',
            'danger': 'exclamation-triangle-fill',
            'warning': 'exclamation-triangle-fill',
            'info': 'info-circle-fill'
        };
        return icons[type] || 'info-circle-fill';
    },

    // 确认对话框
    confirm: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // AJAX请求
    ajax: function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        return fetch(url, finalOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                this.showError('请求失败: ' + error.message);
                throw error;
            });
    },

    // 格式化日期
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // 格式化金额
    formatMoney: function(amount) {
        return '¥' + parseFloat(amount).toFixed(2);
    },

    // 复制到剪贴板
    copyToClipboard: function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                this.showSuccess('已复制到剪贴板');
            });
        } else {
            // 降级处理
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            this.showSuccess('已复制到剪贴板');
        }
    }
};

// 表格相关功能
const TableManager = {
    // 初始化表格
    init: function() {
        this.addSortHandlers();
        this.addFilterHandlers();
    },

    // 添加排序处理
    addSortHandlers: function() {
        const sortableHeaders = document.querySelectorAll('th[data-sort]');
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });
    },

    // 表格排序
    sortTable: function(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const sortOrder = header.dataset.sort === 'asc' ? 'desc' : 'asc';
        
        // 更新排序状态
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.dataset.sort = '';
            th.innerHTML = th.innerHTML.replace(' ↑', '').replace(' ↓', '');
        });
        header.dataset.sort = sortOrder;
        header.innerHTML += sortOrder === 'asc' ? ' ↑' : ' ↓';

        // 排序行
        rows.sort((a, b) => {
            const aValue = a.children[columnIndex].textContent.trim();
            const bValue = b.children[columnIndex].textContent.trim();
            
            if (sortOrder === 'asc') {
                return aValue.localeCompare(bValue, 'zh-CN');
            } else {
                return bValue.localeCompare(aValue, 'zh-CN');
            }
        });

        // 重新插入排序后的行
        rows.forEach(row => tbody.appendChild(row));
    },

    // 添加筛选处理
    addFilterHandlers: function() {
        const filterInputs = document.querySelectorAll('input[data-filter]');
        filterInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.filterTable(input);
            });
        });
    },

    // 表格筛选
    filterTable: function(input) {
        const table = input.closest('.table-container').querySelector('table');
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        const filterValue = input.value.toLowerCase();
        const columnIndex = parseInt(input.dataset.filter);

        rows.forEach(row => {
            const cellValue = row.children[columnIndex].textContent.toLowerCase();
            if (cellValue.includes(filterValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
};

// 模态框管理
const ModalManager = {
    // 显示模态框
    show: function(modalId) {
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();
    },

    // 隐藏模态框
    hide: function(modalId) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if (modal) {
            modal.hide();
        }
    },

    // 重置表单
    resetForm: function(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    }
};

// 表单验证
const FormValidator = {
    // 验证规则
    rules: {
        required: function(value) {
            return value.trim() !== '';
        },
        email: function(value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        },
        phone: function(value) {
            const phoneRegex = /^1[3-9]\d{9}$/;
            return phoneRegex.test(value);
        },
        number: function(value) {
            return !isNaN(value) && value !== '';
        },
        minLength: function(value, min) {
            return value.length >= min;
        },
        maxLength: function(value, max) {
            return value.length <= max;
        }
    },

    // 验证表单
    validate: function(form) {
        const errors = [];
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            const value = input.value;
            const rules = input.dataset.validate ? input.dataset.validate.split('|') : [];
            
            rules.forEach(rule => {
                const [ruleName, ...params] = rule.split(':');
                if (this.rules[ruleName] && !this.rules[ruleName](value, ...params)) {
                    errors.push({
                        field: input.name,
                        message: this.getErrorMessage(ruleName, params)
                    });
                }
            });
        });
        
        return errors;
    },

    // 获取错误消息
    getErrorMessage: function(ruleName, params) {
        const messages = {
            required: '此字段为必填项',
            email: '请输入有效的邮箱地址',
            phone: '请输入有效的手机号码',
            number: '请输入有效的数字',
            minLength: `最少需要${params[0]}个字符`,
            maxLength: `最多允许${params[0]}个字符`
        };
        return messages[ruleName] || '验证失败';
    }
};

// 导出到全局
window.AdminUtils = AdminUtils;
window.TableManager = TableManager;
window.ModalManager = ModalManager;
window.FormValidator = FormValidator;
