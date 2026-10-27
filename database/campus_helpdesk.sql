-- ============================================================================
-- CAMPUS HELPDESK – IT & FACILITY TICKET SYSTEM
-- Final Complete Database Script (All 12 Tables - Updated)
-- Environment: MariaDB 10.4+ / MySQL 8.0.16+ (InnoDB, utf8mb4)
-- Demo Password for all users: Helpdesk@123
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `campus_helpdesk`
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campus_helpdesk`;

-- Drop tables in reverse order of foreign key dependencies for clean re-runs
DROP TABLE IF EXISTS `satisfaction_surveys`, `escalation_logs`, `ticket_status_logs`, 
                     `ticket_comments`, `ticket_assignments`, `tickets`, 
                     `notifications`, `assets`, `classrooms`, 
                     `ticket_categories`, `users`, `departments`;

-- ============================================================================
-- 1. MASTER DATA TABLES
-- ============================================================================

-- 1.1. Table: departments
CREATE TABLE `departments` (
    `department_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `department_code` VARCHAR(20) NOT NULL UNIQUE,
    `department_name` VARCHAR(100) NOT NULL UNIQUE,
    `description`     VARCHAR(255) DEFAULT NULL,
    `contact_email`   VARCHAR(100) DEFAULT NULL,
    `contact_phone`   VARCHAR(20) DEFAULT NULL,
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `chk_departments_is_active` CHECK (`is_active` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2. Table: users
CREATE TABLE `users` (
    `user_id`       INT AUTO_INCREMENT PRIMARY KEY,
    `user_code`     VARCHAR(20) UNIQUE DEFAULT NULL,
    `full_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `phone`         VARCHAR(20) DEFAULT NULL,
    `role`          ENUM('Manager', 'Technician', 'Lecturer', 'Student') NOT NULL DEFAULT 'Student',
    `department_id` INT DEFAULT NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE RESTRICT,
    CONSTRAINT `chk_users_email_format` CHECK (`email` LIKE '%_@_%._%'),
    -- Only Technician strictly requires department_id; Centralized Manager can be NULL
    CONSTRAINT `chk_users_dept_by_role` CHECK (`role` <> 'Technician' OR `department_id` IS NOT NULL),
    CONSTRAINT `chk_users_is_active` CHECK (`is_active` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3. Table: ticket_categories
CREATE TABLE `ticket_categories` (
    `category_id`            INT AUTO_INCREMENT PRIMARY KEY,
    `department_id`          INT NOT NULL,
    `category_code`          VARCHAR(30) NOT NULL UNIQUE,
    `category_name`          VARCHAR(100) NOT NULL,
    `description`            VARCHAR(255) DEFAULT NULL,
    `routing_keywords`       VARCHAR(500) DEFAULT NULL,
    `default_priority`       ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    `sla_response_minutes`   INT NOT NULL DEFAULT 240,
    `sla_resolution_minutes` INT NOT NULL DEFAULT 1440,
    `is_active`              TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_tc_dept_name` (`department_id`, `category_name`),
    CONSTRAINT `fk_tc_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE RESTRICT,
    CONSTRAINT `chk_tc_sla_response` CHECK (`sla_response_minutes` > 0),
    CONSTRAINT `chk_tc_sla_resolution` CHECK (`sla_resolution_minutes` >= `sla_response_minutes`),
    CONSTRAINT `chk_tc_is_active` CHECK (`is_active` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4. Table: classrooms
CREATE TABLE `classrooms` (
    `classroom_id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number`  VARCHAR(50) NOT NULL UNIQUE,
    `building`     VARCHAR(50) NOT NULL,
    `floor`        INT NOT NULL,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5. Table: assets
CREATE TABLE `assets` (
    `asset_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `asset_code`   VARCHAR(50) NOT NULL UNIQUE,
    `asset_name`   VARCHAR(100) NOT NULL,
    `classroom_id` INT NOT NULL,
    `category_id`  INT NOT NULL,
    `status`       ENUM('Active', 'Faulty', 'Under_Repair', 'Decommissioned') NOT NULL DEFAULT 'Active',
    `fault_count`  INT NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_assets_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`classroom_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories`(`category_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 2. TRANSACTIONAL DATA TABLES (TICKETS & LOGS & NOTIFICATIONS)
-- ============================================================================

-- 2.1. Table: notifications
CREATE TABLE `notifications` (
    `notification_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`           INT NOT NULL,
    `title`             VARCHAR(150) NOT NULL,
    `message`           TEXT NOT NULL,
    `notification_type` ENUM('Assignment', 'Status_Update', 'SLA_Warning', 'System') NOT NULL DEFAULT 'System',
    `target_url`        VARCHAR(255) NULL,
    `is_read`           TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2. Table: tickets
CREATE TABLE `tickets` (
    `ticket_id`    INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_code`  VARCHAR(20) NOT NULL UNIQUE,
    `creator_id`   INT NOT NULL,
    `classroom_id` INT NOT NULL,
    `asset_id`     INT NULL,
    `category_id`  INT NOT NULL,
    `title`        VARCHAR(200) NOT NULL,
    `description`  TEXT NOT NULL,
    `image_path`   VARCHAR(255) NULL,
    `priority`     ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    `status`       ENUM('Pending', 'Assigned', 'Received', 'In_Progress', 'Completed', 'Closed') NOT NULL DEFAULT 'Pending',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at`  DATETIME NULL,
    `closed_at`    DATETIME NULL,
    CONSTRAINT `fk_tickets_creator` FOREIGN KEY (`creator_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_tickets_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms`(`classroom_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_tickets_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets`(`asset_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `ticket_categories`(`category_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3. Table: ticket_assignments
CREATE TABLE `ticket_assignments` (
    `assignment_id`     INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`         INT NOT NULL,
    `technician_id`     INT NOT NULL,
    `assigned_by`       INT NULL,
    `sla_deadline`      DATETIME NOT NULL,
    `assignment_status` ENUM('Assigned', 'Received', 'Rejected') NOT NULL DEFAULT 'Assigned',
    `rejection_reason`  TEXT NULL,
    `assignment_note`   VARCHAR(500) NULL,
    `is_current`        TINYINT(1) NOT NULL DEFAULT 1,
    `assigned_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ta_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ta_technician` FOREIGN KEY (`technician_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_ta_manager` FOREIGN KEY (`assigned_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4. Table: ticket_comments
CREATE TABLE `ticket_comments` (
    `comment_id`  INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`   INT NOT NULL,
    `user_id`     INT NOT NULL,
    `content`     TEXT NOT NULL,
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tc_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5. Table: ticket_status_logs
CREATE TABLE `ticket_status_logs` (
    `log_id`      INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`   INT NOT NULL,
    `old_status`  ENUM('Pending', 'Assigned', 'Received', 'In_Progress', 'Completed', 'Closed') NULL,
    `new_status`  ENUM('Pending', 'Assigned', 'Received', 'In_Progress', 'Completed', 'Closed') NOT NULL,
    `changed_by`  INT NOT NULL,
    `notes`       TEXT NULL,
    `proof_image` VARCHAR(255) NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tsl_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tsl_user` FOREIGN KEY (`changed_by`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.6. Table: escalation_logs
CREATE TABLE `escalation_logs` (
    `escalation_id`   INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`       INT NOT NULL,
    `technician_id`   INT NULL,
    `escalation_type` ENUM('Overdue', 'Rejected') NOT NULL,
    `reason`          TEXT NULL,
    `escalated_to`    INT NULL,
    `is_resolved`     TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at`     DATETIME NULL,
    CONSTRAINT `fk_el_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_el_technician` FOREIGN KEY (`technician_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_el_manager` FOREIGN KEY (`escalated_to`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.7. Table: satisfaction_surveys
CREATE TABLE `satisfaction_surveys` (
    `survey_id`        INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`        INT NOT NULL UNIQUE, 
    `user_id`          INT NOT NULL,
    `rating_stars`     TINYINT NOT NULL,
    `feedback_comment` TEXT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ss_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ss_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT,
    CONSTRAINT `chk_ss_rating` CHECK (`rating_stars` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- 3. SEED DATA (MASTER DATA FOR TESTING & DEMO)
-- ============================================================================

-- ---- 3.1. departments --------------------------------------------------------
INSERT INTO `departments` 
    (`department_id`, `department_code`, `department_name`, `description`, `contact_email`, `contact_phone`, `is_active`) 
VALUES
(1, 'IT',  'IT Department', 'Network, WiFi, LMS, computers, software, projectors', 'it.support@campus.edu.vn', '02431234567', 1),
(2, 'FAC', 'Facilities Department', 'Electrical, AC, furniture, plumbing, safety', 'facility@campus.edu.vn', '02437654321', 1),
(3, 'ACA', 'Academic Affairs Office','Timetables, rooms, documents, grades', 'academic@campus.edu.vn', '02438889999', 1);

-- ---- 3.2. users (Password for all: Helpdesk@123) -----------------------------
-- MGR001 configured as Centralized Helpdesk Manager (department_id = NULL)
INSERT INTO `users` 
    (`user_id`, `user_code`, `full_name`, `email`, `password_hash`, `phone`, `role`, `department_id`, `is_active`) 
VALUES
(1, 'MGR001', 'Tran Minh Quan',    'manager@campus.edu.vn',     '$2y$10$zq4x.RHDAlofXP55PQufZ.CmyFyesqc9GOJtqh3AWGAhr2Xp9U5am', '0901000001', 'Manager',    NULL, 1),
(2, 'TECH001','Nguyen Duc Anh',    'tech.it1@campus.edu.vn',    '$2y$10$tisTx/pm.QKSoFQQ59mHEuWqWQ0JxDBmK8vc/4fG47B0obvIABmr.', '0901000003', 'Technician', 1,    1),
(3, 'TECH002','Hoang Van Hung',    'tech.fac1@campus.edu.vn',   '$2y$10$0E.NKHOLZlUcdWejuK1wxu1JXyQKlzwJjBAOzWHY4CIHW2W8oA1Ve', '0901000004', 'Technician', 2,    1),
(4, 'LEC001', 'Nguyen Thi Hai Yen','lecturer1@campus.edu.vn',   '$2y$10$cTlOttQ1ayLWazC5aGt10u3zpo2FqjqbJPBwDr94S2zVMIZOC8sra', '0902000001', 'Lecturer',   NULL, 1),
(5, '23070001','Nguyen Minh Khoi', 'student1@campus.edu.vn',    '$2y$10$DtE6qoS6iLxRhw1c5PB45eCmxc6oJgZ1o6.jMzNCMae1t/jJl8Yy2', '0903000001', 'Student',    NULL, 1);

-- ---- 3.3. ticket_categories --------------------------------------------------
INSERT INTO `ticket_categories` 
    (`category_id`, `department_id`, `category_code`, `category_name`, `routing_keywords`, `default_priority`, `sla_response_minutes`, `sla_resolution_minutes`) 
VALUES
(1, 1, 'IT_NETWORK',    'Network & WiFi',               'wifi,network,internet,lan',      'High',     30, 240),
(2, 1, 'IT_SOFTWARE',   'Software & Operating System',  'software,install,os,windows',    'Medium',   60, 240),
(3, 1, 'IT_PROJECTOR',  'Projectors & Display',         'projector,display,hdmi,screen',  'High',     30, 180),
(4, 2, 'FAC_AIRCON',    'Air Conditioning & Cooling',   'air conditioner,ac,hot,leaking', 'Medium',   60, 360),
(5, 2, 'FAC_SOUND',     'Microphones & Audio',          'mic,microphone,speaker,sound',   'Critical', 15, 60);

-- ---- 3.4. classrooms ---------------------------------------------------------
INSERT INTO `classrooms` 
    (`classroom_id`, `room_number`, `building`, `floor`, `is_active`) 
VALUES
(1, 'A1-201', 'Building A1', 2, 1),
(2, 'A1-202', 'Building A1', 2, 1),
(3, 'B2-301', 'Building B2', 3, 1),
(4, 'B2-405', 'Building B2', 4, 1),
(5, 'C1-102', 'Building C1', 1, 0);

-- ---- 3.5. assets (Now linked with category_id) -------------------------------
INSERT INTO `assets` 
    (`asset_id`, `asset_code`, `asset_name`, `classroom_id`, `category_id`, `status`, `fault_count`) 
VALUES
(1, 'AST-PRJ-001', 'Epson EB-X06 Projector',         1, 3, 'Active', 0),
(2, 'AST-AC-001',  'Daikin Inverter AC 18000BTU',     1, 4, 'Active', 1),
(3, 'AST-MIC-001', 'Shure SVX24 Wireless Microphone', 1, 5, 'Faulty', 3), 
(4, 'AST-PRJ-002', 'Panasonic PT-LB386 Projector',   2, 3, 'Active', 0),
(5, 'AST-SW-001',  'Cisco 24-Port Gigabit Switch',    3, 1, 'Active', 0),
(6, 'AST-PC-001',  'HP ProDesk Lab Computers (x30)',  3, 2, 'Under_Repair', 2);

SET FOREIGN_KEY_CHECKS = 1;