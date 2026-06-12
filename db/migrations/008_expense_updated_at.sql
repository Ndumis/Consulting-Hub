-- Migration 008: add updated_at to expenses (receipt_file column already exists)
ALTER TABLE `expenses`
  ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
