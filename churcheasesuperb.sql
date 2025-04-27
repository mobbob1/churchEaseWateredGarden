/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.5.5-10.4.32-MariaDB : Database - outpouring_oasis
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`outpouring_oasis` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `outpouring_oasis`;

/*Table structure for table `asset_transactions` */

DROP TABLE IF EXISTS `asset_transactions`;

CREATE TABLE `asset_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','depreciation','revaluation','disposal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `fk_asset_transactions` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `asset_transactions` */

/*Table structure for table `assets` */

DROP TABLE IF EXISTS `assets`;

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('current','fixed') NOT NULL,
  `category` varchar(50) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `current_value` decimal(10,2) NOT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','disposed','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `assets` */

/*Table structure for table `attendance` */

DROP TABLE IF EXISTS `attendance`;

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `checkin_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `attendance` */

/*Table structure for table `audit_logs` */

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` text DEFAULT NULL,
  `description` text NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `audit_logs` */

insert  into `audit_logs`(`id`,`user_id`,`action`,`module`,`record_id`,`description`,`old_values`,`new_values`,`ip_address`,`user_agent`,`status`,`error_message`,`created_at`) values (1,NULL,'login_attempt','authentication','31','Login attempt for username: financeofficer',NULL,'{\"username\":\"financeofficer\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-03-12 10:49:06\",\"user_exists\":\"yes\",\"user_status\":\"active\",\"user_role\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','',NULL,'2025-03-12 09:49:06'),(2,NULL,'password_verify','authentication','31','Password verification for user: financeofficer',NULL,'{\"password_verified\":\"yes\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 09:49:06'),(3,NULL,'login_success','authentication','31','Successful login for user: financeofficer',NULL,'{\"roles\":[\"comms\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 09:49:06'),(4,NULL,'login_attempt','authentication','31','Login attempt for username: financeofficer',NULL,'{\"username\":\"financeofficer\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-03-12 11:35:55\",\"user_exists\":\"yes\",\"user_status\":\"active\",\"user_role\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','',NULL,'2025-03-12 10:35:55'),(5,NULL,'password_verify','authentication','31','Password verification for user: financeofficer',NULL,'{\"password_verified\":\"yes\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 10:35:55'),(6,NULL,'login_success','authentication','31','Successful login for user: financeofficer',NULL,'{\"roles\":[\"comms\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 10:35:55'),(7,NULL,'login_attempt','authentication','31','Login attempt for username: financeofficer',NULL,'{\"username\":\"financeofficer\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-03-12 11:37:30\",\"user_exists\":\"yes\",\"user_status\":\"active\",\"user_role\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','',NULL,'2025-03-12 10:37:30'),(8,NULL,'password_verify','authentication','31','Password verification for user: financeofficer',NULL,'{\"password_verified\":\"yes\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 10:37:30'),(9,NULL,'login_success','authentication','31','Successful login for user: financeofficer',NULL,'{\"roles\":[\"comms\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 10:37:30'),(10,NULL,'page_access','chat',NULL,'Accessed chat interface','\"31\"',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 11:32:40'),(11,NULL,'page_access','chat',NULL,'Accessed chat interface','\"31\"',NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-12 11:32:54'),(12,NULL,'login_attempt','authentication','2','Login attempt for username: admin',NULL,'{\"username\":\"admin\",\"ip_address\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/133.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-03-13 10:06:23\",\"user_exists\":\"yes\",\"user_status\":\"active\",\"user_role\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','',NULL,'2025-03-13 09:06:23'),(13,NULL,'password_verify','authentication','2','Password verification for user: admin',NULL,'{\"password_verified\":\"yes\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-13 09:06:23'),(14,NULL,'login_success','authentication','2','Successful login for user: admin',NULL,'{\"roles\":[\"seer\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','success',NULL,'2025-03-13 09:06:23');

/*Table structure for table `bible_classes` */

DROP TABLE IF EXISTS `bible_classes`;

CREATE TABLE `bible_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) NOT NULL,
  `leader_id` int(11) NOT NULL,
  `assistant_leader_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_name` (`class_name`),
  KEY `leader_id` (`leader_id`),
  KEY `assistant_leader_id` (`assistant_leader_id`),
  CONSTRAINT `bible_classes_assistant_fk` FOREIGN KEY (`assistant_leader_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `bible_classes_leader_fk` FOREIGN KEY (`leader_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `bible_classes` */

/*Table structure for table `budgets` */

DROP TABLE IF EXISTS `budgets`;

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `budget_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `budgets` */

/*Table structure for table `chat_conversations` */

DROP TABLE IF EXISTS `chat_conversations`;

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `chat_conversations` */

/*Table structure for table `chat_messages` */

DROP TABLE IF EXISTS `chat_messages`;

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_messages_conversation` (`conversation_id`),
  KEY `idx_chat_messages_sender` (`sender_id`),
  KEY `idx_chat_messages_created_at` (`created_at`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`),
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `chat_messages` */

/*Table structure for table `chat_participants` */

DROP TABLE IF EXISTS `chat_participants`;

CREATE TABLE `chat_participants` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`conversation_id`,`user_id`),
  KEY `idx_chat_participants_user` (`user_id`),
  CONSTRAINT `chat_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`),
  CONSTRAINT `chat_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `chat_participants` */

/*Table structure for table `church_statistics` */

DROP TABLE IF EXISTS `church_statistics`;

CREATE TABLE `church_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `month` varchar(7) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `church_statistics` */

/*Table structure for table `church_statistics_details` */

DROP TABLE IF EXISTS `church_statistics_details`;

CREATE TABLE `church_statistics_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statistics_id` int(11) DEFAULT NULL,
  `operation` varchar(100) NOT NULL,
  `sunday1` int(11) DEFAULT 0,
  `sunday2` int(11) DEFAULT 0,
  `sunday3` int(11) DEFAULT 0,
  `sunday4` int(11) DEFAULT 0,
  `sunday5` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `statistics_id` (`statistics_id`),
  CONSTRAINT `church_statistics_details_ibfk_1` FOREIGN KEY (`statistics_id`) REFERENCES `church_statistics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `church_statistics_details` */

/*Table structure for table `counselling_requests` */

DROP TABLE IF EXISTS `counselling_requests`;

CREATE TABLE `counselling_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `counselling_type` enum('personal','marriage','family','spiritual','career') NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `counselling_requests_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `counselling_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `counselling_requests` */

/*Table structure for table `donations` */

DROP TABLE IF EXISTS `donations`;

CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `donation_date` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `project_category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `donation_date` (`donation_date`),
  KEY `fk_donations_project_category` (`project_category_id`),
  CONSTRAINT `donations_member_fk` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_donations_project_category` FOREIGN KEY (`project_category_id`) REFERENCES `project_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `donations` */

/*Table structure for table `equity` */

DROP TABLE IF EXISTS `equity`;

CREATE TABLE `equity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('opening_balance','retained_earnings','adjustments') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `equity` */

/*Table structure for table `events` */

DROP TABLE IF EXISTS `events`;

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(100) NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `events` */

/*Table structure for table `expense_categories` */

DROP TABLE IF EXISTS `expense_categories`;

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `expense_categories` */

/*Table structure for table `expense_types` */

DROP TABLE IF EXISTS `expense_types`;

CREATE TABLE `expense_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `expense_types` */

/*Table structure for table `expenses` */

DROP TABLE IF EXISTS `expenses`;

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `expense_date` datetime DEFAULT current_timestamp(),
  `expense_type` varchar(50) NOT NULL DEFAULT 'general',
  `category` varchar(50) NOT NULL DEFAULT 'others',
  `receipt_number` varchar(50) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `paid_to` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`),
  KEY `expense_type` (`expense_type`),
  KEY `category` (`category`),
  CONSTRAINT `fk_expense_category` FOREIGN KEY (`category`) REFERENCES `expense_categories` (`name`) ON UPDATE CASCADE,
  CONSTRAINT `fk_expense_type` FOREIGN KEY (`expense_type`) REFERENCES `expense_types` (`name`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `expenses` */

/*Table structure for table `forum_categories` */

DROP TABLE IF EXISTS `forum_categories`;

CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `forum_categories` */

/*Table structure for table `forum_replies` */

DROP TABLE IF EXISTS `forum_replies`;

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`),
  CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `forum_replies` */

/*Table structure for table `forum_topics` */

DROP TABLE IF EXISTS `forum_topics`;

CREATE TABLE `forum_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('active','closed','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`),
  CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `forum_topics` */

