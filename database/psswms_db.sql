/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: psswms_db
-- ------------------------------------------------------
-- Server version	11.8.8-MariaDB-1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_code` varchar(10) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `status` varchar(20) DEFAULT 'AKTIF',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_category`),
  UNIQUE KEY `idx_categories_code_unique` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'CAT001','Susu','AKTIF','2026-11-23 23:30:31',NULL),
(2,'CAT002','Kopi','AKTIF','2026-11-23 23:30:31',NULL),
(3,'CAT003','Biskuit','AKTIF','2026-11-23 23:30:31',NULL),
(4,'CAT004','Wafer','AKTIF','2026-11-23 23:30:31',NULL),
(5,'CAT005','Mie Instan','AKTIF','2026-11-23 23:30:31',NULL),
(6,'CAT006','Saos','AKTIF','2026-11-23 23:30:31',NULL),
(7,'CAT007','Kecap','AKTIF','2026-11-23 23:30:31',NULL),
(8,'CAT008','Minuman','AKTIF','2026-11-23 23:30:31',NULL),
(9,'CAT009','Minyak Goreng','AKTIF','2026-11-23 23:30:31',NULL),
(10,'CAT010','Snack','AKTIF','2026-11-23 23:30:31',NULL),
(11,'CAT011','Air Mineral','AKTIF','2026-11-23 23:35:22',NULL),
(12,'CAT012','Teh','AKTIF','2026-11-23 23:35:22',NULL),
(13,'CAT013','Gula Pasir','AKTIF','2026-11-23 23:36:17','2026-11-23 16:46:00');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `inbound_details`
--

