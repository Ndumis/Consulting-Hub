-- Migration 009: add extended application fields used by the public apply.php form
ALTER TABLE `candidates`
  ADD COLUMN `salary_expectation` DECIMAL(10,2) NULL DEFAULT NULL AFTER `cover_letter`,
  ADD COLUMN `availability_date` DATE NULL DEFAULT NULL AFTER `salary_expectation`,
  ADD COLUMN `preferred_location` VARCHAR(100) NULL DEFAULT NULL AFTER `availability_date`,
  ADD COLUMN `willing_to_relocate` VARCHAR(1) NULL DEFAULT NULL AFTER `preferred_location`,
  ADD COLUMN `work_authorization` VARCHAR(30) NULL DEFAULT NULL AFTER `willing_to_relocate`,
  ADD COLUMN `linkedin_profile` VARCHAR(255) NULL DEFAULT NULL AFTER `work_authorization`,
  ADD COLUMN `portfolio_website` VARCHAR(255) NULL DEFAULT NULL AFTER `linkedin_profile`,
  ADD COLUMN `years_experience` INT NULL DEFAULT NULL AFTER `portfolio_website`;
