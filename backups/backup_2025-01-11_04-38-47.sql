-- OutpouringCRM Database Backup
-- Generated: 2025-01-11 04:38:47

SET FOREIGN_KEY_CHECKS=0;



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




CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` VALUES ('1',NULL,'login','authentication','1','Failed login attempt for username: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:26:51"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','failed','Invalid credentials','2025-01-10 04:26:51');
INSERT INTO `audit_logs` VALUES ('2',NULL,'login','authentication','1','Failed login attempt for username: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:32:20"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','failed','Invalid credentials','2025-01-10 04:32:20');
INSERT INTO `audit_logs` VALUES ('3',NULL,'login','authentication','1','Failed login attempt for username: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:32:31"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','failed','Invalid credentials','2025-01-10 04:32:31');
INSERT INTO `audit_logs` VALUES ('4',NULL,'login','authentication','1','Failed login attempt for username: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:34:22"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','failed','Invalid credentials','2025-01-10 04:34:22');
INSERT INTO `audit_logs` VALUES ('5',NULL,'login','authentication',NULL,'System error during login attempt',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:40:45"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','failed','SQLSTATE[42S02]: Base table or view not found: 1146 Table ''outpouringcrm_db.user_sessions'' doesn''t exist','2025-01-10 04:40:45');
INSERT INTO `audit_logs` VALUES ('6',NULL,'login','authentication','2','Successful login for user: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36","timestamp":"2025-01-10 05:43:55"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36','success',NULL,'2025-01-10 04:43:55');
INSERT INTO `audit_logs` VALUES ('7',NULL,'login','authentication','2','Successful login for user: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36 Edg\/131.0.0.0","timestamp":"2025-01-10 05:55:40"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-10 04:55:40');
INSERT INTO `audit_logs` VALUES ('8',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Testing mss","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 05:44:34');
INSERT INTO `audit_logs` VALUES ('9',NULL,'bulk_message','communication','3','Bulk SMS failed',NULL,'{"recipient_count":2,"http_code":401,"api_response":"{\"error\":\"invalid api key. please make sure your api key is valid and enabled\"}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','failed',NULL,'2025-01-10 05:44:35');
INSERT INTO `audit_logs` VALUES ('10',NULL,'login','authentication','2','Successful login for user: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36 Edg\/131.0.0.0","timestamp":"2025-01-10 09:39:40"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-10 08:39:40');
INSERT INTO `audit_logs` VALUES ('11',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Testing mss","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 08:39:58');
INSERT INTO `audit_logs` VALUES ('12',NULL,'bulk_message','communication',NULL,'Error in bulk SMS process',NULL,'{"error":"SQLSTATE[42S22]: Column not found: 1054 Unknown column ''recipient_count'' in ''field list''"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','failed',NULL,'2025-01-10 08:39:59');
INSERT INTO `audit_logs` VALUES ('13',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Testing mss","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 08:41:08');
INSERT INTO `audit_logs` VALUES ('14',NULL,'bulk_message','communication','4','Bulk SMS failed',NULL,'{"recipient_count":2,"http_code":401,"api_response":"{\"error\":\"invalid api key. please make sure your api key is valid and enabled\"}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','failed',NULL,'2025-01-10 08:41:09');
INSERT INTO `audit_logs` VALUES ('15',NULL,'login','authentication','2','Successful login for user: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36 Edg\/131.0.0.0","timestamp":"2025-01-10 19:00:55"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-10 18:00:55');
INSERT INTO `audit_logs` VALUES ('16',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Test","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 18:02:20');
INSERT INTO `audit_logs` VALUES ('17',NULL,'bulk_message','communication','5','Bulk SMS failed',NULL,'{"recipient_count":2,"http_code":401,"api_response":"{\"error\":\"invalid api key. please make sure your api key is valid and enabled\"}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','failed',NULL,'2025-01-10 18:02:21');
INSERT INTO `audit_logs` VALUES ('18',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Test ME","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 18:09:09');
INSERT INTO `audit_logs` VALUES ('19',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Test","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 18:09:52');
INSERT INTO `audit_logs` VALUES ('20',NULL,'bulk_message','communication','6','Bulk SMS failed',NULL,'{"recipient_count":2,"http_code":401,"api_response":"{\"error\":\"invalid api key. please make sure your api key is valid and enabled\"}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','failed',NULL,'2025-01-10 18:09:55');
INSERT INTO `audit_logs` VALUES ('21',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Test","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 18:15:44');
INSERT INTO `audit_logs` VALUES ('22',NULL,'bulk_message','communication','7','Bulk SMS sent successfully',NULL,'{"recipient_count":2,"http_code":200,"api_response":"{\"status\":\"success\",\"code\":\"2000\",\"message\":\"messages sent successfully\",\"summary\":{\"_id\":\"05B0B1BE-FEC8-4DE4-88E0-25600C3B1950\",\"message_id\":\"20250110233243198125V2\",\"type\":\"API QUICK SMS\",\"total_sent\":2,\"contacts\":2,\"total_rejected\":0,\"numbers_sent\":[\"233208844175\",\"233243198125\"],\"credit_used\":2,\"credit_left\":2276}}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-10 18:15:45');
INSERT INTO `audit_logs` VALUES ('23',NULL,'bulk_message','communication',NULL,'Starting bulk SMS process',NULL,'{"message_title":"Checking","recipient_type":"all","selected_groups":[],"scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','',NULL,'2025-01-10 18:24:18');
INSERT INTO `audit_logs` VALUES ('24',NULL,'bulk_message','communication','8','Bulk SMS sent successfully',NULL,'{"recipient_count":2,"http_code":200,"api_response":"{\"status\":\"success\",\"code\":\"2000\",\"message\":\"messages sent successfully\",\"summary\":{\"_id\":\"C5A48B8F-B67D-4560-AEAA-861F08CC5BAD\",\"message_id\":\"20250110233243198125V2\",\"type\":\"API QUICK SMS\",\"total_sent\":2,\"contacts\":2,\"total_rejected\":0,\"numbers_sent\":[\"233208844175\",\"233243198125\"],\"credit_used\":2,\"credit_left\":2274}}","scheduled_time":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-10 18:24:19');
INSERT INTO `audit_logs` VALUES ('25',NULL,'login','authentication','2','Successful login for user: admin',NULL,'{"username":"admin","ip_address":"::1","user_agent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/131.0.0.0 Safari\/537.36 Edg\/131.0.0.0","timestamp":"2025-01-11 04:05:57"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','success',NULL,'2025-01-11 03:05:57');



CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `budget_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




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
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `donation_date` (`donation_date`),
  CONSTRAINT `donations_member_fk` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `donations` VALUES ('1','2','500.00','USD','cash','2025-01-09','To support the work','2025-01-09 03:48:31','2025-01-09 03:48:31');



CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(100) NOT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `events` VALUES ('1','Tuesday Excellent Service','2024-12-27 19:00:00','Oasis','For the sunday evening service');
INSERT INTO `events` VALUES ('2','Miracle Hour','2025-01-09 10:17:00','Outpouring Oasis','For our usual miracle system');
INSERT INTO `events` VALUES ('3','Prayer Night','2024-12-19 22:18:00','Adenta','Fire works');



CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expense_categories` VALUES ('1','utilities','Expenses related to electricity, water, and internet services','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('2','supplies','Office supplies and cleaning materials','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('3','maintenance','Building and equipment maintenance costs','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('4','equipment','Purchase and rental of equipment','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('5','transportation','Vehicle maintenance, fuel, and transportation costs','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('6','staff_labor','Staff salaries, wages, and labor costs','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('7','events','Event-related expenses and materials','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('8','marketing','Marketing and advertising expenses','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('9','insurance','Insurance premiums and related costs','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('10','rent','Rent and lease payments','2025-01-09 04:18:20');
INSERT INTO `expense_categories` VALUES ('11','others','Other miscellaneous expenses','2025-01-09 04:18:20');



CREATE TABLE `expense_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expense_types` VALUES ('1','fixed','Regular recurring expenses with fixed amounts','2025-01-09 04:18:20');
INSERT INTO `expense_types` VALUES ('2','variable','Regular expenses with varying amounts','2025-01-09 04:18:20');
INSERT INTO `expense_types` VALUES ('3','one_time','One-time or non-recurring expenses','2025-01-09 04:18:20');
INSERT INTO `expense_types` VALUES ('4','emergency','Urgent or emergency expenses','2025-01-09 04:18:20');
INSERT INTO `expense_types` VALUES ('5','project','Project-related expenses','2025-01-09 04:18:20');
INSERT INTO `expense_types` VALUES ('6','others','Other types of expenses','2025-01-09 04:18:20');



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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expenses` VALUES ('1','Utility payment','4000.00','GHS','cash','2025-01-09 00:00:00','fixed','utilities','42311','Rev MOB','Evelyn','','2025-01-09 04:24:06','2025-01-09 04:24:06');
INSERT INTO `expenses` VALUES ('2','Security Payment','500.00','GHS','cash','2025-01-09 00:00:00','fixed','staff_labor','','Rev MOB','Evelyn','','2025-01-09 04:25:04','2025-01-09 04:25:04');



