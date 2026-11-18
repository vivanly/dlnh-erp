/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accessories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accessories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accessories_sku_unique` (`sku`),
  UNIQUE KEY `accessories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `license_number` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `excipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `excipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `excipients_sku_unique` (`sku`),
  UNIQUE KEY `excipients_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_receipt_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` bigint(20) unsigned NOT NULL,
  `purchase_order_item_id` bigint(20) unsigned NOT NULL,
  `herb_id` bigint(20) unsigned NOT NULL,
  `ordered_quantity` decimal(10,2) NOT NULL,
  `received_quantity` decimal(10,2) NOT NULL,
  `returned_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `return_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_items_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `goods_receipt_items_herb_id_foreign` (`herb_id`),
  KEY `goods_receipt_items_purchase_order_item_id_herb_id_index` (`purchase_order_item_id`,`herb_id`),
  CONSTRAINT `goods_receipt_items_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_receipt_items_herb_id_foreign` FOREIGN KEY (`herb_id`) REFERENCES `products` (`id`),
  CONSTRAINT `goods_receipt_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `receipt_code` varchar(255) NOT NULL,
  `receipt_date` date NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `qc_status` enum('pending','passed','failed','partially_passed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_receipts_receipt_code_unique` (`receipt_code`),
  KEY `goods_receipts_receiver_id_foreign` (`receiver_id`),
  KEY `goods_receipts_purchase_order_id_receipt_date_index` (`purchase_order_id`,`receipt_date`),
  CONSTRAINT `goods_receipts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `goods_receipts_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingredients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ingredients_sku_unique` (`sku`),
  UNIQUE KEY `ingredients_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `production_finished_batch_id` bigint(20) unsigned DEFAULT NULL,
  `movement_type` varchar(32) NOT NULL,
  `direction` varchar(8) NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit` varchar(32) NOT NULL,
  `reference_type` varchar(64) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_movements_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `inventory_movements_production_finished_batch_id_foreign` (`production_finished_batch_id`),
  KEY `inventory_movements_created_by_foreign` (`created_by`),
  KEY `inventory_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_movements_product_id_movement_type_created_at_index` (`product_id`,`movement_type`,`created_at`),
  CONSTRAINT `inventory_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_movements_production_finished_batch_id_foreign` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`),
  CONSTRAINT `inventory_movements_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_label_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manual_label_deliveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `manual_label_print_id` bigint(20) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `kind` varchar(10) NOT NULL DEFAULT 'out',
  `received_by` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `delivered_by` bigint(20) unsigned DEFAULT NULL,
  `delivered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manual_label_deliveries_manual_label_print_id_foreign` (`manual_label_print_id`),
  KEY `manual_label_deliveries_delivered_by_foreign` (`delivered_by`),
  CONSTRAINT `manual_label_deliveries_delivered_by_foreign` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `manual_label_deliveries_manual_label_print_id_foreign` FOREIGN KEY (`manual_label_print_id`) REFERENCES `manual_label_prints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_label_prints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manual_label_prints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label_type` varchar(16) NOT NULL,
  `traditional_medicine_name` tinyint(1) NOT NULL DEFAULT 0,
  `product_name` varchar(255) NOT NULL,
  `batch` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `mfg_date` varchar(32) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `exp_date` varchar(32) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `net_weight` varchar(64) DEFAULT NULL,
  `registration` varchar(255) DEFAULT NULL,
  `standard` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `qr_content` text DEFAULT NULL,
  `include_packaging_control` tinyint(1) NOT NULL DEFAULT 0,
  `packaging_controller` varchar(60) DEFAULT NULL,
  `packaging_performer` varchar(60) DEFAULT NULL,
  `watermark` tinyint(1) NOT NULL DEFAULT 0,
  `copies_count` int(10) unsigned NOT NULL,
  `delivered_count` int(10) unsigned NOT NULL DEFAULT 0,
  `returned_count` int(10) unsigned NOT NULL DEFAULT 0,
  `customer` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `order_code` varchar(32) DEFAULT NULL,
  `label_code` varchar(32) DEFAULT NULL,
  `printed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manual_label_prints_printed_by_foreign` (`printed_by`),
  KEY `manual_label_prints_order_code_index` (`order_code`),
  KEY `manual_label_prints_label_code_index` (`label_code`),
  CONSTRAINT `manual_label_prints_printed_by_foreign` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_label_production_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manual_label_production_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label_code` varchar(32) NOT NULL,
  `order_date` date DEFAULT NULL,
  `production_order` varchar(64) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `pack_spec` varchar(64) DEFAULT NULL,
  `quantity` varchar(64) DEFAULT NULL,
  `label_count` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `label_type` varchar(16) DEFAULT NULL,
  `traditional_medicine_name` tinyint(1) DEFAULT NULL,
  `qr_content` text DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `manual_label_production_orders_label_code_unique` (`label_code`),
  KEY `manual_label_production_orders_production_order_index` (`production_order`),
  KEY `manual_label_production_orders_name_index` (`name`),
  KEY `manual_label_production_orders_batch_index` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_label_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manual_label_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name_key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(64) DEFAULT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `standard` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `registration` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `manual_label_products_name_key_unique` (`name_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manual_label_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `manual_label_sequences` (
  `id` bigint(20) unsigned NOT NULL,
  `last_number` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_lot_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_lot_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_lot_id` bigint(20) unsigned NOT NULL,
  `checked_by` bigint(20) unsigned DEFAULT NULL,
  `result` varchar(24) NOT NULL,
  `note` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `inspection_stage` varchar(255) NOT NULL DEFAULT 'qc_inspection',
  `detailed_findings` text DEFAULT NULL,
  `corrective_action` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `report_number` varchar(255) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_lot_inspections_material_lot_id_foreign` (`material_lot_id`),
  KEY `material_lot_inspections_checked_by_foreign` (`checked_by`),
  CONSTRAINT `material_lot_inspections_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lot_inspections_material_lot_id_foreign` FOREIGN KEY (`material_lot_id`) REFERENCES `material_lots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_lots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_item_id` bigint(20) unsigned NOT NULL,
  `material_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `material_type` varchar(255) NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `coa_file` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_qa',
  `temporary_location_code` varchar(255) DEFAULT NULL,
  `temporary_stored_at` timestamp NULL DEFAULT NULL,
  `temporary_stored_by` bigint(20) unsigned DEFAULT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `qc_by` bigint(20) unsigned DEFAULT NULL,
  `qc_at` timestamp NULL DEFAULT NULL,
  `qa_reviewed_at` timestamp NULL DEFAULT NULL,
  `qa_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `qc_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stock_receipt_code` varchar(255) DEFAULT NULL,
  `stock_received_by` bigint(20) unsigned DEFAULT NULL,
  `stock_received_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_lots_stock_receipt_code_unique` (`stock_receipt_code`),
  KEY `material_lots_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `material_lots_received_by_foreign` (`received_by`),
  KEY `material_lots_qc_by_foreign` (`qc_by`),
  KEY `material_lots_temporary_stored_by_foreign` (`temporary_stored_by`),
  KEY `material_lots_qa_reviewed_by_foreign` (`qa_reviewed_by`),
  KEY `material_lots_material_receipt_id_foreign` (`material_receipt_id`),
  KEY `material_lots_stock_received_by_foreign` (`stock_received_by`),
  CONSTRAINT `material_lots_material_receipt_id_foreign` FOREIGN KEY (`material_receipt_id`) REFERENCES `material_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lots_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_lots_qa_reviewed_by_foreign` FOREIGN KEY (`qa_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lots_qc_by_foreign` FOREIGN KEY (`qc_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lots_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lots_stock_received_by_foreign` FOREIGN KEY (`stock_received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_lots_temporary_stored_by_foreign` FOREIGN KEY (`temporary_stored_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_code` varchar(255) DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `preliminary_qa_by` bigint(20) unsigned DEFAULT NULL,
  `preliminary_qc_by` bigint(20) unsigned DEFAULT NULL,
  `preliminary_note` text DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_receipts_receipt_code_unique` (`receipt_code`),
  KEY `material_receipts_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `material_receipts_received_by_foreign` (`received_by`),
  KEY `material_receipts_preliminary_qa_by_foreign` (`preliminary_qa_by`),
  KEY `material_receipts_preliminary_qc_by_foreign` (`preliminary_qc_by`),
  CONSTRAINT `material_receipts_preliminary_qa_by_foreign` FOREIGN KEY (`preliminary_qa_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_receipts_preliminary_qc_by_foreign` FOREIGN KEY (`preliminary_qc_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_receipts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `material_receipts_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `material_type` varchar(30) NOT NULL,
  `material_id` bigint(20) unsigned NOT NULL,
  `movement_type` varchar(30) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `material_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `material_lot_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_stock_movements_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `material_stock_movements_order_item_id_foreign` (`order_item_id`),
  KEY `material_stock_movements_user_id_foreign` (`user_id`),
  KEY `material_stock_movements_material_type_material_id_index` (`material_type`,`material_id`),
  KEY `material_stock_movements_material_receipt_id_foreign` (`material_receipt_id`),
  KEY `material_stock_movements_material_lot_id_foreign` (`material_lot_id`),
  CONSTRAINT `material_stock_movements_material_lot_id_foreign` FOREIGN KEY (`material_lot_id`) REFERENCES `material_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_stock_movements_material_receipt_id_foreign` FOREIGN KEY (`material_receipt_id`) REFERENCES `material_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_stock_movements_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_stock_movements_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `raw_material_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `packaging_spec` varchar(255) DEFAULT NULL,
  `finished_quantity` decimal(12,2) DEFAULT NULL,
  `ppcb_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `actual_quantity` decimal(12,2) DEFAULT NULL,
  `packed_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `warehouse_stock_available_quantity` decimal(12,4) DEFAULT NULL,
  `warehouse_stock_confirmed_quantity` decimal(12,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_ppcb_id_foreign` (`ppcb_id`),
  KEY `order_items_order_id_product_id_index` (`order_id`,`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ppcb_id_foreign` FOREIGN KEY (`ppcb_id`) REFERENCES `ppcb` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_code` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_type` varchar(255) NOT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date NOT NULL,
  `province_city` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_confirmed_at` timestamp NULL DEFAULT NULL,
  `warehouse_packed_at` timestamp NULL DEFAULT NULL,
  `qa_confirmed_at` timestamp NULL DEFAULT NULL,
  `warehouse_stock_checked_at` timestamp NULL DEFAULT NULL,
  `sales_approved_at` timestamp NULL DEFAULT NULL,
  `sales_approved_by` bigint(20) unsigned DEFAULT NULL,
  `sales_rejection_reason` text DEFAULT NULL,
  `production_plan_approved_at` timestamp NULL DEFAULT NULL,
  `production_plan_approved_by` bigint(20) unsigned DEFAULT NULL,
  `production_plan_rejection_reason` text DEFAULT NULL,
  `production_packaged_at` timestamp NULL DEFAULT NULL,
  `production_packaged_by` bigint(20) unsigned DEFAULT NULL,
  `warehouse_stock_checked_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_code_unique` (`order_code`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_sales_approved_user_fk` (`sales_approved_by`),
  KEY `orders_production_plan_approved_by_foreign` (`production_plan_approved_by`),
  KEY `orders_production_packaged_by_foreign` (`production_packaged_by`),
  KEY `orders_warehouse_stock_checked_by_foreign` (`warehouse_stock_checked_by`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_production_packaged_by_foreign` FOREIGN KEY (`production_packaged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_production_plan_approved_by_foreign` FOREIGN KEY (`production_plan_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_sales_approved_user_fk` FOREIGN KEY (`sales_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_warehouse_stock_checked_by_foreign` FOREIGN KEY (`warehouse_stock_checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packaging_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packaging_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `packaging_materials_sku_unique` (`sku`),
  UNIQUE KEY `packaging_materials_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ppcb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ppcb` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ma` varchar(255) NOT NULL,
  `ten_ppcb` varchar(255) NOT NULL,
  `chi_tiet_ppcb` text DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ppcb_ma_unique` (`ma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_bom_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_bom_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_bom_id` bigint(20) unsigned NOT NULL,
  `component_product_id` bigint(20) unsigned DEFAULT NULL,
  `component_type` varchar(255) DEFAULT NULL,
  `component_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_bom_items_component_product_id_foreign` (`component_product_id`),
  KEY `product_bom_items_product_bom_id_component_product_id_index` (`product_bom_id`,`component_product_id`),
  KEY `product_bom_items_component_idx` (`component_type`,`component_id`),
  CONSTRAINT `product_bom_items_component_product_id_foreign` FOREIGN KEY (`component_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `product_bom_items_product_bom_id_foreign` FOREIGN KEY (`product_bom_id`) REFERENCES `product_boms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_boms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_boms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `output_quantity` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `output_unit` varchar(32) NOT NULL,
  `yield_rate` decimal(8,5) NOT NULL DEFAULT 1.00000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` varchar(32) NOT NULL DEFAULT 'approved',
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_boms_product_id_version_unique` (`product_id`,`version`),
  KEY `product_boms_product_id_is_active_index` (`product_id`,`is_active`),
  KEY `product_boms_submitted_by_foreign` (`submitted_by`),
  KEY `product_boms_approved_by_foreign` (`approved_by`),
  KEY `product_boms_product_id_status_index` (`product_id`,`status`),
  CONSTRAINT `product_boms_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_boms_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_boms_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_quality_standards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_quality_standards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `standard_type` varchar(255) NOT NULL,
  `indicator` varchar(255) NOT NULL,
  `requirement` text NOT NULL,
  `method` text NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_quality_standards_product_id_standard_type_index` (`product_id`,`standard_type`),
  CONSTRAINT `product_quality_standards_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_regulatory_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_regulatory_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(20) NOT NULL,
  `document_number` varchar(255) NOT NULL,
  `document_date` date NOT NULL,
  `document_form` varchar(80) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prd_type_number_unique` (`document_type`,`document_number`),
  KEY `prd_product_type_index` (`product_id`,`document_type`),
  CONSTRAINT `product_regulatory_documents_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_storage_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_storage_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `storage_method` text NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_storage_methods_product_id_index` (`product_id`),
  CONSTRAINT `product_storage_methods_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_unit_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_unit_conversions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `unit` varchar(32) NOT NULL,
  `to_base_factor` decimal(20,10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_unit_conversions_product_id_unit_unique` (`product_id`,`unit`),
  CONSTRAINT `product_unit_conversions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_batch_code_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_batch_code_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_finished_batch_id` bigint(20) unsigned NOT NULL,
  `old_batch_number` varchar(255) NOT NULL,
  `new_batch_number` varchar(255) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prod_batch_code_hist_batch_fk` (`production_finished_batch_id`),
  KEY `production_batch_code_histories_changed_by_foreign` (`changed_by`),
  CONSTRAINT `prod_batch_code_hist_batch_fk` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_batch_code_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_batch_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_batch_inputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_finished_batch_id` bigint(20) unsigned NOT NULL,
  `production_material_lot_id` bigint(20) unsigned NOT NULL,
  `consumed_quantity` decimal(15,4) NOT NULL,
  `unit` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prod_batch_inputs_finished_lot_unique` (`production_finished_batch_id`,`production_material_lot_id`),
  KEY `production_batch_inputs_production_material_lot_id_foreign` (`production_material_lot_id`),
  CONSTRAINT `production_batch_inputs_production_finished_batch_id_foreign` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_batch_inputs_production_material_lot_id_foreign` FOREIGN KEY (`production_material_lot_id`) REFERENCES `production_material_lots` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_finished_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_finished_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_order_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `provisional_batch_number` varchar(255) DEFAULT NULL,
  `planned_quantity` decimal(15,4) DEFAULT NULL,
  `initial_quantity` decimal(15,4) NOT NULL,
  `current_quantity` decimal(15,4) NOT NULL,
  `pending_warehouse_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit` varchar(32) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `qa_approved_at` timestamp NULL DEFAULT NULL,
  `qa_approved_by` bigint(20) unsigned DEFAULT NULL,
  `qc_test_report` varchar(255) DEFAULT NULL,
  `qc_date` date DEFAULT NULL,
  `qc_test_report_file` varchar(255) DEFAULT NULL,
  `qc_result` varchar(16) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_received_at` timestamp NULL DEFAULT NULL,
  `warehouse_received_by` bigint(20) unsigned DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `ppcb_id` bigint(20) unsigned DEFAULT NULL,
  `license_number` varchar(255) DEFAULT NULL,
  `batch_number_assigned_at` timestamp NULL DEFAULT NULL,
  `batch_number_assigned_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_finished_batches_batch_number_unique` (`batch_number`),
  KEY `production_finished_batches_received_by_foreign` (`received_by`),
  KEY `production_finished_batches_product_id_status_exp_date_index` (`product_id`,`status`,`exp_date`),
  KEY `production_finished_batches_qa_approved_by_foreign` (`qa_approved_by`),
  KEY `prod_finished_recv_user_fk` (`warehouse_received_by`),
  KEY `production_finished_batches_production_order_id_foreign` (`production_order_id`),
  KEY `production_finished_batches_ppcb_id_foreign` (`ppcb_id`),
  KEY `production_finished_batches_batch_number_assigned_by_foreign` (`batch_number_assigned_by`),
  CONSTRAINT `prod_finished_recv_user_fk` FOREIGN KEY (`warehouse_received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_finished_batches_batch_number_assigned_by_foreign` FOREIGN KEY (`batch_number_assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_finished_batches_ppcb_id_foreign` FOREIGN KEY (`ppcb_id`) REFERENCES `ppcb` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_finished_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_finished_batches_production_order_id_foreign` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`),
  CONSTRAINT `production_finished_batches_qa_approved_by_foreign` FOREIGN KEY (`qa_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_finished_batches_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_material_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_material_lots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_order_material_id` bigint(20) unsigned NOT NULL,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `allocated_quantity` decimal(15,4) NOT NULL,
  `issued_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `issued_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `returned_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reported_returned_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `material_lot_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_material_lots_production_order_material_id_foreign` (`production_order_material_id`),
  KEY `production_material_lots_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `production_material_lots_assigned_by_foreign` (`assigned_by`),
  KEY `production_material_lots_material_lot_id_foreign` (`material_lot_id`),
  CONSTRAINT `production_material_lots_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_material_lots_material_lot_id_foreign` FOREIGN KEY (`material_lot_id`) REFERENCES `material_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_material_lots_production_order_material_id_foreign` FOREIGN KEY (`production_order_material_id`) REFERENCES `production_order_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `production_material_lots_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_monthly_plan_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_monthly_plan_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_monthly_plan_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `ppcb_id` bigint(20) unsigned DEFAULT NULL,
  `product_bom_id` bigint(20) unsigned DEFAULT NULL,
  `planned_quantity` decimal(15,4) NOT NULL,
  `unit` varchar(32) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthly_plan_product_unique` (`production_monthly_plan_id`,`product_id`),
  KEY `production_monthly_plan_lines_product_id_foreign` (`product_id`),
  KEY `production_monthly_plan_lines_product_bom_id_foreign` (`product_bom_id`),
  KEY `production_monthly_plan_lines_ppcb_id_foreign` (`ppcb_id`),
  CONSTRAINT `production_monthly_plan_lines_ppcb_id_foreign` FOREIGN KEY (`ppcb_id`) REFERENCES `ppcb` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_monthly_plan_lines_product_bom_id_foreign` FOREIGN KEY (`product_bom_id`) REFERENCES `product_boms` (`id`),
  CONSTRAINT `production_monthly_plan_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_monthly_plan_lines_production_monthly_plan_id_foreign` FOREIGN KEY (`production_monthly_plan_id`) REFERENCES `production_monthly_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_monthly_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_monthly_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_month` date NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_monthly_plans_plan_month_unique` (`plan_month`),
  KEY `production_monthly_plans_created_by_foreign` (`created_by`),
  KEY `production_monthly_plans_approved_by_foreign` (`approved_by`),
  CONSTRAINT `production_monthly_plans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_monthly_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_order_batch_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_order_batch_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_order_id` bigint(20) unsigned NOT NULL,
  `production_finished_batch_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `received_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_receipt_linked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_batch_allocation_unique` (`production_order_id`,`production_finished_batch_id`),
  KEY `po_batch_allocation_lookup_idx` (`production_finished_batch_id`,`production_order_id`),
  CONSTRAINT `po_batch_alloc_batch_fk` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `po_batch_alloc_order_fk` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_order_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_order_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_order_id` bigint(20) unsigned NOT NULL,
  `product_bom_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `material_type` varchar(30) DEFAULT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `required_quantity` decimal(15,4) NOT NULL,
  `issued_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit` varchar(32) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `consumed_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `production_order_materials_product_bom_item_id_foreign` (`product_bom_item_id`),
  KEY `production_order_materials_product_id_foreign` (`product_id`),
  KEY `prod_order_materials_order_product_idx` (`production_order_id`,`product_id`),
  KEY `prod_order_materials_catalog_idx` (`production_order_id`,`material_type`,`material_id`),
  CONSTRAINT `production_order_materials_product_bom_item_id_foreign` FOREIGN KEY (`product_bom_item_id`) REFERENCES `product_bom_items` (`id`),
  CONSTRAINT `production_order_materials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_order_materials_production_order_id_foreign` FOREIGN KEY (`production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_code` varchar(255) NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `production_monthly_plan_line_id` bigint(20) unsigned DEFAULT NULL,
  `product_bom_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `planned_quantity` decimal(15,4) NOT NULL,
  `provisional_batch_number` varchar(255) DEFAULT NULL,
  `actual_quantity` decimal(15,4) DEFAULT NULL,
  `pending_finished_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `unit` varchar(32) NOT NULL,
  `yield_rate` decimal(8,5) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'planned',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `materials_issued_at` timestamp NULL DEFAULT NULL,
  `production_started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `production_reported_at` timestamp NULL DEFAULT NULL,
  `production_reported_by` bigint(20) unsigned DEFAULT NULL,
  `warehouse_confirmed_at` timestamp NULL DEFAULT NULL,
  `warehouse_confirmed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_orders_production_code_unique` (`production_code`),
  KEY `production_orders_product_bom_id_foreign` (`product_bom_id`),
  KEY `production_orders_product_id_foreign` (`product_id`),
  KEY `production_orders_created_by_foreign` (`created_by`),
  KEY `production_orders_order_id_status_index` (`order_id`,`status`),
  KEY `production_orders_order_item_id_foreign` (`order_item_id`),
  KEY `production_orders_production_monthly_plan_line_id_foreign` (`production_monthly_plan_line_id`),
  KEY `production_orders_production_reported_by_foreign` (`production_reported_by`),
  KEY `production_orders_warehouse_confirmed_by_foreign` (`warehouse_confirmed_by`),
  CONSTRAINT `production_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `production_orders_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `production_orders_product_bom_id_foreign` FOREIGN KEY (`product_bom_id`) REFERENCES `product_boms` (`id`),
  CONSTRAINT `production_orders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `production_orders_production_monthly_plan_line_id_foreign` FOREIGN KEY (`production_monthly_plan_line_id`) REFERENCES `production_monthly_plan_lines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_orders_production_reported_by_foreign` FOREIGN KEY (`production_reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `production_orders_warehouse_confirmed_by_foreign` FOREIGN KEY (`warehouse_confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `raw_material_id` bigint(20) unsigned DEFAULT NULL,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `ppcb_id` bigint(20) unsigned DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `gtin` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  UNIQUE KEY `products_gtin_unique` (`gtin`),
  KEY `products_ppcb_id_foreign` (`ppcb_id`),
  CONSTRAINT `products_ppcb_id_foreign` FOREIGN KEY (`ppcb_id`) REFERENCES `ppcb` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `material_type` varchar(30) DEFAULT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `received_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `coa_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_product_id_foreign` (`product_id`),
  KEY `purchase_order_items_purchase_order_id_product_id_index` (`purchase_order_id`,`product_id`),
  KEY `purchase_order_items_material_type_material_id_index` (`material_type`,`material_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_confirmed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_user_id_foreign` (`user_id`),
  KEY `purchase_orders_supplier_id_status_index` (`supplier_id`,`status`),
  KEY `purchase_orders_status_order_date_index` (`status`,`order_date`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `purchase_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `raw_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raw_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classification` varchar(255) DEFAULT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `scientific_name` varchar(255) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `part_used` varchar(255) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `scientific_name_reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `raw_materials_sku_unique` (`sku`),
  UNIQUE KEY `raw_materials_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_order_label_prints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_order_label_prints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `sales_order_lot_allocation_id` bigint(20) unsigned NOT NULL,
  `copies_count` int(10) unsigned NOT NULL,
  `print_kind` varchar(16) NOT NULL DEFAULT 'required',
  `reason` varchar(255) DEFAULT NULL,
  `printed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_label_print_alloc_fk` (`sales_order_lot_allocation_id`),
  KEY `sales_label_print_user_fk` (`printed_by`),
  KEY `sales_label_print_item_idx` (`order_item_id`),
  CONSTRAINT `sales_label_print_alloc_fk` FOREIGN KEY (`sales_order_lot_allocation_id`) REFERENCES `sales_order_lot_allocations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_label_print_item_fk` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_label_print_user_fk` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_order_lot_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_order_lot_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `production_finished_batch_id` bigint(20) unsigned DEFAULT NULL,
  `reserved_quantity` decimal(15,4) NOT NULL,
  `shipped_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `status` varchar(24) NOT NULL DEFAULT 'reserved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `label_printed_at` timestamp NULL DEFAULT NULL,
  `label_printed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_order_lot_allocations_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `sales_order_lot_allocations_production_finished_batch_id_foreign` (`production_finished_batch_id`),
  KEY `sales_order_lot_allocations_order_item_id_status_index` (`order_item_id`,`status`),
  KEY `sales_order_lot_allocations_label_printed_by_foreign` (`label_printed_by`),
  CONSTRAINT `sales_order_lot_allocations_label_printed_by_foreign` FOREIGN KEY (`label_printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_order_lot_allocations_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_order_lot_allocations_production_finished_batch_id_foreign` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`),
  CONSTRAINT `sales_order_lot_allocations_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_order_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_order_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_return_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `sales_order_lot_allocation_id` bigint(20) unsigned NOT NULL,
  `production_finished_batch_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending_inspection',
  `inspection_note` text DEFAULT NULL,
  `inspected_by` bigint(20) unsigned DEFAULT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `rework_production_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_order_return_items_sales_order_return_id_foreign` (`sales_order_return_id`),
  KEY `sales_order_return_items_sales_order_lot_allocation_id_foreign` (`sales_order_lot_allocation_id`),
  KEY `sales_order_return_items_production_finished_batch_id_foreign` (`production_finished_batch_id`),
  KEY `sales_order_return_items_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `sales_order_return_items_inspected_by_foreign` (`inspected_by`),
  KEY `sales_order_return_items_rework_production_order_id_foreign` (`rework_production_order_id`),
  KEY `sales_order_return_items_order_item_id_status_index` (`order_item_id`,`status`),
  CONSTRAINT `sales_order_return_items_inspected_by_foreign` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_order_return_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  CONSTRAINT `sales_order_return_items_production_finished_batch_id_foreign` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`),
  CONSTRAINT `sales_order_return_items_rework_production_order_id_foreign` FOREIGN KEY (`rework_production_order_id`) REFERENCES `production_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_order_return_items_sales_order_lot_allocation_id_foreign` FOREIGN KEY (`sales_order_lot_allocation_id`) REFERENCES `sales_order_lot_allocations` (`id`),
  CONSTRAINT `sales_order_return_items_sales_order_return_id_foreign` FOREIGN KEY (`sales_order_return_id`) REFERENCES `sales_order_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_order_return_items_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_order_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_order_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `received_by` bigint(20) unsigned DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(32) NOT NULL DEFAULT 'pending_inspection',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_order_returns_received_by_foreign` (`received_by`),
  KEY `sales_order_returns_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `sales_order_returns_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `sales_order_returns_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(255) NOT NULL,
  `initial_quantity` decimal(15,4) NOT NULL,
  `current_quantity` decimal(15,4) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `exp_date` date DEFAULT NULL,
  `coa_file` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_batches_product_id_status_exp_date_index` (`product_id`,`status`,`exp_date`),
  KEY `supplier_batches_goods_receipt_item_id_batch_number_index` (`goods_receipt_item_id`,`batch_number`),
  CONSTRAINT `supplier_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_return_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_return_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_code` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `material_type` varchar(30) DEFAULT NULL,
  `material_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `material_lot_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit` varchar(32) NOT NULL,
  `reason` text NOT NULL,
  `qc_test_report` varchar(255) DEFAULT NULL,
  `qc_date` date DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending_dispatch',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_return_orders_return_code_unique` (`return_code`),
  KEY `supplier_return_orders_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `supplier_return_orders_supplier_batch_id_foreign` (`supplier_batch_id`),
  KEY `supplier_return_orders_created_by_foreign` (`created_by`),
  KEY `supplier_return_orders_supplier_id_status_index` (`supplier_id`,`status`),
  KEY `supplier_return_orders_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `supplier_return_orders_material_lot_id_foreign` (`material_lot_id`),
  CONSTRAINT `supplier_return_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_return_orders_material_lot_id_foreign` FOREIGN KEY (`material_lot_id`) REFERENCES `material_lots` (`id`),
  CONSTRAINT `supplier_return_orders_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `supplier_return_orders_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_return_orders_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`),
  CONSTRAINT `supplier_return_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `traceability_lot_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traceability_lot_codes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_batch_id` bigint(20) unsigned DEFAULT NULL,
  `production_finished_batch_id` bigint(20) unsigned DEFAULT NULL,
  `trace_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `traceability_lot_codes_supplier_batch_id_unique` (`supplier_batch_id`),
  UNIQUE KEY `traceability_lot_codes_production_finished_batch_id_unique` (`production_finished_batch_id`),
  UNIQUE KEY `traceability_lot_codes_trace_code_unique` (`trace_code`),
  CONSTRAINT `traceability_lot_codes_production_finished_batch_id_foreign` FOREIGN KEY (`production_finished_batch_id`) REFERENCES `production_finished_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `traceability_lot_codes_supplier_batch_id_foreign` FOREIGN KEY (`supplier_batch_id`) REFERENCES `supplier_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `traceability_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `traceability_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `base_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `locale` varchar(255) NOT NULL DEFAULT 'vi',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `can_print_labels` tinyint(1) NOT NULL DEFAULT 0,
  `employee_code` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'working',
  `avatar_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_code_unique` (`employee_code`),
  KEY `users_department_id_foreign` (`department_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_15_015427_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_15_015752_add_department_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_15_020401_add_address_and_status_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_15_030526_add_locale_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_15_033349_add_department_and_position_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_15_071044_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_18_080157_create_ppcb_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_18_080158_add_ppcb_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_23_021950_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_23_030807_create_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_23_030816_create_purchase_order_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_23_045431_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_23_082216_create_goods_receipts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_23_082225_create_goods_receipt_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_09_24_020633_create_supplier_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_09_24_063516_create_internal_batches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_09_25_012120_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_09_25_020904_create_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_09_25_024955_create_order_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_09_28_080000_add_warehouse_confirmation_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_09_28_090000_create_bom_and_unit_conversions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_09_28_100000_create_production_workflow_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_09_28_110000_backfill_inventory_opening_movements',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_09_29_000000_add_packing_stage_to_sales_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_09_29_010000_add_warehouse_receipt_to_internal_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_09_29_020000_add_warehouse_stock_check_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_09_29_030000_add_production_material_returns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_09_29_040000_add_qa_approval_to_production_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_09_29_050000_create_sales_order_label_prints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_09_29_060000_add_pending_warehouse_receipt_to_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_30_000000_add_sales_director_approval_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_09_30_010000_add_planned_quantity_to_production_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_09_30_020000_create_supplier_return_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_09_30_030000_add_qc_test_report_file_to_production_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_09_30_040000_add_production_plan_approval_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_09_30_050000_add_packaging_confirmation_to_production_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_09_30_060000_add_qc_result_to_production_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_09_30_070000_add_qc_result_and_report_file_to_internal_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_09_30_090000_add_production_packaging_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_09_30_100000_remove_obsolete_batch_packaging_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_09_30_110000_drop_legacy_internal_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_09_30_120000_make_production_order_optional_for_internal_lots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_09_30_130000_add_metadata_to_production_finished_batches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_10_01_000000_add_approval_workflow_to_product_boms',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_10_01_020000_create_product_regulatory_documents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_10_03_000000_add_warehouse_stock_confirmation_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_10_03_010000_add_pending_finished_quantity_to_production_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_10_05_000000_create_traceability_qr_management_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_10_05_120000_create_monthly_production_plans',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_10_05_130000_add_avatar_path_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_10_06_170000_create_product_quality_standards_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_10_06_170001_create_product_storage_methods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_10_07_090000_decouple_monthly_plans_from_bom',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_10_07_100000_allow_production_materials_without_bom_item',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_10_08_110000_create_raw_materials_and_accessories_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_10_08_120000_create_material_stock_movements_and_raw_material_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_10_08_130000_make_supplier_returns_for_materials',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_10_08_140000_add_catalog_materials_to_production_issue',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_10_08_150000_create_material_lots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_10_08_160000_add_production_reporting_fields_to_production_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_10_08_170000_create_production_order_batch_allocations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_10_09_100000_add_active_status_to_customers_and_suppliers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_10_12_120000_add_received_quantity_to_production_order_batch_allocations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_10_12_130000_link_supplier_returns_to_failed_material_lots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_10_26_090000_add_can_print_labels_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_11_02_000000_add_excipients_packaging_and_bom_components',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_11_05_070000_create_manual_label_prints_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_11_05_080000_add_qr_content_to_manual_label_prints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_11_05_090000_create_manual_label_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_11_05_100000_add_catalog_columns_to_manual_label_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_11_05_110000_create_manual_label_order_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_11_05_120000_add_order_code_to_manual_label_prints_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_11_05_130000_rebuild_manual_label_sources_for_production_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_11_05_140000_add_return_and_customer_to_manual_label_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_11_09_143000_add_preproduction_batch_assignment_audit',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_11_16_000000_standardize_item_and_order_type_codes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_11_16_170000_normalize_manual_label_codes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_11_16_171000_add_manual_label_templates_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_11_17_082500_add_qr_content_to_manual_label_production_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_11_17_083000_add_storage_to_manual_label_production_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_11_17_130000_change_order_status_default_to_draft',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_11_17_141725_add_warehouse_enhancements',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_11_18_090000_split_ingredient_catalog_and_record_material_intake',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_11_18_100000_add_production_start_and_sales_order_returns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_11_18_110000_add_qc_report_to_material_lot_inspections',1);
