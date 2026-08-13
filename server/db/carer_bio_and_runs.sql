-- Extends the Carer Directory with full bio data (title, mobile, email) and
-- adds a maintainable "Runs" list (the geographic rounds/areas carers work),
-- plus per-entry columns so a driver can record who they worked with and
-- which run they were on for each day's mileage row.
-- Run in phpMyAdmin after selecting the Facilitate database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE carer_directory
  ADD COLUMN IF NOT EXISTS title VARCHAR(10) NULL AFTER last_name;

ALTER TABLE carer_directory
  ADD COLUMN IF NOT EXISTS mobile_phone VARCHAR(40) NULL AFTER postcode;

ALTER TABLE carer_directory
  ADD COLUMN IF NOT EXISTS email VARCHAR(191) NULL AFTER mobile_phone;

-- Runs: the geographic rounds carers are assigned to (imported from the
-- "Area" grouping on the employee contact list). Editable from the Run
-- Directory admin screen -- this seed just gives it a sensible starting set.
CREATE TABLE IF NOT EXISTS mileage_runs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_mileage_runs_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mileage_runs (name) VALUES
  ('B90'), ('Bedworth'), ('Bulkington'), ('Coventry'), ('CV7'),
  ('Harbury/Kineton'), ('Kenilworth'), ('Leamington'), ('Nuneaton'),
  ('Office'), ('Other'), ('Shipston-On-Stour'), ('South Rural'),
  ('Stratford'), ('Warwick')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Per-entry: which colleague the driver worked with that day and which run
-- they were on -- both can vary day to day, so they live on the entry, not
-- on the driver/submission as a whole.
ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS colleague_name VARCHAR(190) NULL AFTER vehicle_registration;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS run_name VARCHAR(190) NULL AFTER colleague_name;
