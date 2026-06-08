-- Migration 004: Link hr_employees to users; add personal profile fields
-- Run against kconsulting database

-- Step 1: Add new columns to hr_employees
ALTER TABLE hr_employees
  ADD COLUMN user_id          INT           NULL AFTER employee_id,
  ADD COLUMN bio              TEXT          NULL,
  ADD COLUMN emergency_contact VARCHAR(100) NULL,
  ADD COLUMN emergency_phone  VARCHAR(20)   NULL,
  ADD COLUMN address          TEXT          NULL,
  ADD COLUMN date_of_birth    DATE          NULL,
  ADD COLUMN national_id      VARCHAR(30)   NULL,
  ADD COLUMN role             VARCHAR(50)   NULL,
  ADD COLUMN updated_at       TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  ADD CONSTRAINT fk_hr_emp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  ADD UNIQUE KEY uq_hr_emp_user (user_id);

-- Step 2: Auto-link employees to users by matching email
UPDATE hr_employees e
JOIN   users u ON LOWER(e.email) = LOWER(u.email)
SET    e.user_id = u.id;

-- Step 3: Seed role from users where linked
UPDATE hr_employees e
JOIN   users u ON e.user_id = u.id
SET    e.role = u.role
WHERE  e.role IS NULL;