DROP TABLE IF EXISTS `inbound_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbound_details` (
  `id_inbound_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_inbound` int(10) unsigned DEFAULT NULL,
  `id_product` int(10) unsigned DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `id_batch` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `id_rack` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_inbound_detail`),
  KEY `inbound_header_id` (`id_inbound`),
  KEY `product_id` (`id_product`),
  KEY `batch_id` (`id_batch`),
  KEY `rack_location_id` (`id_rack`),
  KEY `idx_inbound_detail_id_inbound` (`id_inbound`),
  KEY `idx_inbound_detail_id_product` (`id_product`),
  KEY `idx_inbound_detail_id_batch` (`id_batch`),
  KEY `idx_inbound_detail_id_rack` (`id_rack`),
  CONSTRAINT `fk_inbound_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `product_batches` (`id_batch`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inbound_detail_header` FOREIGN KEY (`id_inbound`) REFERENCES `inbound` (`id_inbound`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inbound_detail_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inbound_detail_rack` FOREIGN KEY (`id_rack`) REFERENCES `rack` (`id_rack`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inbound_details`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `inbound_details` WRITE;
/*!40000 ALTER TABLE `inbound_details` DISABLE KEYS */;
INSERT INTO `inbound_details` VALUES
(1,1,1,'BATCH-SUSU-001',1,60,'2027-03-15',1,'2026-11-24 00:08:12'),
(2,1,2,'BATCH-SUSU-002',2,12,'2026-11-26',1,'2026-11-24 00:08:12'),
(3,2,13,'BATCH-MIE-001',13,140,'2027-05-10',4,'2026-11-24 00:08:12'),
(4,2,14,'BATCH-MIE-002',14,110,'2027-05-10',4,'2026-11-24 00:08:12'),
(5,3,4,'BATCH-KOPI-001',4,80,'2027-02-20',2,'2026-11-24 00:08:12'),
(6,3,5,'BATCH-KOPI-002',5,60,'2027-02-20',2,'2026-11-24 00:08:12'),
(7,4,7,'BATCH-BISKUIT-001',7,50,'2027-01-30',3,'2026-11-24 00:08:12'),
(8,4,10,'BATCH-WAFER-001',10,55,'2027-02-05',3,'2026-11-24 00:08:12'),
(9,5,16,'BATCH-SAOS-001',16,50,'2027-03-01',5,'2026-11-24 00:08:12'),
(10,5,17,'BATCH-SAOS-002',17,45,'2027-03-01',5,'2026-11-24 00:08:12'),
(11,6,19,'BATCH-KECAP-001',19,70,'2028-01-20',5,'2026-11-24 00:08:12'),
(12,6,20,'BATCH-KECAP-002',20,30,'2026-11-20',5,'2026-11-24 00:08:12'),
(13,7,22,'BATCH-MINUMAN-001',22,90,'2027-04-25',6,'2026-11-24 00:08:12'),
(14,7,31,'BATCH-AIR-001',31,180,'2028-05-20',7,'2026-11-24 00:08:12'),
(15,8,28,'BATCH-SNACK-001',28,60,'2027-01-15',7,'2026-11-24 00:08:12'),
(16,8,29,'BATCH-SNACK-002',29,55,'2027-01-15',7,'2026-11-24 00:08:12'),
(17,9,34,'BATCH-TEH-001',34,80,'2027-02-28',8,'2026-11-24 00:08:12'),
(18,9,35,'BATCH-TEH-002',35,60,'2026-12-02',8,'2026-11-24 00:08:12'),
(19,10,37,'BATCH-GULA-001',37,40,'2027-08-10',8,'2026-11-24 00:08:12'),
(20,10,38,'BATCH-GULA-002',38,45,'2027-08-10',8,'2026-11-24 00:08:12');
/*!40000 ALTER TABLE `inbound_details` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `inbound`
--

DROP TABLE IF EXISTS `inbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inbound` (
  `id_inbound` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reception_number` varchar(50) NOT NULL,
  `id_supplier` int(10) unsigned DEFAULT NULL,
  `reception_date` date DEFAULT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `delivery_note_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'DRAFT',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_inbound`),
  UNIQUE KEY `idx_reception_number` (`reception_number`),
  KEY `supplier_id` (`id_supplier`),
  KEY `created_by` (`created_by`),
  KEY `idx_reception_date` (`reception_date`),
  KEY `idx_inbound_id_supplier` (`id_supplier`),
  KEY `idx_inbound_created_by` (`created_by`),
  CONSTRAINT `fk_inbound_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `suppliers` (`id_supplier`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inbound_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inbound`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `inbound` WRITE;
/*!40000 ALTER TABLE `inbound` DISABLE KEYS */;
INSERT INTO `inbound` VALUES
(1,'INB-20261110-001',16,'2026-11-10','PO-20261101-001','DN-001','Penerimaan rutin susu','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(2,'INB-20261111-002',1,'2026-11-11','PO-20261101-002','DN-002','Penerimaan mi instan','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(3,'INB-20261112-003',7,'2026-11-12','PO-20261102-001','DN-003','Penerimaan kopi','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(4,'INB-20261113-004',2,'2026-11-13','PO-20261102-002','DN-004','Penerimaan biskuit wafer','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(5,'INB-20261114-005',3,'2026-11-14','PO-20261103-001','DN-005','Penerimaan saus','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(6,'INB-20261115-006',4,'2026-11-15','PO-20261104-001','DN-006','Penerimaan kecap','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(7,'INB-20261118-007',17,'2026-11-18','PO-20261105-001','DN-007','Penerimaan minuman dan air mineral','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(8,'INB-20261119-008',1,'2026-11-19','PO-20261102-003','DN-008','Penerimaan snack','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(9,'INB-20261120-009',2,'2026-11-20','PO-20261103-002','DN-009','Penerimaan teh','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50'),
(10,'INB-20261121-010',1,'2026-11-21','PO-20261105-002','DN-010','Penerimaan gula pasir','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:17:50');
/*!40000 ALTER TABLE `inbound` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `user_role` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_is_read` (`is_read`),
  KEY `notifications_type` (`type`),
  KEY `notifications_reference_type` (`reference_type`),
  KEY `notifications_priority` (`priority`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,'low_stock','Stok rendah: Indomilk Susu Coklat 189ml','Stok tersisa 5 karton, di bawah minimum 10 karton.','batch','2','high','GUDANG',0,'2026-11-24 00:08:12',NULL),
(2,'near_expiry','Mendekati kedaluwarsa: Indomilk Susu Coklat 189ml','Batch BATCH-SUSU-002 kedaluwarsa 2026-11-26.','batch','2','high','GUDANG',0,'2026-11-24 00:08:12',NULL),
(3,'expired','Barang kedaluwarsa: ABC Kecap Manis 135ml','Batch BATCH-KECAP-002 sudah kedaluwarsa 2026-11-20.','batch','20','critical','SUPERADMIN',0,'2026-11-24 00:08:12',NULL),
(4,'low_stock','Stok rendah: X-Hees Wafer 23g','Stok tersisa 8 karton, di bawah minimum 15 karton.','batch','12','high','GUDANG',0,'2026-11-24 00:08:12',NULL),
(5,'low_stock','Stok rendah: Filma Minyak Goreng 1L','Stok tersisa 6 karton, di bawah minimum 10 karton.','batch','27','high','GUDANG',0,'2026-11-24 00:08:12',NULL),
(6,'near_expiry','Mendekati kedaluwarsa: Filma Minyak Goreng 1L','Batch BATCH-MINYAK-003 kedaluwarsa 2026-12-05.','batch','27','high','GUDANG',0,'2026-11-24 00:08:12',NULL),
(7,'inbound_completed','Barang masuk selesai: INB-20261110-001','Penerimaan susu telah selesai dan stok diperbarui.','inbound','1','low','ADMIN',1,'2026-11-24 00:08:12',NULL),
(8,'outbound_completed','Barang keluar selesai: OUT-20261120-001','Pengiriman ke Toko Pusat telah selesai.','outbound','1','low','ADMIN',1,'2026-11-24 00:08:12',NULL),
(9,'pending_approval','Penyesuaian stok menunggu persetujuan','Koreksi stok kopi menunggu persetujuan supervisor.','adjustment','4','critical','ADMIN',0,'2026-11-24 00:08:12',NULL),
(10,'opname_selisih','Selisih stok opname ditemukan','Opname OPN-20261121-001 memiliki 3 selisih.','opname','1','low','GUDANG',0,'2026-11-24 00:08:12',NULL),
(11,'stock_adjustment','Penyesuaian stok disetujui','Koreksi hasil hitung stok wafer telah disetujui.','adjustment','1','high','GUDANG',1,'2026-11-24 00:08:12',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `outbound_details`
--

DROP TABLE IF EXISTS `outbound_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `outbound_details` (
  `id_outbound_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_outbound` int(10) unsigned DEFAULT NULL,
  `id_batch` int(10) unsigned DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_outbound_detail`),
  KEY `outbound_header_id` (`id_outbound`),
  KEY `batch_id` (`id_batch`),
  KEY `idx_outbound_detail_id_outbound` (`id_outbound`),
  KEY `idx_outbound_detail_id_batch` (`id_batch`),
  CONSTRAINT `fk_outbound_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `product_batches` (`id_batch`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_outbound_detail_header` FOREIGN KEY (`id_outbound`) REFERENCES `outbound` (`id_outbound`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound_details`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `outbound_details` WRITE;
/*!40000 ALTER TABLE `outbound_details` DISABLE KEYS */;
INSERT INTO `outbound_details` VALUES
(1,1,13,10,'2026-11-24 00:08:12'),
(2,1,22,8,'2026-11-24 00:08:12'),
(3,2,31,20,'2026-11-24 00:08:12'),
(4,2,34,6,'2026-11-24 00:08:12'),
(5,3,14,12,'2026-11-24 00:08:12'),
(6,3,23,5,'2026-11-24 00:08:12'),
(7,4,1,4,'2026-11-24 00:08:12'),
(8,4,3,6,'2026-11-24 00:08:12'),
(9,5,28,7,'2026-11-24 00:08:12'),
(10,5,35,4,'2026-11-24 00:08:12'),
(11,6,32,15,'2026-11-24 00:08:12'),
(12,6,29,6,'2026-11-24 00:08:12');
/*!40000 ALTER TABLE `outbound_details` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `outbound`
--

DROP TABLE IF EXISTS `outbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `outbound` (
  `id_outbound` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_number` varchar(50) NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'DRAFT',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_outbound`),
  UNIQUE KEY `idx_delivery_number` (`delivery_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_delivery_date` (`delivery_date`),
  KEY `idx_outbound_created_by` (`created_by`),
  CONSTRAINT `fk_outbound_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outbound`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `outbound` WRITE;
/*!40000 ALTER TABLE `outbound` DISABLE KEYS */;
INSERT INTO `outbound` VALUES
(1,'OUT-20261120-001','2026-11-20','Pengiriman ke Toko Pusat','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(2,'OUT-20261121-002','2026-11-21','Pengiriman ke Cabang Barat','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(3,'OUT-20261122-003','2026-11-22','Pengiriman ke Cabang Timur','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(4,'OUT-20261122-004','2026-11-22','Pengiriman ke Toka Swalayan','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(5,'OUT-20261123-005','2026-11-23','Pengiriman ke Minimarket Utara','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(6,'OUT-20261123-006','2026-11-23','Pengiriman ke Grosir Selatan','COMPLETED',1,'2026-11-24 00:08:12','2026-11-24 00:08:12');
/*!40000 ALTER TABLE `outbound` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_batches` (
  `id_batch` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_product` int(10) unsigned DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `id_rack` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  PRIMARY KEY (`id_batch`),
  UNIQUE KEY `idx_batch_product_number` (`id_product`,`batch_number`),
  KEY `idx_batch_expiry` (`expiry_date`),
  KEY `idx_batches_id_product` (`id_product`),
  KEY `idx_batches_id_rack` (`id_rack`),
  CONSTRAINT `fk_batches_product` FOREIGN KEY (`id_product`) REFERENCES `products` (`id_product`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_batches_rack` FOREIGN KEY (`id_rack`) REFERENCES `rack` (`id_rack`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES
(1,1,'BATCH-SUSU-001','2027-03-15',1,'2026-11-24 00:08:12','2026-11-24 00:08:12',48),
(2,2,'BATCH-SUSU-002','2026-11-26',1,'2026-11-24 00:08:12','2026-11-24 00:08:12',5),
(3,3,'BATCH-SUSU-003','2027-01-10',1,'2026-11-24 00:08:12','2026-11-24 00:08:12',32),
(4,4,'BATCH-KOPI-001','2027-02-20',2,'2026-11-24 00:08:12','2026-11-24 00:08:12',60),
(5,5,'BATCH-KOPI-002','2027-02-20',2,'2026-11-24 00:08:12','2026-11-24 00:08:12',44),
(6,6,'BATCH-KOPI-003','2027-04-12',2,'2026-11-24 00:08:12','2026-11-24 00:08:12',28),
(7,7,'BATCH-BISKUIT-001','2027-01-30',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',36),
(8,8,'BATCH-BISKUIT-002','2027-01-30',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',52),
(9,9,'BATCH-BISKUIT-003','2026-12-18',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',24),
(10,10,'BATCH-WAFER-001','2027-02-05',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',40),
(11,11,'BATCH-WAFER-002','2027-02-05',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',30),
(12,12,'BATCH-WAFER-003','2026-11-30',3,'2026-11-24 00:08:12','2026-11-24 00:08:12',8),
(13,13,'BATCH-MIE-001','2027-05-10',4,'2026-11-24 00:08:12','2026-11-24 00:08:12',120),
(14,14,'BATCH-MIE-002','2027-05-10',4,'2026-11-24 00:08:12','2026-11-24 00:08:12',95),
(15,15,'BATCH-MIE-003','2027-05-10',4,'2026-11-24 00:08:12','2026-11-24 00:08:12',80),
(16,16,'BATCH-SAOS-001','2027-03-01',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',42),
(17,17,'BATCH-SAOS-002','2027-03-01',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',38),
(18,18,'BATCH-SAOS-003','2027-06-15',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',24),
(19,19,'BATCH-KECAP-001','2028-01-20',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',55),
(20,20,'BATCH-KECAP-002','2026-11-20',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',18),
(21,21,'BATCH-KECAP-003','2028-02-10',5,'2026-11-24 00:08:12','2026-11-24 00:08:12',26),
(22,22,'BATCH-MINUMAN-001','2027-04-25',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',72),
(23,23,'BATCH-MINUMAN-002','2027-04-25',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',66),
(24,24,'BATCH-MINUMAN-003','2027-04-25',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',58),
(25,25,'BATCH-MINYAK-001','2027-07-30',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',34),
(26,26,'BATCH-MINYAK-002','2027-07-30',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',40),
(27,27,'BATCH-MINYAK-003','2026-12-05',6,'2026-11-24 00:08:12','2026-11-24 00:08:12',6),
(28,28,'BATCH-SNACK-001','2027-01-15',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',50),
(29,29,'BATCH-SNACK-002','2027-01-15',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',45),
(30,30,'BATCH-SNACK-003','2027-01-15',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',39),
(31,31,'BATCH-AIR-001','2028-05-20',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',150),
(32,32,'BATCH-AIR-002','2028-05-20',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',140),
(33,33,'BATCH-AIR-003','2028-05-20',7,'2026-11-24 00:08:12','2026-11-24 00:08:12',130),
(34,34,'BATCH-TEH-001','2027-02-28',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',64),
(35,35,'BATCH-TEH-002','2026-12-02',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',48),
(36,36,'BATCH-TEH-003','2027-03-18',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',36),
(37,37,'BATCH-GULA-001','2027-08-10',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',28),
(38,38,'BATCH-GULA-002','2027-08-10',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',30),
(39,39,'BATCH-GULA-003','2026-12-10',8,'2026-11-24 00:08:12','2026-11-24 00:08:12',7);
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id_product` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku_code` varchar(50) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `category` varchar(200) DEFAULT NULL,
  `id_category` int(10) unsigned DEFAULT NULL,
  `brand_name` varchar(200) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `id_supplier` int(10) unsigned DEFAULT NULL,
  `minimum_stock` int(11) DEFAULT 10,
  `status` varchar(20) NOT NULL DEFAULT 'AKTIF',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_product`),
  UNIQUE KEY `idx_sku_code` (`sku_code`),
  KEY `category_id` (`id_category`),
  KEY `supplier_id` (`id_supplier`),
  KEY `idx_products_id_supplier` (`id_supplier`),
  KEY `idx_products_id_category` (`id_category`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`id_category`) REFERENCES `categories` (`id_category`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_products_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `suppliers` (`id_supplier`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'SKU-SUSU-ULTRA-1L','Ultra Milk Full Cream 1L','Susu',1,'Ultra','Karton',16,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(2,'SKU-SUSU-INDOMILK-189','Indomilk Susu Coklat 189ml','Susu',1,'Indomilk','Karton',1,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(3,'SKU-SUSU-BEAR-189','Bear Brand Susu Steril 189ml','Susu',1,'Bear Brand','Karton',6,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(4,'SKU-KOPI-KAPAL-API','Kapal Api Special Mix 165g','Kopi',2,'Kapal Api','Karton',7,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(5,'SKU-KOPI-ABC-SUSU','ABC Kopi Susu 160g','Kopi',2,'ABC','Karton',3,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(6,'SKU-KOPI-NESCAFE-GOLD','Nescafe Gold 50g','Kopi',2,'Nescafe','Karton',6,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(7,'SKU-BISKUIT-ROMA','Roma Biskuit Kelapa 280g','Biskuit',3,'Roma','Karton',2,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(8,'SKU-BISKUIT-KHG-MARIE','Khong Guan Biskuit Marie 100g','Biskuit',3,'Khong Guan','Karton',14,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(9,'SKU-BISKUIT-MALKIST','Malkist Cream 65g','Biskuit',3,'Mayora','Karton',2,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(10,'SKU-WAFER-NABATI','Nabati Wafer Coklat 58g','Wafer',4,'Nabati','Karton',15,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(11,'SKU-WAFER-TANGGO','Tanggo Wafer Coklat 145g','Wafer',4,'Mayora','Karton',2,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(12,'SKU-WAFER-XHEES','X-Hees Wafer 23g','Wafer',4,'X-Hees','Karton',2,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(13,'SKU-MIE-INDOMIE-GORENG','Indomie Goreng Original 85g','Mie Instan',5,'Indofood','Karton',1,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(14,'SKU-MIE-INDOMIE-KUAH','Indomie Kuah Ayam Bawang 85g','Mie Instan',5,'Indofood','Karton',1,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(15,'SKU-MIE-SEDAAP-GORENG','Sedaap Mi Goreng 85g','Mie Instan',5,'Sedaap','Karton',12,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(16,'SKU-SAOS-ABC-TOMAT','ABC Saus Tomat 135ml','Saos',6,'ABC','Karton',3,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(17,'SKU-SAOS-ABC-SAMBAL','ABC Saus Sambal 135ml','Saos',6,'ABC','Karton',3,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(18,'SKU-SAOS-LKK-TIRAM','Lee Kum Kee Saus Tiram 230g','Saos',6,'Lee Kum Kee','Karton',10,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(19,'SKU-KECAP-BANGO-MANIS','Bango Kecap Manis 137ml','Kecap',7,'Bango','Karton',4,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(20,'SKU-KECAP-ABC-MANIS','ABC Kecap Manis 135ml','Kecap',7,'ABC','Karton',3,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(21,'SKU-KECAP-KIKKOMAN-ASIN','Kikkoman Kecap Asin 250ml','Kecap',7,'Kikkoman','Karton',11,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(22,'SKU-MINUMAN-COCA-COLA','Coca-Cola 330ml','Minuman',8,'Coca-Cola','Karton',17,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(23,'SKU-MINUMAN-SPRITE','Sprite 330ml','Minuman',8,'Sprite','Karton',17,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(24,'SKU-MINUMAN-FANTA','Fanta 330ml','Minuman',8,'Fanta','Karton',17,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(25,'SKU-MINYAK-BIMOLI-1L','Bimoli Minyak Goreng 1L','Minyak Goreng',9,'Bimoli','Karton',9,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(26,'SKU-MINYAK-SANIA-1L','Sania Minyak Goreng 1L','Minyak Goreng',9,'Sania','Karton',13,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(27,'SKU-MINYAK-FILMA-1L','Filma Minyak Goreng 1L','Minyak Goreng',9,'Filma','Karton',9,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(28,'SKU-SNACK-CHITATO','Chitato Sapi Panggang 68g','Snack',10,'Chitato','Karton',1,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(29,'SKU-SNACK-QTELA','Qtela Singkong Balado 68g','Snack',10,'Qtela','Karton',1,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(30,'SKU-SNACK-LAYS','Lays Original 68g','Snack',10,'Lays','Karton',1,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(31,'SKU-AIR-AQUA-600','Aqua Air Mineral 600ml','Air Mineral',11,'Aqua','Karton',5,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(32,'SKU-AIR-DANONE-600','Danone Air Mineral 600ml','Air Mineral',11,'Danone','Karton',5,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(33,'SKU-AIR-CLUB-600','Club Air Mineral 600ml','Air Mineral',11,'Club','Karton',5,20,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(34,'SKU-TEH-PUCUK-HARUM','Teh Pucuk Harum 350ml','Teh',12,'Teh Pucuk Harum','Karton',2,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(35,'SKU-TEH-BOTOL-SOSRO','Teh Botol Sosro 350ml','Teh',12,'Sosro','Karton',8,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(36,'SKU-TEH-TARIK-ABC','Teh Tarik ABC 250ml','Teh',12,'ABC','Karton',3,15,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(37,'SKU-GULA-GULAKU','Gulaku Gula Pasir 1kg','Gula Pasir',13,'Gulaku','Karton',1,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(38,'SKU-GULA-PREMIUM','Gulaku Gula Pasir Premium 1kg','Gula Pasir',13,'Gulaku','Karton',1,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50'),
(39,'SKU-GULA-ISIS','Isis Gula Pasir 1kg','Gula Pasir',13,'Isis','Karton',18,10,'AKTIF','2026-11-24 00:01:08','2026-11-24 00:17:50');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `rack`
--

DROP TABLE IF EXISTS `rack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rack` (
  `id_rack` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rack_code` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'AKTIF',
  PRIMARY KEY (`id_rack`),
  UNIQUE KEY `idx_rack_code` (`rack_code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rack`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `rack` WRITE;
/*!40000 ALTER TABLE `rack` DISABLE KEYS */;
INSERT INTO `rack` VALUES
(1,'RACK-A01','Rak A-01','AKTIF'),
(2,'RACK-A02','Rak A-02','AKTIF'),
(3,'RACK-A03','Rak A-03','AKTIF'),
(4,'RACK-A04','Rak A-04','AKTIF'),
(5,'RACK-B01','Rak B-01','AKTIF'),
(6,'RACK-B02','Rak B-02','AKTIF'),
(7,'RACK-B03','Rak B-03','AKTIF'),
(8,'RACK-B04','Rak B-04','AKTIF');
/*!40000 ALTER TABLE `rack` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id_adjustment` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_batch` int(10) unsigned DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL COMMENT 'IN / OUT',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(200) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING / APPROVED / REJECTED',
  `approved_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_adjustment`),
  KEY `batch_id` (`id_batch`),
  KEY `created_by` (`created_by`),
  KEY `stock_adjustments_ibfk_4` (`approved_by`),
  KEY `idx_adjustment_id_batch` (`id_batch`),
  KEY `idx_adjustment_approved_by` (`approved_by`),
  KEY `idx_adjustment_created_by` (`created_by`),
  CONSTRAINT `fk_adjustment_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_adjustment_batch` FOREIGN KEY (`id_batch`) REFERENCES `product_batches` (`id_batch`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_adjustment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
INSERT INTO `stock_adjustments` VALUES
(1,11,'INCREASE',5,'Koreksi hasil hitung stok','APPROVED',1,1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(2,24,'DECREASE',8,'Barang rusak saat penerimaan','APPROVED',1,1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(3,39,'DECREASE',3,'Selisih opname gula pasir','APPROVED',1,1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(4,5,'INCREASE',10,'Koreksi stok kopi','PENDING',NULL,1,'2026-11-24 00:08:12',NULL),
(5,26,'DECREASE',4,'Barang tidak layak jual','PENDING',NULL,1,'2026-11-24 00:08:12',NULL);
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `stock_opname_details`
--

DROP TABLE IF EXISTS `stock_opname_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_opname_details` (
  `id_opname_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_opname` int(10) unsigned DEFAULT NULL,
  `id_batch` int(10) unsigned DEFAULT NULL,
  `system_quantity` int(11) DEFAULT 0,
  `physical_quantity` int(11) DEFAULT 0,
  `difference` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_opname_detail`),
  KEY `session_id` (`id_opname`),
  KEY `batch_id` (`id_batch`),
  KEY `idx_opname_detail_id_opname` (`id_opname`),
  KEY `idx_opname_detail_id_batch` (`id_batch`),
  CONSTRAINT `fk_opname_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `product_batches` (`id_batch`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_opname_detail_session` FOREIGN KEY (`id_opname`) REFERENCES `stock_opname_sessions` (`id_opname`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opname_details`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stock_opname_details` WRITE;
/*!40000 ALTER TABLE `stock_opname_details` DISABLE KEYS */;
INSERT INTO `stock_opname_details` VALUES
(1,1,1,48,47,-1,'2026-11-24 00:08:12'),
(2,1,13,120,122,2,'2026-11-24 00:08:12'),
(3,1,22,72,70,-2,'2026-11-24 00:08:12'),
(4,2,31,150,148,-2,'2026-11-24 00:08:12'),
(5,2,34,64,65,1,'2026-11-24 00:08:12'),
(6,2,37,28,27,-1,'2026-11-24 00:08:12');
/*!40000 ALTER TABLE `stock_opname_details` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `stock_opname_sessions`
--

DROP TABLE IF EXISTS `stock_opname_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_opname_sessions` (
  `id_opname` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `opname_number` varchar(50) NOT NULL,
  `opname_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'DRAFT',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_opname`),
  UNIQUE KEY `idx_opname_number` (`opname_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_opname_created_by` (`created_by`),
  CONSTRAINT `fk_opname_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_opname_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stock_opname_sessions` WRITE;
/*!40000 ALTER TABLE `stock_opname_sessions` DISABLE KEYS */;
INSERT INTO `stock_opname_sessions` VALUES
(1,'OPN-20261121-001','2026-11-21','COMPLETED','Opname rutin rak A dan B',1,'2026-11-24 00:08:12','2026-11-24 00:08:12'),
(2,'OPN-20261122-002','2026-11-22','COMPLETED','Opname minuman dan gula',1,'2026-11-24 00:08:12','2026-11-24 00:08:12');
/*!40000 ALTER TABLE `stock_opname_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id_supplier` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'AKTIF',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_supplier`),
  UNIQUE KEY `idx_supplier_code` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(1,'SUP001','PT Indofood Sukses Makmur','021-579-9888','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:08:12','2026-11-24 00:08:12'),
(2,'SUP002','PT Mayora Indah Tbk','021-890-2288','Tangerang, Banten','AKTIF','2026-11-24 00:08:12','2026-11-24 00:08:12'),
(3,'SUP003','PT ABC Indonesia','021-381-0900','Jakarta Pusat, DKI Jakarta','AKTIF','2026-11-24 00:08:12','2026-11-24 00:08:12'),
(4,'SUP004','PT Bango Kecap Manis','022-420-7788','Bandung, Jawa Barat','AKTIF','2026-11-24 00:08:12','2026-11-24 00:08:12'),
(5,'SUP005','PT Danone Aqua','021-789-1234','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:08:12','2026-11-24 00:08:12'),
(6,'SUP006','PT Nestle Indonesia','021-789-8899','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(7,'SUP007','PT Kapal Api Group','022-423-4343','Bandung, Jawa Barat','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(8,'SUP008','PT Sinar Sosro','021-887-1122','Bekasi, Jawa Barat','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(9,'SUP009','PT Unilever Indonesia','021-525-5500','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(10,'SUP010','PT Lee Kum Kee Indonesia','021-390-8899','Jakarta Pusat, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(11,'SUP011','PT Kikkoman Indonesia','021-579-0909','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(12,'SUP012','PT Wings Surya','031-829-1500','Surabaya, Jawa Timur','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(13,'SUP013','PT Smart Tani','021-780-1122','Jakarta Pusat, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(14,'SUP014','PT Khong Guan Biscuit Factory','021-345-6789','Jakarta Pusat, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(15,'SUP015','PT Nabati Group','022-456-7890','Bandung, Jawa Barat','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(16,'SUP016','PT Ultra Peternakan','021-555-0101','Jakarta Timur, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(17,'SUP017','PT Coca-Cola Indonesia','021-521-1000','Jakarta Selatan, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50'),
(18,'SUP018','PT Isis','021-489-2200','Jakarta Timur, DKI Jakarta','AKTIF','2026-11-24 00:17:50','2026-11-24 00:17:50');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'ADMIN' COMMENT 'SUPERADMIN, ADMIN, GUDANG',
  `status` varchar(20) NOT NULL DEFAULT 'AKTIF' COMMENT 'AKTIF, NONAKTIF',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `idx_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Super Administrator','superadmin','$2y$12$SxnLz7SgLMDVn2fYYgEQX.LZpMaIYW8P.iKREnIYglsHr.6K.xkn2','SUPERADMIN','AKTIF','2026-07-29 13:38:11','2026-11-17 09:40:40'),
(2,'Administrator','admin','$2y$12$SxnLz7SgLMDVn2fYYgEQX.LZpMaIYW8P.iKREnIYglsHr.6K.xkn2','ADMIN','AKTIF','2026-07-29 13:38:11','2026-11-17 09:40:40'),
(3,'User Gudang','gudang','$2y$12$SxnLz7SgLMDVn2fYYgEQX.LZpMaIYW8P.iKREnIYglsHr.6K.xkn2','GUDANG','AKTIF','2026-07-29 19:14:00','2026-11-17 09:40:40');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-11-24  0:18:10
