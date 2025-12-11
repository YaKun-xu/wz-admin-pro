/*
 Navicat MySQL Dump SQL

 Source Server         : 1panel
 Source Server Type    : MySQL
 Source Server Version : 50744 (5.7.44)
 Source Host           : localhost:3306
 Source Schema         : zhanli

 Target Server Type    : MySQL
 Target Server Version : 50744 (5.7.44)
 File Encoding         : 65001

 Date: 11/12/2025 13:54:23
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for shop_products
-- ----------------------------
DROP TABLE IF EXISTS `shop_products`;
CREATE TABLE `shop_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `product_type` tinyint(4) DEFAULT '1' COMMENT '商品类型：1-普通商品，2-卡密商品',
  `title` varchar(200) NOT NULL COMMENT '商品标题',
  `description` text COMMENT '商品描述',
  `price` decimal(10,2) NOT NULL COMMENT '现价',
  `cover_image` varchar(500) DEFAULT NULL COMMENT '封面图片',
  `sales` int(11) DEFAULT '0' COMMENT '总销量',
  `sales_false` int(11) DEFAULT NULL COMMENT '假销量',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-上架，0-下架',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_sort` (`sort_order`),
  KEY `idx_product_type` (`product_type`),
  CONSTRAINT `shop_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `shop_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of shop_products
-- ----------------------------
BEGIN;
INSERT INTO `shop_products` (`id`, `category_id`, `product_type`, `title`, `description`, `price`, `cover_image`, `sales`, `sales_true`, `sales_false`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (1, 2, 2, '王者战区定位', '王者战区定位，每周一凌晨1点30分-24点才能修改，QQ区玩家周二到周日可以提前扫码登录（可以正常游戏），微信区玩家需在周一当天扫码，微信区如需提前扫码请联系客服', 5.00, 'https://picture.zhaixingge.net/v/69106a55da5ef.jpg', 2, NULL, NULL, 0, 1, '2025-11-07 19:22:17', '2025-11-29 01:58:47');
INSERT INTO `shop_products` (`id`, `category_id`, `product_type`, `title`, `description`, `price`, `cover_image`, `sales`, `sales_true`, `sales_false`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES (2, 1, 1, '查询', '', 0.01, '', 0, NULL, NULL, 0, 0, '2025-11-21 02:23:38', '2025-11-29 01:57:32');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
