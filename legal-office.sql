/*
Navicat MySQL Data Transfer

Source Server         : conn
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : legal-office

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2023-10-20 15:19:29
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `clients`
-- ----------------------------
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `office` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of clients
-- ----------------------------

-- ----------------------------
-- Table structure for `documents`
-- ----------------------------
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_received` date NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL,
  `document_file` varchar(255) DEFAULT NULL,
  `linked_document` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of documents
-- ----------------------------

-- ----------------------------
-- Table structure for `document_resets`
-- ----------------------------
DROP TABLE IF EXISTS `document_resets`;
CREATE TABLE `document_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_received` date NOT NULL,
  `document_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  CONSTRAINT `document_id` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of document_resets
-- ----------------------------

-- ----------------------------
-- Table structure for `employees`
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emp_name` varchar(255) NOT NULL,
  `emp_position` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of employees
-- ----------------------------
INSERT INTO `employees` VALUES ('1', 'Atty. Sunny G. Sacla', 'Provincial Legal Officer', null, null);
INSERT INTO `employees` VALUES ('2', 'Atty. Jake A. Sagpaey', 'Assistant Provincial Legal Officer', null, null);
INSERT INTO `employees` VALUES ('3', 'Atty. Neilson B. Sabog', 'Attorney IV', null, null);
INSERT INTO `employees` VALUES ('4', 'Atty. Dante G. Ganasi', 'Attorney IV', null, null);
INSERT INTO `employees` VALUES ('5', 'Ferdinand P. Catores', 'Supervising Administrative Officer', null, null);
INSERT INTO `employees` VALUES ('6', 'Jay Jay L. Daliones', 'Administrative Officer V', null, null);
INSERT INTO `employees` VALUES ('7', 'Beatrice S. Serrano', 'Legal Assistant II', null, null);
INSERT INTO `employees` VALUES ('8', 'Jovencio D. Caleño', 'Legal Assistant II', null, null);
INSERT INTO `employees` VALUES ('9', 'Doreen P. Matias', 'Admin. Assistant II', null, null);
INSERT INTO `employees` VALUES ('10', 'Quindoline B. Wakit', 'Admin. Aide VI', null, null);
INSERT INTO `employees` VALUES ('11', 'Deliza M. Cutara', 'Admin. Aide IV', null, null);
INSERT INTO `employees` VALUES ('12', 'Mary Ann C. Pastor', 'Admin. Aide IV', null, null);
INSERT INTO `employees` VALUES ('13', 'Wilber C. Saley Jr.', 'Admin. Aide IV', null, null);
INSERT INTO `employees` VALUES ('14', 'Archie Val M. Marcelo', 'Admin. Aide III', null, null);
INSERT INTO `employees` VALUES ('15', 'Connie S. Contero', 'Admin. Aide II', null, null);
INSERT INTO `employees` VALUES ('16', 'Sys Admin', 'Sys Admin', null, null);

-- ----------------------------
-- Table structure for `failed_jobs`
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
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

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for `migrations`
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES ('1', '2014_10_12_000000_create_users_table', '1');
INSERT INTO `migrations` VALUES ('2', '2014_10_12_100000_create_password_reset_tokens_table', '1');
INSERT INTO `migrations` VALUES ('3', '2019_08_19_000000_create_failed_jobs_table', '1');
INSERT INTO `migrations` VALUES ('4', '2019_12_14_000001_create_personal_access_tokens_table', '1');
INSERT INTO `migrations` VALUES ('5', '2023_05_19_040952_add_role_field_to_users_table', '2');
INSERT INTO `migrations` VALUES ('6', '2023_05_23_084155_create_clients_table', '3');
INSERT INTO `migrations` VALUES ('7', '2023_05_23_084219_create_documents_table', '4');
INSERT INTO `migrations` VALUES ('8', '2023_05_24_050122_add_date_received_to_documents', '5');
INSERT INTO `migrations` VALUES ('9', '2023_05_24_083424_add_status_to_documents_table', '6');
INSERT INTO `migrations` VALUES ('10', '2014_10_12_200000_add_two_factor_columns_to_users_table', '7');
INSERT INTO `migrations` VALUES ('11', '2023_06_19_090730_create_employees_table', '8');
INSERT INTO `migrations` VALUES ('12', '2023_06_19_090743_create_transactions_table', '8');
INSERT INTO `migrations` VALUES ('13', '2023_06_20_011119_create_transactions_table', '9');
INSERT INTO `migrations` VALUES ('14', '2023_06_20_012502_create_employees_table', '10');
INSERT INTO `migrations` VALUES ('15', '2023_06_20_012513_create_transactions_table', '10');
INSERT INTO `migrations` VALUES ('16', '2023_06_26_040227_create_settings_table', '11');
INSERT INTO `migrations` VALUES ('17', '2023_08_14_064516_add_avatar_field_to_users_table', '12');
INSERT INTO `migrations` VALUES ('18', '2023_08_17_062323_add_linked_document_field_to_documents', '13');
INSERT INTO `migrations` VALUES ('19', '2023_08_22_070321_add_transaction_type_field_to_transactions_table', '14');
INSERT INTO `migrations` VALUES ('20', '2023_08_24_031112_create_resets_table', '15');
INSERT INTO `migrations` VALUES ('21', '2023_08_30_053148_add_employee_id_field_to_users_table', '16');
INSERT INTO `migrations` VALUES ('22', '2023_08_31_035015_add_employee_id_field_to_users_table', '17');
INSERT INTO `migrations` VALUES ('23', '2023_10_10_011348_create_outgoing_table', '18');
INSERT INTO `migrations` VALUES ('24', '2023_10_10_012129_create_outgoing_documents_table', '19');
INSERT INTO `migrations` VALUES ('25', '2023_10_10_014447_create_outgoing_documents_table', '20');
INSERT INTO `migrations` VALUES ('26', '2023_10_10_015534_add_recipient_field_to_outgoing_documents', '21');

-- ----------------------------
-- Table structure for `outgoing_documents`
-- ----------------------------
DROP TABLE IF EXISTS `outgoing_documents`;
CREATE TABLE `outgoing_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_dispatched` date NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` varchar(255) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `document_file` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `recipient` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of outgoing_documents
-- ----------------------------

-- ----------------------------
-- Table structure for `password_reset_tokens`
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for `personal_access_tokens`
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for `settings`
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of settings
-- ----------------------------
INSERT INTO `settings` VALUES ('1', 'app_name', 'Provincial Legal Office', '2023-07-19 02:43:15', '2023-07-19 02:43:15');
INSERT INTO `settings` VALUES ('2', 'date_format', 'm/d/Y', '2023-07-19 02:43:15', '2023-07-19 02:43:15');
INSERT INTO `settings` VALUES ('3', 'pagination_limit', '10', '2023-07-19 02:43:15', '2023-10-20 06:20:47');

-- ----------------------------
-- Table structure for `transactions`
-- ----------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `document_file` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transactions_employee_id` (`employee_id`),
  CONSTRAINT `transactions_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of transactions
-- ----------------------------

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` tinyint(4) NOT NULL DEFAULT 2,
  `avatar` varchar(255) DEFAULT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', 'Admin', 'admin@gmail.com', null, '$2y$10$jkTHvrd./eDuoJmJQsxKgOjT8F35mVLK7GMjYRRxKKn6LfTiFgj2O', null, null, null, null, '2023-10-19 10:21:27', '2023-10-19 02:21:27', '1', 'public/photos/EqisjgetTWRWbcUA1wPvOEmR5m0iBjGMPwlbWge0.png', '16');
INSERT INTO `users` VALUES ('2', 'saclaSG', 'saclasg@gmail.com', null, '$2y$10$fMrv/DICp9tcv5vvCzuPeuu.EYNtUfsUJizrI6fe.EO4NV1mNn4wy', null, null, null, null, '2023-10-19 03:30:07', '2023-10-19 03:30:07', '2', null, '1');
INSERT INTO `users` VALUES ('3', 'catoresFP', 'catoresfp@gmail.com', null, '$2y$10$WvAEZqTsX7XYSHjPOUEDPeH3XyTYcBPvrv29urAXjTRlfgl2lJPDW', null, null, null, null, '2023-10-19 12:52:24', '2023-10-19 04:52:24', '2', null, '5');
INSERT INTO `users` VALUES ('4', 'cutaraDM', 'cutaradm@gmail.com', null, '$2y$10$5QVX0kPPWZ9MkfdZQjG3auTVUkTQYAHzIOuzcgT7rTGvsZxP9wnm.', null, null, null, null, '2023-10-19 03:31:10', '2023-10-19 03:31:10', '2', null, '11');
INSERT INTO `users` VALUES ('5', 'conteroCS', 'conterocs@gmail.com', null, '$2y$10$TBs5CWiYc6wQU/JkzUrde.kLXiVgww4peu9TFJRF7V1OD3pYH2Lt6', null, null, null, null, '2023-10-19 03:31:29', '2023-10-19 03:31:29', '2', null, '15');
INSERT INTO `users` VALUES ('6', 'pastorMA', 'pastorma@gmail.com', null, '$2y$10$XFshxtb4ZYk32v.BJBAoiuEmqgt2ydcUx4qKt..QnTt8p9rhiqDIC', null, null, null, null, '2023-10-19 08:21:39', '2023-10-19 08:21:39', '2', null, '12');
