-- Adds the two-stage mileage verification workflow:
--   1) Office verifies a driver's claim against Access Care Planning by
--      recording the day's route total plus home<->work commute legs
--      (which Access doesn't include), producing a computed expected total.
--   2) A Manager/Director gives final approval once verification is done.
-- Run in phpMyAdmin after selecting the Facilitate database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Office-maintained carer home addresses, looked up by driver name during
-- verification. Not tied to accounts.ID since driver-portal submissions
-- (no login) only carry a free-text driver name, same as mileage_entries.
CREATE TABLE IF NOT EXISTS carer_directory (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_name VARCHAR(190) NOT NULL,
  first_name VARCHAR(100) NOT NULL DEFAULT '',
  last_name VARCHAR(100) NOT NULL DEFAULT '',
  home_address VARCHAR(255) NOT NULL DEFAULT '',
  address_line1 VARCHAR(190) NULL DEFAULT NULL,
  address_line2 VARCHAR(190) NULL DEFAULT NULL,
  town_city VARCHAR(120) NULL DEFAULT NULL,
  county VARCHAR(120) NULL DEFAULT NULL,
  postcode VARCHAR(20) NULL DEFAULT NULL,
  notes VARCHAR(255) NULL DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_carer_directory_name (driver_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- If carer_directory already exists on this install (table created before
-- these columns existed), add them individually -- same fallback the PHP
-- layer performs at request time via ensureCarerDirectoryTable().
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER driver_name;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(190) NULL AFTER home_address;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(190) NULL AFTER address_line1;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS town_city VARCHAR(120) NULL AFTER address_line2;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS county VARCHAR(120) NULL AFTER town_city;
ALTER TABLE carer_directory ADD COLUMN IF NOT EXISTS postcode VARCHAR(20) NULL AFTER county;

-- Verification breakdown. expected_system_mileage (already existed) becomes
-- the SUM of these three -- the office no longer types one lump "Access"
-- number, they enter each leg and the total is computed:
--   access_run_total_mileage       = Access Care Planning's client-to-client
--                                     route total for the day (does not
--                                     include the commute to/from home)
--   home_to_first_client_mileage   = driver's home -> colleague's house
--                                     (if applicable, varies daily) ->
--                                     first client of the day
--   last_client_to_home_mileage    = last client -> colleague's house
--                                     (if applicable) -> driver's home
-- The existing passenger_pickup_mileage field continues to serve as the
-- optional flat allowance (10 miles/day) already in use.
ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS access_run_total_mileage DECIMAL(10,2) NULL AFTER expected_system_mileage;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS home_to_first_client_mileage DECIMAL(10,2) NULL AFTER access_run_total_mileage;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS last_client_to_home_mileage DECIMAL(10,2) NULL AFTER home_to_first_client_mileage;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS colleague_address VARCHAR(255) NULL AFTER last_client_to_home_mileage;

-- Half-day colleague swap: on a shift split between two colleagues, the
-- driver drops the morning colleague home at lunch and collects the
-- afternoon colleague on the way back out. Neither leg is on the Access
-- route, so it's recorded and added to the expected total the same way the
-- home<->work commute legs are.
ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS is_half_day_swap TINYINT(1) NOT NULL DEFAULT 0 AFTER colleague_address;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS midday_colleague_swap_mileage DECIMAL(10,2) NULL AFTER is_half_day_swap;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS midday_dropoff_colleague_address VARCHAR(255) NULL AFTER midday_colleague_swap_mileage;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS midday_pickup_colleague_address VARCHAR(255) NULL AFTER midday_dropoff_colleague_address;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER midday_pickup_colleague_address;

-- New intermediate status: office has assembled/verified the totals against
-- Access, now awaiting a Manager/Director's final sign-off.
ALTER TABLE mileage_entries
  MODIFY COLUMN admin_status ENUM('draft','submitted','pending_review','pending_manager_approval','approved','rejected','adjusted') NOT NULL DEFAULT 'draft';

-- Seed the new "Final Mileage Approval" permission so it's visible in the
-- Role Access matrix immediately (director/manager true, others false).
-- The frontend also carries these defaults in src/utils/accessControl.js.
INSERT INTO role_permissions (role_key, permission_key, is_allowed)
VALUES
  ('director', 'mileage.final_approval', 1),
  ('manager', 'mileage.final_approval', 1),
  ('care_coordinator', 'mileage.final_approval', 0),
  ('carer', 'mileage.final_approval', 0)
ON DUPLICATE KEY UPDATE
  is_allowed = VALUES(is_allowed),
  updated_at = CURRENT_TIMESTAMP;
