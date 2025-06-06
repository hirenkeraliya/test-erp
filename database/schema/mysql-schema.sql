/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `parent_module_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `batch_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ulid` char(26) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `forgot_password_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token_expiration_at` datetime DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_login_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_employee_id_unique` (`employee_id`),
  UNIQUE KEY `admins_username_unique` (`username`),
  CONSTRAINT `admins_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assembly_child_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembly_child_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `child_item_variant_id` bigint unsigned NOT NULL,
  `units` decimal(14,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assembly_child_items_item_id_foreign` (`item_id`),
  KEY `assembly_child_items_child_item_variant_id_foreign` (`child_item_variant_id`),
  CONSTRAINT `assembly_child_items_child_item_variant_id_foreign` FOREIGN KEY (`child_item_variant_id`) REFERENCES `items` (`id`),
  CONSTRAINT `assembly_child_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assembly_child_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembly_child_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `child_product_id` bigint unsigned NOT NULL,
  `units` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assembly_child_products_product_id_foreign` (`product_id`),
  KEY `assembly_child_products_child_product_id_foreign` (`child_product_id`),
  CONSTRAINT `assembly_child_products_child_product_id_foreign` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `assembly_child_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attached_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attached_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `template_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attached_templates_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `attached_templates_template_id_foreign` (`template_id`),
  CONSTRAINT `attached_templates_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `field_type` tinyint NOT NULL,
  `default_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `from` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` json DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attributes_template_id_foreign` (`template_id`),
  CONSTRAINT `attributes_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_brand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_brand` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  KEY `automated_notification_brand_automated_notification_id_foreign` (`automated_notification_id`),
  KEY `automated_notification_brand_brand_id_foreign` (`brand_id`),
  CONSTRAINT `automated_notification_brand_automated_notification_id_foreign` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`),
  CONSTRAINT `automated_notification_brand_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_category` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  KEY `fk_automated_notification_category` (`automated_notification_id`),
  KEY `automated_notification_category_category_id_foreign` (`category_id`),
  CONSTRAINT `automated_notification_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_automated_notification_category` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_department` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  KEY `fk_automated_notification_department` (`automated_notification_id`),
  KEY `automated_notification_department_department_id_foreign` (`department_id`),
  CONSTRAINT `automated_notification_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_automated_notification_department` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_email_recipient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_email_recipient` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `email_recipient_id` bigint unsigned NOT NULL,
  KEY `automated_notification_email` (`automated_notification_id`),
  KEY `automated_email_recipient` (`email_recipient_id`),
  CONSTRAINT `automated_email_recipient` FOREIGN KEY (`email_recipient_id`) REFERENCES `email_recipients` (`id`),
  CONSTRAINT `automated_notification_email` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_location` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `fk_automated_notification_location` (`automated_notification_id`),
  KEY `automated_notification_location_location_id_foreign` (`location_id`),
  CONSTRAINT `automated_notification_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `fk_automated_notification_location` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_month_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_month_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_id` bigint unsigned NOT NULL,
  `month_date` tinyint NOT NULL COMMENT '1 to 31 of the month',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `automated_notifications_month_dates` (`automated_notification_id`),
  CONSTRAINT `automated_notifications_month_dates` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_product` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  KEY `fk_automated_notification_product` (`automated_notification_id`),
  KEY `automated_notification_product_product_id_foreign` (`product_id`),
  CONSTRAINT `automated_notification_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_automated_notification_product` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_product_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_product_collection` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `product_collection_id` bigint unsigned NOT NULL,
  KEY `fk_automated_notification_pc` (`automated_notification_id`),
  KEY `product_collection_product_collection_id_foreign` (`product_collection_id`),
  CONSTRAINT `fk_automated_notification_pc` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`),
  CONSTRAINT `product_collection_product_collection_id_foreign` FOREIGN KEY (`product_collection_id`) REFERENCES `product_collections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `low_stock_alert_threshold` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_automated_notification_products` (`automated_notification_id`),
  KEY `automated_notification_products_product_id_foreign` (`product_id`),
  KEY `automated_notification_products_store_id_foreign` (`store_id`),
  KEY `automated_notification_products_location_id_foreign` (`location_id`),
  CONSTRAINT `automated_notification_products_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `automated_notification_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `automated_notification_products_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `fk_automated_notification_products` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_sent_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_sent_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_id` bigint unsigned NOT NULL,
  `happened_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_automated_notification_sd` (`automated_notification_id`),
  CONSTRAINT `fk_automated_notification_sd` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_sent_activity_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_sent_activity_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_sent_activities_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_automated_notification_sent_activity_items` (`automated_notification_sent_activities_id`),
  KEY `automated_notification_sent_activity_items_inventory_id_foreign` (`inventory_id`),
  CONSTRAINT `automated_notification_sent_activity_items_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `fk_automated_notification_sent_activity_items` FOREIGN KEY (`automated_notification_sent_activities_id`) REFERENCES `automated_notification_sent_activities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_store` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  KEY `automated_notification_store_automated_notification_id_foreign` (`automated_notification_id`),
  KEY `automated_notification_store_store_id_foreign` (`store_id`),
  CONSTRAINT `automated_notification_store_automated_notification_id_foreign` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`),
  CONSTRAINT `automated_notification_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `low_stock_alert_threshold` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `automated_notification_stores_automated_notification_id_foreign` (`automated_notification_id`),
  KEY `automated_notification_stores_store_id_foreign` (`store_id`),
  KEY `automated_notification_stores_location_id_foreign` (`location_id`),
  CONSTRAINT `automated_notification_stores_automated_notification_id_foreign` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`),
  CONSTRAINT `automated_notification_stores_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `automated_notification_stores_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_style` (
  `automated_notification_id` bigint unsigned NOT NULL,
  `style_id` bigint unsigned NOT NULL,
  KEY `automated_notification_style_automated_notification_id_foreign` (`automated_notification_id`),
  KEY `automated_notification_style_style_id_foreign` (`style_id`),
  CONSTRAINT `automated_notification_style_automated_notification_id_foreign` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`),
  CONSTRAINT `automated_notification_style_style_id_foreign` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notification_week_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notification_week_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `automated_notification_id` bigint unsigned NOT NULL,
  `week_day` tinyint NOT NULL COMMENT '0: Sunday, 1: Monday, 2: Tuesday, 3: Wednesday, 4: Thursday, 5: Friday, 6: Saturday',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `automated_notifications_week_days` (`automated_notification_id`),
  CONSTRAINT `automated_notifications_week_days` FOREIGN KEY (`automated_notification_id`) REFERENCES `automated_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automated_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automated_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: Low Stock, 2: No Stock, 3: Request Stock, 4: Deadline Request Stock',
  `timeframe_type_id` tinyint(1) DEFAULT NULL COMMENT '1: Limit By Day Of The Week, 2: Limit By Day Of The Month',
  `low_stock_alert_threshold` int DEFAULT NULL,
  `sent_notification` tinyint NOT NULL DEFAULT '1',
  `exclude_type_id` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `automated_notifications_company_id_foreign` (`company_id`),
  CONSTRAINT `automated_notifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type_id` int NOT NULL,
  `custom_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `banners_company_id_foreign` (`company_id`),
  CONSTRAINT `banners_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batches_number_company_id_unique` (`number`,`company_id`),
  KEY `batches_company_id_foreign` (`company_id`),
  KEY `batches_product_id_foreign` (`product_id`),
  CONSTRAINT `batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_payment_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra_details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_payment_payments_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_payments_counter_update_id_foreign` (`counter_update_id`),
  KEY `booking_payment_payments_payment_type_id_foreign` (`payment_type_id`),
  CONSTRAINT `booking_payment_payments_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_payments_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `booking_payment_payments_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_product_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_product_promoter` (
  `booking_payment_product_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `booking_payment_product_promoter_p_id` (`booking_payment_product_id`),
  KEY `booking_payment_product_promoter_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `booking_payment_product_promoter_p_id` FOREIGN KEY (`booking_payment_product_id`) REFERENCES `booking_payment_products` (`id`),
  CONSTRAINT `booking_payment_product_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_payment_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_bundle_id` bigint unsigned DEFAULT NULL,
  `product_bundle_package_type_id` bigint unsigned DEFAULT NULL,
  `product_bundle_units` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_payment_products_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_products_product_id_foreign` (`product_id`),
  KEY `booking_payment_products_product_bundle_id_foreign` (`product_bundle_id`),
  KEY `bundle_package_type_foreign` (`product_bundle_package_type_id`),
  CONSTRAINT `booking_payment_products_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_products_product_bundle_id_foreign` FOREIGN KEY (`product_bundle_id`) REFERENCES `product_bundles` (`id`),
  CONSTRAINT `booking_payment_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `bundle_package_type_foreign` FOREIGN KEY (`product_bundle_package_type_id`) REFERENCES `package_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_promoter` (
  `booking_payment_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `booking_payment_promoter_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_promoter_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `booking_payment_promoter_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_payment_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_payment_refunds_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_refunds_counter_update_id_foreign` (`counter_update_id`),
  KEY `booking_payment_refunds_payment_type_id_foreign` (`payment_type_id`),
  CONSTRAINT `booking_payment_refunds_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_refunds_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `booking_payment_refunds_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_uses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_payment_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `sale_payment_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_payment_uses_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_uses_counter_update_id_foreign` (`counter_update_id`),
  KEY `booking_payment_uses_sale_payment_id_foreign` (`sale_payment_id`),
  CONSTRAINT `booking_payment_uses_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_uses_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `booking_payment_uses_sale_payment_id_foreign` FOREIGN KEY (`sale_payment_id`) REFERENCES `sale_payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payment_void_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payment_void_uses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_payment_id` bigint unsigned NOT NULL,
  `booking_payment_uses_id` bigint unsigned NOT NULL,
  `void_sale_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_payment_void_uses_booking_payment_id_foreign` (`booking_payment_id`),
  KEY `booking_payment_void_uses_booking_payment_uses_id_foreign` (`booking_payment_uses_id`),
  KEY `booking_payment_void_uses_void_sale_id_foreign` (`void_sale_id`),
  CONSTRAINT `booking_payment_void_uses_booking_payment_id_foreign` FOREIGN KEY (`booking_payment_id`) REFERENCES `booking_payments` (`id`),
  CONSTRAINT `booking_payment_void_uses_booking_payment_uses_id_foreign` FOREIGN KEY (`booking_payment_uses_id`) REFERENCES `booking_payment_uses` (`id`),
  CONSTRAINT `booking_payment_void_uses_void_sale_id_foreign` FOREIGN KEY (`void_sale_id`) REFERENCES `void_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `available_amount` decimal(10,2) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1: Active 2: Used 3: Refunded',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bill_reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorizer_id` int DEFAULT NULL,
  `authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `happened_at` datetime DEFAULT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_payments_offline_id_unique` (`offline_id`),
  KEY `booking_payments_counter_update_id_foreign` (`counter_update_id`),
  KEY `booking_payments_customer_id_foreign` (`member_id`),
  CONSTRAINT `booking_payments_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `booking_payments_customer_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_company` (
  `brand_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `brand_company_brand_id_foreign` (`brand_id`),
  KEY `brand_company_company_id_foreign` (`company_id`),
  CONSTRAINT `brand_company_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_company_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_happy_hour_discount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_happy_hour_discount` (
  `brand_id` bigint unsigned NOT NULL,
  `happy_hour_discount_id` bigint unsigned NOT NULL,
  KEY `brand_happy_hour_discount_brand_id_foreign` (`brand_id`),
  KEY `brand_happy_hour_discount_happy_hour_discount_id_foreign` (`happy_hour_discount_id`),
  CONSTRAINT `brand_happy_hour_discount_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_happy_hour_discount_happy_hour_discount_id_foreign` FOREIGN KEY (`happy_hour_discount_id`) REFERENCES `happy_hour_discounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_location` (
  `brand_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `brand_location_brand_id_foreign` (`brand_id`),
  KEY `brand_location_location_id_foreign` (`location_id`),
  CONSTRAINT `brand_location_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_loyalty_campaign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_loyalty_campaign` (
  `brand_id` bigint unsigned NOT NULL,
  `loyalty_campaign_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `brand_loyalty_campaign_brand_id_foreign` (`brand_id`),
  KEY `brand_loyalty_campaign_loyalty_campaign_id_foreign` (`loyalty_campaign_id`),
  CONSTRAINT `brand_loyalty_campaign_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_loyalty_campaign_loyalty_campaign_id_foreign` FOREIGN KEY (`loyalty_campaign_id`) REFERENCES `loyalty_campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_loyalty_campaign_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_loyalty_campaign_configuration` (
  `brand_id` bigint unsigned NOT NULL,
  `loyalty_campaign_configuration_id` bigint unsigned NOT NULL,
  KEY `brand_loyalty_campaign_configuration_brand_id_foreign` (`brand_id`),
  CONSTRAINT `brand_loyalty_campaign_configuration_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_product_collection_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_product_collection_filter` (
  `brand_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `brand_product_collection_filter_brand_id_foreign` (`brand_id`),
  KEY `brand_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `brand_product_collection_filter_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_promotion` (
  `brand_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `brand_promotion_brand_id_foreign` (`brand_id`),
  KEY `brand_promotion_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `brand_promotion_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_store` (
  `brand_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `brand_store_brand_id_foreign` (`brand_id`),
  KEY `brand_store_store_id_foreign` (`store_id`),
  CONSTRAINT `brand_store_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_store_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_store_manager` (
  `brand_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `brand_store_manager_brand_id_foreign` (`brand_id`),
  KEY `brand_store_manager_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `brand_store_manager_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `brand_store_manager_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_name_unique` (`name`),
  UNIQUE KEY `brands_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bundle_item_variant_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_item_variant_loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_bundle_id` bigint unsigned NOT NULL,
  `membership_id` bigint unsigned NOT NULL,
  `points` bigint NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_item_variant_bundle_loyalty_points` (`item_variant_bundle_id`),
  KEY `bundle_item_variant_loyalty_points_membership_id_foreign` (`membership_id`),
  CONSTRAINT `bundle_item_variant_loyalty_points_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`),
  CONSTRAINT `fk_item_variant_bundle_loyalty_points` FOREIGN KEY (`item_variant_bundle_id`) REFERENCES `item_variant_bundles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bundle_product_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_product_loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_bundle_id` bigint unsigned NOT NULL,
  `membership_id` bigint unsigned NOT NULL,
  `points` bigint NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bundle_product_loyalty_points_product_bundle_id_foreign` (`product_bundle_id`),
  KEY `bundle_product_loyalty_points_membership_id_foreign` (`membership_id`),
  CONSTRAINT `bundle_product_loyalty_points_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`),
  CONSTRAINT `bundle_product_loyalty_points_product_bundle_id_foreign` FOREIGN KEY (`product_bundle_id`) REFERENCES `product_bundles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cancel_credit_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cancel_credit_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cancel_credit_sales_sale_id_foreign` (`sale_id`),
  KEY `cancel_credit_sales_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `cancel_credit_sales_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `cancel_credit_sales_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cancel_layaway_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cancel_layaway_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cancel_layaway_sales_sale_id_foreign` (`sale_id`),
  KEY `cancel_layaway_sales_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `cancel_layaway_sales_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `cancel_layaway_sales_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_movement_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movement_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: Cash-In, 2: Cash-Out',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_movement_reasons_reason_company_id_unique` (`reason`,`company_id`),
  KEY `cash_movement_reasons_company_id_foreign` (`company_id`),
  CONSTRAINT `cash_movement_reasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `cash_movement_type_id` int NOT NULL COMMENT '1: Cash In, 2: Cash Out',
  `cash_movement_reason_id` bigint unsigned DEFAULT NULL,
  `other_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorizer_id` int DEFAULT NULL,
  `authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `happened_at` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_movements_offline_id_unique` (`offline_id`),
  KEY `cash_movements_counter_update_id_foreign` (`counter_update_id`),
  KEY `cash_movements_cash_movement_reason_id_foreign` (`cash_movement_reason_id`),
  CONSTRAINT `cash_movements_cash_movement_reason_id_foreign` FOREIGN KEY (`cash_movement_reason_id`) REFERENCES `cash_movement_reasons` (`id`),
  CONSTRAINT `cash_movements_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashback_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashback_category` (
  `cashback_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `cashback_category_cashback_id_foreign` (`cashback_id`),
  KEY `cashback_category_category_id_foreign` (`category_id`),
  CONSTRAINT `cashback_category_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`),
  CONSTRAINT `cashback_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashback_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashback_location` (
  `cashback_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `cashback_location_cashback_id_foreign` (`cashback_id`),
  KEY `cashback_location_location_id_foreign` (`location_id`),
  CONSTRAINT `cashback_location_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`),
  CONSTRAINT `cashback_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashback_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashback_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cashback_id` bigint unsigned NOT NULL,
  `condition_operator_type_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashback_prices_cashback_id_foreign` (`cashback_id`),
  CONSTRAINT `cashback_prices_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashback_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashback_product` (
  `cashback_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `cashback_product_cashback_id_foreign` (`cashback_id`),
  KEY `cashback_product_product_id_foreign` (`product_id`),
  CONSTRAINT `cashback_product_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`),
  CONSTRAINT `cashback_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashback_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashback_store` (
  `cashback_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `cashback_store_cashback_id_foreign` (`cashback_id`),
  KEY `cashback_store_store_id_foreign` (`store_id`),
  CONSTRAINT `cashback_store_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`),
  CONSTRAINT `cashback_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `exclude_by_type` tinyint(1) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_type_id` tinyint NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `minimum_spend_amount` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashbacks_company_id_foreign` (`company_id`),
  CONSTRAINT `cashbacks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashier_group_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_group_permissions` (
  `cashier_group_id` bigint unsigned NOT NULL,
  `permission_id` int NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `cashier_group_permissions_cashier_group_id_foreign` (`cashier_group_id`),
  CONSTRAINT `cashier_group_permissions_cashier_group_id_foreign` FOREIGN KEY (`cashier_group_id`) REFERENCES `cashier_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashier_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_override_type` tinyint NOT NULL DEFAULT '1',
  `price_override_limit_percentage_for_item` decimal(5,2) DEFAULT NULL,
  `price_override_limit_percentage_for_cart` decimal(5,2) NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cashier_groups_name_company_id_unique` (`name`,`company_id`),
  KEY `cashier_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `cashier_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashier_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_location` (
  `cashier_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `cashier_location_cashier_id_foreign` (`cashier_id`),
  KEY `cashier_location_location_id_foreign` (`location_id`),
  CONSTRAINT `cashier_location_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`),
  CONSTRAINT `cashier_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashier_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_store` (
  `cashier_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `cashier_store_cashier_id_foreign` (`cashier_id`),
  KEY `cashier_store_store_id_foreign` (`store_id`),
  CONSTRAINT `cashier_store_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`),
  CONSTRAINT `cashier_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cashiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `cashier_group_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL COMMENT 'Filled when a counter is open by the respective cashier',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cashiers_employee_id_unique` (`employee_id`),
  UNIQUE KEY `cashiers_username_unique` (`username`),
  KEY `cashiers_cashier_group_id_foreign` (`cashier_group_id`),
  KEY `cashiers_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `cashiers_cashier_group_id_foreign` FOREIGN KEY (`cashier_group_id`) REFERENCES `cashier_groups` (`id`),
  CONSTRAINT `cashiers_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `cashiers_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `parent_category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0: Inactive, 1: Active',
  `is_available_in_ecommerce` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: No, 1: Yes',
  `is_display_homepage` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: No, 1: Yes',
  `is_display_on_menu` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: No, 1: Yes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `categories_code_company_id_unique` (`code`,`company_id`),
  KEY `categories_company_id_foreign` (`company_id`),
  KEY `categories_parent_category_id_foreign` (`parent_category_id`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `categories_parent_category_id_foreign` FOREIGN KEY (`parent_category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_channel_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_channel_references` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_channel_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `external_category_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_channel_references_sale_channel_id_foreign` (`sale_channel_id`),
  KEY `category_channel_references_category_id_foreign` (`category_id`),
  CONSTRAINT `category_channel_references_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_channel_references_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_happy_hour_discount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_happy_hour_discount` (
  `category_id` bigint unsigned NOT NULL,
  `happy_hour_discount_id` bigint unsigned NOT NULL,
  KEY `category_happy_hour_discount_category_id_foreign` (`category_id`),
  KEY `category_happy_hour_discount_happy_hour_discount_id_foreign` (`happy_hour_discount_id`),
  CONSTRAINT `category_happy_hour_discount_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_happy_hour_discount_happy_hour_discount_id_foreign` FOREIGN KEY (`happy_hour_discount_id`) REFERENCES `happy_hour_discounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_item` (
  `category_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `sort_order` tinyint NOT NULL,
  KEY `category_item_category_id_foreign` (`category_id`),
  KEY `category_item_item_id_foreign` (`item_id`),
  CONSTRAINT `category_item_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_item_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_loyalty_campaign_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_loyalty_campaign_configuration` (
  `category_id` bigint unsigned NOT NULL,
  `loyalty_campaign_configuration_id` bigint unsigned NOT NULL,
  KEY `category_loyalty_campaign_configuration_category_id_foreign` (`category_id`),
  CONSTRAINT `category_loyalty_campaign_configuration_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_product` (
  `category_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `sort_order` tinyint NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `category_product_category_id_foreign` (`category_id`),
  KEY `category_product_product_id_foreign` (`product_id`),
  CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_product_collection_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_product_collection_filter` (
  `category_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `category_product_collection_filter_category_id_foreign` (`category_id`),
  KEY `category_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `category_product_collection_filter_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_promotion` (
  `category_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `category_promotion_category_id_foreign` (`category_id`),
  KEY `category_promotion_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `category_promotion_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `category_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_wise_daily_totals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_wise_daily_totals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `total_units_sold` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_amount_return` decimal(10,2) DEFAULT NULL,
  `total_units_return` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_totals_company_id_foreign` (`company_id`),
  KEY `daily_totals_category_id_foreign` (`category_id`),
  KEY `category_wise_daily_totals_store_id_foreign` (`store_id`),
  KEY `category_wise_daily_totals_counter_update_id_foreign` (`counter_update_id`),
  KEY `category_wise_daily_totals_location_id_foreign` (`location_id`),
  CONSTRAINT `category_wise_daily_totals_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `category_wise_daily_totals_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `category_wise_daily_totals_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `daily_totals_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `daily_totals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `state_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `close_counter_denominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `close_counter_denominations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_update_id` bigint unsigned NOT NULL,
  `denomination` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `close_counter_denominations_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `close_counter_denominations_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `close_counter_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `close_counter_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_update_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `total_transactions` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `close_counter_payments_counter_update_id_foreign` (`counter_update_id`),
  KEY `close_counter_payments_payment_type_id_foreign` (`payment_type_id`),
  CONSTRAINT `close_counter_payments_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `close_counter_payments_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `color_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `color_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `color_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `color_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `color_product_collection_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `color_product_collection_filter` (
  `color_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `color_product_collection_filter_color_id_foreign` (`color_id`),
  KEY `color_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `color_product_collection_filter_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`),
  CONSTRAINT `color_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colors_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `colors_code_company_id_unique` (`code`,`company_id`),
  KEY `colors_company_id_foreign` (`company_id`),
  KEY `colors_group_id_foreign` (`group_id`),
  CONSTRAINT `colors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `colors_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `color_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grn_format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employer_identification_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_security_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `void_sale_number_prefix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_picking_list_prefix` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `send_sale_email_to_member` tinyint(1) NOT NULL,
  `new_member_free_loyalty_points` bigint NOT NULL DEFAULT '0',
  `loyalty_point_expiration_days` int DEFAULT NULL,
  `number_of_receipts` int NOT NULL DEFAULT '0',
  `auto_birthday_voucher_generation` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `commission_type_id` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'This will configure the application to display commission by either 1: Promoter or 2: Department',
  `min_promoters_per_item` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'This will configure maximum allowed promoters for each sale item. Zero (0) means unlimited. At 1 byte it will allow maximum of 255 promoters or unlimited (0) may allowed per item.',
  `is_bill_reference_number_mandatory` tinyint(1) NOT NULL,
  `allow_exchange_to_different_store` tinyint(1) NOT NULL DEFAULT '0',
  `allow_price_override_cart_level` tinyint(1) NOT NULL DEFAULT '0',
  `allow_negative_inventory` tinyint(1) NOT NULL DEFAULT '1',
  `is_employee_booking_payment_allowed` tinyint(1) NOT NULL DEFAULT '0',
  `allow_only_return` tinyint(1) NOT NULL DEFAULT '0',
  `allow_credit_sale` tinyint(1) NOT NULL DEFAULT '0',
  `allow_employee_credit_sale` tinyint(1) NOT NULL DEFAULT '0',
  `discount_applicable_type` tinyint NOT NULL DEFAULT '1',
  `booking_payment_use_type` tinyint NOT NULL DEFAULT '1',
  `booking_payment_refund_type` tinyint NOT NULL DEFAULT '1',
  `yearly_target` decimal(10,2) DEFAULT NULL,
  `enable_ioi_city_mall_integration` tinyint(1) NOT NULL DEFAULT '0',
  `enable_trx_mall_integration` tinyint(1) NOT NULL DEFAULT '0',
  `default_store_id` bigint unsigned DEFAULT NULL,
  `default_location_id` bigint unsigned DEFAULT NULL,
  `location_assignment_type` tinyint NOT NULL DEFAULT '1',
  `allow_happy_hour_discount` tinyint(1) NOT NULL DEFAULT '1',
  `auto_include_in_collections` tinyint(1) NOT NULL DEFAULT '1',
  `creator_can_approve_draft_product` tinyint(1) NOT NULL DEFAULT '0',
  `enable_e_invoice` tinyint(1) NOT NULL DEFAULT '1',
  `show_e_invoice_qr_on_receipt` tinyint(1) NOT NULL DEFAULT '0',
  `default_country_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_email_unique` (`email`),
  UNIQUE KEY `companies_code_unique` (`code`),
  KEY `companies_default_store_id_foreign` (`default_store_id`),
  KEY `companies_default_country_id_foreign` (`default_country_id`),
  KEY `companies_default_location_id_foreign` (`default_location_id`),
  CONSTRAINT `companies_default_country_id_foreign` FOREIGN KEY (`default_country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `companies_default_location_id_foreign` FOREIGN KEY (`default_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `companies_default_store_id_foreign` FOREIGN KEY (`default_store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_country` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  `company_id` bigint unsigned NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_country_company_id_foreign` (`company_id`),
  KEY `company_country_country_id_foreign` (`country_id`),
  CONSTRAINT `company_country_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `company_country_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `complimentary_item_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `complimentary_item_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `complimentary_item_reasons_reason_company_id_unique` (`reason`,`company_id`),
  KEY `complimentary_item_reasons_company_id_foreign` (`company_id`),
  CONSTRAINT `complimentary_item_reasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counter_update_declaration_attempt_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_update_declaration_attempt_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_update_declaration_attempt_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `declared_amount` decimal(10,2) NOT NULL,
  `calculated_amount` decimal(10,2) NOT NULL,
  `denominations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counter_declaration_attempt_id` (`counter_update_declaration_attempt_id`),
  KEY `declaration_payment_type_id` (`payment_type_id`),
  CONSTRAINT `counter_declaration_attempt_id` FOREIGN KEY (`counter_update_declaration_attempt_id`) REFERENCES `counter_update_declaration_attempts` (`id`),
  CONSTRAINT `declaration_payment_type_id` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counter_update_declaration_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_update_declaration_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_update_id` bigint unsigned NOT NULL,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `counter_update_declaration_attempts_offline_id_unique` (`offline_id`),
  KEY `counter_update_declaration_attempts_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `counter_update_declaration_attempts_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counter_update_event_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_update_event_product` (
  `counter_update_event_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  KEY `counter_update_event_product_counter_update_event_id_foreign` (`counter_update_event_id`),
  KEY `counter_update_event_product_product_id_foreign` (`product_id`),
  CONSTRAINT `counter_update_event_product_counter_update_event_id_foreign` FOREIGN KEY (`counter_update_event_id`) REFERENCES `counter_update_events` (`id`),
  CONSTRAINT `counter_update_event_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counter_update_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_update_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: Take a break, 2: Back from Break',
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `counter_update_events_offline_id_unique` (`offline_id`),
  KEY `counter_update_events_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `counter_update_events_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counter_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counter_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_id` bigint unsigned NOT NULL,
  `cashier_id` bigint unsigned NOT NULL,
  `opening_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(10,2) DEFAULT '0.00',
  `closed_at` datetime DEFAULT NULL,
  `opened_by_pos_at` datetime DEFAULT NULL,
  `closed_by_pos_at` datetime DEFAULT NULL,
  `mismatch_amount` decimal(10,2) DEFAULT '0.00',
  `amount_mismatch_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_collection_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sales` int DEFAULT '0',
  `total_sales_amount` decimal(10,2) DEFAULT '0.00',
  `total_layaway_sales` int NOT NULL DEFAULT '0',
  `total_layaway_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_sales` int NOT NULL DEFAULT '0',
  `total_credit_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_voided_sales` int NOT NULL DEFAULT '0',
  `total_voided_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_item_wise_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cart_wide_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sales_round_off` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sale_returns` int DEFAULT '0',
  `total_sale_returns_amount` decimal(10,2) DEFAULT '0.00',
  `total_credit_notes_used_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_notes_used` int DEFAULT '0',
  `total_credit_notes_refunded_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_notes_refunded` int DEFAULT '0',
  `total_sale_returns_round_off` decimal(10,2) DEFAULT '0.00',
  `total_cashback` int NOT NULL DEFAULT '0',
  `total_cashback_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_vouchers_used` int NOT NULL DEFAULT '0',
  `total_voucher_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_vouchers_generated` int NOT NULL DEFAULT '0',
  `total_sale_promotion_used` int NOT NULL DEFAULT '0',
  `total_sale_promotion_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sale_item_promotion_used` int NOT NULL DEFAULT '0',
  `total_sale_item_promotion_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_dream_price_used` int NOT NULL DEFAULT '0',
  `total_dream_price_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_complimentary_item_discount_used` int NOT NULL DEFAULT '0',
  `total_complimentary_item_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price_override_used` int NOT NULL DEFAULT '0',
  `total_price_override_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_refunded_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_used_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_ins_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_outs_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_sales` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_booking_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_booking_payment_refunded` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_credit_note_refunded` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_new_booking_payments` int NOT NULL DEFAULT '0',
  `total_used_booking_payments` int NOT NULL DEFAULT '0',
  `total_cancel_layaway_sales` int NOT NULL DEFAULT '0',
  `total_cancel_layaway_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `closed_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `closed_by_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counter_updates_counter_id_foreign` (`counter_id`),
  KEY `counter_updates_cashier_id_foreign` (`cashier_id`),
  CONSTRAINT `counter_updates_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`),
  CONSTRAINT `counter_updates_counter_id_foreign` FOREIGN KEY (`counter_id`) REFERENCES `counters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `counters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL COMMENT 'Filled when the counter is open',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_locked` tinyint(1) NOT NULL,
  `app_version` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `counters_name_store_id_unique` (`name`,`store_id`),
  KEY `counters_store_id_foreign` (`store_id`),
  KEY `counters_counter_update_id_foreign` (`counter_update_id`),
  KEY `counters_location_id_foreign` (`location_id`),
  CONSTRAINT `counters_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `counters_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `counters_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `iso2` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `phone_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso3` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subregion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_note_expirations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_expirations` (
  `credit_note_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `credit_note_expirations_credit_note_id_foreign` (`credit_note_id`),
  CONSTRAINT `credit_note_expirations_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_note_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL COMMENT 'Authorized by store manager',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_refunds_credit_note_id_foreign` (`credit_note_id`),
  KEY `credit_note_refunds_counter_update_id_foreign` (`counter_update_id`),
  KEY `credit_note_refunds_payment_type_id_foreign` (`payment_type_id`),
  KEY `credit_note_refunds_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `credit_note_refunds_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `credit_note_refunds_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`),
  CONSTRAINT `credit_note_refunds_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`),
  CONSTRAINT `credit_note_refunds_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_note_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_uses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `sale_payment_id` bigint unsigned DEFAULT NULL,
  `booking_payment_payment_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_uses_credit_note_id_foreign` (`credit_note_id`),
  KEY `credit_note_uses_counter_update_id_foreign` (`counter_update_id`),
  KEY `credit_note_uses_sale_payment_id_foreign` (`sale_payment_id`),
  KEY `credit_note_uses_booking_payment_payment_id_foreign` (`booking_payment_payment_id`),
  CONSTRAINT `credit_note_uses_booking_payment_payment_id_foreign` FOREIGN KEY (`booking_payment_payment_id`) REFERENCES `booking_payment_payments` (`id`),
  CONSTRAINT `credit_note_uses_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `credit_note_uses_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`),
  CONSTRAINT `credit_note_uses_sale_payment_id_foreign` FOREIGN KEY (`sale_payment_id`) REFERENCES `sale_payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_note_void_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_note_void_uses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint unsigned NOT NULL,
  `credit_note_uses_id` bigint unsigned NOT NULL,
  `void_sale_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_void_uses_credit_note_id_foreign` (`credit_note_id`),
  KEY `credit_note_void_uses_credit_note_uses_id_foreign` (`credit_note_uses_id`),
  KEY `credit_note_void_uses_void_sale_id_foreign` (`void_sale_id`),
  CONSTRAINT `credit_note_void_uses_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`),
  CONSTRAINT `credit_note_void_uses_credit_note_uses_id_foreign` FOREIGN KEY (`credit_note_uses_id`) REFERENCES `credit_note_uses` (`id`),
  CONSTRAINT `credit_note_void_uses_void_sale_id_foreign` FOREIGN KEY (`void_sale_id`) REFERENCES `void_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `counter_update_id` bigint unsigned NOT NULL,
  `sale_return_id` bigint unsigned DEFAULT NULL,
  `cancel_layaway_sale_id` bigint unsigned DEFAULT NULL,
  `cancel_credit_sale_id` bigint unsigned DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `available_amount` decimal(10,2) NOT NULL,
  `status` tinyint NOT NULL COMMENT '1: Active 2: Used 3: Expired',
  `member_id` bigint unsigned DEFAULT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_notes_counter_update_id_foreign` (`counter_update_id`),
  KEY `credit_notes_sale_return_id_foreign` (`sale_return_id`),
  KEY `credit_notes_cancel_layaway_sale_id_foreign` (`cancel_layaway_sale_id`),
  KEY `credit_notes_member_id_foreign` (`member_id`),
  KEY `credit_notes_cancel_credit_sale_id_foreign` (`cancel_credit_sale_id`),
  CONSTRAINT `credit_notes_cancel_credit_sale_id_foreign` FOREIGN KEY (`cancel_credit_sale_id`) REFERENCES `cancel_credit_sales` (`id`),
  CONSTRAINT `credit_notes_cancel_layaway_sale_id_foreign` FOREIGN KEY (`cancel_layaway_sale_id`) REFERENCES `cancel_layaway_sales` (`id`),
  CONSTRAINT `credit_notes_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `credit_notes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `credit_notes_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `precision` tinyint NOT NULL DEFAULT '2',
  `symbol` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol_native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol_first` tinyint NOT NULL DEFAULT '1',
  `decimal_mark` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '.',
  `thousands_separator` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ',',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `template_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_field_values_model_id_model_type_attribute_id_unique` (`model_id`,`model_type`,`attribute_id`),
  KEY `custom_field_values_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `custom_field_values_template_id_foreign` (`template_id`),
  KEY `custom_field_values_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `custom_field_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`),
  CONSTRAINT `custom_field_values_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `denominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `denominations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `denomination` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `denominations_company_id_foreign` (`company_id`),
  CONSTRAINT `denominations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_happy_hour_discount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_happy_hour_discount` (
  `department_id` bigint unsigned NOT NULL,
  `happy_hour_discount_id` bigint unsigned NOT NULL,
  KEY `department_happy_hour_discount_department_id_foreign` (`department_id`),
  KEY `department_happy_hour_discount_happy_hour_discount_id_foreign` (`happy_hour_discount_id`),
  CONSTRAINT `department_happy_hour_discount_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `department_happy_hour_discount_happy_hour_discount_id_foreign` FOREIGN KEY (`happy_hour_discount_id`) REFERENCES `happy_hour_discounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_product_collection_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_product_collection_filter` (
  `department_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `department_product_collection_filter_department_id_foreign` (`department_id`),
  KEY `department_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `department_product_collection_filter_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `department_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `commission_percentage` decimal(5,2) unsigned DEFAULT NULL COMMENT 'This will configure the percentage of item amount the promoter will receive as commission. This will apply if the Company is configured to use commission type of ByDepartment',
  `discount_type` tinyint NOT NULL DEFAULT '1',
  `flat_commission` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `departments_code_company_id_unique` (`code`,`company_id`),
  KEY `departments_company_id_foreign` (`company_id`),
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `designations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `designations_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `designations_code_company_id_unique` (`code`,`company_id`),
  KEY `designations_company_id_foreign` (`company_id`),
  CONSTRAINT `designations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `digital_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module_id` bigint unsigned NOT NULL,
  `buyer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_tin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_identification_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_sst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `buyer_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `buyer_contact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `digital_invoices_module_type_module_id_index` (`module_type`,`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `director_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `director_location` (
  `director_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `director_location_director_id_foreign` (`director_id`),
  KEY `director_location_location_id_foreign` (`location_id`),
  CONSTRAINT `director_location_director_id_foreign` FOREIGN KEY (`director_id`) REFERENCES `directors` (`id`),
  CONSTRAINT `director_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `director_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `director_store` (
  `director_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `director_store_director_id_foreign` (`director_id`),
  KEY `director_store_store_id_foreign` (`store_id`),
  CONSTRAINT `director_store_director_id_foreign` FOREIGN KEY (`director_id`) REFERENCES `directors` (`id`),
  CONSTRAINT `director_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `directors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `directors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `passcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_override_type` tinyint NOT NULL DEFAULT '1',
  `price_override_limit_percentage_for_item` decimal(5,2) DEFAULT NULL,
  `price_override_limit_percentage_for_cart` decimal(5,2) NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `directors_employee_id_unique` (`employee_id`),
  CONSTRAINT `directors_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `draft_product_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `draft_product_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `approved_by_id` bigint DEFAULT NULL,
  `approved_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by_id` bigint DEFAULT NULL,
  `rejected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `draft_product_transactions_product_id_foreign` (`product_id`),
  CONSTRAINT `draft_product_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_employee_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_employee_group` (
  `dream_price_id` bigint unsigned NOT NULL,
  `employee_group_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `dream_price_employee_group_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_employee_group_employee_group_id_foreign` (`employee_group_id`),
  CONSTRAINT `dream_price_employee_group_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_employee_group_employee_group_id_foreign` FOREIGN KEY (`employee_group_id`) REFERENCES `employee_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_location` (
  `dream_price_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  KEY `dream_price_location_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_location_location_id_foreign` (`location_id`),
  CONSTRAINT `dream_price_location_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_location_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_member_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_member_group` (
  `dream_price_id` bigint unsigned NOT NULL,
  `member_group_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `dream_price_member_group_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_member_group_member_group_id_foreign` (`member_group_id`),
  CONSTRAINT `dream_price_member_group_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_member_group_member_group_id_foreign` FOREIGN KEY (`member_group_id`) REFERENCES `member_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dream_price_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dream_price_products_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_products_product_id_foreign` (`product_id`),
  CONSTRAINT `dream_price_products_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_sale_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_sale_channel` (
  `dream_price_id` bigint unsigned NOT NULL,
  `sale_channel_id` bigint unsigned NOT NULL,
  KEY `dream_price_sale_channel_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_sale_channel_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `dream_price_sale_channel_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_sale_channel_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_price_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_price_store` (
  `dream_price_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `dream_price_store_dream_price_id_foreign` (`dream_price_id`),
  KEY `dream_price_store_store_id_foreign` (`store_id`),
  CONSTRAINT `dream_price_store_dream_price_id_foreign` FOREIGN KEY (`dream_price_id`) REFERENCES `dream_prices` (`id`),
  CONSTRAINT `dream_price_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dream_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dream_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `allow_walk_in_member` tinyint(1) NOT NULL DEFAULT '0',
  `allow_registered_member` tinyint(1) NOT NULL DEFAULT '0',
  `allow_employee` tinyint(1) NOT NULL DEFAULT '0',
  `is_available_in_ecommerce` tinyint(1) NOT NULL DEFAULT '0',
  `is_available_in_pos` tinyint(1) NOT NULL DEFAULT '1',
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dream_prices_name_company_id_unique` (`name`,`company_id`),
  KEY `dream_prices_company_id_foreign` (`company_id`),
  CONSTRAINT `dream_prices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_id` bigint unsigned NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inventory_deduct_order_status` tinyint DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_locations_location_id_foreign` (`location_id`),
  CONSTRAINT `ecommerce_locations_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecommerce_stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecommerce_stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `inventory_deduct_order_status` tinyint DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecommerce_stores_store_id_foreign` (`store_id`),
  CONSTRAINT `ecommerce_stores_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `email_type_id` smallint NOT NULL COMMENT '1: Export-Inventory-Report, 2: Import-Records-Status-Updates, 3: Automated Notification',
  `receiver_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_configurations_company_id_foreign` (`company_id`),
  CONSTRAINT `email_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_group_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_group_promotion` (
  `promotion_id` bigint unsigned NOT NULL,
  `employee_group_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `employee_group_promotion_promotion_id_foreign` (`promotion_id`),
  KEY `employee_group_promotion_employee_group_id_foreign` (`employee_group_id`),
  CONSTRAINT `employee_group_promotion_employee_group_id_foreign` FOREIGN KEY (`employee_group_id`) REFERENCES `employee_groups` (`id`),
  CONSTRAINT `employee_group_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_purchase_limit` int NOT NULL DEFAULT '0',
  `purchase_limit_type_id` tinyint NOT NULL,
  `limit_reset_type_id` tinyint NOT NULL,
  `limit_reset` int NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `employee_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `status` tinyint(1) NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_transactions_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_transactions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `designation_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `home_contact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_joining` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_contact_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `membership_id` bigint unsigned DEFAULT NULL,
  `ic_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_type` tinyint NOT NULL COMMENT '1: Full-Time, 2: Part-Time',
  `spent_till_now` decimal(10,2) NOT NULL DEFAULT '0.00',
  `loyalty_points` bigint DEFAULT NULL,
  `total_sales` bigint DEFAULT '0',
  `total_earned_points` bigint DEFAULT '0',
  `total_redeemed_points` bigint DEFAULT '0',
  `total_expired_points` bigint DEFAULT '0',
  `card_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL COMMENT '0: Inactive, 1: Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_mobile_number_company_id_unique` (`mobile_number`,`company_id`),
  UNIQUE KEY `employees_card_number_company_id_unique` (`card_number`,`company_id`),
  UNIQUE KEY `employees_email_company_id_unique` (`email`,`company_id`),
  KEY `employees_company_id_foreign` (`company_id`),
  KEY `employees_membership_id_foreign` (`membership_id`),
  KEY `employees_designation_id_foreign` (`designation_id`),
  KEY `employees_group_id_foreign` (`group_id`),
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`),
  CONSTRAINT `employees_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `employee_groups` (`id`),
  CONSTRAINT `employees_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `export_record_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `export_record_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `downloaded_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `downloaded_by_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `export_record_transactions_company_id_foreign` (`company_id`),
  CONSTRAINT `export_record_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `export_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `export_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT 'The values will be obtained from the ExportRecordTypes enumeration.',
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` bigint NOT NULL,
  `module_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module_id` bigint DEFAULT NULL,
  `filters` json DEFAULT NULL,
  `headers` json DEFAULT NULL,
  `job_queued_at` datetime NOT NULL,
  `job_started_at` datetime DEFAULT NULL,
  `job_ended_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT 'The values will be obtained from the ExportRecordStatuses enumeration.',
  `total_records` int NOT NULL DEFAULT '0',
  `total_exported_records` int NOT NULL DEFAULT '0',
  `job_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `export_records_company_id_foreign` (`company_id`),
  CONSTRAINT `export_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_connection_id` bigint unsigned NOT NULL,
  `external_company_id` bigint NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_security_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `external_companies_external_connection_id_foreign` (`external_connection_id`),
  CONSTRAINT `external_companies_external_connection_id_foreign` FOREIGN KEY (`external_connection_id`) REFERENCES `external_connections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_connections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `create_by_super_admin_id` bigint unsigned DEFAULT NULL,
  `approve_by_super_admin_id` bigint unsigned DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0: Pending, 1:Approved, 2:Rejected',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_connections_name_unique` (`name`),
  UNIQUE KEY `external_connections_url_unique` (`url`),
  KEY `external_connections_create_by_super_admin_id_foreign` (`create_by_super_admin_id`),
  KEY `external_connections_approve_by_super_admin_id_foreign` (`approve_by_super_admin_id`),
  CONSTRAINT `external_connections_approve_by_super_admin_id_foreign` FOREIGN KEY (`approve_by_super_admin_id`) REFERENCES `super_admins` (`id`),
  CONSTRAINT `external_connections_create_by_super_admin_id_foreign` FOREIGN KEY (`create_by_super_admin_id`) REFERENCES `super_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_company_id` bigint unsigned NOT NULL,
  `old_external_location_id` int DEFAULT NULL,
  `type_id` int DEFAULT NULL,
  `external_location_id` int DEFAULT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `external_locations_external_company_id_foreign` (`external_company_id`),
  CONSTRAINT `external_locations_external_company_id_foreign` FOREIGN KEY (`external_company_id`) REFERENCES `external_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint NOT NULL,
  `external_company_id` bigint NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `upc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_details` json DEFAULT NULL,
  `status` int NOT NULL,
  `approved_by_id` bigint DEFAULT NULL,
  `approved_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejected_by_id` bigint DEFAULT NULL,
  `rejected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gift_card_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_card_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gift_card_id` bigint unsigned NOT NULL,
  `affected_by_id` int NOT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: Used, 2: Void Sale',
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gift_card_transactions_gift_card_id_foreign` (`gift_card_id`),
  CONSTRAINT `gift_card_transactions_gift_card_id_foreign` FOREIGN KEY (`gift_card_id`) REFERENCES `gift_cards` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gift_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL DEFAULT '1' COMMENT '1: Single Use Only, 2: Multiple Uses',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `available_amount` decimal(10,2) NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1: Active, 2: Used, 3: Expired',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gift_cards_number_company_id_unique` (`number`,`company_id`),
  KEY `gift_cards_company_id_foreign` (`company_id`),
  CONSTRAINT `gift_cards_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_received_note_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_received_note_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_received_note_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `purchase_amount_id` bigint unsigned NOT NULL,
  `unit_of_measure_derivative_id` bigint unsigned DEFAULT NULL,
  `input_quantity` decimal(10,2) DEFAULT NULL,
  `derivative_ratio` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_received_note_products_goods_received_note_id_foreign` (`goods_received_note_id`),
  KEY `goods_received_note_products_product_id_foreign` (`product_id`),
  KEY `goods_received_note_products_batch_id_foreign` (`batch_id`),
  KEY `goods_received_note_products_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `derivative_id` (`unit_of_measure_derivative_id`),
  CONSTRAINT `derivative_id` FOREIGN KEY (`unit_of_measure_derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`),
  CONSTRAINT `goods_received_note_products_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `goods_received_note_products_goods_received_note_id_foreign` FOREIGN KEY (`goods_received_note_id`) REFERENCES `goods_received_notes` (`id`),
  CONSTRAINT `goods_received_note_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `goods_received_note_products_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_received_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_received_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `old_location_id` bigint DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grn_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_order_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_order_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` bigint NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_received_notes_grn_reference_company_id_unique` (`grn_reference`,`company_id`),
  KEY `goods_received_notes_company_id_foreign` (`company_id`),
  KEY `goods_received_notes_vendor_id_foreign` (`vendor_id`),
  KEY `goods_received_notes_location_id_foreign` (`location_id`),
  CONSTRAINT `goods_received_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `goods_received_notes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `goods_received_notes_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `happy_hour_discount_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `happy_hour_discount_style` (
  `happy_hour_discount_id` bigint unsigned NOT NULL,
  `style_id` bigint unsigned NOT NULL,
  KEY `happy_hour_discount_style_happy_hour_discount_id_foreign` (`happy_hour_discount_id`),
  KEY `happy_hour_discount_style_style_id_foreign` (`style_id`),
  CONSTRAINT `happy_hour_discount_style_happy_hour_discount_id_foreign` FOREIGN KEY (`happy_hour_discount_id`) REFERENCES `happy_hour_discounts` (`id`),
  CONSTRAINT `happy_hour_discount_style_style_id_foreign` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `happy_hour_discount_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `happy_hour_discount_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `happy_hour_discount_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `authorizer_id` int NOT NULL,
  `authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `happy_hour_discount_transactions_happy_hour_discount_id_foreign` (`happy_hour_discount_id`),
  KEY `happy_hour_discount_transactions_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `happy_hour_discount_transactions_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `happy_hour_discount_transactions_happy_hour_discount_id_foreign` FOREIGN KEY (`happy_hour_discount_id`) REFERENCES `happy_hour_discounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `happy_hour_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `happy_hour_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_type_id` tinyint NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `happy_hour_discounts_company_id_foreign` (`company_id`),
  KEY `happy_hour_discounts_store_id_foreign` (`store_id`),
  KEY `happy_hour_discounts_location_id_foreign` (`location_id`),
  CONSTRAINT `happy_hour_discounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `happy_hour_discounts_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `happy_hour_discounts_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `health_check_result_history_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_check_result_history_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `check_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `short_summary` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json NOT NULL,
  `ended_at` timestamp NOT NULL,
  `batch` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `health_check_result_history_items_created_at_index` (`created_at`),
  KEY `health_check_result_history_items_batch_index` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_booking_payment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_booking_payment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hold_sale_detail_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hold_booking_payment_items_hold_sale_detail_id_foreign` (`hold_sale_detail_id`),
  KEY `hold_booking_payment_items_product_id_foreign` (`product_id`),
  CONSTRAINT `hold_booking_payment_items_hold_sale_detail_id_foreign` FOREIGN KEY (`hold_sale_detail_id`) REFERENCES `hold_sale_details` (`id`),
  CONSTRAINT `hold_booking_payment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sale_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_sale_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hold_sale_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `released_at` datetime DEFAULT NULL,
  `is_layaway` tinyint(1) NOT NULL DEFAULT '0',
  `layaway_pending_amount` decimal(10,2) DEFAULT NULL,
  `is_credit_sale` tinyint(1) NOT NULL DEFAULT '0',
  `credit_pending_amount` decimal(10,2) DEFAULT '0.00',
  `total_amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cart_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `items_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `round_off` decimal(5,2) NOT NULL DEFAULT '0.00',
  `change_due` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bill_reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_manager_id` bigint unsigned DEFAULT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extra_details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hold_sale_details_hold_sale_id_foreign` (`hold_sale_id`),
  KEY `hold_sale_details_store_manager_id_foreign` (`store_manager_id`),
  KEY `hold_sale_details_member_id_foreign` (`member_id`),
  CONSTRAINT `hold_sale_details_hold_sale_id_foreign` FOREIGN KEY (`hold_sale_id`) REFERENCES `hold_sales` (`id`),
  CONSTRAINT `hold_sale_details_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `hold_sale_details_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hold_sale_detail_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `derivative_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_sale_item_id` bigint unsigned DEFAULT NULL,
  `returned_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price_per_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cart_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `item_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_paid_per_unit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `group_id` int DEFAULT NULL,
  `is_exchange` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hold_sale_items_product_id_foreign` (`product_id`),
  KEY `hold_sale_items_derivative_id_foreign` (`derivative_id`),
  KEY `hold_sale_items_original_sale_item_id_foreign` (`original_sale_item_id`),
  KEY `hold_sale_items_hold_sale_detail_id_foreign` (`hold_sale_detail_id`),
  CONSTRAINT `hold_sale_items_derivative_id_foreign` FOREIGN KEY (`derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`),
  CONSTRAINT `hold_sale_items_hold_sale_detail_id_foreign` FOREIGN KEY (`hold_sale_detail_id`) REFERENCES `hold_sale_details` (`id`),
  CONSTRAINT `hold_sale_items_original_sale_item_id_foreign` FOREIGN KEY (`original_sale_item_id`) REFERENCES `sale_items` (`id`),
  CONSTRAINT `hold_sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_sale_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hold_sale_detail_id` bigint unsigned NOT NULL,
  `sale_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `sale_return_reason_id` decimal(10,2) DEFAULT NULL,
  `total_price_paid` decimal(10,2) DEFAULT NULL,
  `cart_discount_amount` decimal(10,2) DEFAULT NULL,
  `item_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_tax_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hold_sale_return_items_hold_sale_detail_id_foreign` (`hold_sale_detail_id`),
  KEY `hold_sale_return_items_sale_item_id_foreign` (`sale_item_id`),
  KEY `hold_sale_return_items_product_id_foreign` (`product_id`),
  CONSTRAINT `hold_sale_return_items_hold_sale_detail_id_foreign` FOREIGN KEY (`hold_sale_detail_id`) REFERENCES `hold_sale_details` (`id`),
  CONSTRAINT `hold_sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `hold_sale_return_items_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hold_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hold_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `type_id` int NOT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `complete_sale_id` bigint unsigned DEFAULT NULL,
  `complete_offline_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complete_sale_return_id` bigint unsigned DEFAULT NULL,
  `complete_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hold_sales_offline_id_unique` (`offline_id`),
  KEY `hold_sales_counter_update_id_foreign` (`counter_update_id`),
  KEY `hold_sales_complete_sale_id_foreign` (`complete_sale_id`),
  KEY `hold_sales_complete_sale_return_id_foreign` (`complete_sale_return_id`),
  CONSTRAINT `hold_sales_complete_sale_id_foreign` FOREIGN KEY (`complete_sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `hold_sales_complete_sale_return_id_foreign` FOREIGN KEY (`complete_sale_return_id`) REFERENCES `sale_returns` (`id`),
  CONSTRAINT `hold_sales_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_record_failed_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_record_failed_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `import_record_id` bigint unsigned NOT NULL,
  `row_data` json NOT NULL,
  `fail_reasons` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_record_failed_rows_import_record_id_foreign` (`import_record_id`),
  CONSTRAINT `import_record_failed_rows_import_record_id_foreign` FOREIGN KEY (`import_record_id`) REFERENCES `import_records` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `module_id` bigint DEFAULT NULL,
  `module_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` bigint NOT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` tinyint NOT NULL,
  `header_columns` json DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1:Pending, 2:In Progress, 3:Completed',
  `records_in_file` int NOT NULL DEFAULT '0' COMMENT 'this column value may not match with the sum of imported and failed records as the phpspreadsheet package counts extra rows sometimes',
  `records_imported` int NOT NULL DEFAULT '0',
  `records_failed` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_records_company_id_foreign` (`company_id`),
  CONSTRAINT `import_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `old_location_id` bigint DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reserved_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventories_product_id_location_id_unique` (`product_id`,`location_id`),
  UNIQUE KEY `inventories_product_id_location_id_location_type_unique` (`product_id`,`old_location_id`,`location_type`),
  KEY `inventories_product_id_foreign` (`product_id`),
  KEY `inventories_location_id_foreign` (`location_id`),
  CONSTRAINT `inventories_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_rollback_order_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_rollback_order_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ecommerce_location_id` bigint unsigned DEFAULT NULL,
  `ecommerce_store_id` bigint unsigned DEFAULT NULL,
  `order_status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_rollback_order_statuses_ecommerce_store_id_foreign` (`ecommerce_store_id`),
  KEY `inventory_rollback_order_statuses_ecommerce_location_id_foreign` (`ecommerce_location_id`),
  CONSTRAINT `inventory_rollback_order_statuses_ecommerce_location_id_foreign` FOREIGN KEY (`ecommerce_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `inventory_rollback_order_statuses_ecommerce_store_id_foreign` FOREIGN KEY (`ecommerce_store_id`) REFERENCES `ecommerce_stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reserved_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_units_inventory_id_foreign` (`inventory_id`),
  KEY `inventory_units_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `inventory_units_batch_id_foreign` (`batch_id`),
  CONSTRAINT `inventory_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `inventory_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `inventory_units_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `purchase_amount_id` bigint unsigned DEFAULT NULL,
  `old_location_id` int DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affected_by_id` bigint NOT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `happened_at` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `closing_stock` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_updates_product_id_foreign` (`product_id`),
  KEY `inventory_updates_batch_id_foreign` (`batch_id`),
  KEY `inventory_updates_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `inventory_updates_location_id_foreign` (`location_id`),
  CONSTRAINT `inventory_updates_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `inventory_updates_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `inventory_updates_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_updates_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_id` bigint unsigned NOT NULL,
  `location_id` bigint NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reserved_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_inventories_item_variant_id_location_id_unique` (`item_variant_id`,`location_id`),
  CONSTRAINT `item_inventories_item_variant_id_foreign` FOREIGN KEY (`item_variant_id`) REFERENCES `item_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_inventory_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_inventory_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `purchase_amount_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint NOT NULL,
  `affected_by_id` bigint NOT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `happened_at` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `closing_stock` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_inventory_updates_item_variant_id_foreign` (`item_variant_id`),
  KEY `item_inventory_updates_batch_id_foreign` (`batch_id`),
  KEY `item_inventory_updates_purchase_amount_id_foreign` (`purchase_amount_id`),
  CONSTRAINT `item_inventory_updates_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `item_inventory_updates_item_variant_id_foreign` FOREIGN KEY (`item_variant_id`) REFERENCES `item_variants` (`id`),
  CONSTRAINT `item_inventory_updates_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_tag` (
  `item_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  KEY `item_tag_item_id_foreign` (`item_id`),
  KEY `item_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `item_tag_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `item_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_variant_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_variant_bundles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_id` bigint unsigned NOT NULL,
  `package_type_id` bigint unsigned NOT NULL,
  `units` decimal(10,2) NOT NULL,
  `retail_price` decimal(10,2) DEFAULT NULL,
  `minimum_price` decimal(10,2) DEFAULT NULL,
  `staff_price` decimal(10,2) DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_variant_bundles_item_variant_id_foreign` (`item_variant_id`),
  KEY `item_variant_bundles_package_type_id_foreign` (`package_type_id`),
  CONSTRAINT `item_variant_bundles_item_variant_id_foreign` FOREIGN KEY (`item_variant_id`) REFERENCES `item_variants` (`id`),
  CONSTRAINT `item_variant_bundles_package_type_id_foreign` FOREIGN KEY (`package_type_id`) REFERENCES `package_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_variant_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_variant_loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_id` bigint unsigned NOT NULL,
  `membership_id` bigint unsigned NOT NULL,
  `points` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_variant_loyalty_points_item_variant_id_foreign` (`item_variant_id`),
  KEY `item_variant_loyalty_points_membership_id_foreign` (`membership_id`),
  CONSTRAINT `item_variant_loyalty_points_item_variant_id_foreign` FOREIGN KEY (`item_variant_id`) REFERENCES `item_variants` (`id`),
  CONSTRAINT `item_variant_loyalty_points_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_variant_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_variant_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_variant_id` bigint unsigned NOT NULL,
  `attribute_id` bigint unsigned NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_variant_values_item_variant_id_foreign` (`item_variant_id`),
  KEY `item_variant_values_attribute_id_foreign` (`attribute_id`),
  CONSTRAINT `item_variant_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`),
  CONSTRAINT `item_variant_values_item_variant_id_foreign` FOREIGN KEY (`item_variant_id`) REFERENCES `item_variants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `compound_item_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `upc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ean` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer_sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retail_price` decimal(14,6) DEFAULT NULL,
  `wholesale_price` decimal(14,6) DEFAULT NULL,
  `staff_price` decimal(14,6) NOT NULL DEFAULT '0.000000',
  `minimum_price` decimal(14,6) DEFAULT NULL,
  `purchase_cost` decimal(14,6) DEFAULT NULL,
  `online_price` decimal(14,6) DEFAULT NULL,
  `is_temporarily_unavailable` tinyint(1) NOT NULL,
  `is_available_in_pos` tinyint(1) NOT NULL DEFAULT '1',
  `is_available_in_ecommerce` tinyint(1) NOT NULL DEFAULT '0',
  `is_sold_as_single_item` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_variants_upc_unique` (`upc`),
  UNIQUE KEY `item_variants_code_unique` (`code`),
  KEY `item_variants_item_id_foreign` (`item_id`),
  KEY `item_variants_compound_item_name_index` (`compound_item_name`),
  CONSTRAINT `item_variants_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `variant_template_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `department_id` bigint unsigned DEFAULT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `unit_of_measure_id` bigint unsigned DEFAULT NULL,
  `article_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` tinyint NOT NULL DEFAULT '1',
  `has_batch` tinyint(1) NOT NULL,
  `is_non_inventory` tinyint(1) NOT NULL,
  `is_non_selling_item` tinyint(1) NOT NULL DEFAULT '0',
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_created_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_article_number_company_id_unique` (`article_number`,`company_id`),
  UNIQUE KEY `items_code_unique` (`code`),
  UNIQUE KEY `items_article_number_unique` (`article_number`),
  KEY `items_company_id_foreign` (`company_id`),
  KEY `items_brand_id_foreign` (`brand_id`),
  KEY `items_variant_template_id_foreign` (`variant_template_id`),
  KEY `items_vendor_id_foreign` (`vendor_id`),
  KEY `items_unit_of_measure_id_foreign` (`unit_of_measure_id`),
  KEY `items_department_id_foreign` (`department_id`),
  CONSTRAINT `items_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `items_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `items_unit_of_measure_id_foreign` FOREIGN KEY (`unit_of_measure_id`) REFERENCES `unit_of_measures` (`id`),
  CONSTRAINT `items_variant_template_id_foreign` FOREIGN KEY (`variant_template_id`) REFERENCES `templates` (`id`),
  CONSTRAINT `items_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_native` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dir` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_loyalty_campaign_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_loyalty_campaign_configuration` (
  `location_id` bigint unsigned NOT NULL,
  `loyalty_campaign_configuration_id` bigint unsigned NOT NULL,
  KEY `location_loyalty_campaign_configuration_location_id_foreign` (`location_id`),
  CONSTRAINT `location_loyalty_campaign_configuration_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_manual_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_manual_notification` (
  `location_id` bigint unsigned NOT NULL,
  `manual_notification_id` bigint unsigned NOT NULL,
  KEY `location_manual_notification_location_id_foreign` (`location_id`),
  KEY `location_manual_notification_manual_notification_id_foreign` (`manual_notification_id`),
  CONSTRAINT `location_manual_notification_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_manual_notification_manual_notification_id_foreign` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_pos_advertisement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_pos_advertisement` (
  `location_id` bigint unsigned NOT NULL,
  `pos_advertisement_id` bigint unsigned NOT NULL,
  KEY `location_pos_advertisement_location_id_foreign` (`location_id`),
  KEY `location_pos_advertisement_pos_advertisement_id_foreign` (`pos_advertisement_id`),
  CONSTRAINT `location_pos_advertisement_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_pos_advertisement_pos_advertisement_id_foreign` FOREIGN KEY (`pos_advertisement_id`) REFERENCES `pos_advertisements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_promoter` (
  `location_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  KEY `location_promoter_location_id_foreign` (`location_id`),
  KEY `location_promoter_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `location_promoter_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_promotion` (
  `location_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  KEY `location_promotion_location_id_foreign` (`location_id`),
  KEY `location_promotion_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `location_promotion_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_sale_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_sale_channel` (
  `location_id` bigint unsigned NOT NULL,
  `sale_channel_id` bigint unsigned NOT NULL,
  KEY `location_sale_channel_location_id_foreign` (`location_id`),
  KEY `location_sale_channel_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `location_sale_channel_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_sale_channel_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_sale_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_sale_target` (
  `location_id` bigint unsigned NOT NULL,
  `sale_target_id` bigint unsigned NOT NULL,
  KEY `location_sale_target_location_id_foreign` (`location_id`),
  KEY `location_sale_target_sale_target_id_foreign` (`sale_target_id`),
  CONSTRAINT `location_sale_target_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_sale_target_sale_target_id_foreign` FOREIGN KEY (`sale_target_id`) REFERENCES `sale_targets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_store_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_store_manager` (
  `location_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  KEY `location_store_manager_location_id_foreign` (`location_id`),
  KEY `location_store_manager_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `location_store_manager_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_store_manager_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_warehouse_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_warehouse_manager` (
  `location_id` bigint unsigned NOT NULL,
  `warehouse_manager_id` bigint unsigned NOT NULL,
  KEY `location_warehouse_manager_location_id_foreign` (`location_id`),
  KEY `location_warehouse_manager_warehouse_manager_id_foreign` (`warehouse_manager_id`),
  CONSTRAINT `location_warehouse_manager_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `location_warehouse_manager_warehouse_manager_id_foreign` FOREIGN KEY (`warehouse_manager_id`) REFERENCES `warehouse_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL COMMENT '1: Store, 2: Warehouse',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `registration_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `web_site` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_tax_percentage` decimal(5,2) DEFAULT NULL,
  `sales_return_days_limit` int DEFAULT '0',
  `credit_note_expiration_days` int DEFAULT '0',
  `loyalty_point_expiration_days` int DEFAULT NULL,
  `is_automatic_day_close` tinyint DEFAULT '0',
  `automatic_day_close_time` time DEFAULT NULL,
  `receipt_footer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disclaimer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cash_out_limit_info` decimal(10,2) DEFAULT '0.00',
  `cash_out_limit_warning` decimal(10,2) DEFAULT '0.00',
  `cash_out_limit_restrict` decimal(10,2) DEFAULT '0.00',
  `enable_ioi_city_mall_data_sharing` tinyint DEFAULT '0',
  `ioi_city_mall_machine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable_trx_mall_data_sharing` tinyint DEFAULT '0',
  `trx_mall_machine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_fall_down_percentage` decimal(5,2) DEFAULT '80.00',
  `share_inventory_to_external_companies` tinyint NOT NULL DEFAULT '0',
  `open_time` time DEFAULT NULL,
  `close_time` time DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` int DEFAULT NULL,
  `ref_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_name_company_id_type_id_unique` (`name`,`company_id`,`type_id`),
  UNIQUE KEY `locations_phone_company_id_type_id_unique` (`phone`,`company_id`,`type_id`),
  UNIQUE KEY `locations_code_company_id_type_id_unique` (`code`,`company_id`,`type_id`),
  UNIQUE KEY `locations_uuid_unique` (`uuid`),
  KEY `locations_company_id_foreign` (`company_id`),
  KEY `locations_region_id_foreign` (`region_id`),
  KEY `locations_country_id_foreign` (`country_id`),
  KEY `locations_state_id_foreign` (`state_id`),
  KEY `locations_city_id_foreign` (`city_id`),
  CONSTRAINT `locations_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `locations_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `locations_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `locations_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_campaign_configuration_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_campaign_configuration_product` (
  `product_id` bigint unsigned NOT NULL,
  `loyalty_campaign_configuration_id` bigint unsigned NOT NULL,
  KEY `loyalty_campaign_configuration_product_product_id_foreign` (`product_id`),
  CONSTRAINT `loyalty_campaign_configuration_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_campaign_configuration_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_campaign_configuration_store` (
  `store_id` bigint unsigned NOT NULL,
  `loyalty_campaign_configuration_id` bigint unsigned NOT NULL,
  KEY `loyalty_campaign_configuration_store_store_id_foreign` (`store_id`),
  CONSTRAINT `loyalty_campaign_configuration_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_campaign_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_campaign_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `loyalty_campaign_type` tinyint NOT NULL,
  `point_earned` int NOT NULL,
  `minimum_purchase_amount` decimal(10,2) NOT NULL,
  `expiration_type` tinyint NOT NULL,
  `include_tax` tinyint(1) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loyalty_campaign_configurations_description_company_id_unique` (`description`,`company_id`),
  KEY `loyalty_campaign_configurations_company_id_foreign` (`company_id`),
  CONSTRAINT `loyalty_campaign_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_spend_amount` decimal(10,2) NOT NULL,
  `loyalty_points` int NOT NULL,
  `loyalty_point_expiration_days` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loyalty_campaigns_name_company_id_unique` (`name`,`company_id`),
  KEY `loyalty_campaigns_company_id_foreign` (`company_id`),
  CONSTRAINT `loyalty_campaigns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_point_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_point_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned DEFAULT NULL,
  `loyalty_point_id` bigint unsigned DEFAULT NULL,
  `affected_by_id` int DEFAULT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: Sale, 2: Sale Return, 3: Used, 4: Expired',
  `points` bigint NOT NULL,
  `closing_loyalty_points_balance` bigint NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_point_updates_member_id_foreign` (`member_id`),
  KEY `loyalty_point_updates_loyalty_point_id_foreign` (`loyalty_point_id`),
  CONSTRAINT `loyalty_point_updates_loyalty_point_id_foreign` FOREIGN KEY (`loyalty_point_id`) REFERENCES `loyalty_points` (`id`),
  CONSTRAINT `loyalty_point_updates_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned DEFAULT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `loyalty_campaign_id` bigint unsigned DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `points` bigint NOT NULL,
  `available_points` bigint NOT NULL,
  `minimum_spend_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_points_sale_id_foreign` (`sale_id`),
  KEY `loyalty_points_loyalty_campaign_id_foreign` (`loyalty_campaign_id`),
  KEY `loyalty_points_member_id_foreign` (`member_id`),
  CONSTRAINT `loyalty_points_loyalty_campaign_id_foreign` FOREIGN KEY (`loyalty_campaign_id`) REFERENCES `loyalty_campaigns` (`id`),
  CONSTRAINT `loyalty_points_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `loyalty_points_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_member` (
  `manual_notification_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  KEY `fk_mn_ms_manual_notification_id` (`manual_notification_id`),
  KEY `manual_notification_member_member_id_foreign` (`member_id`),
  CONSTRAINT `fk_mn_ms_manual_notification_id` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`),
  CONSTRAINT `manual_notification_member_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_member_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_member_group` (
  `manual_notification_id` bigint unsigned NOT NULL,
  `member_group_id` bigint unsigned NOT NULL,
  KEY `fk_mn_mg_manual_notification_id` (`manual_notification_id`),
  KEY `manual_notification_member_group_member_group_id_foreign` (`member_group_id`),
  CONSTRAINT `fk_mn_mg_manual_notification_id` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`),
  CONSTRAINT `manual_notification_member_group_member_group_id_foreign` FOREIGN KEY (`member_group_id`) REFERENCES `member_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_member_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_member_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  `manual_notification_id` bigint unsigned NOT NULL,
  `member_type_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_mn_mt_manual_notification_id` (`manual_notification_id`),
  CONSTRAINT `fk_mn_mt_manual_notification_id` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_promoter` (
  `manual_notification_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  KEY `manual_notification_promoter_manual_notification_id_foreign` (`manual_notification_id`),
  KEY `manual_notification_promoter_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `manual_notification_promoter_manual_notification_id_foreign` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`),
  CONSTRAINT `manual_notification_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_promoter_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_promoter_group` (
  `manual_notification_id` bigint unsigned NOT NULL,
  `promoter_group_id` bigint unsigned NOT NULL,
  KEY `fk_mn_pg_manual_notification_id` (`manual_notification_id`),
  KEY `manual_notification_promoter_group_promoter_group_id_foreign` (`promoter_group_id`),
  CONSTRAINT `fk_mn_pg_manual_notification_id` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`),
  CONSTRAINT `manual_notification_promoter_group_promoter_group_id_foreign` FOREIGN KEY (`promoter_group_id`) REFERENCES `promoter_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notification_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notification_store` (
  `manual_notification_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  KEY `manual_notification_store_manual_notification_id_foreign` (`manual_notification_id`),
  KEY `manual_notification_store_store_id_foreign` (`store_id`),
  CONSTRAINT `manual_notification_store_manual_notification_id_foreign` FOREIGN KEY (`manual_notification_id`) REFERENCES `manual_notifications` (`id`),
  CONSTRAINT `manual_notification_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manual_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '1: Pending, 2: In Progress, 3: Completed',
  `type_id` tinyint NOT NULL,
  `member_filter_type_id` tinyint DEFAULT NULL,
  `promoter_filter_type_id` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manual_notifications_company_id_foreign` (`company_id`),
  CONSTRAINT `manual_notifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_mobile_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_addresses_member_id_foreign` (`member_id`),
  CONSTRAINT `member_addresses_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_group_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_group_promotion` (
  `promotion_id` bigint unsigned NOT NULL,
  `member_group_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `member_group_promotion_promotion_id_foreign` (`promotion_id`),
  KEY `member_group_promotion_member_group_id_foreign` (`member_group_id`),
  CONSTRAINT `member_group_promotion_member_group_id_foreign` FOREIGN KEY (`member_group_id`) REFERENCES `member_groups` (`id`),
  CONSTRAINT `member_group_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `member_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `type_id` tinyint DEFAULT NULL,
  `title_id` tinyint DEFAULT NULL,
  `race_id` tinyint DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender_id` tinyint DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `mobile_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_registration_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_tax_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` bigint DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_id` tinyint NOT NULL DEFAULT '1',
  `created_store_id` bigint unsigned DEFAULT NULL,
  `created_location_id` bigint unsigned DEFAULT NULL,
  `last_purchase_date` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_orders` int NOT NULL DEFAULT '0',
  `spent_till_now` decimal(10,2) NOT NULL DEFAULT '0.00',
  `loyalty_points` bigint DEFAULT NULL,
  `membership_id` bigint unsigned DEFAULT NULL,
  `card_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birthday_voucher_last_generated_at` date DEFAULT NULL,
  `last_birthday_voucher_id` bigint unsigned DEFAULT NULL,
  `welcome_member_voucher_generated_at` datetime DEFAULT NULL,
  `welcome_member_voucher_id` bigint unsigned DEFAULT NULL,
  `otp` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expire_date` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `total_sales` bigint DEFAULT '0',
  `total_earned_points` bigint DEFAULT '0',
  `total_redeemed_points` bigint DEFAULT '0',
  `total_expired_points` bigint DEFAULT '0',
  `status` int NOT NULL DEFAULT '1' COMMENT '1:Active, 2:Deleted by user, 3:Deleted by admin, 4:Inactive',
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_contact` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_mobile_number_company_id_unique` (`mobile_number`,`company_id`),
  UNIQUE KEY `customers_card_number_company_id_unique` (`card_number`,`company_id`),
  KEY `customers_company_id_foreign` (`company_id`),
  KEY `customers_created_store_id_foreign` (`created_store_id`),
  KEY `customers_membership_id_foreign` (`membership_id`),
  KEY `members_last_birthday_voucher_id_foreign` (`last_birthday_voucher_id`),
  KEY `members_welcome_member_voucher_id_foreign` (`welcome_member_voucher_id`),
  KEY `members_group_id_foreign` (`group_id`),
  KEY `members_employee_id_foreign` (`employee_id`),
  KEY `members_created_location_id_foreign` (`created_location_id`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `customers_created_store_id_foreign` FOREIGN KEY (`created_store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `customers_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`),
  CONSTRAINT `members_created_location_id_foreign` FOREIGN KEY (`created_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `members_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `member_groups` (`id`),
  CONSTRAINT `members_last_birthday_voucher_id_foreign` FOREIGN KEY (`last_birthday_voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `members_welcome_member_voucher_id_foreign` FOREIGN KEY (`welcome_member_voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `membership_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membership_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `membership_assignments_membership_id_foreign` (`membership_id`),
  KEY `membership_assignments_member_id_foreign` (`member_id`),
  CONSTRAINT `membership_assignments_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `membership_assignments_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `membership_voucher_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_voucher_configuration` (
  `voucher_configuration_id` bigint unsigned NOT NULL,
  `membership_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `voucher_configuration_id` (`voucher_configuration_id`),
  KEY `membership_id` (`membership_id`),
  CONSTRAINT `membership_id` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`),
  CONSTRAINT `voucher_configuration_id` FOREIGN KEY (`voucher_configuration_id`) REFERENCES `voucher_configurations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lifetime_value` decimal(10,2) NOT NULL,
  `loyalty_points_per_currency_unit` int NOT NULL COMMENT 'Loyalty Points per RM1',
  `min_loyalty_points_for_redemption` int DEFAULT NULL,
  `max_loyalty_points_for_redemption` int DEFAULT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `memberships_name_company_id_unique` (`name`,`company_id`),
  KEY `memberships_company_id_foreign` (`company_id`),
  CONSTRAINT `memberships_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merge_member_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merge_member_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_member_id` bigint unsigned NOT NULL,
  `new_member_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merge_member_transactions_old_member_id_foreign` (`old_member_id`),
  KEY `merge_member_transactions_new_member_id_foreign` (`new_member_id`),
  CONSTRAINT `merge_member_transactions_new_member_id_foreign` FOREIGN KEY (`new_member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `merge_member_transactions_old_member_id_foreign` FOREIGN KEY (`old_member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merge_product_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merge_product_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_product_id` bigint unsigned NOT NULL,
  `new_product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merge_product_transactions_old_product_id_foreign` (`old_product_id`),
  KEY `merge_product_transactions_new_product_id_foreign` (`new_product_id`),
  CONSTRAINT `merge_product_transactions_new_product_id_foreign` FOREIGN KEY (`new_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `merge_product_transactions_old_product_id_foreign` FOREIGN KEY (`old_product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `from_user_id` bigint DEFAULT NULL,
  `from_user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_user_id` bigint NOT NULL,
  `to_user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `mark_as_read_at` datetime DEFAULT NULL,
  `mark_as_read_by_id` bigint DEFAULT NULL,
  `mark_as_read_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_company_id_foreign` (`company_id`),
  CONSTRAINT `notifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `client_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `client_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scopes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redirect` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `online_sales_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_sales_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `charge_type_id` tinyint NOT NULL COMMENT '1: Shipping Charge, 2: Picking Charge, 3: Others',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_amount` decimal(10,2) NOT NULL,
  `maximum_amount` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `online_sales_charges_company_id_foreign` (`company_id`),
  CONSTRAINT `online_sales_charges_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT 'Follow The OrderAddressesType Enum For More.',
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `city_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_addresses_order_id_foreign` (`order_id`),
  KEY `order_addresses_country_id_foreign` (`country_id`),
  KEY `order_addresses_state_id_foreign` (`state_id`),
  KEY `order_addresses_city_id_foreign` (`city_id`),
  CONSTRAINT `order_addresses_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `order_addresses_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `order_addresses_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_addresses_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_channel_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_channel_references` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_channel_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `external_order_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_channel_references_sale_channel_id_foreign` (`sale_channel_id`),
  KEY `order_channel_references_order_id_foreign` (`order_id`),
  CONSTRAINT `order_channel_references_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_channel_references_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_credit_note_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_credit_note_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_id` tinyint NOT NULL,
  `order_credit_note_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `order_payment_id` bigint unsigned NOT NULL,
  `amount` decimal(16,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_credit_note_transactions_order_credit_note_id_foreign` (`order_credit_note_id`),
  KEY `order_credit_note_transactions_store_manager_id_foreign` (`store_manager_id`),
  KEY `order_credit_note_transactions_store_id_foreign` (`store_id`),
  KEY `order_credit_note_transactions_order_payment_id_foreign` (`order_payment_id`),
  KEY `order_credit_note_transactions_location_id_foreign` (`location_id`),
  CONSTRAINT `order_credit_note_transactions_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `order_credit_note_transactions_order_credit_note_id_foreign` FOREIGN KEY (`order_credit_note_id`) REFERENCES `order_credit_notes` (`id`),
  CONSTRAINT `order_credit_note_transactions_order_payment_id_foreign` FOREIGN KEY (`order_payment_id`) REFERENCES `order_payments` (`id`),
  CONSTRAINT `order_credit_note_transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `order_credit_note_transactions_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_credit_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_manager_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `order_return_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned NOT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `total_amount` decimal(16,6) NOT NULL,
  `available_amount` decimal(16,6) NOT NULL,
  `status` tinyint NOT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_credit_notes_store_manager_id_foreign` (`store_manager_id`),
  KEY `order_credit_notes_store_id_foreign` (`store_id`),
  KEY `order_credit_notes_order_return_id_foreign` (`order_return_id`),
  KEY `order_credit_notes_member_id_foreign` (`member_id`),
  KEY `order_credit_notes_location_id_foreign` (`location_id`),
  CONSTRAINT `order_credit_notes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `order_credit_notes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `order_credit_notes_order_return_id_foreign` FOREIGN KEY (`order_return_id`) REFERENCES `order_returns` (`id`),
  CONSTRAINT `order_credit_notes_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `order_credit_notes_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item_assembly_child_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item_assembly_child_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint unsigned NOT NULL,
  `child_product_id` bigint unsigned NOT NULL,
  `units` decimal(16,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_assembly_child_products_order_item_id_foreign` (`order_item_id`),
  KEY `fk_product_child_product_id` (`child_product_id`),
  CONSTRAINT `fk_product_child_product_id` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_item_assembly_child_products_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item_exchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item_exchanges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint unsigned DEFAULT NULL,
  `old_item_price` decimal(14,6) NOT NULL,
  `current_item_price` decimal(14,6) NOT NULL,
  `price_differences` decimal(14,6) NOT NULL,
  `old_discount_amount` decimal(14,6) NOT NULL,
  `old_item_tax` decimal(14,6) NOT NULL,
  `current_item_tax` decimal(14,6) NOT NULL,
  `tax_differences` decimal(14,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_exchanges_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `order_item_exchanges_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item_promoter` (
  `order_item_id` bigint unsigned DEFAULT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `order_item_promoter_promoter_id_foreign` (`promoter_id`),
  KEY `order_item_promoter_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `order_item_promoter_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `order_item_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_item_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint unsigned DEFAULT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(14,6) NOT NULL,
  `return_quantity` decimal(14,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_units_inventory_id_foreign` (`inventory_id`),
  KEY `order_item_units_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `order_item_units_batch_id_foreign` (`batch_id`),
  KEY `order_item_units_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `order_item_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `order_item_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `order_item_units_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `order_item_units_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `exchange_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,6) NOT NULL,
  `complimentary_item_reason_id` bigint unsigned DEFAULT NULL,
  `promotion_id` bigint unsigned DEFAULT NULL,
  `original_product_price_per_unit` decimal(14,6) NOT NULL,
  `cart_discount_amount` decimal(14,6) NOT NULL,
  `item_discount_amount` decimal(14,6) NOT NULL,
  `total_discount_amount` decimal(14,6) NOT NULL,
  `item_tax_amount` decimal(14,6) NOT NULL,
  `price_paid_per_unit` decimal(14,6) NOT NULL,
  `total_price_paid` decimal(14,6) NOT NULL,
  `vendor_commission_percentage` decimal(10,2) DEFAULT NULL,
  `is_exchange` decimal(14,6) DEFAULT NULL,
  `product_bundle_units` decimal(16,6) DEFAULT NULL,
  `product_bundle_id` bigint unsigned DEFAULT NULL,
  `product_bundle_package_type_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_complimentary_item_reason_id_foreign` (`complimentary_item_reason_id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_exchange_item_id_foreign` (`exchange_item_id`),
  KEY `order_items_promotion_id_foreign` (`promotion_id`),
  KEY `order_items_product_bundle_id_foreign` (`product_bundle_id`),
  KEY `order_items_product_bundle_package_type_id_foreign` (`product_bundle_package_type_id`),
  CONSTRAINT `order_items_complimentary_item_reason_id_foreign` FOREIGN KEY (`complimentary_item_reason_id`) REFERENCES `complimentary_item_reasons` (`id`),
  CONSTRAINT `order_items_exchange_item_id_foreign` FOREIGN KEY (`exchange_item_id`) REFERENCES `order_return_items` (`id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_product_bundle_id_foreign` FOREIGN KEY (`product_bundle_id`) REFERENCES `product_bundles` (`id`),
  CONSTRAINT `order_items_product_bundle_package_type_id_foreign` FOREIGN KEY (`product_bundle_package_type_id`) REFERENCES `package_types` (`id`),
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_items_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `store_manager_id` bigint unsigned DEFAULT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `amount` decimal(14,6) NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `store_day_close_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_payments_store_manager_id_foreign` (`store_manager_id`),
  KEY `order_payments_store_id_foreign` (`store_id`),
  KEY `order_payments_payment_type_id_foreign` (`payment_type_id`),
  KEY `order_payments_order_id_foreign` (`order_id`),
  KEY `order_payments_store_day_close_id_foreign` (`store_day_close_id`),
  KEY `order_payments_location_id_foreign` (`location_id`),
  CONSTRAINT `order_payments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_payments_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`),
  CONSTRAINT `order_payments_store_day_close_id_foreign` FOREIGN KEY (`store_day_close_id`) REFERENCES `store_day_closes` (`id`),
  CONSTRAINT `order_payments_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `order_payments_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_picking_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_picking_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_picking_list_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_picking_list_items_order_picking_list_id_foreign` (`order_picking_list_id`),
  KEY `order_picking_list_items_order_id_foreign` (`order_id`),
  CONSTRAINT `order_picking_list_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_picking_list_items_order_picking_list_id_foreign` FOREIGN KEY (`order_picking_list_id`) REFERENCES `order_picking_lists` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_picking_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_picking_lists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_picking_lists_company_id_foreign` (`company_id`),
  CONSTRAINT `order_picking_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_return_id` bigint unsigned DEFAULT NULL,
  `original_order_item_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `order_return_reason_id` bigint unsigned NOT NULL,
  `quantity` decimal(14,6) NOT NULL,
  `total_price_paid` decimal(16,6) DEFAULT NULL,
  `cart_discount_amount` decimal(14,6) NOT NULL,
  `item_discount_amount` decimal(14,6) NOT NULL,
  `total_discount_amount` decimal(14,6) NOT NULL,
  `total_tax_amount` decimal(14,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_return_items_product_id_foreign` (`product_id`),
  KEY `order_return_items_order_return_reason_id_foreign` (`order_return_reason_id`),
  KEY `order_return_items_order_return_id_foreign` (`order_return_id`),
  KEY `order_return_items_original_order_item_id_foreign` (`original_order_item_id`),
  CONSTRAINT `order_return_items_order_return_id_foreign` FOREIGN KEY (`order_return_id`) REFERENCES `order_returns` (`id`),
  CONSTRAINT `order_return_items_order_return_reason_id_foreign` FOREIGN KEY (`order_return_reason_id`) REFERENCES `sale_return_reasons` (`id`),
  CONSTRAINT `order_return_items_original_order_item_id_foreign` FOREIGN KEY (`original_order_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `order_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_manager_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `receipt_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_order_id` bigint unsigned DEFAULT NULL,
  `total_tax_amount` decimal(14,6) NOT NULL,
  `cart_discount_amount` decimal(14,6) NOT NULL,
  `item_discount_amount` decimal(14,6) NOT NULL,
  `total_discount_amount` decimal(14,6) NOT NULL,
  `total_amount_before_round_off` decimal(14,6) NOT NULL,
  `round_off_amount` decimal(14,6) NOT NULL,
  `total_price_paid` decimal(14,6) NOT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_returns_store_manager_id_foreign` (`store_manager_id`),
  KEY `order_returns_store_id_foreign` (`store_id`),
  KEY `order_returns_member_id_foreign` (`member_id`),
  KEY `order_returns_original_order_id_foreign` (`original_order_id`),
  KEY `order_returns_location_id_foreign` (`location_id`),
  CONSTRAINT `order_returns_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `order_returns_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `order_returns_original_order_id_foreign` FOREIGN KEY (`original_order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_returns_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `order_returns_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_manager_id` bigint unsigned DEFAULT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `order_return_id` bigint unsigned DEFAULT NULL,
  `store_day_close_id` bigint unsigned DEFAULT NULL,
  `receipt_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_tax_amount` decimal(14,6) NOT NULL,
  `cart_discount_amount` decimal(14,6) NOT NULL,
  `item_discount_amount` decimal(14,6) NOT NULL,
  `total_discount_amount` decimal(14,6) NOT NULL,
  `credit_pending_amount` decimal(16,6) DEFAULT NULL,
  `credit_completed_at` date DEFAULT NULL,
  `layaway_pending_amount` decimal(16,6) DEFAULT NULL,
  `layaway_completed_at` date DEFAULT NULL,
  `total_amount_before_round_off` decimal(14,6) NOT NULL,
  `round_off` decimal(14,6) NOT NULL,
  `total_amount_paid` decimal(14,6) NOT NULL,
  `delivery_charges` decimal(14,6) NOT NULL DEFAULT '0.000000',
  `type_id` tinyint NOT NULL COMMENT 'Order Type Enum Is Used.',
  `channel_id` tinyint NOT NULL COMMENT 'Order Channel Enum Is Used.',
  `sale_channel_id` bigint unsigned DEFAULT NULL,
  `status` tinyint DEFAULT NULL,
  `cancel_order_reason_id` bigint unsigned DEFAULT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bill_reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_store_id` bigint unsigned DEFAULT NULL,
  `pickup_location_id` bigint unsigned DEFAULT NULL,
  `tracking_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipment_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courier_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_store_manager_id_foreign` (`store_manager_id`),
  KEY `orders_store_id_foreign` (`store_id`),
  KEY `orders_member_id_foreign` (`member_id`),
  KEY `orders_cancel_order_reason_id_foreign` (`cancel_order_reason_id`),
  KEY `orders_order_return_id_foreign` (`order_return_id`),
  KEY `orders_store_day_close_id_foreign` (`store_day_close_id`),
  KEY `fk_store_pickup_store_id` (`pickup_store_id`),
  KEY `orders_sale_channel_id_foreign` (`sale_channel_id`),
  KEY `orders_location_id_foreign` (`location_id`),
  KEY `fk_location_pickup_location_id` (`pickup_location_id`),
  CONSTRAINT `fk_location_pickup_location_id` FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `fk_store_pickup_store_id` FOREIGN KEY (`pickup_store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `orders_cancel_order_reason_id_foreign` FOREIGN KEY (`cancel_order_reason_id`) REFERENCES `void_sale_reasons` (`id`),
  CONSTRAINT `orders_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `orders_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `orders_order_return_id_foreign` FOREIGN KEY (`order_return_id`) REFERENCES `order_returns` (`id`),
  CONSTRAINT `orders_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`),
  CONSTRAINT `orders_store_day_close_id_foreign` FOREIGN KEY (`store_day_close_id`) REFERENCES `store_day_closes` (`id`),
  CONSTRAINT `orders_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `orders_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `package_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `package_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_types_company_id_foreign` (`company_id`),
  CONSTRAINT `package_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partially_receive_fulfillment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partially_receive_fulfillment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partially_receive_fulfillment_id` bigint unsigned NOT NULL,
  `purchase_order_fulfillment_item_id` bigint unsigned NOT NULL,
  `received_quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fK_prfi_prf_partially_receive_fulfillment_id` (`partially_receive_fulfillment_id`),
  KEY `fK_prfi_pofi_purchase_order_fulfillment_item_id` (`purchase_order_fulfillment_item_id`),
  CONSTRAINT `fK_prfi_pofi_purchase_order_fulfillment_item_id` FOREIGN KEY (`purchase_order_fulfillment_item_id`) REFERENCES `purchase_order_fulfillment_items` (`id`),
  CONSTRAINT `fK_prfi_prf_partially_receive_fulfillment_id` FOREIGN KEY (`partially_receive_fulfillment_id`) REFERENCES `partially_receive_fulfillments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partially_receive_fulfillments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partially_receive_fulfillments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_id` bigint unsigned NOT NULL,
  `received_by_user_id` bigint NOT NULL,
  `received_by_user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fK_prf_pof_purchase_order_fulfillment_id` (`purchase_order_fulfillment_id`),
  CONSTRAINT `fK_prf_pof_purchase_order_fulfillment_id` FOREIGN KEY (`purchase_order_fulfillment_id`) REFERENCES `purchase_order_fulfillments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `past_year_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `past_year_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `date` date DEFAULT NULL,
  `sale_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sale` int NOT NULL DEFAULT '0',
  `units_sold` decimal(10,2) NOT NULL DEFAULT '0.00',
  `return_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `units_return` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_sales` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `past_year_data_company_id_foreign` (`company_id`),
  KEY `past_year_data_store_id_foreign` (`store_id`),
  KEY `past_year_data_brand_id_foreign` (`brand_id`),
  KEY `past_year_data_location_id_foreign` (`location_id`),
  CONSTRAINT `past_year_data_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `past_year_data_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `past_year_data_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `past_year_data_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `parent_payment_type_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_member_required` tinyint(1) NOT NULL,
  `is_available_for_refund` tinyint(1) NOT NULL,
  `trigger_card_payment_machine` tinyint(1) NOT NULL DEFAULT '0',
  `trigger_qr_code_payment_machine` tinyint(1) NOT NULL DEFAULT '0',
  `trigger_card_affin_payment_machine` tinyint(1) NOT NULL DEFAULT '0',
  `is_card_payment` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL,
  `payment_terminal_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trigger_card_bank_rakyat_terminal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_types_name_company_id_unique` (`name`,`company_id`),
  KEY `payment_types_company_id_foreign` (`company_id`),
  KEY `payment_types_parent_payment_type_id_foreign` (`parent_payment_type_id`),
  CONSTRAINT `payment_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `payment_types_parent_payment_type_id_foreign` FOREIGN KEY (`parent_payment_type_id`) REFERENCES `payment_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_advertisement_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_advertisement_store` (
  `store_id` bigint unsigned NOT NULL,
  `pos_advertisement_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `pos_advertisement_store_store_id_foreign` (`store_id`),
  KEY `pos_advertisement_store_pos_advertisement_id_foreign` (`pos_advertisement_id`),
  CONSTRAINT `pos_advertisement_store_pos_advertisement_id_foreign` FOREIGN KEY (`pos_advertisement_id`) REFERENCES `pos_advertisements` (`id`),
  CONSTRAINT `pos_advertisement_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_advertisements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_advertisements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT '1:Image,2:Video',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '0:Inactive, 1:Active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pos_advertisements_company_id_foreign` (`company_id`),
  CONSTRAINT `pos_advertisements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pos_mismatches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos_mismatches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` bigint NOT NULL,
  `module_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `causer` tinyint DEFAULT NULL COMMENT '1: POS, 2: Backend, 3: Configuration Updates',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_ageings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_ageings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `product_created_at` date NOT NULL,
  `last_selling_date` date DEFAULT NULL,
  `first_transfer_in` date DEFAULT NULL,
  `first_goods_received_note` date DEFAULT NULL,
  `quantity_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `quantity_remaining` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `first_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `second_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `third_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `fourth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `fifth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `sixth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `seventh_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `eighth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `ninth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `tenth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `eleventh_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `twelfth_month_sold` decimal(16,6) NOT NULL DEFAULT '0.000000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_ageings_product_id_foreign` (`product_id`),
  KEY `product_ageings_store_id_foreign` (`store_id`),
  KEY `product_ageings_location_id_foreign` (`location_id`),
  CONSTRAINT `product_ageings_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `product_ageings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_ageings_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_bundles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_bundles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `package_type_id` bigint unsigned NOT NULL,
  `units` decimal(10,2) NOT NULL,
  `retail_price` decimal(10,2) DEFAULT NULL,
  `staff_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_bundles_product_id_foreign` (`product_id`),
  KEY `product_bundles_package_type_id_foreign` (`package_type_id`),
  CONSTRAINT `product_bundles_package_type_id_foreign` FOREIGN KEY (`package_type_id`) REFERENCES `package_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_bundles_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_channel_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_channel_references` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_channel_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `external_product_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_variant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_channel_references_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `product_channel_references_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filter_season`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filter_season` (
  `season_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `product_collection_filter_season_season_id_foreign` (`season_id`),
  KEY `season_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `product_collection_filter_season_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `season_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filter_size`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filter_size` (
  `size_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `product_collection_filter_size_size_id_foreign` (`size_id`),
  KEY `size_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `product_collection_filter_size_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`),
  CONSTRAINT `size_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filter_style`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filter_style` (
  `style_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `product_collection_filter_style_style_id_foreign` (`style_id`),
  KEY `style_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `product_collection_filter_style_style_id_foreign` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`),
  CONSTRAINT `style_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filter_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filter_tag` (
  `tag_id` bigint unsigned NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  KEY `product_collection_filter_tag_tag_id_foreign` (`tag_id`),
  KEY `tag_product_collection_filter_foreign` (`product_collection_filter_id`),
  CONSTRAINT `product_collection_filter_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`),
  CONSTRAINT `tag_product_collection_filter_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filter_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filter_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL,
  `product_collection_filter_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_collection_filter_types_foreign` (`product_collection_filter_id`),
  CONSTRAINT `product_collection_filter_types_foreign` FOREIGN KEY (`product_collection_filter_id`) REFERENCES `product_collection_filters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_filters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_collection_id` bigint unsigned NOT NULL,
  `filter_type_id` tinyint NOT NULL,
  `condition_operator_type_id` tinyint DEFAULT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_collection_filters_product_collection_id_foreign` (`product_collection_id`),
  CONSTRAINT `product_collection_filters_product_collection_id_foreign` FOREIGN KEY (`product_collection_id`) REFERENCES `product_collections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_collection_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `is_synced` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_collection_id_product_id_unique` (`product_collection_id`,`product_id`),
  KEY `product_collection_products_product_id_foreign` (`product_id`),
  CONSTRAINT `product_collection_products_product_collection_id_foreign` FOREIGN KEY (`product_collection_id`) REFERENCES `product_collections` (`id`),
  CONSTRAINT `product_collection_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collection_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collection_promotion` (
  `product_collection_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  KEY `product_collection_promotion_product_collection_id_foreign` (`product_collection_id`),
  KEY `product_collection_promotion_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `product_collection_promotion_product_collection_id_foreign` FOREIGN KEY (`product_collection_id`) REFERENCES `product_collections` (`id`),
  CONSTRAINT `product_collection_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_products` int DEFAULT NULL,
  `pending_products` int DEFAULT NULL,
  `logical_connector_type_id` tinyint NOT NULL COMMENT '1: AND, 2: OR',
  `last_sync_at` datetime DEFAULT NULL,
  `status` tinyint NOT NULL COMMENT '0: Inactive, 1: Active',
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_collections_company_id_foreign` (`company_id`),
  CONSTRAINT `product_collections_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `membership_id` bigint unsigned NOT NULL,
  `points` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_loyalty_points_product_id_foreign` (`product_id`),
  KEY `product_loyalty_points_membership_id_foreign` (`membership_id`),
  CONSTRAINT `product_loyalty_points_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `memberships` (`id`),
  CONSTRAINT `product_loyalty_points_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_promotion` (
  `product_id` bigint unsigned NOT NULL,
  `promotion_id` bigint unsigned NOT NULL,
  `type` tinyint NOT NULL COMMENT '1: Regular, 2: Buy Product, 3: Get Product',
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `product_promotion_product_id_foreign` (`product_id`),
  KEY `product_promotion_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `product_promotion_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_promotion_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_sale_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_sale_channel` (
  `product_id` bigint unsigned NOT NULL,
  `sale_channel_id` bigint unsigned NOT NULL,
  KEY `product_sale_channel_product_id_foreign` (`product_id`),
  KEY `product_sale_channel_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `product_sale_channel_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_sale_channel_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_tag` (
  `product_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `product_tag_product_id_foreign` (`product_id`),
  KEY `product_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `product_tag_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `compound_product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `unit_of_measure_id` bigint unsigned DEFAULT NULL,
  `season_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `sub_department_id` tinyint DEFAULT NULL,
  `color_id` bigint unsigned DEFAULT NULL,
  `size_id` bigint unsigned DEFAULT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `style_id` bigint unsigned DEFAULT NULL,
  `retail_planning_hierarchy_id` int DEFAULT NULL,
  `upc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ean` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manufacturer_sku` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `article_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` tinyint NOT NULL DEFAULT '1' COMMENT '1: Regular product, 2: Special order, 3: Custom order, 4: Postage cost',
  `retail_price` decimal(10,2) DEFAULT NULL,
  `franchise_price_1` decimal(10,2) DEFAULT NULL,
  `franchise_price_2` decimal(10,2) DEFAULT NULL,
  `franchise_price_3` decimal(10,2) DEFAULT NULL,
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `company_or_tender_price` decimal(10,2) DEFAULT NULL,
  `branch_price` decimal(10,2) DEFAULT NULL,
  `minimum_price` decimal(10,2) DEFAULT NULL,
  `original_capital_price` decimal(10,2) DEFAULT NULL,
  `capital_price` decimal(10,2) DEFAULT NULL,
  `staff_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `online_price` decimal(10,2) DEFAULT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_temporarily_unavailable` tinyint(1) NOT NULL COMMENT '1: Yes, 0: No',
  `has_batch` tinyint(1) NOT NULL COMMENT '1: Yes, 0: No',
  `status` int NOT NULL DEFAULT '1' COMMENT '1: Draft, 2: Active, 3: Archived',
  `is_non_inventory` tinyint(1) NOT NULL,
  `is_non_selling_item` tinyint(1) NOT NULL DEFAULT '0',
  `is_available_in_pos` tinyint(1) NOT NULL DEFAULT '1',
  `is_available_in_ecommerce` tinyint(1) NOT NULL DEFAULT '0',
  `is_sold_as_single_item` tinyint(1) NOT NULL DEFAULT '1',
  `original_created_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_upc_company_id_unique` (`upc`,`company_id`),
  UNIQUE KEY `products_code_company_id_unique` (`code`,`company_id`),
  KEY `products_company_id_foreign` (`company_id`),
  KEY `products_unit_of_measure_id_foreign` (`unit_of_measure_id`),
  KEY `products_season_id_foreign` (`season_id`),
  KEY `products_department_id_foreign` (`department_id`),
  KEY `products_color_id_foreign` (`color_id`),
  KEY `products_size_id_foreign` (`size_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_style_id_foreign` (`style_id`),
  KEY `products_ean_index` (`ean`),
  KEY `products_upc_index` (`upc`),
  KEY `products_compound_product_name_index` (`compound_product_name`),
  KEY `products_article_number_index` (`article_number`),
  KEY `products_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `products_color_id_foreign` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`),
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `products_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `products_season_id_foreign` FOREIGN KEY (`season_id`) REFERENCES `seasons` (`id`),
  CONSTRAINT `products_size_id_foreign` FOREIGN KEY (`size_id`) REFERENCES `sizes` (`id`),
  CONSTRAINT `products_style_id_foreign` FOREIGN KEY (`style_id`) REFERENCES `styles` (`id`),
  CONSTRAINT `products_unit_of_measure_id_foreign` FOREIGN KEY (`unit_of_measure_id`) REFERENCES `unit_of_measures` (`id`),
  CONSTRAINT `products_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_commission_regenerations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_commission_regenerations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period` date NOT NULL,
  `admin_id` bigint unsigned NOT NULL,
  `super_admin_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promoter_commission_regenerations_admin_id_foreign` (`admin_id`),
  KEY `promoter_commission_regenerations_super_admin_id_foreign` (`super_admin_id`),
  CONSTRAINT `promoter_commission_regenerations_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  CONSTRAINT `promoter_commission_regenerations_super_admin_id_foreign` FOREIGN KEY (`super_admin_id`) REFERENCES `super_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_commission_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_commission_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promoter_commission_id` bigint unsigned NOT NULL,
  `affected_by_id` bigint DEFAULT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Possible types: 1. Sale Item, 2. Sale Return Item',
  `department_id` bigint unsigned DEFAULT NULL COMMENT 'In case of company implementing commission by department, store the relevant Department id here.',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `brand_id` bigint unsigned DEFAULT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `commission_percentage` decimal(10,2) NOT NULL,
  `discount_type` tinyint NOT NULL DEFAULT '1',
  `flat_commission` decimal(14,6) DEFAULT NULL,
  `commission_amount` decimal(14,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promoter_commission_updates_promoter_commission_id_foreign_new` (`promoter_commission_id`),
  KEY `promoter_commission_updates_store_id_foreign` (`store_id`),
  KEY `promoter_commission_updates_brand_id_foreign` (`brand_id`),
  KEY `promoter_commission_updates_location_id_foreign` (`location_id`),
  CONSTRAINT `promoter_commission_updates_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `promoter_commission_updates_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `promoter_commission_updates_promoter_commission_id_foreign_new` FOREIGN KEY (`promoter_commission_id`) REFERENCES `promoter_commissions` (`id`),
  CONSTRAINT `promoter_commission_updates_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promoter_id` bigint unsigned NOT NULL,
  `commission_amount` decimal(14,6) NOT NULL,
  `commission_amount_rounding` decimal(14,6) NOT NULL DEFAULT '0.000000',
  `total_sales_amount` decimal(10,2) NOT NULL,
  `total_sales_amount_rounding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_return_sales_amount` decimal(10,2) DEFAULT '0.00',
  `total_return_sales_amount_rounding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monthly_sales_target` decimal(10,2) NOT NULL,
  `commission_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promoter_commissions_promoter_id_foreign_new` (`promoter_id`),
  CONSTRAINT `promoter_commissions_promoter_id_foreign_new` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` tinyint NOT NULL DEFAULT '1',
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promoter_groups_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `promoter_groups_code_company_id_unique` (`code`,`company_id`),
  KEY `promoter_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `promoter_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_sale_target`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_sale_target` (
  `sale_target_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  KEY `promoter_sale_target_sale_target_id_foreign` (`sale_target_id`),
  KEY `promoter_sale_target_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `promoter_sale_target_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`),
  CONSTRAINT `promoter_sale_target_sale_target_id_foreign` FOREIGN KEY (`sale_target_id`) REFERENCES `sale_targets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoter_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoter_store` (
  `promoter_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `promoter_store_promoter_id_foreign` (`promoter_id`),
  KEY `promoter_store_store_id_foreign` (`store_id`),
  CONSTRAINT `promoter_store_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`),
  CONSTRAINT `promoter_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promoters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promoters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_sales_target` decimal(10,2) DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `default_commission_amount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'The amount of commission the promoter will receive irrespective of monthly target.',
  `monthly_target_commission_percentage` decimal(5,2) DEFAULT NULL COMMENT 'The amount of commission the promoter will receive if the monthly target is achieved.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `promoters_employee_id_unique` (`employee_id`),
  KEY `promoters_group_id_foreign` (`group_id`),
  CONSTRAINT `promoters_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `promoters_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `promoter_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_month_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_month_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `month_date` tinyint NOT NULL COMMENT '1 to 31 of the month',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_month_dates_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `promotion_month_dates_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_promo_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `promo_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_promo_codes_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `promotion_promo_codes_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_sale_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_sale_channel` (
  `promotion_id` bigint unsigned NOT NULL,
  `sale_channel_id` bigint unsigned NOT NULL,
  KEY `promotion_sale_channel_promotion_id_foreign` (`promotion_id`),
  KEY `promotion_sale_channel_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `promotion_sale_channel_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`),
  CONSTRAINT `promotion_sale_channel_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_store` (
  `promotion_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `promotion_store_promotion_id_foreign` (`promotion_id`),
  KEY `promotion_store_store_id_foreign` (`store_id`),
  CONSTRAINT `promotion_store_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`),
  CONSTRAINT `promotion_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_tag` (
  `promotion_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `promotion_tag_promotion_id_foreign` (`promotion_id`),
  KEY `promotion_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `promotion_tag_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`),
  CONSTRAINT `promotion_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `buy_value` decimal(10,2) NOT NULL,
  `get_value` decimal(10,2) NOT NULL,
  `get_quantity` decimal(10,2) DEFAULT NULL COMMENT 'Can be Quantity',
  `max_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_tiers_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `promotion_tiers_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_week_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_week_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotion_id` bigint unsigned NOT NULL,
  `week_day` tinyint NOT NULL COMMENT '0: Sunday, 1: Monday, 2: Tuesday, 3: Wednesday, 4: Thursday, 5: Friday, 6: Saturday',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_week_days_promotion_id_foreign` (`promotion_id`),
  CONSTRAINT `promotion_week_days_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `promotion_applicable_type_id` tinyint NOT NULL,
  `discount_type_id` tinyint DEFAULT NULL,
  `cart_wide_promotion_type_id` tinyint DEFAULT NULL,
  `item_wise_promotion_type_id` tinyint DEFAULT NULL,
  `timeframe_type_id` tinyint NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `flat_amount` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `allow_walk_in_member` tinyint(1) NOT NULL DEFAULT '0',
  `allow_registered_member` tinyint(1) NOT NULL DEFAULT '0',
  `allow_employee` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL COMMENT '0: Inactive, 1: Active',
  `dream_price_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `is_automatic` tinyint(1) NOT NULL DEFAULT '1',
  `usage_type` tinyint DEFAULT NULL,
  `is_available_in_pos` tinyint(1) NOT NULL DEFAULT '1',
  `is_available_in_ecommerce` tinyint(1) NOT NULL DEFAULT '0',
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotions_name_company_id_unique` (`name`,`company_id`),
  KEY `promotions_company_id_foreign` (`company_id`),
  CONSTRAINT `promotions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_amounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_amounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `landed_cost` decimal(10,2) NOT NULL,
  `fob` decimal(10,2) DEFAULT NULL,
  `freight_charges` decimal(10,2) DEFAULT NULL,
  `insurance_charges` decimal(10,2) DEFAULT NULL,
  `duty` decimal(10,2) DEFAULT NULL,
  `sst` decimal(10,2) DEFAULT NULL,
  `handling_charges` decimal(10,2) DEFAULT NULL,
  `other_charges` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillment_item_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillment_item_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_item_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `is_discrepancy` tinyint NOT NULL DEFAULT '0',
  `received_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_batches_fulfillment_item_id` (`purchase_order_fulfillment_item_id`),
  KEY `fulfillment_item_id_batch_id` (`batch_id`),
  CONSTRAINT `fulfillment_item_id_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `item_batches_fulfillment_item_id` FOREIGN KEY (`purchase_order_fulfillment_item_id`) REFERENCES `purchase_order_fulfillment_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillment_item_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillment_item_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_item_id` bigint unsigned NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_fulfillment_item_id` (`purchase_order_fulfillment_item_id`),
  CONSTRAINT `transaction_fulfillment_item_id` FOREIGN KEY (`purchase_order_fulfillment_item_id`) REFERENCES `purchase_order_fulfillment_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillment_item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillment_item_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_item_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_fulfillment_items` (`purchase_order_fulfillment_item_id`),
  KEY `purchase_order_fulfillment_item_units_inventory_id_foreign` (`inventory_id`),
  KEY `purchase_amounts` (`purchase_amount_id`),
  KEY `purchase_order_fulfillment_item_units_batch_id_foreign` (`batch_id`),
  CONSTRAINT `purchase_amounts` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`),
  CONSTRAINT `purchase_order_fulfillment_item_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `purchase_order_fulfillment_item_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `purchase_order_fulfillment_items` FOREIGN KEY (`purchase_order_fulfillment_item_id`) REFERENCES `purchase_order_fulfillment_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_id` bigint unsigned NOT NULL,
  `purchase_order_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `external_purchase_order_fulfillment_item_id` bigint DEFAULT NULL,
  `transfer_quantity` decimal(10,2) DEFAULT NULL,
  `received_quantity` decimal(10,2) DEFAULT NULL,
  `package_type_id` bigint unsigned DEFAULT NULL,
  `package_quantity` int DEFAULT NULL,
  `package_total_quantity` decimal(10,2) DEFAULT NULL,
  `discrepancy_type` smallint DEFAULT NULL COMMENT '1:Positive, 2:Negative',
  `is_extra_item` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_fulfillments` (`purchase_order_fulfillment_id`),
  KEY `purchase_order_fulfillment_items_product_id_foreign` (`product_id`),
  KEY `purchase_order_fulfillment_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_order_fulfillment_items_package_type_id_foreign` (`package_type_id`),
  CONSTRAINT `purchase_order_fulfillment_items_package_type_id_foreign` FOREIGN KEY (`package_type_id`) REFERENCES `package_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_fulfillment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_order_fulfillment_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`),
  CONSTRAINT `purchase_order_fulfillments` FOREIGN KEY (`purchase_order_fulfillment_id`) REFERENCES `purchase_order_fulfillments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_fulfillment_id` bigint unsigned NOT NULL,
  `old_status` tinyint DEFAULT NULL,
  `new_status` tinyint NOT NULL,
  `user_id` bigint DEFAULT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_purchase_order_fulfillment_id` (`purchase_order_fulfillment_id`),
  CONSTRAINT `transaction_purchase_order_fulfillment_id` FOREIGN KEY (`purchase_order_fulfillment_id`) REFERENCES `purchase_order_fulfillments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_fulfillments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_fulfillments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `created_by_company_id` bigint unsigned DEFAULT NULL,
  `external_purchase_order_fulfillment_id` bigint DEFAULT NULL,
  `purchase_order_invoice_id` bigint unsigned DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `delivery_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_fulfillments_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_fulfillments_created_by_company_id_foreign` (`created_by_company_id`),
  KEY `fulfillments_purchase_order_invoice_id` (`purchase_order_invoice_id`),
  CONSTRAINT `fulfillments_purchase_order_invoice_id` FOREIGN KEY (`purchase_order_invoice_id`) REFERENCES `purchase_order_invoices` (`id`),
  CONSTRAINT `purchase_order_fulfillments_created_by_company_id_foreign` FOREIGN KEY (`created_by_company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `purchase_order_fulfillments_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_invoice_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_invoice_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_invoice_id` bigint unsigned NOT NULL,
  `old_status` tinyint DEFAULT NULL,
  `new_status` tinyint NOT NULL,
  `user_id` bigint DEFAULT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_purchase_order
                _invoice_id` (`purchase_order_invoice_id`),
  CONSTRAINT `transaction_purchase_order
                _invoice_id` FOREIGN KEY (`purchase_order_invoice_id`) REFERENCES `purchase_order_invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `created_by_company_id` bigint unsigned DEFAULT NULL,
  `external_purchase_order_invoice_id` bigint DEFAULT NULL,
  `invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_invoices_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_invoices_created_by_company_id_foreign` (`created_by_company_id`),
  KEY `purchase_order_invoices_company_id_foreign` (`company_id`),
  CONSTRAINT `purchase_order_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `purchase_order_invoices_created_by_company_id_foreign` FOREIGN KEY (`created_by_company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `purchase_order_invoices_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_purchase_order_item_id` bigint DEFAULT NULL,
  `purchase_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `rejected_quantity` decimal(10,2) DEFAULT NULL,
  `transferred_quantity` decimal(10,2) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_of_measure_derivative_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_product_id_foreign` (`product_id`),
  KEY `purchase_order_unit_of_measure_derivative_id` (`unit_of_measure_derivative_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `purchase_order_unit_of_measure_derivative_id` FOREIGN KEY (`unit_of_measure_derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `old_status` tinyint DEFAULT NULL,
  `new_status` tinyint NOT NULL,
  `user_id` bigint DEFAULT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_transactions_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `purchase_order_transactions_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `external_purchase_order_id` bigint DEFAULT NULL,
  `parent_purchase_order_id` bigint DEFAULT NULL,
  `external_company_id` bigint unsigned NOT NULL,
  `external_location_id` bigint unsigned NOT NULL,
  `external_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `old_location_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attention` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `require_date` date DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `order_type` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by_company_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_orders_external_company_id_foreign` (`external_company_id`),
  KEY `purchase_orders_external_location_id_foreign` (`external_location_id`),
  KEY `purchase_orders_company_id_foreign` (`company_id`),
  KEY `purchase_orders_created_by_company_id_foreign` (`created_by_company_id`),
  KEY `purchase_orders_location_id_foreign` (`location_id`),
  CONSTRAINT `purchase_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `purchase_orders_created_by_company_id_foreign` FOREIGN KEY (`created_by_company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `purchase_orders_external_company_id_foreign` FOREIGN KEY (`external_company_id`) REFERENCES `external_companies` (`id`),
  CONSTRAINT `purchase_orders_external_location_id_foreign` FOREIGN KEY (`external_location_id`) REFERENCES `external_locations` (`id`),
  CONSTRAINT `purchase_orders_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `regions_company_id_foreign` (`company_id`),
  CONSTRAINT `regions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reserved_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reserved_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `inventory_unit_id` bigint unsigned NOT NULL,
  `affected_by_id` bigint NOT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reserved_stocks_inventory_id_foreign` (`inventory_id`),
  KEY `reserved_stocks_inventory_unit_id_foreign` (`inventory_unit_id`),
  KEY `reserved_stocks_affected_by_id_affected_by_type_index` (`affected_by_id`,`affected_by_type`),
  CONSTRAINT `reserved_stocks_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `reserved_stocks_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retail_planning_hierarchies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `retail_planning_hierarchies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retail_planning_hierarchies_parent_id_foreign` (`parent_id`),
  KEY `retail_planning_hierarchies_company_id_foreign` (`company_id`),
  CONSTRAINT `retail_planning_hierarchies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `retail_planning_hierarchies_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `retail_planning_hierarchies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_achieved_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_achieved_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_target_timeframe_id` bigint unsigned NOT NULL,
  `targetable_id` bigint NOT NULL,
  `targetable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `achieved_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_achieved_targets_sale_target_timeframe_id_foreign` (`sale_target_timeframe_id`),
  CONSTRAINT `sale_achieved_targets_sale_target_timeframe_id_foreign` FOREIGN KEY (`sale_target_timeframe_id`) REFERENCES `sale_target_timeframes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_cashbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_cashbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `cashback_id` bigint unsigned NOT NULL,
  `cash_movement_id` bigint unsigned NOT NULL,
  `petty_cash_usage_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `round_off` decimal(10,2) NOT NULL DEFAULT '0.00',
  `happened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_cashbacks_sale_id_foreign` (`sale_id`),
  KEY `sale_cashbacks_cashback_id_foreign` (`cashback_id`),
  KEY `sale_cashbacks_petty_cash_usage_id_foreign` (`petty_cash_usage_id`),
  KEY `sale_cashbacks_cash_movement_id_foreign` (`cash_movement_id`),
  CONSTRAINT `sale_cashbacks_cash_movement_id_foreign` FOREIGN KEY (`cash_movement_id`) REFERENCES `cash_movements` (`id`),
  CONSTRAINT `sale_cashbacks_cashback_id_foreign` FOREIGN KEY (`cashback_id`) REFERENCES `cashbacks` (`id`),
  CONSTRAINT `sale_cashbacks_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_channel_inventory_rollback_order_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_channel_inventory_rollback_order_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_channel_id` bigint unsigned NOT NULL,
  `order_status` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_channel_foreign_id` (`sale_channel_id`),
  CONSTRAINT `sale_channel_foreign_id` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_channel_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_channel_store` (
  `sale_channel_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  KEY `sale_channel_store_sale_channel_id_foreign` (`sale_channel_id`),
  KEY `sale_channel_store_store_id_foreign` (`store_id`),
  CONSTRAINT `sale_channel_store_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`),
  CONSTRAINT `sale_channel_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_channel_webhook_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_channel_webhook_urls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_channel_id` bigint unsigned NOT NULL,
  `webhook_url_type_id` tinyint NOT NULL COMMENT 'WebhookUrls Enum is Used.',
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_channel_webhook_urls_sale_channel_id_foreign` (`sale_channel_id`),
  CONSTRAINT `sale_channel_webhook_urls_sale_channel_id_foreign` FOREIGN KEY (`sale_channel_id`) REFERENCES `sale_channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_channels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `default_store_id` bigint unsigned DEFAULT NULL,
  `default_location_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL COMMENT 'check the SaleChannelTypes Enum',
  `inventory_deduct_order_status` int NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_channels_company_id_foreign` (`company_id`),
  KEY `sale_channels_default_store_id_foreign` (`default_store_id`),
  KEY `sale_channels_default_location_id_foreign` (`default_location_id`),
  CONSTRAINT `sale_channels_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `sale_channels_default_location_id_foreign` FOREIGN KEY (`default_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `sale_channels_default_store_id_foreign` FOREIGN KEY (`default_store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `discountable_id` bigint DEFAULT NULL,
  `discountable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `promo_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_discounts_sale_id_foreign` (`sale_id`),
  CONSTRAINT `sale_discounts_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_assembly_child_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_assembly_child_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `child_product_id` bigint unsigned NOT NULL,
  `units` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_assembly_child_products_sale_item_id_foreign` (`sale_item_id`),
  KEY `sale_item_assembly_child_products_child_product_id_foreign` (`child_product_id`),
  CONSTRAINT `sale_item_assembly_child_products_child_product_id_foreign` FOREIGN KEY (`child_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_item_assembly_child_products_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_complimentaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_complimentaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `authorizer_id` int NOT NULL,
  `authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_complimentaries_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `sale_item_complimentaries_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `discountable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discountable_id` bigint DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `promo_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_discounts_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `sale_item_discounts_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_exchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_exchanges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `old_item_price` decimal(10,2) NOT NULL,
  `current_item_price` decimal(10,2) NOT NULL,
  `price_difference` decimal(10,2) NOT NULL,
  `old_discount_amount` decimal(10,2) NOT NULL,
  `old_item_tax` decimal(10,2) NOT NULL,
  `current_item_tax` decimal(10,2) NOT NULL,
  `tax_difference` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_exchanges_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `sale_item_exchanges_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_price_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_price_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `negotiator_id` int NOT NULL,
  `negotiator_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `override_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_price_overrides_sale_item_id_foreign` (`sale_item_id`),
  CONSTRAINT `sale_item_price_overrides_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_promoter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_promoter` (
  `sale_item_id` bigint unsigned NOT NULL,
  `promoter_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `sale_item_promoter_sale_item_id_foreign` (`sale_item_id`),
  KEY `sale_item_promoter_promoter_id_foreign` (`promoter_id`),
  CONSTRAINT `sale_item_promoter_promoter_id_foreign` FOREIGN KEY (`promoter_id`) REFERENCES `promoters` (`id`),
  CONSTRAINT `sale_item_promoter_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_item_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_item_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `returned_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_item_units_sale_item_id_foreign` (`sale_item_id`),
  KEY `sale_item_units_inventory_id_foreign` (`inventory_id`),
  KEY `sale_item_units_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `sale_item_units_batch_id_foreign` (`batch_id`),
  CONSTRAINT `sale_item_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `sale_item_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `sale_item_units_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`),
  CONSTRAINT `sale_item_units_sale_item_id_foreign` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `derivative_id` bigint unsigned DEFAULT NULL,
  `quantity_of_derivative` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_based_on_derivative` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_paid_of_derivative` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sale_return_item_id` bigint unsigned DEFAULT NULL COMMENT 'When exchange save sale return item id.',
  `quantity` decimal(10,2) NOT NULL,
  `returned_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price_per_unit` decimal(10,2) NOT NULL,
  `cart_discount_amount` decimal(10,2) DEFAULT NULL,
  `item_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_tax_amount` decimal(10,2) DEFAULT NULL,
  `price_paid_per_unit` decimal(10,2) DEFAULT NULL,
  `total_price_paid` decimal(10,2) DEFAULT NULL,
  `vendor_commission_percentage` decimal(10,2) DEFAULT NULL,
  `group_id` int DEFAULT NULL,
  `discount_item_sequence` int DEFAULT NULL,
  `is_exchange` tinyint(1) NOT NULL DEFAULT '0',
  `product_bundle_units` decimal(10,2) DEFAULT NULL,
  `product_bundle_package_type_id` bigint unsigned DEFAULT NULL,
  `product_bundle_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_product_id_foreign` (`product_id`),
  KEY `sale_items_derivative_id_foreign` (`derivative_id`),
  KEY `sale_items_sale_return_item_id_foreign` (`sale_return_item_id`),
  KEY `sale_items_product_bundle_id_foreign` (`product_bundle_id`),
  KEY `sale_items_product_bundle_package_type_id_foreign` (`product_bundle_package_type_id`),
  CONSTRAINT `sale_items_derivative_id_foreign` FOREIGN KEY (`derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`),
  CONSTRAINT `sale_items_product_bundle_id_foreign` FOREIGN KEY (`product_bundle_id`) REFERENCES `product_bundles` (`id`),
  CONSTRAINT `sale_items_product_bundle_package_type_id_foreign` FOREIGN KEY (`product_bundle_package_type_id`) REFERENCES `package_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `sale_items_sale_return_item_id_foreign` FOREIGN KEY (`sale_return_item_id`) REFERENCES `sale_return_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `loyalty_points` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_loyalty_points_product_id_foreign` (`product_id`),
  KEY `sale_loyalty_points_sale_id_foreign` (`sale_id`),
  CONSTRAINT `sale_loyalty_points_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_loyalty_points_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL COMMENT 'Filled when the layaway payment is added',
  `amount` decimal(10,2) NOT NULL,
  `happened_at` datetime NOT NULL,
  `extra_details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_payments_sale_id_foreign` (`sale_id`),
  KEY `sale_payments_payment_type_id_foreign` (`payment_type_id`),
  KEY `sale_payments_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `sale_payments_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `sale_payments_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`),
  CONSTRAINT `sale_payments_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_price_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_price_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `negotiator_id` int NOT NULL,
  `negotiator_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `override_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_price_overrides_sale_id_foreign` (`sale_id`),
  CONSTRAINT `sale_price_overrides_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint unsigned NOT NULL,
  `original_sale_item_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `total_price_paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Total Price Paid = (Subtotal - Total Discount Amount) + Total Tax Amount',
  `cart_discount_amount` decimal(10,2) DEFAULT NULL,
  `item_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_tax_amount` decimal(10,2) DEFAULT NULL,
  `sale_return_reason_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  KEY `sale_return_items_original_sale_item_id_foreign` (`original_sale_item_id`),
  KEY `sale_return_items_product_id_foreign` (`product_id`),
  KEY `sale_return_items_sale_return_reason_id_foreign` (`sale_return_reason_id`),
  CONSTRAINT `sale_return_items_original_sale_item_id_foreign` FOREIGN KEY (`original_sale_item_id`) REFERENCES `sale_items` (`id`),
  CONSTRAINT `sale_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`),
  CONSTRAINT `sale_return_items_sale_return_reason_id_foreign` FOREIGN KEY (`sale_return_reason_id`) REFERENCES `sale_return_reasons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_return_reason_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_return_reason_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_reason_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_reason_types_sale_return_reason_id_foreign` (`sale_return_reason_id`),
  CONSTRAINT `sale_return_reason_types_sale_return_reason_id_foreign` FOREIGN KEY (`sale_return_reason_id`) REFERENCES `sale_return_reasons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_return_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_return_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_location_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `put_back_in_inventory` tinyint(1) NOT NULL COMMENT '1: Yes, 0: No',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_return_reasons_reason_company_id_unique` (`reason`,`company_id`),
  KEY `sale_return_reasons_company_id_foreign` (`company_id`),
  KEY `sale_return_reasons_location_id_foreign` (`location_id`),
  CONSTRAINT `sale_return_reasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `sale_return_reasons_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offline_sale_return_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_sale_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `total_tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cart_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `items_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price_paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'With round-off effect',
  `round_off_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount_before_round_off` decimal(10,2) NOT NULL DEFAULT '0.00',
  `happened_at` datetime NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_mismatch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1: Yes, 0: No',
  `member_id` bigint unsigned DEFAULT NULL,
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_offline_sale_return_id_unique` (`offline_sale_return_id`),
  KEY `sale_returns_original_sale_id_foreign` (`original_sale_id`),
  KEY `sale_returns_counter_update_id_foreign` (`counter_update_id`),
  KEY `sale_returns_member_id_foreign` (`member_id`),
  CONSTRAINT `sale_returns_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `sale_returns_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `sale_returns_original_sale_id_foreign` FOREIGN KEY (`original_sale_id`) REFERENCES `sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_seasons_company_id_foreign` (`company_id`),
  CONSTRAINT `sale_seasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_target_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_target_store` (
  `sale_target_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned NOT NULL,
  KEY `sale_target_store_sale_target_id_foreign` (`sale_target_id`),
  KEY `sale_target_store_store_id_foreign` (`store_id`),
  CONSTRAINT `sale_target_store_sale_target_id_foreign` FOREIGN KEY (`sale_target_id`) REFERENCES `sale_targets` (`id`),
  CONSTRAINT `sale_target_store_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_target_timeframes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_target_timeframes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_target_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `target_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,6) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_target_timeframes_sale_target_id_foreign` (`sale_target_id`),
  CONSTRAINT `sale_target_timeframes_sale_target_id_foreign` FOREIGN KEY (`sale_target_id`) REFERENCES `sale_targets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `amount_type` tinyint NOT NULL DEFAULT '1',
  `target_type` tinyint NOT NULL,
  `time_interval_type` tinyint NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `re_generate_target` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_targets_name_unique` (`name`),
  KEY `sale_targets_company_id_foreign` (`company_id`),
  CONSTRAINT `sale_targets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_through_ratios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_through_ratios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_through_ratios_company_id_foreign` (`company_id`),
  CONSTRAINT `sale_through_ratios_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sale_void_cashbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_void_cashbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_cashback_id` bigint unsigned NOT NULL,
  `void_sale_id` bigint unsigned NOT NULL,
  `cash_movement_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_void_cashbacks_sale_cashback_id_foreign` (`sale_cashback_id`),
  KEY `sale_void_cashbacks_void_sale_id_foreign` (`void_sale_id`),
  KEY `sale_void_cashbacks_cash_movement_id_foreign` (`cash_movement_id`),
  CONSTRAINT `sale_void_cashbacks_cash_movement_id_foreign` FOREIGN KEY (`cash_movement_id`) REFERENCES `cash_movements` (`id`),
  CONSTRAINT `sale_void_cashbacks_sale_cashback_id_foreign` FOREIGN KEY (`sale_cashback_id`) REFERENCES `sale_cashbacks` (`id`),
  CONSTRAINT `sale_void_cashbacks_void_sale_id_foreign` FOREIGN KEY (`void_sale_id`) REFERENCES `void_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint unsigned DEFAULT NULL COMMENT 'When Exchange or Return With new purchase at that time this field will be set to effective sale return.',
  `offline_sale_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `total_tax_amount` decimal(10,2) DEFAULT NULL,
  `cart_discount_amount` decimal(10,2) DEFAULT NULL,
  `items_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_discount_amount` decimal(10,2) DEFAULT NULL,
  `total_amount_before_round_off` decimal(10,2) DEFAULT NULL,
  `round_off` decimal(10,2) DEFAULT NULL,
  `total_amount_paid` decimal(10,2) DEFAULT NULL,
  `change_due` decimal(10,2) DEFAULT NULL,
  `layaway_pending_amount` decimal(10,2) DEFAULT NULL,
  `layaway_completed_at` datetime DEFAULT NULL,
  `layaway_authorizer_id` int DEFAULT NULL,
  `layaway_authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_authorizer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_authorizer_id` bigint DEFAULT NULL,
  `credit_completed_at` datetime DEFAULT NULL,
  `credit_pending_amount` decimal(10,2) DEFAULT '0.00',
  `status` tinyint NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bill_reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `extra_details` json DEFAULT NULL,
  `has_mismatch` tinyint(1) NOT NULL COMMENT '1: Yes, 0: No',
  `digital_invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digital_invoice_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_offline_sale_id_unique` (`offline_sale_id`),
  KEY `sales_counter_update_id_foreign` (`counter_update_id`),
  KEY `sales_sale_return_id_foreign` (`sale_return_id`),
  KEY `idx_sales_order_id_date_desc` (`id`,`happened_at`),
  KEY `sales_member_id_foreign` (`member_id`),
  CONSTRAINT `sales_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `sales_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `sales_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seasons_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `seasons_code_company_id_unique` (`code`,`company_id`),
  KEY `seasons_company_id_foreign` (`company_id`),
  CONSTRAINT `seasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `old_location_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_id` smallint NOT NULL COMMENT '1: Request Order (RO), 2: Transfer Order (TO), 3: Transfer In (TIN), 4: Transfer Out (TOUT)',
  `number` mediumint(8) unsigned zerofill NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sequences_location_id_foreign` (`location_id`),
  CONSTRAINT `sequences_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_configurations_type_id_unique` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `size_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `size_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `size_groups_company_id_foreign` (`company_id`),
  CONSTRAINT `size_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sizes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sizes_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `sizes_code_company_id_unique` (`code`,`company_id`),
  KEY `sizes_company_id_foreign` (`company_id`),
  KEY `sizes_group_id_foreign` (`group_id`),
  CONSTRAINT `sizes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `sizes_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `size_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mobile_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `sending_date` datetime NOT NULL,
  `response_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `old_location_id` int DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned DEFAULT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_of_measure_derivative_id` bigint unsigned DEFAULT NULL,
  `derivative_ratio` decimal(10,2) DEFAULT NULL,
  `input_quantity` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  KEY `stock_adjustment_items_product_id_foreign` (`product_id`),
  KEY `stock_adjustment_items_unit_of_measure_derivative_id_foreign` (`unit_of_measure_derivative_id`),
  KEY `stock_adjustment_items_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `stock_adjustment_items_batch_id_foreign` (`batch_id`),
  KEY `stock_adjustment_items_location_id_foreign` (`location_id`),
  CONSTRAINT `stock_adjustment_items_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `stock_adjustment_items_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_adjustment_items_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`),
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`),
  CONSTRAINT `stock_adjustment_items_unit_of_measure_derivative_id_foreign` FOREIGN KEY (`unit_of_measure_derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_admin_id` bigint unsigned NOT NULL,
  `approved_by_employee_id` bigint unsigned NOT NULL,
  `adjustment_date` date DEFAULT NULL,
  `type_id` tinyint NOT NULL COMMENT '1: STI, 2: STO',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustments_company_id_foreign` (`company_id`),
  KEY `stock_adjustments_created_by_admin_id_foreign` (`created_by_admin_id`),
  KEY `stock_adjustments_approved_by_employee_id_foreign` (`approved_by_employee_id`),
  CONSTRAINT `stock_adjustments_approved_by_employee_id_foreign` FOREIGN KEY (`approved_by_employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `stock_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `stock_adjustments_created_by_admin_id_foreign` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_take_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_take_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_take_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `actual_stock` decimal(10,2) NOT NULL,
  `submitted_stock` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_take_products_stock_take_id_foreign` (`stock_take_id`),
  KEY `stock_take_products_product_id_foreign` (`product_id`),
  CONSTRAINT `stock_take_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_take_products_stock_take_id_foreign` FOREIGN KEY (`stock_take_id`) REFERENCES `stock_takes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_takes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_takes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `stock_record_date` date NOT NULL,
  `requested_by_id` bigint unsigned NOT NULL,
  `requested_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_location_id` int DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_by_id` bigint unsigned DEFAULT NULL,
  `submitted_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `compare_stock_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_uploaded_products` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_takes_requested_store_manager_id_foreign` (`requested_by_id`),
  KEY `stock_takes_store_id_foreign` (`old_location_id`),
  KEY `stock_takes_submitted_store_manager_id_foreign` (`submitted_by_id`),
  KEY `stock_takes_company_id_foreign` (`company_id`),
  KEY `stock_takes_location_id_foreign` (`location_id`),
  CONSTRAINT `stock_takes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `stock_takes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_average_lead_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_average_lead_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `old_from_location_id` int DEFAULT NULL,
  `from_location_id` bigint unsigned NOT NULL,
  `from_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_to_location_id` int DEFAULT NULL,
  `to_location_id` bigint unsigned NOT NULL,
  `to_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `average_days` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_average_lead_days_from_location_id_foreign` (`from_location_id`),
  KEY `stock_transfer_average_lead_days_to_location_id_foreign` (`to_location_id`),
  CONSTRAINT `stock_transfer_average_lead_days_from_location_id_foreign` FOREIGN KEY (`from_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_transfer_average_lead_days_to_location_id_foreign` FOREIGN KEY (`to_location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_item_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_item_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_item_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_item_batches_stock_transfer_item_id_foreign` (`stock_transfer_item_id`),
  KEY `stock_transfer_item_batches_batch_id_foreign` (`batch_id`),
  CONSTRAINT `stock_transfer_item_batches_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `stock_transfer_item_batches_stock_transfer_item_id_foreign` FOREIGN KEY (`stock_transfer_item_id`) REFERENCES `stock_transfer_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_item_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_item_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_item_id` bigint unsigned NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_item_transactions_stock_transfer_item_id_foreign` (`stock_transfer_item_id`),
  CONSTRAINT `stock_transfer_item_transactions_stock_transfer_item_id_foreign` FOREIGN KEY (`stock_transfer_item_id`) REFERENCES `stock_transfer_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_item_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_item_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_item_id` bigint unsigned NOT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `purchase_amount_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_item_units_stock_transfer_item_id_foreign` (`stock_transfer_item_id`),
  KEY `stock_transfer_item_units_inventory_id_foreign` (`inventory_id`),
  KEY `stock_transfer_item_units_purchase_amount_id_foreign` (`purchase_amount_id`),
  KEY `stock_transfer_item_units_batch_id_foreign` (`batch_id`),
  CONSTRAINT `stock_transfer_item_units_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`),
  CONSTRAINT `stock_transfer_item_units_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `stock_transfer_item_units_purchase_amount_id_foreign` FOREIGN KEY (`purchase_amount_id`) REFERENCES `purchase_amounts` (`id`),
  CONSTRAINT `stock_transfer_item_units_stock_transfer_item_id_foreign` FOREIGN KEY (`stock_transfer_item_id`) REFERENCES `stock_transfer_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `package_type_id` bigint unsigned DEFAULT NULL,
  `unit_of_measure_derivative_id` bigint unsigned DEFAULT NULL,
  `derivative_ratio` decimal(10,2) DEFAULT NULL,
  `is_extra_item` tinyint(1) NOT NULL DEFAULT '0',
  `package_quantity` int DEFAULT NULL,
  `package_total_quantity` decimal(10,2) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `received_quantity` decimal(10,2) DEFAULT NULL,
  `discrepancy_type` smallint DEFAULT NULL COMMENT '1: Positive, 2: Negative',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  KEY `stock_transfer_items_product_id_foreign` (`product_id`),
  KEY `stock_transfer_items_unit_of_measure_derivative_id_foreign` (`unit_of_measure_derivative_id`),
  KEY `stock_transfer_items_package_type_id_foreign` (`package_type_id`),
  CONSTRAINT `stock_transfer_items_package_type_id_foreign` FOREIGN KEY (`package_type_id`) REFERENCES `package_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`),
  CONSTRAINT `stock_transfer_items_unit_of_measure_derivative_id_foreign` FOREIGN KEY (`unit_of_measure_derivative_id`) REFERENCES `unit_of_measure_derivatives` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transfer_reasons_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `stock_transfer_reasons_code_company_id_unique` (`code`,`company_id`),
  KEY `stock_transfer_reasons_company_id_foreign` (`company_id`),
  CONSTRAINT `stock_transfer_reasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfer_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfer_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint unsigned NOT NULL,
  `old_status` tinyint NOT NULL,
  `new_status` tinyint NOT NULL,
  `user_id` bigint NOT NULL,
  `user_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_transactions_stock_transfer_id_foreign` (`stock_transfer_id`),
  CONSTRAINT `stock_transfer_transactions_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_reason_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `transfer_type` tinyint NOT NULL COMMENT '1: request order, 2: transfer order',
  `stock_transfer_average_lead_day_id` bigint unsigned DEFAULT NULL,
  `source_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_source_location_id` int DEFAULT NULL,
  `source_location_id` bigint unsigned NOT NULL,
  `destination_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_destination_location_id` int DEFAULT NULL,
  `destination_location_id` bigint unsigned NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `require_date` date DEFAULT NULL COMMENT 'only for request order',
  `received_date` date DEFAULT NULL,
  `attention` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_by_id` int NOT NULL,
  `old_created_by_location_id` int DEFAULT NULL,
  `created_by_location_id` bigint unsigned DEFAULT NULL,
  `created_by_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint NOT NULL,
  `transit_location_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_transit_location_id` int DEFAULT NULL,
  `transit_location_id` bigint unsigned DEFAULT NULL,
  `transfer_out_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_in_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_order_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `discrepancy_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfers_company_id_foreign` (`company_id`),
  KEY `stock_transfers_stock_transfer_reason_id_foreign` (`stock_transfer_reason_id`),
  KEY `stock_transfers_source_location_id_foreign` (`source_location_id`),
  KEY `stock_transfers_destination_location_id_foreign` (`destination_location_id`),
  KEY `stock_transfers_transit_location_id_foreign` (`transit_location_id`),
  KEY `stock_transfers_created_by_location_id_foreign` (`created_by_location_id`),
  KEY `stock_transfers_stock_transfer_average_lead_day_id_foreign` (`stock_transfer_average_lead_day_id`),
  CONSTRAINT `stock_transfers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `stock_transfers_created_by_location_id_foreign` FOREIGN KEY (`created_by_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_transfers_destination_location_id_foreign` FOREIGN KEY (`destination_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_transfers_source_location_id_foreign` FOREIGN KEY (`source_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `stock_transfers_stock_transfer_average_lead_day_id_foreign` FOREIGN KEY (`stock_transfer_average_lead_day_id`) REFERENCES `stock_transfer_average_lead_days` (`id`),
  CONSTRAINT `stock_transfers_stock_transfer_reason_id_foreign` FOREIGN KEY (`stock_transfer_reason_id`) REFERENCES `stock_transfer_reasons` (`id`),
  CONSTRAINT `stock_transfers_transit_location_id_foreign` FOREIGN KEY (`transit_location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_day_close_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_day_close_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_day_close_id` bigint unsigned NOT NULL,
  `payment_type_id` bigint unsigned NOT NULL,
  `total_transactions` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_order_transactions` decimal(14,6) DEFAULT NULL,
  `total_order_amount` decimal(14,6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_day_close_payments_store_day_close_id_foreign` (`store_day_close_id`),
  KEY `store_day_close_payments_payment_type_id_foreign` (`payment_type_id`),
  CONSTRAINT `store_day_close_payments_payment_type_id_foreign` FOREIGN KEY (`payment_type_id`) REFERENCES `payment_types` (`id`),
  CONSTRAINT `store_day_close_payments_store_day_close_id_foreign` FOREIGN KEY (`store_day_close_id`) REFERENCES `store_day_closes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_day_closes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_day_closes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `opening_balance` decimal(10,2) DEFAULT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime NOT NULL,
  `closed_by_store_manager_id` bigint unsigned DEFAULT NULL,
  `sales_collection_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sales` int NOT NULL,
  `total_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_layaway_sales` int NOT NULL DEFAULT '0',
  `total_layaway_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_sales` int NOT NULL DEFAULT '0',
  `total_credit_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_voided_sales` int NOT NULL DEFAULT '0',
  `total_voided_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_item_wise_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cart_wide_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sales_round_off` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sale_returns` int NOT NULL DEFAULT '0',
  `total_sale_returns_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_notes_used_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_notes_used` int DEFAULT '0',
  `total_credit_notes_refunded_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_credit_notes_refunded` int DEFAULT '0',
  `total_sale_returns_round_off` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cashback` int NOT NULL DEFAULT '0',
  `total_cashback_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_vouchers_used` int NOT NULL DEFAULT '0',
  `total_voucher_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_vouchers_generated` int NOT NULL DEFAULT '0',
  `total_sale_promotion_used` int NOT NULL DEFAULT '0',
  `total_sale_promotion_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sale_item_promotion_used` int NOT NULL DEFAULT '0',
  `total_sale_item_promotion_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_dream_price_used` int NOT NULL DEFAULT '0',
  `total_dream_price_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_complimentary_item_discount_used` int NOT NULL DEFAULT '0',
  `total_complimentary_item_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price_override_used` int NOT NULL DEFAULT '0',
  `total_price_override_discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_refunded_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_booking_payment_used_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_ins_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_outs_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_petty_cash_usage_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_sales` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_booking_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_booking_payment_refunded` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_cash_amount_in_credit_note_refunded` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_new_booking_payments` int NOT NULL DEFAULT '0',
  `total_used_booking_payments` int NOT NULL DEFAULT '0',
  `total_cancel_layaway_sales` int NOT NULL DEFAULT '0',
  `total_cancel_layaway_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `counter_update_ids` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `orders_collection_amount` decimal(14,6) NOT NULL,
  `total_orders` decimal(14,6) NOT NULL,
  `total_orders_amount` decimal(14,6) NOT NULL,
  `total_layaway_orders` decimal(14,6) NOT NULL,
  `total_layaway_orders_amount` decimal(14,6) NOT NULL,
  `total_credit_orders` decimal(14,6) NOT NULL,
  `total_credit_orders_amount` decimal(14,6) NOT NULL,
  `total_cancelled_orders` decimal(14,6) NOT NULL,
  `total_cancelled_orders_amount` decimal(14,6) NOT NULL,
  `total_order_item_wise_discount_amount` decimal(14,6) NOT NULL,
  `total_order_cart_wide_discount_amount` decimal(14,6) NOT NULL,
  `total_order_tax_amount` decimal(14,6) NOT NULL,
  `total_orders_round_off` decimal(14,6) NOT NULL,
  `total_order_returns` decimal(14,6) NOT NULL,
  `total_order_returns_amount` decimal(14,6) NOT NULL,
  `total_order_returns_round_off` decimal(14,6) NOT NULL,
  `total_order_complimentary_item_discount_used` decimal(14,6) NOT NULL,
  `total_order_complimentary_item_discount_amount` decimal(14,6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `store_day_closes_store_id_foreign` (`store_id`),
  KEY `store_day_closes_closed_by_store_manager_id_foreign` (`closed_by_store_manager_id`),
  KEY `store_day_closes_location_id_foreign` (`location_id`),
  CONSTRAINT `store_day_closes_closed_by_store_manager_id_foreign` FOREIGN KEY (`closed_by_store_manager_id`) REFERENCES `store_managers` (`id`),
  CONSTRAINT `store_day_closes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `store_day_closes_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_manager_authorization_code_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_manager_authorization_code_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_manager_authorization_code_id` bigint unsigned NOT NULL,
  `usage_type_id` tinyint NOT NULL,
  `reference_id` bigint NOT NULL,
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_store_manager_auth_code` (`store_manager_authorization_code_id`),
  CONSTRAINT `fk_store_manager_auth_code` FOREIGN KEY (`store_manager_authorization_code_id`) REFERENCES `store_manager_authorization_codes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_manager_authorization_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_manager_authorization_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `store_manager_id` bigint unsigned NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` datetime NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1' COMMENT 'Refer ENUM: StoreManagerAuthorizationCodeStatuses',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_manager_authorization_codes_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `store_manager_authorization_codes_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `passcode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price_override_type` tinyint NOT NULL DEFAULT '1',
  `price_override_limit_percentage_for_item` decimal(5,2) DEFAULT NULL,
  `price_override_limit_percentage_for_cart` decimal(5,2) NOT NULL,
  `can_manage_wholesale` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token_expiration_at` datetime DEFAULT NULL,
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_managers_employee_id_unique` (`employee_id`),
  UNIQUE KEY `store_managers_username_unique` (`username`),
  CONSTRAINT `store_managers_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_store_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_store_manager` (
  `store_id` bigint unsigned NOT NULL,
  `store_manager_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `store_store_manager_store_id_foreign` (`store_id`),
  KEY `store_store_manager_store_manager_id_foreign` (`store_manager_id`),
  CONSTRAINT `store_store_manager_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `store_store_manager_store_manager_id_foreign` FOREIGN KEY (`store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_wise_daily_totals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_wise_daily_totals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned NOT NULL,
  `brand_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned DEFAULT NULL,
  `total_sales_count` int NOT NULL DEFAULT '0',
  `total_units_sold` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_sales_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount_return` decimal(10,2) DEFAULT NULL,
  `total_units_return` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_wise_daily_totals_company_id_foreign` (`company_id`),
  KEY `store_wise_daily_totals_store_id_foreign` (`store_id`),
  KEY `store_wise_daily_totals_counter_update_id_foreign` (`counter_update_id`),
  KEY `store_wise_daily_totals_brand_id_foreign` (`brand_id`),
  KEY `store_wise_daily_totals_location_id_foreign` (`location_id`),
  CONSTRAINT `store_wise_daily_totals_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `store_wise_daily_totals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `store_wise_daily_totals_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `store_wise_daily_totals_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `store_wise_daily_totals_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `registration_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_tax_percentage` decimal(5,2) NOT NULL,
  `sales_return_days_limit` int NOT NULL,
  `credit_note_expiration_days` int DEFAULT NULL,
  `loyalty_point_expiration_days` int DEFAULT NULL,
  `is_automatic_day_close` tinyint(1) NOT NULL,
  `automatic_day_close_time` time DEFAULT NULL,
  `receipt_footer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disclaimer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cash_out_limit_info` decimal(10,2) DEFAULT '0.00',
  `cash_out_limit_warning` decimal(10,2) DEFAULT '0.00',
  `cash_out_limit_restrict` decimal(10,2) DEFAULT '0.00',
  `enable_ioi_city_mall_data_sharing` tinyint(1) NOT NULL DEFAULT '0',
  `ioi_city_mall_machine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable_trx_mall_data_sharing` tinyint(1) NOT NULL DEFAULT '0',
  `trx_mall_machine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_fall_down_percentage` decimal(5,2) NOT NULL DEFAULT '80.00',
  `share_inventory_to_external_companies` tinyint(1) NOT NULL DEFAULT '0',
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stores_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `stores_phone_company_id_unique` (`phone`,`company_id`),
  UNIQUE KEY `stores_code_company_id_unique` (`code`,`company_id`),
  KEY `stores_company_id_foreign` (`company_id`),
  KEY `stores_region_id_foreign` (`region_id`),
  KEY `stores_country_id_foreign` (`country_id`),
  KEY `stores_state_id_foreign` (`state_id`),
  KEY `stores_city_id_foreign` (`city_id`),
  CONSTRAINT `stores_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `stores_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `stores_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `stores_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`),
  CONSTRAINT `stores_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `styles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `styles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `styles_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `styles_code_company_id_unique` (`code`,`company_id`),
  KEY `styles_company_id_foreign` (`company_id`),
  CONSTRAINT `styles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `super_admin_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `super_admin_password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `super_admin_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `super_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `super_admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `super_admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_company_id_unique` (`name`,`company_id`),
  KEY `tags_company_id_foreign` (`company_id`),
  CONSTRAINT `tags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_variant` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `templates_company_id_foreign` (`company_id`),
  CONSTRAINT `templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timezones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timezones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `top_twenty_aggregate_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `top_twenty_aggregate_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `counter_update_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `quantity` decimal(16,6) NOT NULL,
  `gross_sales` decimal(16,6) NOT NULL,
  `discount` decimal(16,6) NOT NULL,
  `net_sales` decimal(16,6) NOT NULL,
  `tax` decimal(16,6) NOT NULL,
  `total_amount` decimal(16,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `top_twenty_aggregate_data_product_id_foreign` (`product_id`),
  KEY `top_twenty_aggregate_data_counter_update_id_foreign` (`counter_update_id`),
  CONSTRAINT `top_twenty_aggregate_data_counter_update_id_foreign` FOREIGN KEY (`counter_update_id`) REFERENCES `counter_updates` (`id`),
  CONSTRAINT `top_twenty_aggregate_data_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transit_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transit_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `inventory_unit_id` bigint unsigned NOT NULL,
  `affected_by_id` bigint NOT NULL,
  `affected_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,6) NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transit_stocks_inventory_id_foreign` (`inventory_id`),
  KEY `transit_stocks_inventory_unit_id_foreign` (`inventory_unit_id`),
  CONSTRAINT `transit_stocks_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `transit_stocks_inventory_unit_id_foreign` FOREIGN KEY (`inventory_unit_id`) REFERENCES `inventory_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_of_measure_derivatives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_of_measure_derivatives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unit_of_measure_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ratio` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_of_measure_derivatives_name_unit_of_measure_id_unique` (`name`,`unit_of_measure_id`),
  KEY `unit_of_measure_derivatives_unit_of_measure_id_foreign` (`unit_of_measure_id`),
  CONSTRAINT `unit_of_measure_derivatives_unit_of_measure_id_foreign` FOREIGN KEY (`unit_of_measure_id`) REFERENCES `unit_of_measures` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_of_measures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_of_measures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `allow_decimal_qty` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_of_measures_name_company_id_unique` (`name`,`company_id`),
  KEY `unit_of_measures_company_id_foreign` (`company_id`),
  CONSTRAINT `unit_of_measures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int NOT NULL,
  `forgot_password_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token_expiration_at` datetime DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_employee_id_foreign` (`employee_id`),
  CONSTRAINT `users_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_consignment` tinyint(1) NOT NULL DEFAULT '0',
  `commission_percentage` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendors_name_unique` (`name`),
  UNIQUE KEY `vendors_phone_company_id_unique` (`phone`,`company_id`),
  KEY `vendors_company_id_foreign` (`company_id`),
  CONSTRAINT `vendors_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `void_sale_reason_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `void_sale_reason_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `void_sale_reason_id` bigint unsigned NOT NULL,
  `type_id` tinyint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `void_sale_reason_types_void_sale_reason_id_foreign` (`void_sale_reason_id`),
  CONSTRAINT `void_sale_reason_types_void_sale_reason_id_foreign` FOREIGN KEY (`void_sale_reason_id`) REFERENCES `void_sale_reasons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `void_sale_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `void_sale_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `void_sale_reasons_reason_company_id_unique` (`reason`,`company_id`),
  KEY `void_sale_reasons_company_id_foreign` (`company_id`),
  CONSTRAINT `void_sale_reasons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `void_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `void_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `void_sale_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `voided_by_store_manager_id` bigint unsigned NOT NULL,
  `void_sale_reason_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `void_sales_sale_id_foreign` (`sale_id`),
  KEY `void_sales_voided_by_store_manager_id_foreign` (`voided_by_store_manager_id`),
  KEY `void_sales_void_sale_reason_id_foreign` (`void_sale_reason_id`),
  CONSTRAINT `void_sales_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `void_sales_void_sale_reason_id_foreign` FOREIGN KEY (`void_sale_reason_id`) REFERENCES `void_sale_reasons` (`id`),
  CONSTRAINT `void_sales_voided_by_store_manager_id_foreign` FOREIGN KEY (`voided_by_store_manager_id`) REFERENCES `store_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_configuration_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_configuration_category` (
  `voucher_configuration_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `voucher_configuration_category_voucher_configuration_id_foreign` (`voucher_configuration_id`),
  KEY `voucher_configuration_category_category_id_foreign` (`category_id`),
  CONSTRAINT `voucher_configuration_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `voucher_configuration_category_voucher_configuration_id_foreign` FOREIGN KEY (`voucher_configuration_id`) REFERENCES `voucher_configurations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_configuration_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_configuration_product` (
  `voucher_configuration_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `voucher_configuration_product_voucher_configuration_id_foreign` (`voucher_configuration_id`),
  KEY `voucher_configuration_product_product_id_foreign` (`product_id`),
  CONSTRAINT `voucher_configuration_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `voucher_configuration_product_voucher_configuration_id_foreign` FOREIGN KEY (`voucher_configuration_id`) REFERENCES `voucher_configurations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_configuration_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_configuration_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_configuration_id` bigint unsigned NOT NULL,
  `minimum_spend_amount` decimal(10,2) NOT NULL,
  `maximum_spend_amount` decimal(10,2) DEFAULT NULL,
  `get_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_configuration_tiers_voucher_configuration_id_foreign` (`voucher_configuration_id`),
  CONSTRAINT `voucher_configuration_tiers_voucher_configuration_id_foreign` FOREIGN KEY (`voucher_configuration_id`) REFERENCES `voucher_configurations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `restricted_by_type` smallint NOT NULL COMMENT 'this is used for both - generation + redemption; 1: All, 2: Member Only, 3: Non-Member Only',
  `voucher_type` tinyint NOT NULL COMMENT '1: Birthday Voucher, 2: Tier Voucher, 3: Multiple Voucher',
  `exclude_by_type` smallint NOT NULL COMMENT 'this is used for both - generation + redemption; 1: None, 2: Products, 3: Categories',
  `issue_minimum_spend_amount` decimal(10,2) NOT NULL,
  `use_minimum_spend_amount` decimal(10,2) DEFAULT NULL,
  `validity_days` int NOT NULL,
  `discount_type` tinyint NOT NULL COMMENT '1: Percentage, 2: Flat',
  `get_value` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_by_id` int DEFAULT NULL,
  `created_by_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0: Inactive, 1: Active',
  `dream_price_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `item_wise_promotion_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `cart_wide_promotion_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `redemption_foot_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `handover_foot_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `terms_and_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_configurations_company_id_foreign` (`company_id`),
  CONSTRAINT `voucher_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `voucher_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `action_type_id` tinyint NOT NULL COMMENT '1. Reset, 2. Cancel',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `store_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `sale_id` bigint unsigned DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_transactions_voucher_id_foreign` (`voucher_id`),
  KEY `voucher_transactions_store_id_foreign` (`store_id`),
  KEY `voucher_transactions_sale_id_foreign` (`sale_id`),
  KEY `voucher_transactions_location_id_foreign` (`location_id`),
  CONSTRAINT `voucher_transactions_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `voucher_transactions_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `voucher_transactions_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `voucher_transactions_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_configuration_id` bigint unsigned NOT NULL,
  `member_id` bigint unsigned DEFAULT NULL,
  `generated_by_sale_id` bigint unsigned DEFAULT NULL,
  `created_by_store_id` bigint unsigned DEFAULT NULL,
  `created_by_location_id` bigint unsigned DEFAULT NULL,
  `discount_type` tinyint NOT NULL COMMENT '1: Percentage, 2: Flat',
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_spend_amount` decimal(10,2) NOT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `flat_amount` decimal(10,2) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `dream_price_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `item_wise_promotion_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `cart_wide_promotion_applicable` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vouchers_voucher_configuration_id_foreign` (`voucher_configuration_id`),
  KEY `vouchers_customer_id_foreign` (`member_id`),
  KEY `vouchers_generated_by_sale_id_foreign` (`generated_by_sale_id`),
  KEY `vouchers_created_by_store_id_foreign` (`created_by_store_id`),
  KEY `vouchers_created_by_location_id_foreign` (`created_by_location_id`),
  CONSTRAINT `vouchers_created_by_location_id_foreign` FOREIGN KEY (`created_by_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `vouchers_created_by_store_id_foreign` FOREIGN KEY (`created_by_store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `vouchers_customer_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `vouchers_generated_by_sale_id_foreign` FOREIGN KEY (`generated_by_sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `vouchers_voucher_configuration_id_foreign` FOREIGN KEY (`voucher_configuration_id`) REFERENCES `voucher_configurations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `warehouse_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forgot_password_token_expiration_at` datetime DEFAULT NULL,
  `fcm_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_login_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_managers_employee_id_unique` (`employee_id`),
  UNIQUE KEY `warehouse_managers_username_unique` (`username`),
  CONSTRAINT `warehouse_managers_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `warehouse_warehouse_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_warehouse_manager` (
  `warehouse_id` bigint unsigned NOT NULL,
  `warehouse_manager_id` bigint unsigned NOT NULL,
  `invisible_id` bigint unsigned NOT NULL AUTO_INCREMENT /*!80023 INVISIBLE */,
  PRIMARY KEY (`invisible_id`),
  KEY `warehouse_warehouse_manager_warehouse_id_foreign` (`warehouse_id`),
  KEY `warehouse_warehouse_manager_warehouse_manager_id_foreign` (`warehouse_manager_id`),
  CONSTRAINT `warehouse_warehouse_manager_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `warehouse_warehouse_manager_warehouse_manager_id_foreign` FOREIGN KEY (`warehouse_manager_id`) REFERENCES `warehouse_managers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `sst_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fax` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `share_inventory_to_external_companies` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_name_company_id_unique` (`name`,`company_id`),
  UNIQUE KEY `warehouses_code_company_id_unique` (`code`,`company_id`),
  KEY `warehouses_company_id_foreign` (`company_id`),
  KEY `warehouses_country_id_foreign` (`country_id`),
  KEY `warehouses_state_id_foreign` (`state_id`),
  KEY `warehouses_city_id_foreign` (`city_id`),
  CONSTRAINT `warehouses_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `warehouses_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`),
  CONSTRAINT `warehouses_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_100000_create_super_admin_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2022_03_31_091001_create_super_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2022_04_07_060501_create_brands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2022_04_12_110953_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2022_04_12_115606_create_media_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_04_14_070352_create_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_04_18_081524_create_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2022_04_19_090811_create_brand_company_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2022_04_19_112923_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2022_04_25_133950_create_sale_return_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_04_26_070842_create_stores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_04_26_101020_create_unit_of_measures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_04_27_043210_create_seasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_04_27_092033_create_unit_of_measure_derivatives_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2022_04_27_093119_create_colors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2022_04_27_104007_create_sizes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2022_04_27_121150_create_styles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2022_04_28_054105_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2022_04_29_051334_create_counters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2022_04_29_093653_create_cashier_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2022_04_29_095126_create_cashier_group_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2022_05_02_044728_create_tags_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2022_05_02_060914_create_brand_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2022_05_03_053043_create_cashiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2022_05_03_053412_create_cashier_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2022_05_03_060926_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2022_05_03_064731_create_category_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2022_05_03_065111_create_product_tag_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2022_05_04_100325_create_payment_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2022_05_05_071355_create_promoters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2022_05_05_071739_create_promoter_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2022_05_09_091415_create_warehouses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2022_05_09_100847_create_goods_received_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2022_05_10_044606_create_store_managers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2022_05_10_044650_create_store_store_manager_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2022_05_11_125921_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2022_05_12_124707_create_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2022_05_12_124746_create_purchase_amounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2022_05_12_124747_create_goods_received_note_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2022_05_12_125357_create_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2022_05_12_125408_create_inventory_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2022_05_12_142429_create_inventory_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2022_05_13_063444_create_cash_movement_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2022_05_13_072129_create_petty_cash_usage_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2022_05_13_113653_create_void_sale_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2022_05_13_130315_remove_price_theshold_columns_from_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2022_05_13_132756_create_directors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2022_05_13_132857_create_director_store',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2022_05_13_140116_add_price_override_limit_percentage_to_cashier_group_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2022_05_16_041434_add_price_override_limit_to_store__managers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2022_05_16_115122_update_column_created_by_to_created_by_id_in_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2022_05_16_125227_remove_column_credit_balance_and_loyalty_points_in_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2022_05_18_035959_create_complimentary_item_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2022_05_18_071212_create_dream_prices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2022_05_18_071306_create_dream_price_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2022_05_18_071343_create_dream_price_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2022_05_18_084744_create_import_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2022_05_18_094011_create_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2022_05_18_094027_create_promotion_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2022_05_18_094038_create_promotion_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2022_05_18_094058_create_product_promotion_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2022_05_18_094113_create_category_promotion_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2022_05_18_094145_create_promotion_week_days_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2022_05_18_094202_create_promotion_month_dates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2022_05_19_094316_create_import_record_failed_rows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2022_05_23_054717_update_column_types_to_import_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2022_05_23_085637_create_email_configurations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2022_05_24_101451_remove_priority_column_from_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2022_05_24_115634_update_stock_column_default_value_to_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2022_05_24_120034_update_batch_id_column_nullable_to_inventory_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2022_05_25_041023_create_counter_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2022_05_25_041040_add_new_column_counter_update_id_to_counters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2022_05_25_041058_add_new_column_counter_update_id_to_cashiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2022_05_26_063920_remove_column_opened_at_to_counters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2022_05_30_101248_update_all_columns_type_to_purchase_amounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2022_05_30_111445_update_columns_type_to_promotion_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2022_05_30_111640_update_flat_amount_column_type_to_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2022_05_30_111946_update_price_column_type_to_dream_price_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2022_05_30_112109_update_spent_till_now_column_type_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2022_05_30_112211_update_columns_type_to_counter_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2022_05_30_112632_update_columns_type_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2022_05_30_113256_update_column_quantity_type_to_inventory_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2022_05_30_113446_update_column_quantity_type_to_goods_received_note_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2022_05_30_113607_update_column_stock_type_to_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2022_05_30_113715_update_columns_type_to_inventory_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2022_05_30_113825_update_column_price_type_to_dream_price_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2022_06_01_100626_add_created_by_columns_to_import_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2022_06_03_051804_stock_adjustments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2022_06_03_052437_stock_adjustment_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2022_06_07_111703_update_all_columns_type_to_purchase_amounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2022_06_07_111834_update_columns_type_to_promotion_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2022_06_07_113128_update_flat_amount_column_type_to_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2022_06_07_113158_update_price_column_type_to_dream_price_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2022_06_07_113313_update_spent_till_now_column_type_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2022_06_07_113452_update_columns_type_to_counter_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2022_06_07_113540_update_columns_type_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2022_06_07_113642_update_column_quantity_type_to_inventory_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2022_06_07_113758_update_column_quantity_type_to_goods_received_note_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2022_06_07_113832_update_column_stock_type_to_inventories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2022_06_07_113921_update_columns_type_to_inventory_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2022_06_07_114052_update_column_price_type_to_dream_price_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2022_06_10_073751_add_comment_on_column_records_in_file_in_import_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2022_06_14_123206_create_stock_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2022_06_14_124147_create_stock_transfer_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2022_06_14_131533_create_stock_transfer_item_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2022_06_15_121136_rename_email_configurations_to_email_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2022_06_16_100005_create_sales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2022_06_16_100026_create_sale_discounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2022_06_16_100041_create_void_sales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2022_06_16_100052_create_sale_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2022_06_16_100103_create_sale_item_discounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2022_06_16_100117_create_sale_item_promoter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2022_06_16_100137_create_sale_item_price_overrides_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2022_06_16_100150_create_sale_mismatches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2022_06_16_100202_create_sale_item_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2022_06_16_100213_create_sale_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2022_06_17_084200_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2022_06_17_084201_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2022_06_17_084202_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2022_06_20_052755_create_voucher_configurations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2022_06_20_052811_create_voucher_configuration_tiers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2022_06_20_062109_voucher_configuration_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2022_06_20_062118_voucher_configuration_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2022_06_20_065610_create_cashbacks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2022_06_20_065642_create_cashback_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2022_06_20_065706_create_cashback_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2022_06_20_065747_create_cashback_store_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2022_06_25_084234_rename_column_limited_by_type_to_excluded_by_in_cashbacks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2022_06_29_043844_create_memberships_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2022_06_29_051417_add_only_for_employees_column_in_promotions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2022_06_29_083543_create_loyalty_campaigns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2022_06_29_112943_create_petty_cash_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2022_07_01_103244_add_counter_update_id_foreign_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2022_07_05_042735_update_column_batch_id_null_in_stock_transfer_item_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2022_07_05_042929_update_column_received_quantity_null_in_stock_transfer_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2022_07_05_052229_update_column_transfer_order_number_null_in_stock_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2022_07_06_113358_update_column_voucher_configuration_get_value_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2022_07_11_065234_create_cash_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2022_07_13_114816_add_sale_id_in_sale_discounts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2022_07_15_113736_set_auto_increment_to100_in_payment_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2022_07_18_101633_make_company_id_nullable_in_payment_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2022_07_19_101459_add_stock_movement_decision_to_stock_transfer_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2022_07_20_041925_add_column_parent_stock_transfer_id_in_stock_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2022_07_20_122700_add_group_id_in_sale_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2022_07_22_130551_add_expiration_day_limit_column_in_stores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2022_07_23_034756_create_booking_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2022_07_23_035103_create_booking_payment_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2022_07_23_035250_create_booking_payment_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2022_07_23_043440_create_booking_payment_uses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2022_07_23_044047_create_booking_payment_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2022_07_23_102136_update_column_status_in_booking_payment_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2022_07_23_184127_create_stock_transfer_item_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2022_07_25_064733_set_auto_increment_to100_in_petty_cash_usage_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2022_07_25_065807_make_company_id_nullable_in_petty_cash_usage_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2022_07_25_112010_create_sale_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2022_07_25_112041_create_sale_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2022_07_25_112134_create_sale_return_mismatches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2022_07_25_124534_create_credit_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2022_07_25_124601_create_credit_note_expirations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2022_07_25_124617_create_credit_note_uses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2022_07_25_124631_create_credit_note_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2022_07_27_091929_create_vouchers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2022_07_29_074647_add_columns_in_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2022_07_29_074648_add_columns_in_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2022_07_29_074649_add_columns_in_promoters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2022_07_29_074650_add_columns_in_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2022_07_29_075730_create_promoter_commissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2022_07_29_075731_create_promoter_commission_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2022_07_29_075732_create_loyalty_point_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2022_07_29_075733_create_membership_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2022_07_29_140855_update_column_external_id_in_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2022_08_03_100548_remove_discrepancy_movement_scenario_column_from_stock_transfer_item_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2022_08_04_092417_create_sale_cashbacks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2022_08_05_060211_create_loyalty_points_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2022_08_05_071645_add_location_columns_to_sale_return_reasons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2022_08_05_082208_remove_unique_in_name_into_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2022_08_05_110403_rename_column_minimum_spend_amount_to_minimum_lifetime_spend_amount_in_member_ships_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2022_08_06_130713_add_column_derivative_id_in_sale_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2022_08_08_112840_add_returned_quantity_in_sale_item_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2022_08_09_104930_remove_columns_in_counter_update',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2022_08_09_105023_add_columns_in_counter_updates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2022_08_09_110910_create_close_counter_denominations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2022_08_09_111625_create_close_counter_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2022_08_11_054704_add_column_spend_till_now_in_employees_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2022_08_13_105648_drop_membership_update_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2022_08_13_112451_create_membership_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2022_08_13_114414_add_generated_by_sale_id_column_to_vouchers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2022_08_16_102112_create_store_day_closes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2022_08_16_102137_create_store_day_close_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2022_08_17_081917_update_column_expiry_date_in_loyalty_points_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2022_08_18_103945_add_columns_in_stores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2022_08_23_104522_update_column_closed_by_store_manager_id_in_store_day_closes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2022_08_23_134845_create_designations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2022_08_23_135000_add_column_designation_id_in_employees_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2022_08_25_082455_add_comments_to_voucher_configurations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2022_08_27_101021_add_columns_in_loyalty_points_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2022_08_29_110418_nullable_column_in_employees_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2022_08_29_110446_nullable_column_in_promoters_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2022_08_30_102804_nullable_column_in_customers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2022_08_30_113449_code_column_in_promoters_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2022_08_30_130136_drop_designation_id_in_employees_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2022_08_31_140903_add_designation_id_in_employees_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2022_08_31_141101_drop_sale_id_in_sale_discount_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2022_08_31_141114_add_sale_id_in_sale_discount_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2022_09_07_115723_add_is_exchange_in_sale_items',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2022_09_13_134159_add_cash_columns_in_counter_updates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2022_09_13_134160_add_cash_columns_in_store_day_closes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2022_09_15_090146_create_sale_item_complimentaries_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2022_09_19_131343_create_pos_mismatches_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2022_09_19_132335_drop_sale_and_return_mismatches_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2022_09_21_122912_add_get_quantity_in_promotion_tiers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2022_09_24_111635_add_prices_columns_in_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2022_09_29_123328_add_registraton_number_and_sst_number_in_warehouses',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2022_10_05_103809_create_stock_transfer_reasons_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2022_10_06_123148_add_adjustment_date_in_stock_adjustments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2022_10_07_112835_add_start_date_and_end_date_in_loyalty_campaigns',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2022_10_08_112211_alter_customers_add_card_number_column_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2022_10_08_125221_rename_price_to_retail_price_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2022_10_08_132216_create_denominations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2022_10_08_154918_add_stock_transfer_reason_id_column_to_stock_transfer_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2022_10_10_102713_change_type_card_number_in_customers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2022_10_10_103844_add_transfer_date_in_stock_transfer_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2022_10_10_120052_add_attention_name_in_stock_transfer_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2022_10_11_091004_add_bill_reference_number_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2022_10_11_091040_alter_employees_add_card_number_column_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2022_10_11_095552_add_requested_store_id_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2022_10_11_100915_change_type_card_number_in_employees_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2022_10_13_063901_add_strat_date_and_end_date_in_cashbacks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2022_10_15_093337_add_columns_in_counter_updates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2022_10_15_093338_add_columns_in_store_day_closes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2022_10_17_095443_create_notifications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2022_10_17_105127_add_image_name_colummn_in_payment_types',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2022_10_21_061745_add_change_due_column_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2022_10_21_113924_rename_customer_table_to_member_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2022_10_21_191628_rename_column_customer_id_to_member_id_in_payment_types_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2022_10_21_193446_rename_column_customer_id_to_member_id_in_booking_table_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2022_10_22_062555_rename_column_customer_id_to_member_id_vouchers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2022_10_22_123934_rename_column_customer_id_to_member_id_promotions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2022_11_01_090128_add_column_remarks_in_stock_transfer_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2022_11_01_105634_rename_column_customer_to_member_in_company_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2022_11_03_031559_create_stock_takes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2022_11_03_031626_create_stock_take_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2022_11_03_043916_add_column_received_date_in_stock_transfer_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2022_11_04_043438_add_columns_in_customers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2022_11_04_044032_add_column_is_may_bank_card_and_is_may_bank_qr_code_in_payment_types_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2022_11_07_110657_drop_promoter_commissions_and_promoter_commission_updates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2022_11_07_111345_drop_promoter_commission_percentage_above_sales_target_and_promoter_commission_percentage_below_sales_target_in_categories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2022_11_07_144237_create_brand_promotion_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2022_11_08_051039_add_column_new_member_loyalty_points_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2022_11_10_041531_create_unit_of_measure_two_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2022_11_10_071158_add_columns_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2022_11_12_093211_add_offline_id_in_booking_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2022_11_14_102909_update_column_sale_id_and_loyalty_campaign_id_in_loyalty_points_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2022_11_16_092251_add_column_happened_at_in_booking_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2022_11_16_095018_add_columns_in_cash_movements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2022_11_16_103521_add_column_closed_happened_at_and_opened_happened_at_in_counter_updates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2022_11_16_105759_drop_column_petty_cash_usage_id_in_sale_cashbacks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2022_11_16_120047_drop_column_total_petty_cash_usage_amount_in_counter_update_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2022_11_16_120524_drop_petty_cash_usage_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2022_11_22_061832_add_column_in_stores_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2022_11_22_100638_add_column_extra_details_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2022_11_22_132807_company_add_commission_type_column',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2022_11_23_050254_set_column_offline_id_unique_in_booking_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2022_11_24_103756_update_column_happened_at_in_cash_movements',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2022_11_25_043118_add_columns_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2022_11_25_115611_update_column_authorizer_in_cash_movements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2022_11_25_115612_add_cash_movement_reason_id_in_sale_cashback_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2022_11_25_132913_nullable_column_company_id_cash_movement_reasons_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2022_11_26_084325_add_commission_configuration_in_promoter',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2022_11_26_123034_configure_promotion_in_departments',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2022_11_28_114637_add_column_for_changes_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2022_11_28_131957_add_columns_in_stock_transfer_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2022_11_28_133519_create_new_sequences_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2022_11_29_234605_create_promoter_commissions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2022_11_30_184655_add_column_package_total_quantity_in_stock_transfer_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2022_11_30_185032_required_code_column_in_stores',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2022_12_01_113124_create_promoter_commission_updates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2022_12_06_134335_create_new_booking_payment_promoter_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2022_12_06_134859_create_new_booking_payment_product_promoter_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2022_12_07_123033_create_petty_cash_usages_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2022_12_08_120303_add_columns_in_booking_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2022_12_15_113824_remove_is_promoter_required_column_in_sale_column_in_stores',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2022_12_17_203347_add_layaway_completed_at_column_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2022_12_20_115259_add_column_compound_product_name_in_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2022_12_21_131609_create_gift_cards_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2022_12_21_133346_create_gift_card_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2022_12_21_184334_rename_column_transfer_order_number_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2022_12_21_190013_drop_parent_stock_transfer_id_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2022_12_21_191740_rename_column_discrepancy_decision_in_stock_transfer_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2022_12_22_002100_create_credit_note_void_uses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2022_12_22_151651_create_booking_payment_void_uses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2022_12_22_151842_rename_discrepancy_decision_to_discrepancy_type_stock_transfer_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2022_12_22_191715_rename_column_max_promoters_per_item_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2022_12_23_143857_create_voucher_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2022_12_23_144933_add_column_cancelled_at_in_vouchers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2022_12_23_145404_add_column_require_date_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2022_12_23_161013_add_column_is_bill_reference_number_mandatory_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2022_12_24_190952_add_sale_return_id_column_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2022_12_24_195040_add_sale_return_item_id_column_in_sale_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2022_12_24_204209_create_sale_void_cashbacks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2022_12_26_141826_add_columns_in_stock_takes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2022_12_27_211938_add_column_payment_terminal_key_in_payment_types_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2022_12_28_222248_add_unique_for_offline_id_in_sales_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2022_12_28_222310_add_unique_for_offline_id_in_sale_returns_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2022_12_29_134845_update_comment_in_sequences_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2022_12_29_134917_add_column_in_stock_transfers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2022_12_30_134800_create_counter_update_events_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2022_12_30_170819_create_counter_update_declaration_attempts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2022_12_30_170833_create_counter_update_declaration_attempt_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2023_01_02_195253_add_column_in_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2023_01_02_220904_add_column_in_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2023_01_09_135819_create_brand_loyalty_campaign_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2023_01_09_185110_add_column_staff_price_in_products_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2023_01_20_163000_add_column_allow_exchange_to_different_store_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2023_01_27_205642_add_invisible_primary_keys_to_pivot_tables_for_oci_ha',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2023_01_20_143557_create_sale_item_exchanges_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2023_02_01_213441_update_total_credit_note_used_amount_in_counter_updates',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2023_02_02_164651_add_column_allow_price_override_cart_level_in_companies_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2023_02_03_164356_create_warehouse_managers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2023_02_03_172913_create_warehouse_warehouse_manager_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2023_02_06_155004_update_column_in_sequences_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2023_02_09_172406_update_purchase_amount_id_nullable_in_inventory_updates',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2023_02_09_215508_add_index_in_products_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2023_02_17_191701_update_discrepancy_status_in_stock_transfer_items',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2023_02_10_164522_create_member_users_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2023_02_21_183729_update_received_quantity_in_stock_transfers',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2023_02_22_183729_update_received_quantity_in_stock_transfers',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2023_02_20_130134_add_column_remarks_in_loyalty_point_updates_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2023_02_17_171033_create_pos_advertisements_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2023_02_17_172045_create_pos_advertisement_store_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2023_02_08_205614_rename_column_price_override_limit_percentage_to_price_override_limit_percentage_for_items_in_store_maangers_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2023_02_08_205720_add_column_price_override_limit_percentage_for_cart_in_store_maangers_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2023_02_08_210203_rename_column_price_override_limit_percentage_to_price_override_limit_percentage_for_items_in_directors_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2023_02_08_210219_add_column_price_override_limit_percentage_for_cart_in_directors_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2023_02_08_210243_rename_column_price_override_limit_percentage_to_price_override_limit_percentage_for_items_in_cashier_groups_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2023_02_08_210253_add_column_price_override_limit_percentage_for_cart_in_cashier_groups_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2023_02_09_160429_create_sale_price_overrides_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2023_02_27_183729_update_received_quantity_in_stock_transfers',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2023_02_28_184455_add_nullable_column_in_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2023_03_02_123125_drop_member_user_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2023_03_02_123148_add_column_in_members_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2023_02_20_130636_add_column_import_records_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2023_03_06_143212_update_void_sale_number_in_void_sale_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2023_02_24_115721_rename_column_store_id_to_location_id_in_stock_takes_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2023_02_24_121016_add_column_location_type_on_stock_takes_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2023_02_27_170448_add_contrained_in_requested_by_id_and_submitted_by_id_in_stock_takes',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2023_02_21_175716_add_column_in_sizes_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2023_03_06_183125_create_daily_totals_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2023_03_08_224348_add_column_yearly_target_in_company_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2023_03_09_195355_add_data_in_daily_totals_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (361,'2023_03_16_183729_update_sale_return_inventory_updates',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2023_03_16_224901_stock_adjustment_inventory_update_mapping_fix',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2023_16_16_183729_update_goods_received_note_in_inventory_updates',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (364,'2023_03_21_185047_add_soft_delete_in_promoter_commissions_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (365,'2023_03_21_185101_add_soft_delete_in_promoter_commission_updates_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (366,'2023_03_21_203125_create_promoter_commission_regenerations_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (367,'2023_03_22_203347_rename_daily_total_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (368,'2023_03_22_203349_update_category_wise_daily_total_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (369,'2023_03_22_201759_create_store_wise_daily_totals_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (370,'2023_03_22_210339_add_data_store_wise_daily_totals_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (371,'2023_03_21_180052_add_column_in_store_day_closes_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (372,'2023_03_21_181431_store_day_closes_table_update_the_counter_update_ids',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (373,'2023_03_28_223735_update_data_store_wise_daily_totals_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (374,'2023_03_30_205409_static_member_details_in_members_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (375,'2023_03_31_182324_add_column_reason_on_promoter_commission_regenerations_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (376,'2023_04_02_120022_store_day_closes_code_fix',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (377,'2023_04_04_194831_create_hold_sales_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (378,'2023_04_04_195649_create_hold_sale_details_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (379,'2023_04_04_200833_create_hold_sale_items_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (380,'2023_04_05_193358_remove_column_hold_sale_id_in_hold_sale_items_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (381,'2023_04_05_193428_add_column_hold_sale_detail_id_in_hold_sale_items_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (382,'2023_04_07_154018_add_column_sales_collection_amount_on_counter_updates',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (383,'2023_04_08_211409_add_column_in_hold_sales_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (384,'2023_04_10_150323_update_sales_collection_amount_in_counter_updates',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (385,'2023_04_11_230648_birthday_voucher_generation_script',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (386,'2023_04_12_145842_add_column_sales_collection_amount_on_store_day_close',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (387,'2023_04_05_204927_add_column_is_uploaded_products_on_stock_takes_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (388,'2018_08_08_100000_create_telescope_entries_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (389,'2023_04_17_195325_add_column_created_by_id_and_created_by_type_on_promotions_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (390,'2023_04_17_204401_add_column_created_by_id_and_created_by_type_on_dream_prices_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (391,'2023_04_18_123726_add_column_created_by_id_and_created_by_type_on_voucher_configuration_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (392,'2023_04_18_132133_add_column_created_by_id_and_created_by_type_on_loyalty_campaigns_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (393,'2023_04_18_135249_add_column_created_by_id_and_created_by_type_on_memberships_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (394,'2023_04_17_170823_add_column_created_by_type_in_goods_received_notes_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (395,'2023_04_18_142618_add_column_created_by_id_and_created_by_type_on_products_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (396,'2023_04_18_172554_add_column_created_by_id_and_created_by_type_on_employees_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (397,'2023_04_18_174623_add_store_id_and_brand_id_in_promoter_commission_updates_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (398,'2023_04_19_150154_update_the_query_promoter_commission_total_sales',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (399,'2023_04_20_140538_january_promoter_commission_generate',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (400,'2023_04_20_140539_february_promoter_commission_generate',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (401,'2023_04_20_140540_march_promoter_commission_generate',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (402,'2023_04_21_145928_regenerate_sales_collection_for_counters',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (403,'2023_04_21_200743_rename_column_created_by__type__id_in_goods_received_notes_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (404,'2023_04_21_211022_store_day_closes_code_fix',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (405,'2023_05_02_170241_january_promoter_commission_generate',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (406,'2023_05_02_170302_february_promoter_commission_generate',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (407,'2023_05_02_170316_march_promoter_commission_generate',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (408,'2023_05_02_170328_april_promoter_commission_generate',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (409,'2023_04_20_160936_add_column_category_wise_daily_total_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (410,'2023_04_20_161006_add_column_store_wise_daily_total_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (411,'2023_04_20_162822_update_category_wise_daily_total_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (412,'2023_04_20_162833_update_store_wise_daily_total_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (413,'2023_05_08_211117_add_expires_at_column_in_personal_access_tokens_tables',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (414,'2023_05_11_135150_update_category_wise_daily_total_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (415,'2023_05_11_135533_update_store_wise_daily_total_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (416,'2023_05_09_193822_add_column_in_hold_sale_details_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (417,'2023_05_10_135617_create_hold_sale_return_items',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (418,'2023_05_11_184407_add_type_id_column_in_hold_sales_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (419,'2023_05_11_200129_create_hold_booking_payment_items',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (420,'2023_05_01_152757_add_column_status_in_members_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (421,'2023_04_16_140915_create_member_lite_cards_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (422,'2023_04_17_192910_add_uuid_column_in_members_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (423,'2023_05_22_120822_add_column_username_and_password_on_promoters',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (424,'2023_05_11_201708_add_column_allow_negative_inventory_on_companies',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (425,'2023_05_23_161743_add_received_date_in_stock_transfer_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (426,'2023_05_17_141305_add_column_store_wise_daily_total_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (427,'2023_05_17_184443_add_column_category_wise_daily_total_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (428,'2023_05_17_202934_update_store_wise_daily_total_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (429,'2023_05_17_210513_update_category_wise_daily_total_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (430,'2023_05_25_130445_remove_nullable_on_code_column_in_warehouses',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (431,'2023_05_25_135009_update_store_wise_daily_total_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (432,'2023_05_10_195833_add_column_max_value_on_promotion_tiers',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (433,'2023_05_16_133944_add_column_in_members_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (434,'2023_05_16_140120_update_column_expiry_date_in_vouchers_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (435,'2023_05_29_121318_create_member_groups_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (436,'2023_05_29_163749_add_column_member_group_id_in_members_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (437,'2023_05_30_114114_create_promotion_member_group_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (438,'2023_05_30_170625_add_column_in_dream_prices_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (439,'2023_05_30_170931_create_dream_price_member_group_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (440,'2023_05_31_202753_add_column_opening_balance_in_store_day_closes_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (441,'2023_06_02_113407_update_inventory_unit_data',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (442,'2023_06_05_124844_add_column_can_manage_inventory_on_warehouse_managers',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (443,'2023_06_07_122423_update_purchase_amount',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (444,'2023_06_07_191220_add_received_date_in_stock_transfer_item_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (445,'2023_06_08_120030_update_sale_return_inventory_update',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (446,'2023_06_08_141753_add_column_is_employee_booking_payment_allowed_on_companies',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (447,'2023_05_31_210435_update_inventory_data',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (448,'2023_06_08_202441_update-inventory_units-using-inventories',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (449,'2023_06_09_122408_change_type_android_link_and_ios_link_on_member_lite_cards',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (450,'2023_06_09_145121_add_void_sale_in_inventory_update',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (451,'2023_06_09_165336_update_inventory_data',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (452,'2023_06_19_122839_create_color_groups_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (453,'2023_06_19_141410_add_column_group_id_on_colors',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (454,'2023_06_19_175014_create_regions_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (455,'2023_06_19_184035_add_column_region_id_on_stores',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (456,'2023_06_20_113501_create_past_year_data_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (457,'2023_06_22_195357_create_vendors_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (458,'2023_06_23_131905_add_column_vendor_id_on_goods_received_notes',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (459,'2023_06_23_192045_add_authorizer_by_columns_in_booking_payment_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (460,'2023_06_23_210453_add_authorizer_by_columns_in_sale_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (461,'2023_06_09_121624_create_export_records_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (462,'2023_06_09_123548_create_export_record_transactions_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (463,'2023_06_26_121527_add_ariani_old_data',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (464,'2023_06_27_121527_add_ariani_old_data_by_domain_url',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (465,'2023_06_26_152315_add_columns_location_in_goods_received_note_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (466,'2023_06_26_165857_remove_location_columns_goods_receieved_note_products',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (467,'2023_06_27_202210_create_new_stock_transfer_transactions_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (468,'2023_06_12_143919_create_employee_groups_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (469,'2023_06_13_140505_add_column_group_id_in_employees_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (470,'2023_06_13_172430_create_dream_price_employee_group_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (471,'2023_06_14_125038_create_employee_group_promotion_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (472,'2023_06_29_143547_add_column_allowed_negative_payment_on_companies',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (473,'2023_06_29_123935_add_is_extra_item_column_in_stock_transfer_items_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (474,'2023_07_03_121532_add_column_is_card_payment_on_payment_types',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (475,'2023_07_03_144317_create_size_groups_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (476,'2023_07_05_123956_add_column_group_id_on_sizes',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (477,'2023_07_06_183236_add_column_discount_applicable_type_on_companies',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (478,'2023_07_06_200924_add_column_purchase_cost_on_products',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (479,'2023_07_04_172005_add_column_allow_walk_in_member_and_allow_registered_member_and_allow_employee_on_dream_prices',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (480,'2023_07_04_180429_remove_column_is_member_required_and_only_for_employees_on_dream_prices',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (481,'2023_07_04_191824_add_column_allow_walk_in_member_and_allow_registered_member_and_allow_employee_on_promotions',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (482,'2023_07_04_192154_remove_column_is_member_required_and_only_for_employees_in_promotions',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (483,'2023_06_28_193418_create_tag_promotion_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (484,'2023_07_11_182048_rename_loyalty_points_per_ringgit_to_loyalty_points_per_one_currency_unit_on_memberships',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (485,'2023_07_11_182947_add_column_sale_payment_extra_details',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (486,'2023_07_12_212925_add_soft_delete_in_stock_transfer_items',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (487,'2023_07_11_144946_add_sale_item_in_inventory_update',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (488,'2023_07_12_143125_add_sale_item_in_inventory_update_january',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (489,'2023_07_12_144241_add_sale_item_in_inventory_update_march',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (490,'2023_07_12_144657_add_sale_item_in_inventory_update_april',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (491,'2023_07_12_145132_add_sale_item_in_inventory_update_may',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (492,'2023_07_12_145545_add_sale_item_in_inventory_update_june',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (493,'2023_07_13_122052_revert_back_inventory_current_request_order_open',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (494,'2023_07_13_122052_revert_back_inventory_current_request_order_open',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (495,'2023_07_14_140142_revert_stock_transfer_item_inventory_extra_entries',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (496,'2023_07_12_115615_add_column_voucher_configurations_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (497,'2023_07_12_131124_add_column_promotions_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (498,'2023_07_13_153228_add_column_vouchers_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (499,'2023_07_11_172340_update_inventory_data',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (500,'2023_07_14_193650_add_column_is_non_selling_item_on_products',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (501,'2023_07_18_202034_add_column_booking_payment_use_type_on_companies',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (502,'2023_07_13_190956_create_site_configurations_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (503,'2023_07_17_142526_add_column_price_override_type_on_store_managers',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (504,'2023_07_17_170922_add_column_price_override_type_on_directors',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (505,'2023_07_17_175908_add_column_price_override_type_on_cashier_groups',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (506,'2023_07_18_132319_create_promoter_groups_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (507,'2023_07_18_152825_add_column_group_id_on_promoters',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (508,'2023_07_13_125323_update_stock_trannsfer_notification_message_in_notification',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (509,'2023_07_19_210932_update_sale_status',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (510,'2023_07_19_140456_create_voucher_configuration_membership_table',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (511,'2023_07_19_162413_change_type_maximum_spend_amount_on_voucher_configuration_tiers',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (512,'2023_07_21_145601_create_cancel_layaway_sales_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (513,'2023_07_21_171524_update_column_sale_return_id_in_credit_notes_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (514,'2023_07_24_171303_add_column_in_hold_sale_details_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (515,'2023_07_17_140142_revert_deleted_stock_transfer_items',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (516,'2023_07_24_182455_add_column_in_promoter_commissions_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (517,'2023_07_24_193747_update_promoter_commissions_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (518,'2023_07_24_141817_add_column_address_in_company',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (519,'2023_07_25_143430_update_inventory_data',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (520,'2023_07_24_182647_add_column_total_new_booking_paymens_and_total_used_booking_paymnets_on_counter_updates',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (521,'2023_07_24_183410_add_column_total_new_booking_paymens_and_total_used_booking_paymnets_on_store_day_closes',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (522,'2023_07_24_121400_create_permission_tables',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (523,'2023_07_26_205717_add_column_reserved_stock_days_limit_on_companies',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (524,'2023_07_26_201837_create_reserved_stocks_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (525,'2023_07_26_203233_add_reserved_stock_column_in_inventories_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (526,'2023_07_26_211712_add_reserved_stock_column_in_inventory_units_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (527,'2023_07_27_162145_add_column_purchase_by_loyalty_point_on_products',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (528,'2023_07_31_122713_remove_column_loyalty_points_in_products',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (529,'2023_08_01_194310_create_product_loyalty_points_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (530,'2023_08_02_204621_create_brand_store_manager_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (531,'2023_08_07_125222_remove_telescope_tables',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (532,'2023_07_31_120109_add_column_flat_commission_in_departments',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (533,'2023_07_31_145741_add_column_flat_commission_in_promoter_commission_updates_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (534,'2023_08_07_160910_add_columns_stores_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (535,'2023_08_07_181207_add_column_app_version_in_counters_table',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (536,'2023_08_08_174644_update_sales_collection_amount_in_close_counter',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (537,'2023_08_08_180238_add_column_total_cancel_layaway_sales_and_total_cancel_layaway_sales_amount_on_counter_update',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (538,'2023_08_08_180845_add_column_total_cancel_layaway_sales_and_total_cancel_layaway_sales_amount_on_store_day_closes',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (539,'2023_08_07_212504_update_reserved_stock_of_current_stock_transfers',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (540,'2023_08_08_164506_add_unit_of_measure_derivative_id_column_in_goods_received_note_products_table',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (541,'2023_08_07_120654_create_new_stock_transfer_item_transactions_table',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (542,'2023_08_11_122738_add_column_auto_birthday_voucher_generation_on_companies',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (543,'2023_08_09_185248_add_column_in_sales',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (544,'2023_08_09_211329_add_column_allow_credit_sale_on_companies',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (545,'2023_08_10_192626_add_column_in_stock_adjustment_items_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (546,'2023_08_11_122223_add_column_in_hold_sale_details_table',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (547,'2023_08_11_131342_add_column_in_counter_updates',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (548,'2023_08_11_164240_add_column_in_store_day_closes',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (549,'2023_08_22_122000_add_column_allow_decimal_qty_on_unit_of_measures',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (550,'2023_08_22_181934_add_soft_delete_in_booking_payment_products_table',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (551,'2023_08_24_122511_duplicate-inventory-updates-delete-script',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (552,'2023_08_24_132511_duplicate-inventory-updates-split-quantity-delete-script',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (553,'2023_08_24_203430_update_inventory_data',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (554,'2023_08_24_182549_remove_duplicate_inventory_entries',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (555,'2023_08_24_194716_add_unique_in_inventories_columns',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (556,'2023_08_25_160846_add_stock_transfer_items_missing_entries_in_inventory_entries',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (557,'2023_08_25_203430_update_inventory_data',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (558,'2023_08_28_175949_add_column_remarks_on_cash_movements',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (559,'2023_08_29_205822_add_inventory_record_of_current_stock_transfer_item',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (560,'2023_08_26_001326_january_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (561,'2023_08_26_002052_february_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (562,'2023_08_26_002147_march_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (563,'2023_08_26_002231_april_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (564,'2023_08_26_002352_may_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (565,'2023_08_26_002425_june_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (566,'2023_08_26_002453_july_promoter_commission_generate',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (567,'2023_08_18_173902_add_column_in_vouchers_table',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (568,'2023_08_30_191723_update_inventory_data',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (569,'2023_09_11_124609_remove_wrong_location_entries_in_inventories',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (570,'2023_09_14_212638_update_counter_updates_records',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (571,'2023_09_15_212638_update_counter_updates_records',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (572,'2023_09_16_184413_remove_receive_quantity_null_closed_transfer',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (573,'2023_09_20_153736_add_column_created_by_store_id_in_voucher_table',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (574,'2023_09_15_225552_update_old_store_day_close_records',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (575,'2023_08_14_141917_add_column_is_automatic_on_promotion',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (576,'2023_08_14_145348_create_promotion_promocode_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (577,'2023_09_04_151951_add_column_promo_code_in_sale_item_discounts_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (578,'2023_09_04_151957_add_column_promo_code_in_sale_discounts_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (579,'2023_09_20_162842_update_inventory_updates_of_discrepancy_stock_transfer_items',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (580,'2023_09_21_234539_add_stock_transfer_items_missing_entries_in_inventory_entries',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (581,'2023_09_23_191723_update_inventory_data',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (582,'2023_09_25_190505_add_column_footer_notes_in_voucher_configurations_table',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (583,'2023_08_21_131544_add_column_can_manage_wholesale_on_store_managers',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (584,'2023_08_28_201313_create_orders_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (585,'2023_08_28_214136_create_order_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (586,'2023_08_28_215442_create_order_item_exchanges_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (587,'2023_08_28_215821_create_order_item_units_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (588,'2023_08_28_221744_create_order_item_promoter_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (589,'2023_08_28_222055_create_order_payments_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (590,'2023_08_28_222056_create_order_returns_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (591,'2023_08_28_222057_create_order_return_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (592,'2023_08_29_121228_add_column_order_return_id_in_orders_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (593,'2023_08_29_121334_add_column_order_return_item_id_in_order_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (594,'2023_08_29_153642_add_column_type_id_in_sale_return_reason_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (595,'2023_08_29_154239_add_column_type_id_in_void_sale_reasons_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (596,'2023_08_29_154317_add_column_type_id_in_promoter_group_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (597,'2023_10_01_135713_drop_uuid_contrained_in_order_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (598,'2023_10_01_135714_drop_uuid_contrained_in_order_item_exchanges_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (599,'2023_10_01_135715_drop_uuid_contrained_in_order_item_units_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (600,'2023_10_01_135716_drop_uuid_contrained_in_order_payments_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (601,'2023_10_01_135717_drop_uuid_contrained_in_order_returns_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (602,'2023_10_01_135718_drop_uuid_contrained_in_order_return_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (603,'2023_10_01_135719_drop_uuid_contrained_in_orders_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (604,'2023_10_01_135720_drop_uuid_contrained_in_order_item_promoter_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (605,'2023_10_01_145326_update_uuid_to_id_orders_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (606,'2023_10_01_145327_update_uuid_to_id_in_order_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (607,'2023_10_01_145328_update_uuid_to_id_in_order_item_exchanges_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (608,'2023_10_01_145329_update_uuid_to_id_in_order_item_units_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (609,'2023_10_01_145330_update_uuid_to_id_in_order_payments_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (610,'2023_10_01_145330_update_uuid_to_id_in_order_returns_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (611,'2023_10_01_145332_update_uuid_to_id_in_order_return_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (612,'2023_10_01_145333_update_uuid_to_id_in_order_item_promoters_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (613,'2023_10_01_145333_update_uuid_to_id_update_order_items_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (614,'2023_10_01_145333_update_uuid_to_id_update_orders_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (615,'2023_10_03_123609_add_soft_delete_in_stock_transfer_item_units_table',139);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (616,'2023_09_26_171436_create_health_tables',140);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (617,'2023_08_17_123318_create_merge_product_transactions_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (618,'2023_08_17_140936_add_column_in_products_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (619,'2023_10_05_194801_drop_model_has_permissions_table',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (620,'2023_10_09_132024_create_sale_through_ratios_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (621,'2023_10_09_201512_create_automated_notifications_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (622,'2023_10_10_202809_create_automated_notification_week_days_table',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (623,'2023_10_10_203704_create_automated_notification_month_dates_table',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (624,'2023_10_16_170632_update_image_name_in_payment_types',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (625,'2023_08_28_125743_create_external_connections_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (626,'2023_08_28_192535_create_external_companies_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (627,'2023_08_29_180409_change_null_add_company_id_in_notifications',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (628,'2023_09_04_140008_create_external_locations_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (629,'2023_09_05_175036_create_purchase_orders_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (630,'2023_09_05_182824_create_purchase_order_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (631,'2023_09_14_122426_add_column_in_purchase_order_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (632,'2023_09_14_171217_create_purchase_order_fulfillments_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (633,'2023_09_14_175332_create_purchase_order_fulfillments_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (634,'2023_09_14_175705_create_purchase_order_fulfillments_item_units_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (635,'2023_09_14_201027_add_column_parent_purchase_order_and_created_by_company_id_on_purchase_orders',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (636,'2023_09_15_180921_add_column_in_purchase_order_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (637,'2023_09_18_194611_add_column_null_in_purchase_orders_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (638,'2023_09_19_115855_add_column_null_in_purchase_order_fulfillment_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (639,'2023_09_19_163122_create_purchase_order_transactions_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (640,'2023_09_20_150103_add_column_unit_of_measure_two_id_and_package_quantity_and_package_total_quantity_on_purchase_order_fulfillment_items',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (641,'2023_09_20_163111_add_column_in_purchase_order_fulfillments_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (642,'2023_09_20_163556_add_column_in_purchase_order_fulfillment_items_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (643,'2023_09_21_131908_add_column_is_extra_item_and_discrepancy_type_on_purchase_order_fulfillment_items',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (644,'2023_09_21_151048_create_purchase_order_fulfillment_item_batches_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (645,'2023_09_22_204042_create_purchase_order_fulfillment_item_transactions_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (646,'2023_09_27_140728_create_purchase_order_invoices_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (647,'2023_09_27_141735_add_column_purchase_order_invoice_id_on_purchase_order_fulfillments',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (648,'2023_09_28_124338_create_purchase_order_fulfillment_transactions__table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (649,'2023_09_28_204915_add_column_created_by_company_id_on_purchase_order_invoices',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (650,'2023_09_29_192728_add_column_company_id_and_extrenal_invoice_id_on_purchase_order_invoices',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (651,'2023_10_02_144553_create_purchase_order_invoice_transactions_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (652,'2023_10_03_131434_change_type_code_on_external_companies',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (653,'2023_10_03_132826_change_type_delivery_order_number_and_add_note_column_on_purchase_order_fulfillments',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (654,'2023_10_03_170634_add_column_invoice_number_on_purchase_order_invoices',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (655,'2023_10_05_190920_update_column_in_purchase_order_fulfillment_item_units_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (656,'2023_10_06_140231_add_column_in_external_companies_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (657,'2023_10_06_181102_update_column_in_purchase_order_fulfillments_table',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (658,'2023_10_04_204033_add_missing_inventory_units',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (659,'2023_10_11_120700_adjust-reserved-stocks-records',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (660,'2023_10_22_043952_adjust-reserved-stocks-records',148);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (661,'2023_10_23_144840_update_column_in_automated_notifications_table',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (662,'2023_10_23_135202_add_column_description_in_sale_through_ratio_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (663,'2023_10_25_123820_rename_attention_name_column_in_stock_transfer',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (664,'2023_10_25_123821_rename_attention_name_column_in_purchase_orders',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (665,'2023_10_24_204033_add_missing_inventory_units',151);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (666,'2023_10_11_181532_adjust-reserved-stocks-with-inventory',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (667,'2023_10_11_214007_add_stock_transfer_items_missing_entries_in_inventory_entries',153);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (668,'2023_10_17_113547_update_stock_transfer_items_quntity_with_inventory_updates',154);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (669,'2023_10_12_125718_update_master_inventory_records',155);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (670,'2023_10_28_124733_update_sales_totals_script',156);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (671,'2023_11_02_185141_add_column_in_voucher_transactions_table',157);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (672,'2023_11_02_190409_update_voucher_transactions_table',157);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (673,'2023_11_02_194235_remove_column_voucher_transactions_table',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (674,'2023_10_31_120232_remove_deleted_stock_transfer_items_entries',159);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (675,'2023_10_31_124033_add_missing_inventory_units',160);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (676,'2023_11_01_011845_remove_extra_inventory_update_records',161);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (677,'2023_11_01_125718_update_master_inventory_records',162);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (678,'2023_11_08_143022_add_column_in_voucher_transactions_table',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (679,'2023_11_08_201458_update_voucher_transactions_table',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (680,'2023_11_21_115126_update_voucher_transactions_table',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (681,'2023_11_23_161823_add_column_handover_footer_notes_on_voucher_configurations',165);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (682,'2023_11_23_183833_update_payment_type_report_permission',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (683,'2023_11_11_133133_create_sale_loyalty_points_table',167);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (684,'2023_11_27_114708_update_remarks_to_transantion_table',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (685,'2023_11_27_140007_remove_remarks_in_stock_transfer_item_table',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (686,'2023_11_28_152412_add_column_ioi_city_mall_configuration',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (687,'2023_11_28_153425_add_column_machine_id_in_stores_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (688,'2023_11_28_192114_update_promoter_commissions_table',170);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (689,'2023_11_28_192144_update_promoter_commission_updates_table',170);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (690,'2023_11_24_131825_update_master_inventory_records',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (691,'2023_11_28_131106_add_columns_in_counter_updates_table',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (692,'2023_12_04_164528_add_new_remarks_column_in_stock_transfer_transaction_table',172);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (693,'2023_12_07_143454_remove_duplicate_stock_transfer_items',173);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (694,'2023_12_01_182851_add_column_discount_item_sequence_in_ssale_items_table',174);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (695,'2023_12_11_165934_rename_column_trigger_maybank_card_to_trigger_payment_machine_card_in_payment_types',175);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (696,'2023_12_12_125008_remove_invisble_id_column_in_all_table',175);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (697,'2023_12_12_115008_add_invisble_id_column_in_all_table',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (698,'2023_12_14_200301_remove_extra_inventory_update_records',177);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (699,'2023_12_14_211825_update_master_inventory_records',178);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (700,'2023_07_21_195359_create_sms_histories_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (701,'2023_12_05_150756_update_store_wise_daily_total_table',180);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (702,'2023_12_12_142803_update_from_may_to_august_store_wise_daily_total_table',181);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (703,'2023_12_12_144723_update_from_sept_to_dec_store_wise_daily_total_table',182);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (704,'2023_12_12_151448_update_past_year_total_table',183);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (705,'2023_12_18_133710_rename_voucher_apply_footer_notes_to_redemption_foot_note_in_voucher_configurations',184);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (706,'2023_06_07_000001_create_pulse_tables',185);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (707,'2023_12_18_150741_rename_column_allow_ioi_city_mall_data_sharing_to_enable_ioi_city_mall_data_sharing_in_stores',185);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (708,'2023_12_18_165445_remove_extra_stock_transfer_item_unit',186);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (709,'2023_12_18_181825_update_master_inventory_records',187);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (710,'2023_12_19_180055_update_close_counter_update_payment',188);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (711,'2023_12_19_212051_update_store_day_close_payment',188);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (712,'2023_12_22_181709_add_column_in_members_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (713,'2023_12_22_184229_add_column_in_employees_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (714,'2023_12_21_151625_update_column_in_site_configurations_table',190);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (715,'2023_12_21_161516_temporary_update_default_company',190);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (716,'2023_12_22_203640_add_column_default_store_new_member_and_location_assignment_in_companies',190);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (717,'2023_12_28_210415_update_created_store_id_column_in_members_table',190);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (718,'2023_12_25_150607_temporary_update_old_data_employee_and_member',191);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (719,'2024_01_02_181825_update_master_inventory_records',192);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (720,'2023_12_29_123604_add_extra_details_in_booking_payment_payments_table',193);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (721,'2024_01_02_120453_temporary_update_is_available_for_refund_in_payment_types_table',193);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (722,'2024_01_04_142019_temporary_update_sale_total_amount_paid_data',193);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (723,'2024_01_04_144429_temporary_update_sale_total_amount_before_round_off_data',193);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (724,'2024_01_04_182234_add_fax_in_external_locations_tables',194);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (725,'2024_01_04_191837_temporary_update_counter_update_data',195);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (726,'2024_01_04_201523_temporary_update_store_day_close_data',196);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (727,'2024_01_02_185951_add_transit_location_columns_in_stock_transfer',197);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (728,'2024_01_08_131645_temporary_update_sale_price_paid_and_sale_amount',198);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (729,'2024_01_08_143344_temporary_update_sale_payment_amount',198);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (730,'2024_01_08_145508_temporary_update_sale_total_amount_paid',199);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (731,'2024_01_08_153052_temporary_delete_payment_with_zero_amount',199);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (732,'2024_01_08_171336_temporary_update_sale_total_amount_paid',199);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (733,'2024_01_08_183424_temporary_update_counter_update_data',199);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (734,'2024_01_12_144252_add_relationship_in_merge_product_transaction_table',200);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (735,'2023_12_28_130957_create_sale_targets_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (736,'2023_12_28_131457_create_sale_target_promoter_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (737,'2023_12_28_131527_create_sale_target_store_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (738,'2023_12_29_121647_create_sale_target_time_frames_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (739,'2023_12_29_122101_create_sale_achieved_targets_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (740,'2024_01_17_191449_temporary_update_sale_return_data',202);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (741,'2024_01_18_135336_temporary_update_return_credit_not',202);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (742,'2024_01_19_170200_temporary_update_salee_amount_as_discount',202);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (743,'2024_01_19_192302_remove_reserved_stock_records',202);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (744,'2024_01_22_163716_temporary_update_counter_update_open_by_pos_date',203);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (745,'2024_01_22_181825_update_master_inventory_records',203);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (746,'2024_01_19_180228_temporary_update_counter_update_data',204);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (747,'2024_01_17_151517_add_colunm_amount_type_on_sale_targets',205);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (748,'2024_01_19_181228_temporary_update_store_day_close_data',206);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (749,'2024_01_23_130927_add_colunm_status_on_dream_prices',207);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (750,'2024_01_01_173510_add_allow_happy_hour_discount_col_in_companies_tbl',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (751,'2024_01_01_191434_create_happy_hour_discounts_table',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (752,'2024_01_02_152408_brand_happy_hour_discount',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (753,'2024_01_02_152420_category_happy_hour_discount',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (754,'2024_01_02_152434_happy_hour_discount_style',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (755,'2024_01_02_152457_department_happy_hour_discount',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (756,'2024_01_23_120955_add_trx_mall_configuration_in_companies_table',209);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (757,'2024_01_23_121224_add_trx_mall_configuration_in_stores_table',209);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (758,'2024_01_18_112445_add_columns_in_goods_received_notes_table',210);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (759,'2024_01_29_121617_add_column_company_address_on_members',210);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (760,'2024_01_29_143708_create_counter_update_event_product_table',210);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (761,'2024_01_18_183047_add_unit_of_measure_derivative_id_column_in_stock_transfer_item',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (762,'2024_01_24_113503_add_title_to_notifications_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (763,'2024_01_25_143346_create_manual_notifications_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (764,'2024_01_25_145054_create_promoter_manual_notification_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (765,'2024_01_25_150239_create_promoter_group_manual_notification_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (766,'2024_01_25_150420_create_store_manual_notification_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (767,'2024_02_01_171420_add_company_unique_gift_card_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (768,'2024_01_25_142155_create_product_bundles_table',212);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (769,'2024_01_29_130218_add_layaway_pending_amount_in_orders_table',212);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (770,'2024_01_31_145020_add_column_product_bundle_id_in_sale_items_table',212);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (771,'2024_01_31_175602_add_credit_pending_amount_in_orders_table',212);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (772,'2024_02_05_150619_remove_colunm_reserved_stock_days_limit_on_companies',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (773,'2024_02_06_132325_add_index_to_sales_table',214);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (774,'2024_02_08_231825_update_master_inventory_records',215);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (775,'2024_02_09_180300_temporary_update_store_wise_daily_total_table',216);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (776,'2024_01_28_234139_add_index_columns_in_reserved_stocks_table',217);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (777,'2024_02_12_181914_add_column_booking_payment_refund_type_in_companies',218);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (778,'2024_01_30_183045_create_assembly_child_products_table',219);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (779,'2024_02_05_150919_add_soft_delete_in_assembly_child_products_table',219);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (780,'2024_02_05_163532_create_sale_item_assembly_child_products_table',219);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (781,'2024_02_13_130449_add_column_created_by_id_and_created_by_type_on_designations_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (782,'2024_02_13_150017_add_column_created_by_id_and_created_by_type_in_employee_groups_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (783,'2024_02_13_173228_add_column_created_by_id_and_created_by_type_in_promoters_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (784,'2024_02_13_200852_add_column_created_by_id_and_created_by_type_in_promoter_groups_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (785,'2024_02_14_122557_add_column_created_by_id_and_created_by_type_in_directors_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (786,'2024_02_14_133859_add_column_created_by_id_and_created_by_type_in_cashiers_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (787,'2024_02_14_164227_add_column_created_by_id_and_created_by_type_in_cashier_groups_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (788,'2024_02_15_133345_create_store_manager_authorization_codes_table',221);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (789,'2024_02_07_201045_add_columns_to_sale_target_timeframes_table',222);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (790,'2024_02_16_182033_add_column_title_and_description_term_conditions_in_voucher_configurations_table',222);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (791,'2024_02_16_203918_create_stock_transfer_average_lead_days_table',222);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (792,'2024_02_20_115514_add_purchase_cost_in_purchase_order_items_table',223);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (793,'2024_02_19_162023_create_ecommerce_stores_table',224);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (794,'2024_02_20_201542_add_price_fall_down_percentage_column_in_stores_table',224);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (795,'2024_02_22_192902_add_pic_contact_and_name_in_members_table',224);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (796,'2024_02_20_132400_add_fcm_token_column_in_member_app',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (797,'2024_02_20_141240_add_fcm_token_column_in_store_manager_table',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (798,'2024_02_20_141248_add_fcm_token_column_in_warehouse_manager_table',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (799,'2024_02_20_141255_add_fcm_token_column_in_promoter_table',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (800,'2024_02_23_120426_add_signup_token_to_admins_table',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (801,'2024_02_26_121919_add_column_online_price_in_products_table',226);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (802,'2024_02_28_121955_add_column_is_available_in_pos_and_is_available_in_ecommerce_in_products_table',226);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (803,'2024_02_26_175653_temporary_add_cash_movement_resone',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (804,'2024_02_27_123455_create_bundle_product_loyalty_points_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (805,'2024_02_29_212000_add_order_columns_in_store_day_close_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (806,'2024_03_01_123352_add_store_day_close_column_in_orders_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (807,'2024_03_01_133756_add_total_order_transaction_column_in_store_day_close_payment_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (808,'2024_03_01_151459_add_store_day_close_id_column_in_order_payment_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (809,'2024_03_01_205341_update_mobile_number_unique_validation_in_vendors_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (810,'2024_03_05_193940_add_column_trigger_card_affin_payment_machine_in_payment_types_table',228);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (811,'2024_03_05_193851_remove_petty_cash_tables_and_permissions',229);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (812,'2024_03_08_163017_rename_column_in_stock_transfers_table',230);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (813,'2024_03_08_173017_old_records_created_by_location_id',231);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (814,'2024_03_11_154117_create_sale_season_table',232);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (815,'2024_03_11_154118_update_sale_season_table',232);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (816,'2024_03_11_171939_create_manual_notification_member_group',233);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (817,'2024_03_11_172446_create_manual_notification_member_types',233);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (818,'2024_03_12_184553_temporary_update_store_wise_daily_total_table',233);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (819,'2024_02_20_140346_create_store_manager_authorization_code_usages_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (820,'2024_03_13_190604_add_column_total_expired_points_in_members_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (821,'2024_03_13_191550_add_column_total_expired_points_in_employees_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (822,'2024_03_14_161727_temporary_update_member_and_employee_points',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (823,'2024_03_18_130615_temporary_update_day_closed_data',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (824,'2024_03_21_141611_add_open_time_and_close_time_columns_in_stores_table',236);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (825,'2024_03_26_114642_add_share_inventory_to_external_companies_to_stores_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (826,'2024_03_26_114922_add_share_inventory_to_external_companies_to_warehouses_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (827,'2024_03_19_123017_add_manager_name_and_manager_email_columns_in_regions_table',238);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (828,'2024_03_28_123321_add_column_external_order_number_in_purchase_orders_table',238);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (829,'2024_03_01_145950_temporary_update_product_images',239);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (830,'2024_03_29_183747_add_column_external_login_token_in_warehouse_managers_table',240);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (831,'2024_04_01_165513_add_column_is_available_in_pos_and_is_available_in_ecommerce_in_promotions_table',240);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (832,'2024_04_02_204518_temporary_update_old_data_intercompany_reseved_stock',240);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (833,'2024_03_27_165154_create_external_products_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (834,'2024_04_01_184244_add_column_channel_id_in_members_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (835,'2024_04_02_144113_create_partially_receive_fulfillments_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (836,'2024_04_02_144524_create_partially_receive_fulfillment_items_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (837,'2024_04_03_162913_add_external_username_in_purchase_order_transactions_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (838,'2024_04_03_163111_add_external_username_in_purchase_order_fulfillment_transactions_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (839,'2024_04_03_205524_add_column_status_in_orders_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (840,'2024_04_03_210238_update_column_nullable_in_order_payment_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (841,'2024_04_04_132141_temporary_fill_channel_id_for_previous_records',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (842,'2024_04_08_124651_add_column_is_available_in_pos_and_is_available_in_ecommerce_in_dream_prices_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (843,'2024_04_08_165152_add_column_inventory_deduct_order_status_in_ecommerce_stores_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (844,'2024_04_08_173658_create_inventory_rollback_order_statuses_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (845,'2024_04_08_175713_temporary_update_old_layaway_and_credit_sale_data',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (846,'2024_04_09_143742_temporary_update_old_data_ecommerce_store',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (847,'2024_04_10_123655_temporary_update_bundel_product_data',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (848,'2024_04_10_140129_add_column_external_login_token_in_admins_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (849,'2024_04_08_152729_create_happy_hour_discount_transactions_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (850,'2024_04_08_160908_temporary_remove_columns_in_happy_hour_discounts_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (851,'2024_04_11_180450_temporary_update_old_data_for_storemanager_permission',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (852,'2024_04_11_202742_temporary_update_old_data_for_warehouse_manager_permission',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (853,'2024_04_09_123654_add_pickup_store_id_to_orders_table',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (854,'2024_04_09_162621_create_draft_product_transactions_table',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (855,'2024_04_09_163735_chnage_data_type_in_status_column_to_products_table',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (856,'2024_04_11_181648_remove_column_allow_negative_payment_to_allow_only_return_in_companies_table',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (857,'2024_04_11_182202_temporary_update_allow_only_return_default_value',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (858,'2024_04_11_192314_add_column_promotion_id_in_order_items_table',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (859,'2024_04_08_210846_add_column_filter_type_id_in_manual_notification',244);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (860,'2024_04_11_203814_chnage_data_type_in_status_column_to_products_table',244);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (861,'2024_04_12_014223_create_manual_notification_members',244);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (862,'2024_04_12_175909_temporary_manual_notification_type_update',244);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (863,'2024_04_16_145535_site_configuration_update',245);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (864,'2024_04_01_125119_create_order_credit_notes_table',246);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (865,'2024_04_01_125210_create_order_credit_note_uses_table',246);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (866,'2024_04_03_173322_add_column_total_price_paid_in_order_return_items_table',246);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (867,'2024_04_22_131355_add_company_id_column_in_stock_takes_table',247);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (868,'2024_04_23_125249_add_column_color_code_in_colors',247);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (869,'2024_04_23_134319_add_column_color_code_in_color_groups',247);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (870,'2024_04_25_152852_temporary_update_product_category_data',248);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (871,'2024_04_18_180017_create_banners_table',249);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (872,'2024_04_29_124025_add_column_low_stock_alert_threshold_on_companies',250);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (873,'2024_04_29_133646_temporary_update_old_data_for_companies',250);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (874,'2024_04_28_004900_temporary_update_master_inventory_records',251);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (875,'2024_05_01_004900_temporary_update_master_inventory_records',252);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (876,'2020_07_07_055656_create_countries_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (877,'2020_07_07_055725_create_cities_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (878,'2020_07_07_055746_create_timezones_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (879,'2021_10_19_071730_create_states_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (880,'2021_10_23_082414_create_currencies_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (881,'2022_01_22_034939_create_languages_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (882,'2024_04_24_185852_create_top_twenty_aggregate_data_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (883,'2024_04_25_145607_rename-uom2-to-package-types',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (884,'2024_04_25_185742_rename-package-type-id-column-for-product-bundles',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (885,'2024_04_25_191116_temporary_run_top_twenty_aggregate_data_report__migration',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (886,'2024_04_26_135257_rename-package-type-id-column-for-purchase-order-fullfillment-items',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (887,'2024_04_26_145223_rename-package-type-id-column-for-sales-items',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (888,'2024_04_26_152037_rename-package-type-id-column-for-stock-transfer-items',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (889,'2024_04_30_144915_temporary_country_update_seeder_call',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (890,'2024_04_30_151336_create_company_countries_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (891,'2024_04_30_185331_add_column_bundle_id_in_order_items_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (892,'2024_04_30_204120_temporary_update_country_data_for_companies',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (893,'2024_05_01_134305_add_column_country_id_on_stores',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (894,'2024_05_01_134903_temporary_update_country_data_for_stores',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (895,'2024_05_02_133643_create_order_item_assembly_child_products_table',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (896,'2024_05_06_134305_add_column_description_on_products',254);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (897,'2024_05_10_001050_temporary_country_update_seeder_call',255);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (898,'2024_05_10_001221_temporary_update_country_data_for_companies',255);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (899,'2024_05_10_001258_temporary_update_country_data_for_stores',255);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (900,'2024_05_10_021221_temporary_update_country_data_for_companies',256);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (901,'2024_05_10_031258_temporary_update_country_data_for_stores',256);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (902,'2024_04_29_152956_add_product_bundle_id_to_booking_payment_products_table',257);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (903,'2024_04_30_145111_add_booking_payment_payment_id_to_credit_note_uses_table',257);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (904,'2024_05_13_125149_create_loyalty_campaign_configurations_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (905,'2024_05_13_151010_create_brand_loyalty_campaign_configuration_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (906,'2024_05_13_155724_create_store_loyalty_campaign_configuration_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (907,'2024_05_13_155744_create_product_loyalty_campaign_configuration_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (908,'2024_05_13_171346_create_product_low_stock_thresholds_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (909,'2024_05_13_171658_create_category_loyalty_campaign_configuration_table',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (910,'2024_04_28_170636_create_templates_table',259);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (911,'2024_05_01_135927_create_attributes_table',259);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (912,'2024_05_09_222745_create_custom_field_values_table',259);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (913,'2024_05_13_165601_rename_column_loyalty_points_per_one_currency_unit_to_loyalty_points_per_currency_unit_in_memberships',260);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (914,'2024_05_15_144419_temporary_update_column_code_categories_table',260);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (915,'2024_05_15_153307_temporary_update_code_column_in_departments_table',260);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (916,'2024_05_15_153943_temporary_update_code_column_in_styles_table',260);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (917,'2024_05_08_131807_add_column_discount_type_and_percentage_in_cashbacks',261);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (918,'2024_05_08_132236_temporary_old_data_update_for_cashbacks',261);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (919,'2024_05_16_131703_remove_columns_in_flat_amount_in_cashbacks_table',261);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (920,'2024_03_27_190439_create_product_agings_table',262);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (921,'2024_03_27_195606_temporary_update_the_product_aging_table',262);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (922,'2024_05_14_143242_create_sale_return_reason_type_table',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (923,'2024_05_14_173903_temporary_old_data_update_for_sale_return_reasons',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (924,'2024_05_14_201030_remove_columns_in_sale_return_reasons_table',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (925,'2024_05_15_193754_create_void_sale_reason_types_table',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (926,'2024_05_16_121110_temporary_old_data_update_for_void_sale_reasons',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (927,'2024_05_16_121521_remove_colunm_type_id_in_void_sale_reasons',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (928,'2024_04_22_154827_create_product_collections_table',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (929,'2024_04_22_164927_create_product_collection_filters_table',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (930,'2024_04_23_182218_add_auto_include_in_collection_column_to_companies_table',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (931,'2024_04_23_200541_create_category_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (932,'2024_04_23_200706_create_season_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (933,'2024_04_23_200853_create_department_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (934,'2024_04_23_200951_create_color_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (935,'2024_04_23_201034_create_size_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (936,'2024_04_23_201147_create_brand_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (937,'2024_04_23_201313_create_style_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (938,'2024_04_23_201402_create_tag_product_collection_filter',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (939,'2024_04_29_142057_create_product_collection_filter_types_table',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (940,'2024_05_06_170210_create_product_collection_products_table',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (941,'2024_05_20_181415_remove_can_manage_inventory_in_stores_warehouse_table',265);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (942,'2024_05_07_133643_update_comment_in_email_recipients_table',266);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (943,'2024_05_07_151336_create_automated_notification_email_recipient_table',266);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (944,'2024_05_21_113339_add_credit_note_used_count_and_refund_count_columns_in_counter_updates_table',267);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (945,'2024_05_21_131425_add_credit_note_used_count_and_refund_count_columns_in_store_day_closes_table',267);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (946,'2024_05_22_171659_temporary_update_credit_note_used_count_and_refund_count_in_counter_update',267);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (947,'2024_05_22_175924_temporary_update_credit_note_used_count_and_refund_count_in_store_day_closes',267);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (948,'2024_05_28_005514_remove_slug_column_from_attributes_table',268);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (949,'2024_05_28_005621_remove_slug_column_from_templates_table',268);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (950,'2024_05_28_011546_remove_order_column_from_attributes_table',268);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (951,'2024_05_28_020817_create_attached_templates_table',268);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (952,'2024_05_31_235517_temporary_update_master_inventory_records',269);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (953,'2024_06_03_142504_change_data_type_related_table_int_to_bigint',270);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (954,'2024_01_19_132003_add_column_employee_id_in_members_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (955,'2024_02_01_165037_create_employee_transactions_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (956,'2024_02_05_130051_add_column_member_id_in_sales_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (957,'2024_02_07_143450_add_column_member_id_in_sale_returns',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (958,'2024_02_09_134341_add_column_member_id_in_credit_notes',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (959,'2024_05_02_131412_add_column_member_id_in_hold_sale_details_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (960,'2024_05_02_133958_add_column_member_id_in_loyalty_point_updates_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (961,'2024_05_02_135054_add_column_member_id_in_loyalty_points_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (962,'2024_05_02_163405_add_column_member_id_in_membership_assignments_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (963,'2024_05_03_191907_temporary_employee_add_in_member_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (964,'2024_05_10_171820_temporary_employee_to_member_sale_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (965,'2024_05_16_165419_temporary_employee_to_member_credit_note_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (966,'2024_05_16_192009_temporary_employee_to_member_hold_sale_detail_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (967,'2024_05_17_125451_temporary_employee_to_member_loyalty_point_update_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (968,'2024_05_17_144332_temporary_employee_to_member_loyalty_point_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (969,'2024_05_17_150511_temporary_employee_to_member_sale_return_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (970,'2024_05_17_155732_temporary_employee_to_member_membership_assignment_data_update',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (971,'2024_06_04_142246_create_product_channel_reference_table',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (972,'2024_06_04_202515_temporary_add_sale_loyalty_points',272);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (973,'2024_06_05_183937_add_column_in_sale_targets_table',273);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (974,'2024_06_06_204121_add_columns_in_activity_logs_table',274);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (975,'2024_06_12_151746_add_columns_in_categories_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (976,'2024_06_12_151746_temporary_update_category_image_collection_name',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (977,'2024_06_13_115207_update_column_in_loyalty_point_updates_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (978,'2024_06_13_115928_update_column_in_loyalty_points_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (979,'2024_06_13_120035_update_column_in_membership_assignments_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (980,'2024_06_13_151000_create_cancel_credit_sales_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (981,'2024_06_13_173705_add_cancel_credit_sale_id_to_credit_notes_table',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (982,'2024_06_17_124527_add_creator_can_approve_draft_product_column_to_companies_table',276);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (983,'2024_06_18_135516_add_column_original_created_at_in_products',277);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (984,'2024_06_04_133622_add_columns_in_automated_notifications_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (985,'2024_06_04_174313_create_automated_notification_stores_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (986,'2024_06_06_172607_create_automated_notification_products_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (987,'2024_06_07_141133_create_automated_notification_brand_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (988,'2024_06_07_141139_create_automated_notification_product_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (989,'2024_06_07_141222_create_automated_notification_category_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (990,'2024_06_07_141254_create_automated_notification_style_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (991,'2024_06_07_141304_create_automated_notification_department_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (992,'2024_06_07_141328_create_automated_notification_product_collection_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (993,'2024_06_10_125851_create_automated_notification_sent_activities_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (994,'2024_06_10_194328_create_automated_notification_sent_activitiy_items_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (995,'2024_06_13_191050_temporary_add_company_low_stock_thresold_data',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (996,'2024_06_13_191905_remove_columns_companies_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (997,'2024_06_13_192127_remove_product_low_stock_thresholds_table',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (998,'2024_06_19_233014_temporary_update_stock_transfer_items_quntity_with_inventory_updates',279);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (999,'2024_06_19_234514_temporary_master_inventory_upates',279);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1000,'2024_06_20_182002_remove_colunm_user_id_and_user_type_member_related_all_table',279);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1001,'2024_06_20_212304_add_ulid_to_admins_table',280);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1002,'2024_05_22_191214_alter_column_default_value_from_string_to_text_in_attributes_table',281);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1003,'2024_06_27_141853_create_online_sales_charges_table',281);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1004,'2024_04_15_141434_create_member_addresses_table',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1005,'2024_04_16_130200_temporary_update_old_data_for_members',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1006,'2024_06_26_185640_temporary_update_inventory_unit_issue_by_merge_product',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1007,'2024_06_28_124730_add_state_id_and_city_id_to_stores_table',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1008,'2024_06_28_140656_change_city_column_nullable_in_stores',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1009,'2024_06_28_150931_add_state_id_and_city_id_and_country_id_to_warehouses',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1010,'2024_06_28_151005_change_city_column_nullable_in_warehouses',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1011,'2024_06_27_151806_add_stock_transfer_item_units_entries',283);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1012,'2024_07_01_141855_create_product_collection_promotion',283);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1013,'2024_06_28_165232_add_inventory_update_entries',284);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1014,'2024_07_01_133909_temporary_update_master_inventory_records',285);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1015,'2024_06_29_001003_add_hierarchy_id_in_products_table',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1016,'2024_07_03_141936_create_order_picking_lists_table',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1017,'2024_07_03_142248_create_order_picking_list_items_table',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1018,'2024_07_03_151639_add_column_order_picking_list_prefix_in_companies',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1019,'2024_07_03_190350_temporary_update_order_picking_list_prefix_for_companies',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1020,'2024_07_04_180007_change_location_id_and_type_default_nullable_in_sequences',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1021,'2024_07_08_195606_temporary_update_the_product_aging_table',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1022,'2024_06_11_141550_create_sale_channels_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1023,'2024_06_11_143422_create_sale_channel_inventory_rollback_order_statuses_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1024,'2024_06_11_143443_create_sale_channel_webhook_urls_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1025,'2024_06_11_185748_temporary_update_site_configuration_data',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1026,'2024_06_13_181926_create_store_sale_channels_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1027,'2024_06_14_202913_add_table_dream_price_sale_channel_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1028,'2024_06_28_171246_remove_column_signup_token_in_admins_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1029,'2024_07_03_143851_update_column_sale_channel_id_on_product_channel_references_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1030,'2024_07_10_164322_temporary_seed_new_cities_table',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1031,'2024_07_08_192639_create_cashback_prices_table',288);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1032,'2024_07_10_181310_add_new_column_in_orders_table',288);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1033,'2024_07_12_120527_create_order_addresses_table',288);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1034,'2024_07_01_122156_add_column_currency_symbol_in_companies',289);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1035,'2024_07_01_183301_temporary_update_default_country_for_companies',289);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1036,'2024_07_19_164322_temporary_seed_new_cities_table',290);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1037,'2024_07_15_134025_add_round_off_column_to_sale_cashbacks_table',291);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1038,'2024_07_19_184322_temporary_seed_new_cities_table',291);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1039,'2024_07_19_202053_remove_extra_stock_transfer_item_unit',292);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1040,'2024_07_21_001141_temporary_update_master_inventory_records',292);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1041,'2024_07_22_164130_add_new_column_in_orders_address_table',293);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1042,'2024_07_17_193733_add_vendor_id_to_products_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1043,'2024_07_17_194144_add_is_consignment_and_commission_percentage_to_vendors_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1044,'2024_07_22_193605_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_sales',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1045,'2024_07_22_193649_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_sale_returns',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1046,'2024_07_22_193709_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_orders',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1047,'2024_07_22_193828_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_order_returns',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1048,'2024_07_22_193926_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_booking_payments',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1049,'2024_07_22_194050_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_credit_notes',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1050,'2024_07_22_194104_add_digital_invoice_number_and_digital_invoice_submitted_columns_in_order_credit_notes',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1051,'2024_07_23_174109_create_digital_invoices_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1052,'2024_07_24_185451_add_new_column_in_purchase_order_fulfillment_item_batches',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1053,'2024_07_25_143129_add_vendor_commission_percentage_to_sale_items_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1054,'2024_07_25_165940_drop_unique_email_constrint_from_members',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1055,'2024_07_25_180530_drop_member_lite_cards_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1056,'2024_07_25_202646_add_new_column_tracking_url_in_orders_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1057,'2024_07_26_121013_add_vendor_commission_percentage_to_order_items_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1058,'2024_07_26_135223_add_sale_channel_id_in_orders_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1059,'2024_07_26_182841_add_shipment_order_number_to_orders_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1060,'2024_07_26_190445_remove_column_uuid_on_members',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1061,'2024_07_26_201414_create_order_channel_references_table',294);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1062,'2024_07_29_134716_update_status_comment_on_members_table',295);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1063,'2024_07_31_143857_add_pos_message_and_payload_in_notifications_table',296);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1064,'2024_08_02_175916_temporary_update_data_purchase_order',297);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1065,'2024_08_02_201342_temporary_update_loyalty_point_expired',298);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1066,'2024_08_03_202053_remove_extra_stock_transfer_item_unit',299);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1067,'2024_08_04_001342_temporary_update_loyalty_point_expired',300);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1068,'2024_08_04_001541_temporary_update_master_inventory_records',301);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1069,'2024_06_06_001101_add_header_total_count_exported_count_columns_to_export_records_table',302);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1070,'2024_07_29_125916_create_product_sale_channel_table',302);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1071,'2024_08_05_195647_temporary_update_loyalty_point_date',303);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1072,'2024_08_05_202631_temporary_update_loyalty_point_date',304);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1073,'2024_08_12_140359_remove_column_supplier_adress_and_contact_on_digital_invoices_table',305);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1074,'2024_08_12_155452_add_enable_e_invoice_column_to_companies_table',305);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1075,'2024_08_12_150422_create_merge_member_transactions_table',306);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1076,'2024_08_14_210922_temporary_update_member_loyalty_points',307);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1077,'2024_08_16_143244_add-column-delivery-charges-in-orders-table',307);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1078,'2024_08_15_142205_create_retail_planning_hierarchies_table',308);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1079,'2024_08_16_170944_temporary_update_member_loyalty_points_sale',308);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1080,'2024_08_16_212124_temporary_update_member_loyalty_points_update',308);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1081,'2024_08_20_182325_temporary_update_member_loyalty_points_update',309);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1082,'2024_08_20_183306_temporary_update_member_loyalty_points_update',310);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1083,'2024_08_20_164319_add_column_in_booking_payment_product_table',311);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1084,'2024_08_21_140013_temporary_update_member_loyalty_points_update',311);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1085,'2024_08_21_145349_temporary_update_member_loyalty_points_update',312);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1086,'2024_08_22_125534_temporary_update_original_creared_at',313);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1087,'2024_08_22_125534_temporary_update_original_created_at',314);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1088,'2024_08_22_125535_temporary_update_original_created_at',315);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1089,'2024_08_09_194840_create_promotion_sale_channel_table',316);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1090,'2024_08_27_163924_temporary_update_the_product_aging_table',317);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1091,'2024_09_02_121659_add_new_column_trigger_card_bank_rakyat_terminal_in_payment_types_table',318);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1092,'2024_09_02_122030_add_is_sold_as_single_item_column_to_products',318);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1093,'2024_09_01_215404_create_new_transit_stocks_table',319);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1094,'2024_09_05_184107_show_e_invoice_qr_on_receipt_column_in_companies_table',320);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1095,'2024_09_05_172409_remove_column_achieved_date_in_sale_achieved_targets_table',321);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1096,'2024_09_08_191831_add-column-uuid-in-company-table',322);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1097,'2024_09_09_204122_create_category_channel_references_table',322);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1098,'2024_09_09_204829_temporary_update_category_channel_reference',322);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1099,'2024_09_09_211847_temporary_add_categories_in_webspert',322);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1100,'2024_09_02_141029_create_items_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1101,'2024_09_02_164025_create_category_item_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1102,'2024_09_02_170632_create_item_variants_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1103,'2024_09_02_182937_create_item_inventories_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1104,'2024_09_02_184151_create_item_inventory_updates_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1105,'2024_09_02_185930_create_item_variant_loyalty_points_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1106,'2024_09_02_192713_create_item_variant_bundles_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1107,'2024_09_09_145716_create_assembly_child_items_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1108,'2024_09_09_182911_create_item_tag_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1109,'2024_09_11_140315_create_automated_notification_store_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1110,'2024_09_12_120618_create_item_variant_values_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1111,'2024_09_12_192353_create_bundle_item_variant_loyalty_points_table',323);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1112,'2024_09_17_121233_remove-unique-ean-column-in-item-variants-table',324);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1113,'2024_08_07_174515_adjust-stock-transfer-related-records-by-statuses',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1114,'2024_08_08_150508_temporary_update_grn_inventry',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1115,'2024_08_09_133619_temporary_update_inventory_stock_adjustement',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1116,'2024_08_09_145439_temporary_update_inventory_sale_items',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1117,'2024_08_09_190206_temporary_update_inventory_void_sale',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1118,'2024_08_12_144449_temporary_update_inventory_layaway-sale',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1119,'2024_08_29_140835_temporary_update_purhcase_order_inventory_update',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1120,'2024_08_29_190206_temporary_update_inventory_sale_return_items',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1121,'2024_09_18_180559_add_new_column_department_id_in_items_table',325);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1122,'2024_08_30_185903_temporary_delete_inventry_update_record',326);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1123,'2024_08_30_185931_temporary_update_inventory',326);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1124,'2024_09_16_203221_add_purchase_order_fullfillment_unit',327);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1125,'2024_09_19_122500_add_new_column_is_variant_in_templates_table',328);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1126,'2024_09_19_163130_add_number_of_receipts_to_companies_table',329);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1127,'2024_09_19_174437_create_users_table',329);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1128,'2024_09_20_130449_add_purchase_amount_id_and_batch_id_in_stock_adjustment_items',329);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1129,'2024_09_23_171025_add_app_color_configurations_in_site_configuration_table',330);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1130,'2024_09_16_130338_add_min_loyalty_points_and_max_loyalty_points_columns_to_memberships',331);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1131,'2024_09_16_140623_add_loyalty_point_expiration_days_to_loyalty_compaigns',331);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1132,'2024_09_16_140919_add_loyalty_point_expiration_days_to_companies',331);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1133,'2024_09_17_182005_add_loyalty_point_id_to_loyalty_points_id_table',331);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1134,'2024_10_01_133430_temporary_update_inventory_layaway_sale',332);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1135,'2024_10_01_141157_temporary_update_inventory',332);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1136,'2024_05_29_174316_create_locations_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1137,'2024_05_29_175211_create_brand_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1138,'2024_05_31_123717_create_ecommerce_locations_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1139,'2024_05_31_125311_add_ecommerce_location_id_column_in_inventory_rollback_order_statuses_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1140,'2024_06_12_125636_temporary-copy-store-and-warehouse-to-locations-table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1141,'2024_06_12_135019_create_cashback_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1142,'2024_06_13_124042_create_cashier_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1143,'2024_06_17_134205_add_location_id_column_in_category_wise_daily_totals_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1144,'2024_06_17_180450_add_location_id_column_in_counters_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1145,'2024_06_19_131636_create_location_store_manager_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1146,'2024_06_19_181441_create_director_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1147,'2024_06_21_132347_create_dream_price_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1148,'2024_06_21_190755_add_location_id_column_in_happy_hour_discounts_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1149,'2024_06_24_124150_create_location_loyalty_campaign_configuration',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1150,'2024_06_24_162029_create_location_manual_notification',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1151,'2024_06_24_174759_add_location_id_column_in_order_credit_note_transactions',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1152,'2024_06_24_182833_add_location_id_column_in_order_credit_note',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1153,'2024_06_24_192621_add_location_id_column_in_order_payments',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1154,'2024_06_24_201007_add_location_id_column_in_order_returns',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1155,'2024_06_25_134538_add_location_id_column_in_orders',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1156,'2024_06_26_144919_add_location_id_column_in_past_year_data',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1157,'2024_06_26_164938_create_location_pos_advertisement',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1158,'2024_06_26_184157_add_location_id_column_in_product_ageings',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1159,'2024_06_27_145219_add_location_id_column_in_promoter_commission_updates',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1160,'2024_06_27_191045_create_location_promoter',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1161,'2024_06_28_170548_create_location_sale_target',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1162,'2024_07_01_132612_add_location_id_column_in_store_day_closes',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1163,'2024_07_01_184015_add_location_id_column_in_store_wise_daily_totals',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1164,'2024_07_04_125001_add_location_id_column_in_voucher_transactions',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1165,'2024_07_04_204340_create_location_promotion',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1166,'2024_07_05_141119_rename_column_name_to_good_received_notes',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1167,'2024_07_05_143144_add_location_id_to_goods_received_notes',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1168,'2024_07_05_184411_rename_column_name_in_inventories',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1169,'2024_07_05_184542_add_location_id_to_inventories_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1170,'2024_07_12_122627_create_location_sale_channel',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1171,'2024_07_12_140247_add_sales_channel_default_store_id_to_default_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1172,'2024_07_15_123248_add_member_created_store_id_to_created_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1173,'2024_07_15_151714_add_voucher_created_by_store_id_to_created_by_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1174,'2024_07_15_184604_rename_col_location_id_to_old_location_id_in_purchase_orders_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1175,'2024_07_15_184719_add_purchase_order_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1176,'2024_07_15_201307_rename_col_location_id_to_old_location_id_in_sale_return_reasons_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1177,'2024_07_15_201322_add_sale_return_reasons_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1178,'2024_07_16_123007_rename_col_location_id_to_old_location_id_in_sequence_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1179,'2024_07_16_123026_add_sequence_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1180,'2024_07_16_135308_rename_col_location_id_to_old_location_id_in_stock_adjustment_items',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1181,'2024_07_16_135403_add_stock_adjustment_items_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1182,'2024_07_16_145212_rename_col_location_id_to_old_location_id_in_inventory_updates',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1183,'2024_07_16_145235_add_inventory_updates_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1184,'2024_07_16_162718_rename_col_location_id_to_old_location_id_in_stock_take',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1185,'2024_07_16_162732_add_stock_take_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1186,'2024_07_16_191715_rename_col_location_id_to_old_location_id_in_stock_transfer_average_lead_days',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1187,'2024_07_16_191758_add_stock_take_from_location_id_and_to_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1188,'2024_07_16_201051_rename_col_location_id_to_old_location_id_in_stock_transfer',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1189,'2024_07_16_201158_add_stock_transfer_source_location_id_and_destination_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1190,'2024_07_19_194734_add_automated_notification_products_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1191,'2024_07_19_195048_add_automated_notification_stores_to_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1192,'2024_07_22_132412_rename_col_external_location_id_to_old_external_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1193,'2024_07_22_132448_add_external_location_id_to_external_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1194,'2024_07_31_140745_temporary_copy_cashback_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1195,'2024_07_31_140746_temporary_copy_cashier_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1196,'2024_07_31_140747_temporary_update_category_wise_daily_totals_table_data',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1197,'2024_07_31_140748_temporary_update_counters_table_data',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1198,'2024_07_31_140749_temporary_copy_store_store_manager_to_location_store_manager',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1199,'2024_07_31_140750_temporary_copy_director_store_to_director_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1200,'2024_07_31_140751_temporary_copy_dream_price_store_to_dream_price_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1201,'2024_07_31_140752_temporary_copy_happy_hour_discounts_store_id_to_location_id_column',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1202,'2024_07_31_140753_temporary_copy_loyalty_campaign_configuration_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1203,'2024_07_31_140754_temporary_copy_manual_notification_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1204,'2024_07_31_140755_temporary_copy_order_credit_note_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1205,'2024_07_31_140756_temporary_copy_order_payment_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1206,'2024_07_31_140757_temporary_copy_order_returns_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1207,'2024_07_31_140758_temporary_copy_order_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1208,'2024_07_31_140759_temporary_copy_past_year_data_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1209,'2024_07_31_140760_temporary_copy_pos_advertisement_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1210,'2024_07_31_140761_temporary_copy_product_ageing_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1211,'2024_07_31_140762_temporary_copy_promoter_commission_store_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1212,'2024_07_31_140763_temporary_copy_promoter_store_to_location_promoter',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1213,'2024_07_31_140764_temporary_copy_sale_target_store_to_location_sale_target',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1214,'2024_07_31_140765_temporary_copy_store_day_close_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1215,'2024_07_31_140767_temporary_copy_store_wise_daily_totals_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1216,'2024_07_31_140768_temporary_copy_automated_notification_products',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1217,'2024_07_31_140769_temporary_copy_automated_notification_stores_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1218,'2024_07_31_140770_temporary_copy_external_location_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1219,'2024_07_31_140771_temporary_copy_city_and_state_in_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1220,'2024_07_31_140772_temporary_copy_goods_received_notes_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1221,'2024_07_31_140773_temporary_copy_inventories_data_to_locations',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1222,'2024_07_31_140774_temporary_copy_member_store_id_to_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1223,'2024_07_31_140775_temporary_copy_inventory_updates_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1224,'2024_07_31_140776_temporary_copy_promotion_store_to_location_promotion',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1225,'2024_07_31_140777_temporary_copy_purchase_order_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1226,'2024_07_31_140778_temporary_copy_sale_channel_default_store_to_default_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1227,'2024_07_31_140779_temporary_copy_sale_channel_store_to_location_sale_channel',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1228,'2024_07_31_140780_temporary_copy_sale_return_reasons_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1229,'2024_07_31_140781_temporary_copy_stock_adjustment_items_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1230,'2024_07_31_140782_temporary_copy_stock_take_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1231,'2024_07_31_140783_temporary_copy_stock_transfer_average_lead_days_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1232,'2024_07_31_140784_temporary_copy_stock_transfer_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1233,'2024_07_31_140785_temporary_copy_voucher_store_id_to_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1234,'2024_07_31_140786_temporary_copy_sequence_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1235,'2024_07_31_140787_temporary_copy_voucher_transactions_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1236,'2024_07_31_140788_temporary_update_sale_achieved_target',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1237,'2024_08_01_172226_location_warehouse_manager',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1238,'2024_08_01_174627_temporary_copy_warehouse_warehouse_manager_to_location_warehouse_manager',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1239,'2024_08_08_142406_add_company_default_store_id_to_default_location_id',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1240,'2024_08_08_143127_temporary_copy_company_default_store_to_default_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1241,'2024_09_05_115034_inventroy_unique_product_and_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1242,'2024_09_11_140315_create_automated_notification_location_table',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1243,'2024_09_16_124550_temporary_update_activity_log',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1244,'2024_09_17_162426_temporary_copy_automated_notification_stores_to_location',333);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1245,'2024_10_01_190940_add-columns-in-stock-transfer-table',334);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1246,'2024_10_07_205337_required_location_id',334);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1247,'2024_10_07_210649_rename_column_loyalty_points',334);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1248,'2024_10_07_210818_rename_column_companies',334);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1249,'2024_10_08_124333_add_column_in_sale_items_table',334);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1250,'2024_10_09_152753_temporary-update-product-ageing-table',335);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1251,'2024_10_11_163925_add_column_in_uuid_in_location',335);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1252,'2024_10_11_164645_temporary_add_uuid_in_location',335);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1253,'2024_10_11_171414_temporary_member_data_update',335);