/*Table structure for table `institutions` */

DROP TABLE IF EXISTS `institutions`;

CREATE TABLE `institutions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `institution_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `institutions` */

insert  into `institutions`(`id`,`institution_name`,`created_at`) values (1,'KNUST','2025-03-12 09:26:48'),(2,'University Of Ghana','2025-03-12 09:26:50'),(3,'Methodist University','2025-03-12 09:26:53'),(4,'Accra Technical University ','2025-03-12 09:26:55'),(5,'Koforidua Technical University','2025-03-12 09:26:58');

/*Table structure for table `liabilities` */

DROP TABLE IF EXISTS `liabilities`;

CREATE TABLE `liabilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('current','long_term') NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `due_date` date DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','paid','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `liabilities` */

/*Table structure for table `liability_payments` */

DROP TABLE IF EXISTS `liability_payments`;

CREATE TABLE `liability_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `liability_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `liability_id` (`liability_id`),
  CONSTRAINT `fk_liability_payments` FOREIGN KEY (`liability_id`) REFERENCES `liabilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `liability_payments` */

/*Table structure for table `member_feedback` */

DROP TABLE IF EXISTS `member_feedback`;

CREATE TABLE `member_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `type` enum('feedback','counseling') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `member_feedback_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `member_feedback_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `member_feedback` */

/*Table structure for table `member_groups` */

DROP TABLE IF EXISTS `member_groups`;

CREATE TABLE `member_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `member_groups` */

