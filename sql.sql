-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: poxiao_qystudio
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `login_name` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'定醇','admin','$2y$12$4n.ksGdCSSjpm6LI7drtJu8cTI9CThf0XYUs1sBjhRvuBqnvhbU0y','','https://picture.qystudio.cn/uploads/20250911/c3b99cc36d322a810c97dc37e724df9e.jpg',1,'2025-09-11 04:51:45','2025-09-12 05:49:40');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configs`
--

DROP TABLE IF EXISTS `configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(50) NOT NULL COMMENT '页面名称',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `parent_key` varchar(100) DEFAULT NULL COMMENT '父级键',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config` (`page_name`,`config_key`,`parent_key`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configs`
--

LOCK TABLES `configs` WRITE;
/*!40000 ALTER TABLE `configs` DISABLE KEYS */;
INSERT INTO `configs` VALUES (1,'tongyong','workTime','9:00-23:01','serviceInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(2,'tongyong','remark','修改战区','serviceInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(3,'index','qrcodeImage','https://picture.qystudio.cn/uploads/20250912/5c845c16d140f3673e54b974fff7b6df.png',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(4,'index','path','lib/item/dist/pages/index/index?scene=7500647739',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(5,'index','appId','wx4d1258677af59f5c',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(6,'index','rewardedVideoAd','adunit-263f988f99e947eb',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(7,'index','videoAdunit','adunit-6ea734a7e6f7341a',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(8,'index','wxAdEnabled','true','adConfig','2025-07-19 17:16:54','2025-09-12 05:59:51'),(9,'index','dyAdEnabled','false','adConfig','2025-07-19 17:16:54','2025-09-12 05:59:51'),(10,'zhanli','weidianUrl','lib/item/dist/pages/index/index?scene=7500647739','miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(11,'zhanli','weidianId','wx4d1258677af59f5c','miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(12,'zhanli','weburl','https://shop.lll666.cn/',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(13,'zhanli','swiperAdId','adunit-e589f2ad21a2bd8c','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(14,'zhanli','bottomAdId','adunit-e589f2ad21a2bd8c','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(15,'zhanli','interstitialAdUnitId','adunit-88b533e9f8c81331','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(16,'zhanli','type','ad','swiperList.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(17,'zhanli','target','miniProgram','swiperList.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(18,'zhanli','appId','wx4d1258677af59f5c','swiperList.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(19,'zhanli','path','lib/item/dist/pages/index/index?scene=7500647739','swiperList.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(20,'zhanli','image','https://cdn.yixinzy.cn/zlico/dy/fds.png','swiperList.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(21,'zhanli','type','ad','swiperList.1','2025-07-19 17:16:54','2025-09-12 05:59:51'),(22,'zhanli','switch','0',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(23,'zhanli','qrcodeImage','https://picture.qystudio.cn/uploads/20250912/5c845c16d140f3673e54b974fff7b6df.png',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(24,'zhanli','ddappId','wx4d1258677af59f5c',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(25,'zhanli','ddpath','lib/item/dist/pages/index/index?scene=7507010073',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(26,'my','nativeAdunit','adunit-6ea734a7e6f7341a',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(27,'my','qrcodeImage','https://picture.qystudio.cn/uploads/20250912/5c845c16d140f3673e54b974fff7b6df.png',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(28,'my','gzhewm','https://picture.qystudio.cn/uploads/20250912/587bc047380919119ae9ebd9972385ea.jpg',NULL,'2025-07-19 17:16:54','2025-09-12 05:59:51'),(29,'my','avatar','https://cdn.yixinzy.cn/json/wechat.png','userInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(30,'my','nickname','微信用户','userInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(31,'my','userId','88888','userInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(32,'my','appId','wx4d1258677af59f5c','config.miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(33,'my','orderPath','lib/orders/dist/pages/index/index?type=0','config.miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(34,'my','buyPath','lib/item/dist/pages/index/index?scene=7500647739','config.miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(35,'my','ddappId','wx4d1258677af59f5c','config.miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(36,'my','ddpath','lib/item/dist/pages/index/index?scene=7507010073','config.miniProgram','2025-07-19 17:16:54','2025-09-12 05:59:51'),(37,'my','orderUrl','https://shop.lll666.cn/#/user/order/index','config.h5','2025-07-19 17:16:54','2025-09-12 05:59:51'),(38,'my','buyUrl','https://shop.lll666.cn/#/category/buy?gid=1212','config.h5','2025-07-19 17:16:54','2025-09-12 05:59:51'),(39,'my','path','/pages/about/index','config.about','2025-07-19 17:16:54','2025-09-12 05:59:51'),(40,'about','wechat','internal6688','contactInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(41,'about','publicAccount','欢游网络','contactInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(42,'about','templateId','adunit-b274e4006f334e91','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(43,'settings','avatar','https://cdn.yixinzy.cn/json/wechat.png','userInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(44,'settings','nickname','微信用户','userInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(45,'settings','unitId','adunit-0083fed78bfdc814','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(46,'rename','rewardedVideoAd','adunit-263f988f99e947eb','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(47,'rename','videoAdunit','adunit-6ea734a7e6f7341a','adInfo','2025-07-19 17:16:54','2025-09-12 05:59:51'),(48,'rename','imageUrl','https://cdn.yixinzy.cn/daida/shoutu.jpg','bannerInfo.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(49,'rename','appId','','bannerInfo.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(50,'rename','path','pages/index/index','bannerInfo.0','2025-07-19 17:16:54','2025-09-12 05:59:51'),(53,'order','videoTutorialUrl','https://cdn.yixinzy.cn/daida/jc.mp4',NULL,'2025-07-29 13:05:23','2025-09-12 05:59:51'),(54,'index','noticeContent','🔥英雄战力并非实时数据，确定好地区后游戏排行榜选择要改的地区确定下在修改！',NULL,'2025-07-29 13:24:05','2025-09-12 05:59:51');
/*!40000 ALTER TABLE `configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `miniprogram_config`
--

DROP TABLE IF EXISTS `miniprogram_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `miniprogram_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_name` varchar(100) NOT NULL COMMENT '小程序名称',
  `app_id` varchar(100) NOT NULL COMMENT '小程序AppID',
  `app_secret` varchar(200) NOT NULL COMMENT '小程序AppSecret',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `login_enabled` tinyint(1) DEFAULT '1' COMMENT '是否开启登录功能',
  `phone_bind_required` tinyint(1) DEFAULT '1' COMMENT '是否需要绑定手机号:1=需要,0=不需要',
  `mch_id` varchar(32) DEFAULT NULL COMMENT '微信支付商户号',
  `pay_key` varchar(32) DEFAULT NULL COMMENT '微信支付API密钥',
  `pay_enabled` tinyint(1) DEFAULT '0' COMMENT '是否启用支付:1-是,0-否',
  `pay_notify_url` varchar(255) DEFAULT NULL COMMENT '支付回调地址',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_id` (`app_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='小程序配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `miniprogram_config`
--

LOCK TABLES `miniprogram_config` WRITE;
/*!40000 ALTER TABLE `miniprogram_config` DISABLE KEYS */;
INSERT INTO `miniprogram_config` VALUES (1,'1','wx3fc3eca17b2b2b71','dd13c89cff2c786d9f0e9f3e89ce4d7a',1,0,0,'1696246701','yixin888yixin888yixin888yixin888',1,'http://127.0.0.1:8080/pay_notify.php','2025-07-24 17:34:27','2025-09-12 05:59:57'),(4,'2','wxd0f61fb2d75d6256','e2ee57e39a445246aa03a6f379aeee6c',0,0,1,'1696246701','yixin888yixin888yixin888yixin888',1,'https://api.wzgzq.cn/server/pay_notify.php','2025-07-26 04:51:25','2025-09-12 05:57:34');
/*!40000 ALTER TABLE `miniprogram_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `app_id` varchar(100) NOT NULL COMMENT '小程序ID',
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `product_title` varchar(200) NOT NULL COMMENT '商品标题',
  `product_price` decimal(10,2) NOT NULL COMMENT '商品价格',
  `total_amount` decimal(10,2) NOT NULL COMMENT '订单总金额',
  `status` enum('pending','paid','processing','completed','cancelled','refunded') DEFAULT 'pending' COMMENT '订单状态',
  `pay_method` varchar(20) DEFAULT 'wxpay' COMMENT '支付方式',
  `transaction_id` varchar(64) DEFAULT NULL COMMENT '微信支付交易号',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `card_key` varchar(255) DEFAULT NULL COMMENT '分配的卡密内容',
  `card_key_id` int(11) DEFAULT NULL COMMENT '关联卡密表ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_order_no` (`order_no`),
  KEY `idx_status` (`status`),
  KEY `product_id` (`product_id`),
  KEY `idx_orders_card_key_id` (`card_key_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`),
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`card_key_id`) REFERENCES `shop_card_keys` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_card_keys`
--

DROP TABLE IF EXISTS `shop_card_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_card_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `card_key` varchar(255) NOT NULL COMMENT '卡密内容',
  `status` tinyint(4) DEFAULT '0' COMMENT '使用状态：0-未使用，1-已使用',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_card_key` (`card_key`),
  KEY `idx_card_product` (`product_id`),
  KEY `idx_card_status` (`status`),
  CONSTRAINT `shop_card_keys_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_card_keys`
--

LOCK TABLES `shop_card_keys` WRITE;
/*!40000 ALTER TABLE `shop_card_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `shop_card_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_categories`
--

DROP TABLE IF EXISTS `shop_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_categories`
--

LOCK TABLES `shop_categories` WRITE;
/*!40000 ALTER TABLE `shop_categories` DISABLE KEYS */;
INSERT INTO `shop_categories` VALUES (1,'全部',0,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(2,'战区修改',1,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(3,'代练服务',2,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(4,'皮肤代充',3,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(5,'其他服务',4,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(6,'战力查询',1,1,'2025-08-01 15:40:35','2025-08-01 15:40:35'),(7,'改名服务',2,1,'2025-08-01 15:40:35','2025-08-01 15:40:35'),(8,'战区修改',3,1,'2025-08-01 15:40:35','2025-08-01 15:40:35'),(9,'账号服务',4,1,'2025-08-01 15:40:35','2025-08-01 15:40:35'),(10,'其他服务',5,1,'2025-08-01 15:40:35','2025-08-01 15:40:35');
/*!40000 ALTER TABLE `shop_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_faqs`
--

DROP TABLE IF EXISTS `shop_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用FAQ）',
  `category` varchar(50) DEFAULT NULL COMMENT 'FAQ分类',
  `question` text NOT NULL COMMENT '问题',
  `answer` text NOT NULL COMMENT '答案',
  `tags` json DEFAULT NULL COMMENT '问题标签',
  `view_count` int(11) DEFAULT '0' COMMENT '查看次数',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_product` (`product_id`),
  CONSTRAINT `shop_faqs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_faqs`
--

LOCK TABLES `shop_faqs` WRITE;
/*!40000 ALTER TABLE `shop_faqs` DISABLE KEYS */;
INSERT INTO `shop_faqs` VALUES (1,1,'服务时间','修改需要多长时间？','<p>测试数据111111111111111111111111111111111111111111111111</p>','[\"时间\", \"速度\"]',0,1,1,'2025-07-26 16:06:08','2025-08-01 07:20:46'),(2,1,'售后服务','修改失败怎么办？','测试数据1','[\"失败\", \"退款\"]',0,2,1,'2025-07-26 16:06:08','2025-07-26 16:49:24'),(3,1,'服务范围','可以修改到任意地区吗？','测试数据2','[\"地区\", \"限制\"]',0,3,1,'2025-07-26 16:06:08','2025-07-26 16:49:26'),(4,1,'生效时间','修改后多久生效？','测试数据3','[\"生效\", \"验证\"]',0,4,1,'2025-07-26 16:06:08','2025-07-26 16:49:28'),(5,1,'退款政策','是否支持退款？','测试数据4','[\"退款\", \"政策\"]',0,5,1,'2025-07-26 16:06:08','2025-07-26 16:49:30');
/*!40000 ALTER TABLE `shop_faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_modify_steps`
--

DROP TABLE IF EXISTS `shop_modify_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_modify_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用步骤）',
  `step_number` int(11) NOT NULL COMMENT '步骤序号',
  `title` varchar(200) NOT NULL COMMENT '步骤标题',
  `description` text NOT NULL COMMENT '步骤描述',
  `note` text COMMENT '注意事项',
  `icon` varchar(255) DEFAULT NULL COMMENT '步骤图标',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_steps_product` (`product_id`),
  KEY `idx_steps_sort` (`sort_order`),
  CONSTRAINT `shop_modify_steps_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_modify_steps`
--

LOCK TABLES `shop_modify_steps` WRITE;
/*!40000 ALTER TABLE `shop_modify_steps` DISABLE KEYS */;
INSERT INTO `shop_modify_steps` VALUES (1,1,1,'下单购买','选择需要的服务套餐，完成支付后获取修改战区卡密','请保存好卡密，后续修改需要使用',NULL,1,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(2,1,2,'复制卡密','将获得的卡密复制到剪贴板，准备进行下一步操作','卡密只能使用一次，请妥善保管',NULL,2,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(3,1,3,'打开浏览器','使用手机浏览器打开指定的修改页面链接','建议使用Safari或Chrome浏览器',NULL,3,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(4,1,4,'输入信息','在修改页面输入游戏账号信息和卡密','请确保账号信息填写正确',NULL,4,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(5,1,5,'选择战区','选择想要修改到的目标战区位置','建议选择战力要求较低的地区',NULL,5,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(6,1,6,'提交修改','确认信息无误后提交修改申请，等待处理完成','修改时间通常为1-24小时',NULL,6,1,'2025-07-26 16:06:08','2025-07-26 16:06:08'),(7,2,1,'2','3','3',NULL,0,1,'2025-08-01 07:20:31','2025-08-01 07:20:31');
/*!40000 ALTER TABLE `shop_modify_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_product_images`
--

DROP TABLE IF EXISTS `shop_product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '商品ID',
  `image_url` varchar(500) NOT NULL COMMENT '图片URL',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_images_product` (`product_id`),
  CONSTRAINT `shop_product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_product_images`
--

LOCK TABLES `shop_product_images` WRITE;
/*!40000 ALTER TABLE `shop_product_images` DISABLE KEYS */;
INSERT INTO `shop_product_images` VALUES (1,1,'https://cdn.yixinzy.cn/img/bg1.jpg',1,'2025-07-26 16:06:08'),(2,1,'https://cdn.yixinzy.cn/img/bg1.jpg',2,'2025-07-26 16:06:08'),(3,1,'https://cdn.yixinzy.cn/img/bg1.jpg',3,'2025-07-26 16:06:08'),(4,2,'https://cdn.yixinzy.cn/img/bg1.jpg',1,'2025-07-26 16:06:08'),(5,2,'https://cdn.yixinzy.cn/img/bg1.jpg',2,'2025-07-26 16:06:08'),(6,3,'https://cdn.yixinzy.cn/img/bg1.jpg',1,'2025-07-26 16:06:08'),(7,4,'https://cdn.yixinzy.cn/img/bg1.jpg',1,'2025-07-26 16:06:08'),(8,5,'https://cdn.yixinzy.cn/img/bg1.jpg',1,'2025-07-26 16:06:08'),(9,5,'https://cdn.yixinzy.cn/img/bg1.jpg',2,'2025-07-26 16:06:08'),(10,6,'https://cdn.yixinzy.cn/img/bg1.jpg',0,'2025-07-28 16:02:10');
/*!40000 ALTER TABLE `shop_product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_products`
--

DROP TABLE IF EXISTS `shop_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '分类ID',
  `product_type` tinyint(4) DEFAULT '1' COMMENT '商品类型：1-普通商品，2-卡密商品',
  `title` varchar(200) NOT NULL COMMENT '商品标题',
  `description` text COMMENT '商品描述',
  `price` decimal(10,2) NOT NULL COMMENT '现价',
  `cover_image` varchar(500) DEFAULT NULL COMMENT '封面图片',
  `sales` int(11) DEFAULT '0' COMMENT '销量',
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_products`
--

LOCK TABLES `shop_products` WRITE;
/*!40000 ALTER TABLE `shop_products` DISABLE KEYS */;
INSERT INTO `shop_products` VALUES (1,2,1,'王者荣耀战区修改','专业战区修改服务，快速提升排名。我们拥有专业的技术团队，为您提供安全、快速、稳定的战区修改服务。',0.10,'https://cdn.yixinzy.cn/img/goods1.jpg',5,1,1,'2025-07-26 16:06:08','2025-07-28 15:24:02'),(2,3,1,'王者荣耀代练上分','专业代练团队，安全快速上分。资深玩家团队，保证账号安全，快速提升段位。',0.20,'https://cdn.yixinzy.cn/img/goods1.jpg',0,2,1,'2025-07-26 16:06:08','2025-07-27 16:59:18'),(3,4,1,'皮肤代充服务','全皮肤代充，价格优惠。支持所有英雄皮肤代充，价格比官方优惠，安全可靠。',0.02,'https://cdn.yixinzy.cn/img/goods1.jpg',0,3,1,'2025-07-26 16:06:08','2025-07-27 16:59:18'),(4,1,1,'账号安全检测','专业账号安全检测服务。全面检测账号安全状况，预防盗号风险。',0.03,'https://cdn.yixinzy.cn/img/goods1.jpg',0,4,1,'2025-07-26 16:06:08','2025-07-27 17:19:24'),(5,2,1,'高级战区修改套餐','包含多个英雄战区修改。一次购买，多个英雄同时修改，性价比更高。',0.05,'https://cdn.yixinzy.cn/img/goods1.jpg',0,5,1,'2025-07-26 16:06:08','2025-07-27 16:59:18'),(6,2,2,'王者荣耀战区修改卡密','自助卡密商品，购买后即可获得卡密，自行操作修改战区。操作简单快捷，无需等待人工处理。',0.02,'https://cdn.yixinzy.cn/img/goods1.jpg',6,6,1,'2025-07-27 18:54:39','2025-07-28 15:39:57'),(7,1,1,'王者战力查询服务','快速查询当前战力排名',5.00,'',10,1,1,'2025-08-01 15:40:48','2025-08-01 15:40:48'),(8,2,1,'改名卡服务','一键修改游戏昵称',10.00,'',5,2,1,'2025-08-01 15:40:48','2025-08-01 15:40:48'),(9,3,1,'战区修改服务','更改所在战区位置',15.00,'',8,3,1,'2025-08-01 15:40:48','2025-08-01 15:40:48');
/*!40000 ALTER TABLE `shop_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_tutorials`
--

DROP TABLE IF EXISTS `shop_tutorials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_tutorials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用教程）',
  `title` varchar(200) NOT NULL COMMENT '教程标题',
  `image_url` varchar(500) DEFAULT NULL COMMENT '教程图片',
  `content` text COMMENT '详细内容',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tutorials_product` (`product_id`),
  CONSTRAINT `shop_tutorials_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_tutorials`
--

LOCK TABLES `shop_tutorials` WRITE;
/*!40000 ALTER TABLE `shop_tutorials` DISABLE KEYS */;
INSERT INTO `shop_tutorials` VALUES (1,1,'步骤一：获取卡密','https://cdn.yixinzy.cn/img/tg1.jpg','购买成功后，系统会自动生成专属卡密，您可以在订单详情页面查看和复制卡密信息。',1,1,'2025-07-26 16:06:08','2025-07-26 17:10:17'),(2,1,'步骤二：打开修改页面','https://cdn.yixinzy.cn/img/tg2.jpg','点击修改链接或手动输入修改网址，进入战区修改页面。推荐使用手机自带浏览器。',2,1,'2025-07-26 16:06:08','2025-07-26 17:10:32'),(3,1,'步骤三：填写信息','https://cdn.yixinzy.cn/img/tg3.jpg','准确填写您的游戏账号信息，包括区服、角色名等，并输入获得的卡密。',3,1,'2025-07-26 16:06:08','2025-07-26 17:10:35');
/*!40000 ALTER TABLE `shop_tutorials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_videos`
--

DROP TABLE IF EXISTS `shop_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shop_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL COMMENT '关联商品ID（NULL表示通用视频）',
  `title` varchar(200) NOT NULL COMMENT '视频标题',
  `description` text COMMENT '视频描述',
  `video_url` varchar(500) DEFAULT NULL COMMENT '视频链接',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `status` tinyint(4) DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_videos_product` (`product_id`),
  CONSTRAINT `shop_videos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_videos`
--

LOCK TABLES `shop_videos` WRITE;
/*!40000 ALTER TABLE `shop_videos` DISABLE KEYS */;
INSERT INTO `shop_videos` VALUES (1,1,'完整修改流程演示','从购买到修改完成的完整操作演示','https://cdn.yixinzy.cn/daida/jc.mp4',1,1,'2025-07-26 16:06:08','2025-07-26 17:16:05'),(2,1,'常见问题解决方案','修改过程中可能遇到的问题及解决方法','https://cdn.yixinzy.cn/daida/jc.mp4',2,1,'2025-07-26 16:06:08','2025-07-26 17:16:07');
/*!40000 ALTER TABLE `shop_videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `app_id` varchar(100) NOT NULL COMMENT '小程序AppID',
  `token` varchar(200) NOT NULL COMMENT '登录令牌',
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户会话表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(100) NOT NULL COMMENT '所属小程序AppID',
  `openid` varchar(100) NOT NULL COMMENT '用户openid',
  `unionid` varchar(100) DEFAULT NULL COMMENT '用户unionid',
  `session_key` varchar(100) DEFAULT NULL COMMENT '会话密钥',
  `nickname` varchar(100) DEFAULT NULL COMMENT '用户昵称',
  `avatar_url` varchar(500) DEFAULT NULL COMMENT '头像地址',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `is_phone_verified` tinyint(1) DEFAULT '0' COMMENT '手机号是否验证',
  `last_login_time` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`app_id`,`openid`),
  KEY `idx_openid` (`openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_configs`
--

DROP TABLE IF EXISTS `website_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `website_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL COMMENT '配置键名',
  `config_label` varchar(100) DEFAULT NULL COMMENT '中文标签',
  `config_value` text COMMENT '配置值',
  `config_type` varchar(50) DEFAULT 'text' COMMENT '配置类型',
  `is_required` tinyint(1) DEFAULT '0' COMMENT '是否必填',
  `help_text` text COMMENT '帮助说明',
  `category` varchar(50) DEFAULT 'basic' COMMENT '配置分类',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`config_key`),
  KEY `idx_category` (`category`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COMMENT='网站信息配置表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_configs`
--

LOCK TABLES `website_configs` WRITE;
/*!40000 ALTER TABLE `website_configs` DISABLE KEYS */;
INSERT INTO `website_configs` VALUES (1,'site_name','网站名称','王者荣耀查战力系统','text',1,'显示在网站标题和页面头部','basic',1,'2025-09-11 07:25:00','2025-09-11 07:31:33'),(2,'site_description','网站描述','专业的王者荣耀战力查询平台，提供准确的英雄战力数据查询服务，支持多服务器查询，让您轻松了解自己的游戏实力。','textarea',1,'用于SEO和页面描述','basic',2,'2025-09-11 07:25:00','2025-09-11 07:31:33'),(3,'site_keywords','关键词','王者荣耀,战力查询,英雄战力,段位查询,游戏数据,王者荣耀助手','text',0,'SEO关键词，用逗号分隔','basic',3,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(4,'site_logo','网站Logo','https://cdn.yixinzy.cn/logo/wangzhe-logo.png','url',0,'网站Logo图片地址','basic',4,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(5,'site_favicon','网站图标','https://cdn.yixinzy.cn/favicon/wangzhe.ico','url',0,'网站favicon图标地址','basic',5,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(6,'version','版本号','v2.1.0','text',0,'当前系统版本号','basic',6,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(7,'contact_email','联系邮箱','support@wangzhe.com','email',1,'客服联系邮箱','contact',1,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(8,'contact_phone','联系电话','400-888-9999','tel',1,'客服联系电话','contact',2,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(9,'contact_wechat','微信号','wangzhe_support','text',0,'客服微信号','contact',3,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(10,'contact_qq','QQ号','888888888','text',0,'客服QQ号','contact',4,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(11,'service_time','服务时间','7×24小时在线服务，节假日正常服务','text',0,'客服服务时间','contact',5,'2025-09-11 07:25:01','2025-09-11 07:31:33'),(12,'company_name','公司名称','王者荣耀查战力科技有限公司','text',0,'公司全称','company',1,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(13,'company_address','公司地址','北京市朝阳区建国路88号SOHO现代城A座1001室','text',0,'公司详细地址','company',2,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(14,'icp_number','ICP备案号','京ICP备2024000001号-1','text',0,'ICP备案号','company',3,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(15,'beian_number','公安备案号','京公网安备11010502012345号','text',0,'公安备案号','company',4,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(16,'copyright','版权信息','© 2024 王者荣耀查战力系统 版权所有 | 京ICP备2024000001号-1','text',0,'版权声明','company',5,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(17,'privacy_policy','隐私政策','我们非常重视您的隐私保护。本隐私政策详细说明了我们如何收集、使用、存储和保护您的个人信息。我们承诺按照相关法律法规要求，采取相应的安全保护措施，保护您的个人信息安全。','textarea',0,'隐私保护政策内容','legal',1,'2025-09-11 07:25:01','2025-09-11 07:31:34'),(18,'terms_of_service','服务条款','欢迎使用王者荣耀查战力系统！使用本服务即表示您同意遵守以下条款和条件。请仔细阅读本服务条款，特别是限制责任和争议解决条款。如果您不同意本条款的任何内容，请不要使用我们的服务。','textarea',0,'服务使用条款','legal',2,'2025-09-11 07:25:02','2025-09-11 07:31:34'),(19,'about_us','关于我们','我们是一家专注于游戏数据查询服务的科技公司，致力于为玩家提供准确、及时的游戏数据查询服务。我们的团队由资深游戏开发者和数据分析师组成，拥有丰富的游戏行业经验和技术实力。','textarea',0,'公司介绍和业务说明','legal',3,'2025-09-11 07:25:02','2025-09-11 07:31:34'),(20,'maintenance_notice','维护公告','系统将定期进行维护升级，维护期间服务可能暂时中断。我们会提前24小时在官网和APP内发布维护公告，请各位用户合理安排使用时间。维护完成后，所有功能将恢复正常。','textarea',0,'系统维护时的公告内容','legal',4,'2025-09-11 07:25:02','2025-09-11 07:31:34');
/*!40000 ALTER TABLE `website_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'poxiao_qystudio'
--

--
-- Dumping routines for database 'poxiao_qystudio'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-12 14:07:15
