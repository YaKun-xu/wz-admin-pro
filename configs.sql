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

 Date: 11/12/2025 10:36:56
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for configs
-- ----------------------------
DROP TABLE IF EXISTS `configs`;
CREATE TABLE `configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(50) NOT NULL COMMENT '页面名称',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `parent_key` varchar(100) DEFAULT NULL COMMENT '父级键',
  `notes` varchar(255) DEFAULT NULL COMMENT '注释',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config` (`page_name`,`config_key`,`parent_key`)
) ENGINE=InnoDB AUTO_INCREMENT=853 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of configs
-- ----------------------------
BEGIN;
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (11, 'tongyong', 'workTime', '9:00-23:01', 'serviceInfo', '弹窗通用上', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (12, 'tongyong', 'remark', '修改战区', 'serviceInfo', '弹窗通用下', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (13, 'index', 'kefu', 'false', NULL, '客服开关 ', '2025-11-12 10:58:16', '2025-12-11 06:48:41');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (14, 'index', 'jump', 'false', NULL, '帮改开关', '2025-12-09 10:22:48', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (20, 'index', 'noticeContent', '🔥英雄战力并非实时数据，确定好地区后游戏排行榜选择要改的地区确定下在修改！', NULL, '公告', '2025-07-29 21:24:05', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (21, 'index', 'qrcodeImage', 'https://picture.zhaixingge.net/v/690e05d7daaa6.png', NULL, '微信二维码', '2025-07-20 01:16:54', '2025-12-11 06:45:11');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (22, 'index', 'appId', 'wx4d1258677af59f5c', NULL, '帮改按钮小程序ID', '2025-07-20 01:16:54', '2025-12-11 06:45:11');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (23, 'index', 'path', 'lib/item/dist/pages/index/index?scene=7500647739', NULL, '帮改按钮小程序路径', '2025-07-20 01:16:54', '2025-12-11 06:45:11');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (26, 'index', 'rewardedVideoAd', 'adunit-0ddf423010bfae78', NULL, '激励视频', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (27, 'index', 'videoAdunit', 'adunit-4363f71e82733c84', NULL, '视频广告', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (28, 'index', 'wxAdEnabled', 'false', '', '微信激励广告开关', '2025-07-20 01:16:54', '2025-12-11 06:51:12');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (29, 'index', 'dyAdEnabled', 'false', 'adConfig', NULL, '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (201, 'zhanli', 'weidianId', 'wx4d1258677af59f5c', 'miniProgram', '小程序ID', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (202, 'zhanli', 'weidianUrl', 'lib/item/dist/pages/index/index?scene=7500647739', 'miniProgram', '小程序路径', '2025-07-20 01:16:54', '2025-12-11 07:08:56');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (212, 'zhanli', 'weburl', 'https://shop.lll666.cn/', NULL, NULL, '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (222, 'zhanli', 'switch', '3', NULL, '联系客服按钮', '2025-07-20 01:16:54', '2025-12-11 10:34:02');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (223, 'zhanli', 'qrcodeImage', 'https://picture.zhaixingge.net/v/690e05d7daaa6.png', NULL, '客服二维码', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (224, 'zhanli', 'ddappId', 'wx4d1258677af59f5c', NULL, '客服小程序跳转appid', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (225, 'zhanli', 'ddpath', 'lib/item/dist/pages/index/index?scene=6139540551', NULL, '客服小程序跳转路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (226, 'zhanli', 'qywxid', ' ww9d1997bd77105313', NULL, '企业微信appid', '2025-12-08 19:52:25', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (227, 'zhanli', 'qykfurl', 'https://work.weixin.qq.com/kfid/kfc54a82b285684b21b', NULL, '企业客服链接', '2025-12-08 19:53:12', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (232, 'zhanli', 'bottomAdId', 'adunit-39b9529713e90550', 'adInfo', '底部广告', '2025-07-20 01:16:54', '2025-12-11 06:38:12');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (233, 'zhanli', 'interstitialAdUnitId', '', 'adInfo', '插屏广告（弹窗广告）', '2025-07-20 01:16:54', '2025-12-11 06:38:15');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (353, 'order', 'videoTutorialUrl', 'https://cdn.yixinzy.cn/daida/jc.mp4', NULL, '使用教程', '2025-07-29 21:05:23', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (501, 'my', 'switch', '1', NULL, '客服模式', '2025-11-12 11:10:45', '2025-12-11 07:54:24');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (510, 'my', 'qrcodeImage', 'https://picture.zhaixingge.net/v/690e05d7daaa6.png', NULL, '客服二维码', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (521, 'my', 'qywxid', ' ww9d1997bd77105313', NULL, '企业微信appid', '2025-12-08 19:52:25', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (522, 'my', 'qykfurl', 'https://work.weixin.qq.com/kfid/kfc54a82b285684b21b', NULL, '企业客服链接', '2025-12-08 19:53:12', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (528, 'my', 'gzhewm', 'https://picture.zhaixingge.net/v/1/690df8970c400.jpg', NULL, '公众号', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (529, 'my', 'avatar', 'https://cdn.yixinzy.cn/json/wechat.png', 'userInfo', '用户默认展示头像', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (530, 'my', 'nickname', '微信用户', 'userInfo', '用户默认展示昵称', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (531, 'my', 'userId', '88888', 'userInfo', '用户默认展示ID', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (532, 'my', 'appId', 'wx4d1258677af59f5c', 'config.miniProgram', '小程序appid', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (533, 'my', 'orderPath', 'lib/orders/dist/pages/index/index?type=0', 'config.miniProgram', '订单列表路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (534, 'my', 'buyPath', 'lib/item/dist/pages/index/index?scene=6139540551', 'config.miniProgram', '商品列表路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (535, 'my', 'ddappId', 'wxb85a59af600a989b', 'config.miniProgram', '备用程序appid', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (536, 'my', 'ddpath', 'pages/index/index', 'config.miniProgram', '备用程序跳转路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (537, 'my', 'orderUrl', 'https://shop.lll666.cn/#/user/order/index', 'config.h5', 'h5订单列表-可不填', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (538, 'my', 'buyUrl', 'https://shop.lll666.cn/#/category/buy?gid=1212', 'config.h5', 'h5商品列表-可不填', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (539, 'my', 'path', '/pages/about/index', 'config.about', '关于我们路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (599, 'my', 'nativeAdunit', '', NULL, '广告', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (640, 'about', 'wechat', '1888888', 'contactInfo', '客服微信', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (641, 'about', 'publicAccount', '星阁工作室', 'contactInfo', '公众号', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (642, 'about', 'templateId', '星阁', 'adInfo', NULL, '2025-07-20 01:16:54', '2025-12-11 07:09:23');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (743, 'settings', 'avatar', 'https://cdn.yixinzy.cn/json/wechat.png', 'userInfo', NULL, '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (744, 'settings', 'nickname', '微信用户', 'userInfo', '默认用户名称', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (745, 'settings', 'unitId', '', 'adInfo', NULL, '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (846, 'rename', 'notice', ' 改名服务已经升级，请前往改名小程序。。', '', '改名页面公告', '2025-11-12 18:17:40', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (847, 'rename', 'appId', 'wx94e041496872a521', 'shop', '重复名小程序appId', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (848, 'rename', 'path', 'pages/index/index?scene=1', 'shop', '重复名小程序路径', '2025-07-20 01:16:54', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (849, 'rename', 'text1', '【安卓】【鸿蒙】【苹果手机】均完美显示', 'text.0', '注意事项第一行', '2025-11-12 18:20:51', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (850, 'rename', 'text2', '【安卓】【鸿蒙】【苹果手机】均完美显示', 'text.1', '注意事项第二行', '2025-11-12 18:20:51', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (851, 'rename', 'text3', '【王者荣耀】支持生成6字重复名', 'text.2', '注意事项第三行', '2025-11-12 18:20:51', '2025-12-11 06:29:07');
INSERT INTO `configs` (`id`, `page_name`, `config_key`, `config_value`, `parent_key`, `notes`, `created_at`, `updated_at`) VALUES (852, 'rename', 'text4', '【王者荣耀】支持生成6字重复名', 'text.3', '注意事项第四行', '2025-11-12 18:13:03', '2025-12-11 06:29:07');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