/*Table structure for table `member_relationships` */

DROP TABLE IF EXISTS `member_relationships`;

CREATE TABLE `member_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `related_member_id` int(11) NOT NULL,
  `relationship_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `related_member_id` (`related_member_id`),
  CONSTRAINT `member_relationships_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_relationships_ibfk_2` FOREIGN KEY (`related_member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `member_relationships` */

/*Table structure for table `member_types` */

DROP TABLE IF EXISTS `member_types`;

CREATE TABLE `member_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `member_types` */

insert  into `member_types`(`id`,`type_name`,`description`,`created_at`,`updated_at`) values (5,'Youth','','2025-02-23 15:01:13','2025-02-23 15:01:13'),(6,'Child','','2025-02-23 15:08:02','2025-02-23 15:08:02'),(7,'Adult','','2025-02-23 15:08:13','2025-02-23 15:08:13'),(8,'Visitor','','2025-02-24 09:35:04','2025-02-24 09:35:04'),(9,'Student',NULL,'2025-03-11 21:44:22','2025-03-11 21:44:22');

/*Table structure for table `members` */

DROP TABLE IF EXISTS `members`;

CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `date_joined` datetime DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `gps_number` varchar(50) DEFAULT NULL,
  `member_group` varchar(50) DEFAULT NULL,
  `contact_number_1` varchar(15) DEFAULT NULL,
  `contact_number_2` varchar(15) DEFAULT NULL,
  `next_of_kin_name` varchar(100) DEFAULT NULL,
  `next_of_kin_contact_number` varchar(15) DEFAULT NULL,
  `gender` varchar(8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `member_type_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `bible_class_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `mode_of_registration` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `institution_id` int(11) DEFAULT NULL,
  `hostel_residence` varchar(255) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_number1` (`contact_number_1`),
  UNIQUE KEY `email_unique` (`email`),
  KEY `idx_member_group` (`member_group`),
  KEY `fk_members_created_by` (`created_by`),
  KEY `member_type_id` (`member_type_id`),
  KEY `parent_id` (`parent_id`),
  KEY `members_organization_fk` (`organization_id`),
  KEY `members_bible_class_fk` (`bible_class_id`),
  KEY `institution_id` (`institution_id`),
  CONSTRAINT `fk_members_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `members_bible_class_fk` FOREIGN KEY (`bible_class_id`) REFERENCES `bible_classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`member_type_id`) REFERENCES `member_types` (`id`),
  CONSTRAINT `members_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `members` (`id`),
  CONSTRAINT `members_ibfk_3` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
  CONSTRAINT `members_organization_fk` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `members` */

/*Table structure for table `messages` */

DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_time` datetime DEFAULT current_timestamp(),
  `message_type` enum('single','bulk','group') NOT NULL DEFAULT 'single',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `recipient_group` varchar(50) DEFAULT NULL,
  `message_title` varchar(100) DEFAULT NULL,
  `delivery_report` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `scheduled_time` datetime DEFAULT NULL,
  `recipient_count` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `message_type` (`message_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `messages` */

/*Table structure for table `notifications` */

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `notification_text` text NOT NULL,
  `notification_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `notifications` */

/*Table structure for table `offerings` */

DROP TABLE IF EXISTS `offerings`;

CREATE TABLE `offerings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `offering_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `offering_type` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `offerings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `offerings` */

/*Table structure for table `organization_statistics` */

DROP TABLE IF EXISTS `organization_statistics`;

CREATE TABLE `organization_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `month` varchar(7) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_org_month` (`organization_id`,`month`),
  CONSTRAINT `organization_statistics_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `organization_statistics` */

/*Table structure for table `organization_statistics_details` */

DROP TABLE IF EXISTS `organization_statistics_details`;

CREATE TABLE `organization_statistics_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statistics_id` int(11) NOT NULL,
  `operation` varchar(50) NOT NULL,
  `sunday1` int(11) DEFAULT 0,
  `sunday2` int(11) DEFAULT 0,
  `sunday3` int(11) DEFAULT 0,
  `sunday4` int(11) DEFAULT 0,
  `sunday5` int(11) DEFAULT 0,
  `total` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_operation` (`statistics_id`,`operation`),
  CONSTRAINT `organization_statistics_details_ibfk_1` FOREIGN KEY (`statistics_id`) REFERENCES `organization_statistics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `organization_statistics_details` */

/*Table structure for table `organizations` */

DROP TABLE IF EXISTS `organizations`;

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_name` varchar(100) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `leader_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organization_name` (`organization_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `organizations` */

/*Table structure for table `other_income` */

DROP TABLE IF EXISTS `other_income`;

CREATE TABLE `other_income` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `income_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `income_date` (`income_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `other_income` */

/*Table structure for table `project_categories` */

DROP TABLE IF EXISTS `project_categories`;

CREATE TABLE `project_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `project_categories` */

insert  into `project_categories`(`id`,`name`,`description`,`status`,`created_at`) values (1,'Building Fund','Donations for church building and renovation projects','active','2025-02-28 00:11:14'),(2,'Mission Fund','Support for missionary work and evangelism','active','2025-02-28 00:11:14'),(3,'Youth Ministry','Support for youth programs and activities','active','2025-02-28 00:11:14'),(4,'Community Outreach','Donations for community service and outreach programs','active','2025-02-28 00:11:14'),(5,'Education Fund','Support for educational initiatives and scholarships','active','2025-02-28 00:11:14'),(6,'Equipment Fund','Donations for church equipment and facilities','active','2025-02-28 00:11:14');

/*Table structure for table `roles` */

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `roles` */

insert  into `roles`(`id`,`role_key`,`name`,`description`,`created_at`,`updated_at`) values (1,'seer','Seer','Super user with full system access','2025-03-06 19:52:21','2025-03-06 19:52:21'),(2,'pastorate','Pastorate','Pastoral staff','2025-03-06 19:52:21','2025-03-06 19:52:21'),(3,'executive_admin_1','Executive Admin 1','Executive administrator with membership management focus','2025-03-06 19:52:21','2025-03-06 19:52:21'),(4,'executive_admin_2','Executive Admin 2','Executive administrator with counseling focus','2025-03-06 19:52:21','2025-03-06 19:52:21'),(5,'finance','Finance','Financial management','2025-03-06 19:52:21','2025-03-06 19:52:21'),(6,'hr','HR','Human Resources and Event Management','2025-03-06 19:52:21','2025-03-06 19:52:21'),(7,'comms','Communications','Communication management','2025-03-06 19:52:21','2025-03-06 19:52:21'),(8,'audit','Audit','System auditing','2025-03-06 19:52:21','2025-03-06 19:52:21'),(9,'reports','Reports','Report generation','2025-03-06 19:52:21','2025-03-06 19:52:21'),(10,'admin_assistant','Administrative Assistant','Administrative support staff','2025-03-06 19:52:21','2025-03-06 19:52:21'),(11,'general','General User','Basic member access','2025-03-06 19:52:21','2025-03-06 19:52:21');

/*Table structure for table `system_settings` */

DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `system_settings` */

insert  into `system_settings`(`id`,`setting_key`,`setting_value`,`setting_description`,`created_at`,`updated_at`) values (46,'church_name','','Church Name','2025-02-23 15:13:18','2025-02-23 15:13:18'),(47,'church_address','','Church Address','2025-02-23 15:13:18','2025-02-23 15:13:18'),(48,'church_phone','','Church Contact Number','2025-02-23 15:13:18','2025-02-23 15:13:18'),(49,'church_email','','Church Email','2025-02-23 15:13:18','2025-02-23 15:13:18'),(50,'sms_sender_id','ID used for sending SMS messages','SMS Sender ID','2025-02-23 15:13:18','2025-02-23 15:13:18'),(51,'sms_api_key','API key for SMS service','SMS API Key','2025-02-23 15:13:18','2025-02-23 15:13:18'),(52,'currency_symbol','₵','Currency Symbol','2025-02-23 15:13:18','2025-02-23 15:13:18'),(53,'date_format','Y-m-d','Date Format','2025-02-23 15:13:18','2025-02-23 15:13:18'),(54,'time_format','H:i:s','Time Format','2025-02-23 15:13:18','2025-02-23 15:13:18'),(55,'session_timeout','30','Session Timeout (minutes)','2025-02-23 15:13:19','2025-02-23 15:13:19'),(56,'items_per_page','25','Items Per Page','2025-02-23 15:13:19','2025-02-23 15:13:19'),(57,'backup_retention_days','30','Backup Retention Days','2025-02-23 15:13:19','2025-02-23 15:13:19'),(58,'system_email','For sending system notifications','System Email','2025-02-23 15:13:19','2025-02-23 15:13:19'),(59,'maintenance_mode','0','Maintenance Mode','2025-02-23 15:13:19','2025-02-23 15:13:19'),(60,'debug_mode','0','Debug Mode','2025-02-23 15:13:19','2025-02-23 15:13:19');

/*Table structure for table `tithes` */

DROP TABLE IF EXISTS `tithes`;

CREATE TABLE `tithes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'GHS',
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `tithe_date` date NOT NULL,
  `tithe_month` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `tithe_date` (`tithe_date`),
  KEY `tithe_month` (`tithe_month`),
  CONSTRAINT `tithes_member_fk` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tithes` */

/*Table structure for table `user_roles` */

DROP TABLE IF EXISTS `user_roles`;

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role_key`),
  KEY `fk_user_roles_role` (`role_key`),
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_key`) REFERENCES `roles` (`role_key`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `user_roles` */

insert  into `user_roles`(`id`,`user_id`,`role_key`,`created_at`,`created_by`) values (1,2,'seer','2025-03-07 12:36:57',NULL),(5,31,'comms','2025-03-07 18:09:43',2);

/*Table structure for table `user_sessions` */

DROP TABLE IF EXISTS `user_sessions`;

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `last_activity` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `user_sessions` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password_changed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to check if first-time password has been changed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `created_by` (`created_by`),
  KEY `idx_member_id` (`member_id`),
  CONSTRAINT `fk_user_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `users_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`member_id`,`username`,`email`,`password`,`full_name`,`status`,`phone`,`department`,`position`,`last_login`,`login_attempts`,`password_reset_token`,`password_reset_expires`,`profile_image`,`permissions`,`created_by`,`created_at`,`updated_at`,`password_changed`) values (2,NULL,'admin','admin@example.com','$2y$10$qm3/o0CTeXyNiehj3xwQueudZZ.4m61rM3KD1sG8iHlFdAXM.ULma','System Administrator','active',NULL,'IT','System Administrator','2025-03-13 13:42:53',0,NULL,NULL,NULL,NULL,NULL,'2025-01-10 14:39:12','2025-03-13 13:42:53',1),(30,NULL,'myfinuser','myfinusessr@yahoo.com','$2y$10$ZVWtnLT3EKVvYMxgrAaxNeyaXYeVigVzPPhufJFP.sDKcNgfCHCXC','Felicia Oppong','active','0244441122','Finance','Finance Officer','2025-03-09 09:58:58',0,NULL,NULL,NULL,NULL,2,'2025-03-07 16:49:40','2025-03-09 09:58:58',1),(31,NULL,'financeofficer','financeofficer@yahoo.com','$2y$10$YozN2vJ6B78cvjvXUOswQe6ep7c7rreURVI3xf8A6vYVtAIzqP9Ra','Obed Sarpong','active','0544121212','Finance Office','FC','2025-03-12 11:32:54',0,NULL,NULL,NULL,NULL,2,'2025-03-07 18:09:43','2025-03-12 11:32:54',1);

/*Table structure for table `voice_call_recipients` */

DROP TABLE IF EXISTS `voice_call_recipients`;

CREATE TABLE `voice_call_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voice_call_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voice_call_id` (`voice_call_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `voice_call_recipients_ibfk_1` FOREIGN KEY (`voice_call_id`) REFERENCES `voice_calls` (`id`),
  CONSTRAINT `voice_call_recipients_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `voice_call_recipients` */

/*Table structure for table `voice_calls` */

DROP TABLE IF EXISTS `voice_calls`;

CREATE TABLE `voice_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `call_title` varchar(255) NOT NULL,
  `voice_file` varchar(255) NOT NULL,
  `recipient_filter` text DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `api_response` text DEFAULT NULL,
  `schedule_time` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `voice_calls_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `voice_calls` */

/*Table structure for table `whatsapp_messages` */

DROP TABLE IF EXISTS `whatsapp_messages`;

CREATE TABLE `whatsapp_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_number` varchar(15) NOT NULL,
  `message_id` varchar(100) NOT NULL,
  `message_content` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'SENT',
  `response_data` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `whatsapp_messages` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
