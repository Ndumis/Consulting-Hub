-- Migration 007: add proof-of-payment file and updated_at to project_revenues
ALTER TABLE `project_revenues`
  ADD COLUMN `proof_file` VARCHAR(255) NULL DEFAULT NULL AFTER `notes`,
  ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