CREATE TABLE `member_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `member_groups` VALUES ('1','Choir','Church choir members','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('2','Ushers','Church ushering team','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('3','Youth','Youth ministry members','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('4','Men Fellowship','Men''s fellowship group','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('5','Women Fellowship','Women''s fellowship group','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('6','Sunday School','Sunday school teachers and assistants','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('7','Prayer Warriors','Prayer team members','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('8','Media Team','Church media and technical team','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('9','Evangelism Team','Evangelism and outreach team','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('10','New Converts','Newly converted members','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('11','Elders','Church elders and leaders','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('12','Deacons','Church deacons and deaconesses','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('13','Children Ministry','Children''s ministry workers','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('14','Welfare Team','Church welfare committee','2025-01-09 21:19:12','2025-01-09 21:19:12');
INSERT INTO `member_groups` VALUES ('15','Protocol','Church protocol team','2025-01-09 21:19:12','2025-01-09 21:19:12');



CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `date_joined` datetime DEFAULT current_timestamp(),
  `first_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `home_address` varchar(255) DEFAULT NULL,
  `work_address` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_number1` (`contact_number_1`),
  KEY `idx_member_group` (`member_group`),
  KEY `fk_members_created_by` (`created_by`),
  CONSTRAINT `fk_members_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `members` VALUES ('1','MARTHA OSEI-BOBIE','mcommey89@gmail.com',NULL,'2025-01-08 07:21:14','MARTHA','OSEI-BOBIE','','BLK 20A R.1 COMMUNITY 3 ,TEMA ,GHANA','','',NULL,'0208844175','','','','MALE','2025-01-10 05:11:31','2025-01-10 05:11:31',NULL);
