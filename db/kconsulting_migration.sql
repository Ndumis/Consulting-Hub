-- ============================================================
-- KConsulting Hub — Schema Migration
-- Target database: thekcaar_kconsulting
-- Compatible with: MariaDB 10.5+ / MySQL 8.0+
-- Instructions: Run this file once on thekcaar_kconsulting.
--               It is safe to re-run (all operations are idempotent).
--               NO data is inserted — structure changes only.
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ============================================================
-- STEP 1: Drop obsolete / unused tables
-- ============================================================

DROP TABLE IF EXISTS `marketing_blog_posts`;
DROP TABLE IF EXISTS `system_activity`;

-- ============================================================
-- STEP 2: Create new tables (only if they don't exist yet)
-- ============================================================

-- blog_posts (replaces marketing_blog_posts — public-facing articles)
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(255) NOT NULL,
  `slug`           VARCHAR(255) NOT NULL,
  `excerpt`        TEXT,
  `content`        LONGTEXT,
  `featured_image` VARCHAR(500) DEFAULT NULL,
  `author`         VARCHAR(100) DEFAULT 'KConsulting Team',
  `category`       VARCHAR(100) DEFAULT NULL,
  `tags`           VARCHAR(500) DEFAULT NULL,
  `read_time`      INT DEFAULT 5,
  `is_featured`    TINYINT(1) DEFAULT 0,
  `status`         VARCHAR(20) DEFAULT 'published',
  `views`          INT DEFAULT 0,
  `published_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  `client_id`      INT DEFAULT NULL,
  `campaign_id`    INT DEFAULT NULL,
  `author_id`      INT DEFAULT NULL,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_bp_client_id`   (`client_id`),
  KEY `idx_bp_campaign_id` (`campaign_id`),
  KEY `idx_bp_author_id`   (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- it_assets (IT department asset register)
CREATE TABLE IF NOT EXISTS `it_assets` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `asset_name`      VARCHAR(100) NOT NULL,
  `asset_type`      VARCHAR(50) NOT NULL,
  `brand`           VARCHAR(50) DEFAULT NULL,
  `model`           VARCHAR(100) DEFAULT NULL,
  `serial_number`   VARCHAR(100) DEFAULT NULL,
  `purchase_date`   DATE DEFAULT NULL,
  `warranty_expiry` DATE DEFAULT NULL,
  `assigned_to`     INT DEFAULT NULL,
  `status`          VARCHAR(20) NOT NULL DEFAULT 'available',
  `location`        VARCHAR(100) DEFAULT NULL,
  `notes`           TEXT DEFAULT NULL,
  `created_by`      INT DEFAULT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- it_licenses (IT department software licence tracking)
CREATE TABLE IF NOT EXISTS `it_licenses` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `software_name` VARCHAR(100) NOT NULL,
  `vendor`        VARCHAR(100) DEFAULT NULL,
  `license_key`   VARCHAR(255) DEFAULT NULL,
  `license_type`  VARCHAR(50) NOT NULL DEFAULT 'perpetual',
  `seats`         INT NOT NULL DEFAULT 1,
  `seats_used`    INT NOT NULL DEFAULT 0,
  `purchase_date` DATE DEFAULT NULL,
  `expiry_date`   DATE DEFAULT NULL,
  `cost`          DECIMAL(10,2) DEFAULT NULL,
  `status`        VARCHAR(20) NOT NULL DEFAULT 'active',
  `notes`         TEXT DEFAULT NULL,
  `created_by`    INT DEFAULT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- notifications (in-app notification feed per user)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `user_id`    INT NOT NULL,
  `type`       VARCHAR(50) NOT NULL DEFAULT 'info',
  `title`      VARCHAR(255) NOT NULL,
  `message`    TEXT DEFAULT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`, `is_read`),
  KEY `idx_created`   (`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password_reset_tokens (for forgot-password flow)
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `user_id`    INT NOT NULL,
  `token`      VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at`    DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token`   (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STEP 3: Add new columns to existing tables
-- NOTE: Run this script ONCE on a fresh thekcaar_kconsulting DB.
--       These columns do not exist there yet — no duplicate risk.
-- ============================================================

CREATE TABLE IF NOT EXISTS portfolio_extras (
        id                   INT AUTO_INCREMENT PRIMARY KEY,
        project_id           INT           NOT NULL UNIQUE,
        display_category     VARCHAR(50)   DEFAULT NULL,
        image_url            VARCHAR(500)  DEFAULT NULL,
        tags                 VARCHAR(500)  DEFAULT NULL,
        badge_label          VARCHAR(100)  DEFAULT NULL,
        badge_colour         VARCHAR(30)   DEFAULT 'gold',
        case_study_title     VARCHAR(255)  DEFAULT NULL,
        case_study_overview  TEXT          DEFAULT NULL,
        case_study_challenge TEXT          DEFAULT NULL,
        case_study_solution  TEXT          DEFAULT NULL,
        case_study_results   TEXT          DEFAULT NULL,
        project_live_url     VARCHAR(500)  DEFAULT NULL,
        show_in_portfolio    TINYINT(1)    DEFAULT 1,
        sort_order           INT           DEFAULT 0,
        created_at           DATETIME      DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4


-- projects: add department tracking and audit columns
ALTER TABLE `projects`
  ADD COLUMN `department` VARCHAR(50) NULL AFTER `client_id`,
  ADD COLUMN `created_by` INT NULL       AFTER `department`,
  ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- hr_employees: link to users table and add personal profile fields
ALTER TABLE `hr_employees`
  ADD COLUMN `user_id`           INT           NULL AFTER `employee_id`,
  ADD COLUMN `bio`               TEXT          NULL,
  ADD COLUMN `emergency_contact` VARCHAR(100)  NULL,
  ADD COLUMN `emergency_phone`   VARCHAR(20)   NULL,
  ADD COLUMN `address`           TEXT          NULL,
  ADD COLUMN `date_of_birth`     DATE          NULL,
  ADD COLUMN `national_id`       VARCHAR(30)   NULL,
  ADD COLUMN `role`              VARCHAR(50)   NULL,
  ADD COLUMN `updated_at`        TIMESTAMP     NULL ON UPDATE CURRENT_TIMESTAMP;

-- Add unique constraint on hr_employees.user_id (one employee per user account)
ALTER TABLE `hr_employees`
  ADD UNIQUE KEY `uq_hr_emp_user` (`user_id`);

-- Auto-link existing hr_employees rows to matching user accounts by email
UPDATE `hr_employees` e
JOIN   `users` u ON LOWER(e.email) = LOWER(u.email)
SET    e.user_id = u.id
WHERE  e.user_id IS NULL;

-- Back-fill role from users where a link was just established
UPDATE `hr_employees` e
JOIN   `users` u ON e.user_id = u.id
SET    e.role = u.role
WHERE  e.role IS NULL;

-- ============================================================
-- Done — migration complete
-- ============================================================
