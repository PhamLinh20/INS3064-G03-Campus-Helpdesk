-- ============================================================================
-- CAMPUS HELPDESK – IT & FACILITY TICKET SYSTEM
-- Final Complete Database Script (All 12 Tables - Optimized English Edition)
-- Environment: MariaDB 10.4+ / MySQL 8.0.16+ (InnoDB, utf8mb4)
-- Demo Password for all seeded users: Helpdesk@123
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

-- 1.4. Table: classrooms (10 standard physical classrooms)
CREATE TABLE `classrooms` (
    `classroom_id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_number`  VARCHAR(50) NOT NULL UNIQUE,
    `building`     VARCHAR(50) NOT NULL,
    `floor`        INT NOT NULL,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5. Table: assets (1-N relationship with classrooms)
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
-- 2. TRANSACTIONAL DATA TABLES (TICKETS & AUDIT LOGS)
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

-- 2.4. Table: ticket_comments (Strictly Internal Technical Notes between Manager & Technicians)
CREATE TABLE `ticket_comments` (
    `comment_id`  INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id`   INT NOT NULL,
    `user_id`     INT NOT NULL,
    `content`     TEXT NOT NULL,
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

-- Indexes for performance optimization on high-frequency queries
ALTER TABLE `tickets` ADD INDEX `idx_tickets_status` (`status`);
ALTER TABLE `tickets` ADD INDEX `idx_tickets_created_at` (`created_at`);
ALTER TABLE `notifications` ADD INDEX `idx_notifications_user_unread` (`user_id`, `is_read`);


-- ============================================================================
-- 3. SEED DATA (MASTER DATA & ALL-ENGLISH TRANSACTIONAL RECORDS)
-- ============================================================================

-- ---- 3.1. departments --------------------------------------------------------
INSERT INTO `departments` 
    (`department_id`, `department_code`, `department_name`, `description`, `contact_email`, `contact_phone`, `is_active`) 
VALUES
(1, 'IT',  'IT Department', 'Campus network, Wi-Fi, LMS, computer laboratories, software, and audiovisual hardware', 'it.support@campus.edu.vn', '02431234567', 1),
(2, 'FAC', 'Facilities Department', 'Electrical infrastructure, HVAC systems, physical furniture, plumbing, and building safety', 'facility@campus.edu.vn', '02437654321', 1),
(3, 'ACA', 'Academic Affairs Office', 'Course timetables, lecture hall allocation, examination records, and student certifications', 'academic@campus.edu.vn', '02438889999', 1);

-- ---- 3.2. users (Password for all: Helpdesk@123) -----------------------------
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
(1, 1, 'IT_NETWORK',    'Network & Wi-Fi',              'wifi,network,internet,lan',      'High',     30, 240),
(2, 1, 'IT_SOFTWARE',   'Software & Operating System',  'software,install,os,windows',    'Medium',   60, 240),
(3, 1, 'IT_PROJECTOR',  'Projectors & Display',         'projector,display,hdmi,screen',  'High',     30, 180),
(4, 2, 'FAC_AIRCON',    'Air Conditioning & Cooling',   'air conditioner,ac,hot,leaking', 'Medium',   60, 360),
(5, 2, 'FAC_SOUND',     'Microphones & Audio',          'mic,microphone,speaker,sound',   'Critical', 15, 60);

-- ---- 3.4. classrooms (10 standard physical classrooms from midterm) ---------
INSERT INTO `classrooms` 
    (`classroom_id`, `room_number`, `building`, `floor`, `is_active`) 
VALUES
(1,  'A1-201', 'Building A1', 2, 1),
(2,  'A1-202', 'Building A1', 2, 1),
(3,  'A1-301', 'Building A1', 3, 1),
(4,  'B2-101', 'Building B2', 1, 1),
(5,  'B2-202', 'Building B2', 2, 1),
(6,  'B2-301', 'Building B2', 3, 1),
(7,  'B2-405', 'Building B2', 4, 1),
(8,  'C1-101', 'Building C1', 1, 1),
(9,  'C1-102', 'Building C1', 1, 0),
(10, 'C1-203', 'Building C1', 2, 1);

-- ---- 3.5. assets (11 items; rooms 7, 8, 9, 10 empty for testing LEFT JOIN) ---
INSERT INTO `assets` 
    (`asset_id`, `asset_code`, `asset_name`, `classroom_id`, `category_id`, `status`, `fault_count`) 
VALUES
(1,  'AST-PRJ-001', 'Epson EB-X06 Projector',         1, 3, 'Active', 0),
(2,  'AST-AC-001',  'Daikin Inverter AC 18000BTU',     1, 4, 'Active', 1),
(3,  'AST-MIC-001', 'Shure SVX24 Wireless Microphone', 1, 5, 'Faulty', 3),
(4,  'AST-PRJ-002', 'Panasonic PT-LB386 Projector',   2, 3, 'Active', 0),
(5,  'AST-AC-002',  'Panasonic Inverter AC 12000BTU',  2, 4, 'Active', 0),
(6,  'AST-SW-001',  'Cisco 24-Port Gigabit Switch',    3, 1, 'Active', 0),
(7,  'AST-PC-001',  'HP ProDesk Lab Computers (x30)',  3, 2, 'Under_Repair', 2),
(8,  'AST-PRJ-003', 'Sony VPL-EX435 Projector',       4, 3, 'Active', 0),
(9,  'AST-MIC-002', 'Sony Wireless Handheld Mic',      5, 5, 'Active', 0),
(10, 'AST-AC-003',  'Daikin Ceiling AC 24000BTU',      6, 4, 'Active', 1),
(11, 'AST-SW-002',  'TP-Link 16-Port Gigabit Switch',  6, 1, 'Active', 0);

-- ---- 3.6. tickets (6 sample tickets matching UI specifications) ---------------
INSERT INTO `tickets` 
    (`ticket_id`, `ticket_code`, `creator_id`, `classroom_id`, `asset_id`, `category_id`, `title`, `description`, `priority`, `status`, `created_at`, `updated_at`, `resolved_at`, `closed_at`) 
VALUES
(1, 'TIC-7606', 5, 2, NULL, 4, 'General Facility (Wall Socket)', 'Wall power outlet in room A1-202 is loose and sparking upon plug insertion.', 'Medium', 'Pending', '2026-10-14 08:00:00', '2026-10-14 08:00:00', NULL, NULL),
(2, 'TIC-7580', 4, 1, 1, 3, 'Epson EB-X06 Projector', 'Projector in room A1-201 is flickering, dim, and fails to detect HDMI input.', 'High', 'In_Progress', '2026-10-14 09:15:00', '2026-10-14 09:45:00', NULL, NULL),
(3, 'TIC-7542', 4, 1, 2, 4, 'Daikin Inverter AC 18000BTU', 'Air conditioner in room A1-201 is blowing warm air with continuous humming noise.', 'Low', 'Assigned', '2026-10-13 14:00:00', '2026-10-13 14:20:00', NULL, NULL),
(4, 'TIC-7512', 4, 1, 3, 5, 'Shure SVX24 Wireless Microphone', 'Wireless microphone in room A1-201 completely lost audio signal during lecture.', 'Critical', 'Closed', '2026-10-13 08:30:00', '2026-10-13 10:00:00', '2026-10-13 09:15:00', '2026-10-13 10:00:00'),
(5, 'TIC-7490', 5, 3, 7, 2, 'HP ProDesk Lab Computers (x30)', 'Multiple workstations in Computer Lab B2-301 freeze on Windows boot logo.', 'Medium', 'Completed', '2026-10-12 10:00:00', '2026-10-12 16:30:00', '2026-10-12 16:30:00', NULL),
(6, 'TIC-7450', 5, 3, 6, 1, 'Cisco 24-Port Gigabit Switch', 'Network switch in room B2-301 shows blinking amber lights; entire lab lost LAN connectivity.', 'High', 'Closed', '2026-10-10 13:00:00', '2026-10-10 16:00:00', '2026-10-10 15:30:00', '2026-10-10 16:00:00');

-- ---- 3.7. ticket_assignments ------------------------------------------------
INSERT INTO `ticket_assignments` 
    (`assignment_id`, `ticket_id`, `technician_id`, `assigned_by`, `sla_deadline`, `assignment_status`, `is_current`, `assigned_at`) 
VALUES
(1, 2, 2, 1, '2026-10-14 12:15:00', 'Received', 1, '2026-10-14 09:20:00'),
(2, 3, 3, 1, '2026-10-13 20:00:00', 'Assigned', 1, '2026-10-13 14:20:00'),
(3, 4, 3, 1, '2026-10-13 09:30:00', 'Received', 1, '2026-10-13 08:35:00'),
(4, 5, 2, 1, '2026-10-12 14:00:00', 'Received', 1, '2026-10-12 10:15:00'), -- Overdue (completed at 16:30 > deadline 14:00)
(5, 6, 2, 1, '2026-10-10 17:00:00', 'Received', 1, '2026-10-10 13:10:00');  -- On time (completed at 15:30 <= deadline 17:00)

-- ---- 3.8. ticket_comments (Strictly Internal Technical Notes) ----------------
INSERT INTO `ticket_comments` 
    (`comment_id`, `ticket_id`, `user_id`, `content`, `created_at`) 
VALUES
(1, 2, 2, 'Brought a replacement HDMI cable to room A1-201 to test video input.', '2026-10-14 09:35:00'),
(2, 2, 1, 'If the HDMI controller IC on the projector is blown, submit an asset handover report for warranty repair.', '2026-10-14 09:40:00'),
(3, 4, 3, 'Rechargeable batteries were depleted and UHF frequency drifted; replaced with 2 fresh batteries and recalibrated channels.', '2026-10-13 09:10:00'),
(4, 5, 2, 'Boot partition corrupted due to sudden power outage; deployed standardized OS backup image across all lab workstations.', '2026-10-12 15:45:00');

-- ---- 3.9. ticket_status_logs (Audit Trail for Progress Tracking) -------------
INSERT INTO `ticket_status_logs` 
    (`ticket_id`, `old_status`, `new_status`, `changed_by`, `notes`, `created_at`) 
VALUES
(1, NULL, 'Pending', 5, 'Student submitted initial maintenance request for electrical socket.', '2026-10-14 08:00:00'),
(2, NULL, 'Pending', 4, 'Lecturer reported malfunctioning projector.', '2026-10-14 09:15:00'),
(2, 'Pending', 'Assigned', 1, 'Helpdesk Manager assigned ticket to IT Technician Nguyen Duc Anh.', '2026-10-14 09:20:00'),
(2, 'Assigned', 'In_Progress', 2, 'Technician acknowledged assignment and commenced on-site diagnosis.', '2026-10-14 09:45:00'),
(4, NULL, 'Pending', 4, 'Urgent ticket created for wireless microphone outage.', '2026-10-13 08:30:00'),
(4, 'Pending', 'Assigned', 1, 'Directly dispatched to Facilities Technician Hoang Van Hung.', '2026-10-13 08:35:00'),
(4, 'Assigned', 'In_Progress', 3, 'Technician arrived at room A1-201.', '2026-10-13 08:45:00'),
(4, 'In_Progress', 'Completed', 3, 'Batteries replaced and microphone audio transmission verified clear.', '2026-10-13 09:15:00'),
(4, 'Completed', 'Closed', 4, 'Lecturer confirmed successful resolution and closed the ticket.', '2026-10-13 10:00:00');

-- ---- 3.10. escalation_logs (Exceptions & Overdue Tracking) -------------------
INSERT INTO `escalation_logs` 
    (`escalation_id`, `ticket_id`, `technician_id`, `escalation_type`, `reason`, `escalated_to`, `is_resolved`, `created_at`, `resolved_at`) 
VALUES
(1, 5, 2, 'Overdue', 'Lab re-imaging duration exceeded SLA deadline by 2.5 hours due to concurrent disk writes across 30 workstations.', 1, 1, '2026-10-12 14:05:00', '2026-10-12 16:30:00');

-- ---- 3.11. satisfaction_surveys (1-1 with Closed Tickets) --------------------
INSERT INTO `satisfaction_surveys` 
    (`survey_id`, `ticket_id`, `user_id`, `rating_stars`, `feedback_comment`, `created_at`) 
VALUES
(1, 4, 4, 5, 'Technician arrived within 10 minutes, was very courteous, and the lecture resumed without major delay.', '2026-10-13 10:05:00'),
(2, 6, 5, 5, 'Computer lab network connectivity is fully restored and stable. Thank you for the quick turnaround.', '2026-10-10 16:15:00');

-- ---- 3.12. notifications ----------------------------------------------------
INSERT INTO `notifications` 
    (`user_id`, `title`, `message`, `notification_type`, `is_read`, `created_at`) 
VALUES
(2, 'New Task Assigned', 'You have been assigned to ticket TIC-7580 (Epson EB-X06 Projector) in room A1-201.', 'Assignment', 1, '2026-10-14 09:20:00'),
(3, 'New Task Assigned', 'You have been assigned to ticket TIC-7542 (Daikin Inverter AC) in room A1-201.', 'Assignment', 0, '2026-10-13 14:20:00'),
(1, 'SLA Breach Warning', 'Ticket TIC-7490 in Computer Lab B2-301 has breached the agreed SLA resolution deadline.', 'SLA_Warning', 1, '2026-10-12 14:05:00');

SET FOREIGN_KEY_CHECKS = 1;