-- Migration 002: Add department/created_by to projects; create IT asset and license tables

ALTER TABLE `projects`
    ADD COLUMN `department` VARCHAR(50) NULL AFTER `client_id`,
    ADD COLUMN `created_by` INT NULL AFTER `department`,
    ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

CREATE TABLE IF NOT EXISTS `it_assets` (
    `id`              INT NOT NULL AUTO_INCREMENT,
    `asset_name`      VARCHAR(100) NOT NULL,
    `asset_type`      VARCHAR(50) NOT NULL,
    `brand`           VARCHAR(50) NULL,
    `model`           VARCHAR(100) NULL,
    `serial_number`   VARCHAR(100) NULL,
    `purchase_date`   DATE NULL,
    `warranty_expiry` DATE NULL,
    `assigned_to`     INT NULL,
    `status`          VARCHAR(20) NOT NULL DEFAULT 'available',
    `location`        VARCHAR(100) NULL,
    `notes`           TEXT NULL,
    `created_by`      INT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `assigned_to` (`assigned_to`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `it_licenses` (
    `id`            INT NOT NULL AUTO_INCREMENT,
    `software_name` VARCHAR(100) NOT NULL,
    `vendor`        VARCHAR(100) NULL,
    `license_key`   VARCHAR(255) NULL,
    `license_type`  VARCHAR(50) NOT NULL DEFAULT 'perpetual',
    `seats`         INT NOT NULL DEFAULT 1,
    `seats_used`    INT NOT NULL DEFAULT 0,
    `purchase_date` DATE NULL,
    `expiry_date`   DATE NULL,
    `cost`          DECIMAL(10,2) NULL,
    `status`        VARCHAR(20) NOT NULL DEFAULT 'active',
    `notes`         TEXT NULL,
    `created_by`    INT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `status` (`status`),
    KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