INSERT INTO `members` VALUES ('2','Henry Opoku','henrykwei@gmail.com',NULL,'2025-01-08 07:21:57','Henry','Opoku','ICT Professional','BLK 1A TDC AFFORDABLE COMMUNITY 26','First Bank ','012192-01',NULL,'0243198125','','','','FEMALE','2025-01-10 05:11:31','2025-01-10 05:11:31',NULL);



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
  KEY `status` (`status`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `members` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `messages` VALUES ('2','2',NULL,'Checking if it works','2025-01-10 05:24:03','bulk','failed','all','Testing',NULL,'{"error":"invalid api key. please make sure your api key is valid and enabled"}',NULL,NULL);
INSERT INTO `messages` VALUES ('3','2',NULL,'testing up ','2025-01-10 05:44:35','bulk','failed','all','Testing mss',NULL,'{"error":"invalid api key. please make sure your api key is valid and enabled"}',NULL,NULL);
INSERT INTO `messages` VALUES ('4','2',NULL,'Checking receipients','2025-01-10 08:41:09','bulk','failed','all','Testing mss',NULL,'{"error":"invalid api key. please make sure your api key is valid and enabled"}',NULL,'2');
INSERT INTO `messages` VALUES ('5','2',NULL,'Checking me with this ','2025-01-10 18:02:21','bulk','failed','all','Test',NULL,'{"error":"invalid api key. please make sure your api key is valid and enabled"}',NULL,'2');
INSERT INTO `messages` VALUES ('6','2',NULL,'test','2025-01-10 18:09:55','bulk','failed','all','Test',NULL,'{"error":"invalid api key. please make sure your api key is valid and enabled"}',NULL,'2');
INSERT INTO `messages` VALUES ('7','2',NULL,'test','2025-01-10 18:15:45','bulk','sent','all','Test',NULL,'{"status":"success","code":"2000","message":"messages sent successfully","summary":{"_id":"05B0B1BE-FEC8-4DE4-88E0-25600C3B1950","message_id":"20250110233243198125V2","type":"API QUICK SMS","total_sent":2,"contacts":2,"total_rejected":0,"numbers_sent":["233208844175","233243198125"],"credit_used":2,"credit_left":2276}}',NULL,'2');
INSERT INTO `messages` VALUES ('8','2',NULL,'We are testing the SMS box','2025-01-10 18:24:19','bulk','sent','all','Checking',NULL,'{"status":"success","code":"2000","message":"messages sent successfully","summary":{"_id":"C5A48B8F-B67D-4560-AEAA-861F08CC5BAD","message_id":"20250110233243198125V2","type":"API QUICK SMS","total_sent":2,"contacts":2,"total_rejected":0,"numbers_sent":["233208844175","233243198125"],"credit_used":2,"credit_left":2274}}',NULL,'2');



CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `notification_text` text NOT NULL,
  `notification_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES ('2','1','We are testing the SMS box','2025-01-10 18:24:19');
INSERT INTO `notifications` VALUES ('3','2','We are testing the SMS box','2025-01-10 18:24:19');



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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `offerings` VALUES ('1','2','9000.00','GHS','cash','2025-01-08 07:48:56','','general','2025-01-09 03:57:25','2025-01-09 03:57:26');
INSERT INTO `offerings` VALUES ('2','3','500.00','GHS','cash','2025-01-06 00:00:00','','general','2025-01-09 03:57:25','2025-01-09 03:57:26');
INSERT INTO `offerings` VALUES ('3','2','700.00','GHS','cash','2025-01-09 00:00:00','','general','2025-01-09 03:57:25','2025-01-09 03:57:26');
INSERT INTO `offerings` VALUES ('4','2','200.00','GHS','cash','2025-01-09 00:00:00','','general','2025-01-09 03:58:43','2025-01-09 03:58:43');
INSERT INTO `offerings` VALUES ('5','1','600.00','USD','cash','2025-01-09 00:00:00','','general','2025-01-09 04:06:11','2025-01-09 04:06:11');
INSERT INTO `offerings` VALUES ('6','3','2500.00','GHS','cash','2025-01-09 00:00:00','','special','2025-01-09 04:09:54','2025-01-09 04:09:54');



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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tithes` VALUES ('1','2','300.00','USD','cash','2024-12-17','2025-01-01','2025-01-09 03:40:37','2025-01-09 03:40:37');
INSERT INTO `tithes` VALUES ('2','1','450.00','GHS','cash','2025-01-06','2025-01-01','2025-01-09 03:41:54','2025-01-09 03:41:54');
INSERT INTO `tithes` VALUES ('3','2','1200.00','GHS','cash','2025-01-09','2025-01-01','2025-01-09 03:42:25','2025-01-09 03:42:25');
INSERT INTO `tithes` VALUES ('4','2','700.00','GBP','cash','2025-01-09','2025-01-01','2025-01-09 03:43:01','2025-01-09 03:43:01');
INSERT INTO `tithes` VALUES ('5','1','750.00','USD','cash','2025-01-09','2025-01-01','2025-01-09 03:43:25','2025-01-09 03:43:25');
INSERT INTO `tithes` VALUES ('6','1','300.00','USD','bank','2025-01-09','2024-12-01','2025-01-09 03:44:10','2025-01-09 03:44:10');



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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_sessions` VALUES ('5','2','sucmt99lj8jje86p88c9aakivq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0','2025-01-11 03:38:47','2025-01-11 03:05:57');



CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `created_by` (`created_by`),
  KEY `idx_member_id` (`member_id`),
  CONSTRAINT `fk_user_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `users_creator_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('2',NULL,'admin','admin@example.com','$2y$10$h085jV9PhzJtfptRlpTxLuKAHpENoVFNJLpQj3YIX2qE9FoxLxZSC','System Administrator','admin','active',NULL,'IT','System Administrator','2025-01-11 03:05:57','0',NULL,NULL,NULL,NULL,NULL,'2025-01-10 04:39:12','2025-01-11 03:05:57');


SET FOREIGN_KEY_CHECKS=1;
