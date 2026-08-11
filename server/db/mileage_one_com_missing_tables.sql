-- One.com / phpMyAdmin migration for missing Mileage module tables.
-- Run this after selecting database: facilitatecareservices_co_ukfacilitate
-- This script assumes existing app tables like accounts and role_permissions already exist.

USE facilitatecareservices_co_ukfacilitate;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS mileage_entries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  driver_name VARCHAR(190) NOT NULL DEFAULT '',
  work_date DATE NOT NULL,
  submission_week_start DATE NOT NULL,
  submission_week_end DATE NOT NULL,
  starting_location VARCHAR(255) NOT NULL,
  ending_location VARCHAR(255) NOT NULL,
  odometer_start DECIMAL(10,2) NOT NULL,
  odometer_end DECIMAL(10,2) NOT NULL,
  claimed_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  expected_system_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  passenger_pickup_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  midday_payable_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  midday_mileage_reason VARCHAR(255) NULL,
  lunch_home_mileage_deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  went_home_for_lunch TINYINT(1) NOT NULL DEFAULT 0,
  adjusted_claimed_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  difference_from_system DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  threshold_flag TINYINT(1) NOT NULL DEFAULT 0,
  explanation_required TINYINT(1) NOT NULL DEFAULT 0,
  driver_explanation TEXT NULL,
  admin_status ENUM('draft','submitted','pending_review','approved','rejected','adjusted') NOT NULL DEFAULT 'draft',
  admin_adjusted_payable_mileage DECIMAL(10,2) NULL,
  final_payable_mileage DECIMAL(10,2) NULL,
  mileage_rate DECIMAL(5,2) NOT NULL DEFAULT 0.30,
  final_payable_amount DECIMAL(10,2) NULL,
  notes TEXT NULL,
  admin_notes TEXT NULL,
  submitted_at DATETIME NULL,
  reviewed_at DATETIME NULL,
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mileage_entries_user_id (user_id),
  INDEX idx_mileage_entries_work_date (work_date),
  INDEX idx_mileage_entries_status (admin_status),
  INDEX idx_mileage_entries_week (submission_week_start, submission_week_end),
  INDEX idx_mileage_entries_threshold (threshold_flag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mileage_entry_care_staff (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mileage_entry_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mileage_entry_staff (mileage_entry_id, user_id),
  INDEX idx_mileage_staff_user (user_id),
  CONSTRAINT fk_mileage_staff_entry
    FOREIGN KEY (mileage_entry_id) REFERENCES mileage_entries(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mileage_submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  driver_name VARCHAR(190) NOT NULL DEFAULT '',
  week_start DATE NOT NULL,
  week_end DATE NOT NULL,
  status ENUM('submitted','pending_review','approved','paid','reopened') NOT NULL DEFAULT 'submitted',
  total_claimed_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_adjusted_claimed_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_expected_system_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_final_payable_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_payable_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  flagged_count INT UNSIGNED NOT NULL DEFAULT 0,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mileage_submission_week_user (user_id, week_start, week_end),
  INDEX idx_mileage_submissions_week (week_start, week_end),
  INDEX idx_mileage_submissions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mileage_reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  mileage_entry_id BIGINT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NULL,
  reviewer_name VARCHAR(190) NOT NULL DEFAULT '',
  review_status ENUM('approved','rejected','adjusted') NOT NULL,
  adjusted_payable_mileage DECIMAL(10,2) NULL,
  final_payable_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  admin_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_mileage_reviews_entry (mileage_entry_id),
  INDEX idx_mileage_reviews_reviewer (reviewer_id),
  CONSTRAINT fk_mileage_reviews_entry
    FOREIGN KEY (mileage_entry_id) REFERENCES mileage_entries(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mileage_vehicles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  registration VARCHAR(20) NOT NULL,
  label VARCHAR(190) NOT NULL DEFAULT '',
  vehicle_type ENUM('own_vehicle','company_vehicle') NOT NULL DEFAULT 'own_vehicle',
  fuel_card_number VARCHAR(80) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mileage_vehicle_registration (registration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mileage_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(80) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL DEFAULT '',
  updated_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mileage_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mileage_settings (setting_key, setting_value, description)
VALUES
  ('mileage_rate', '0.30', 'Default payable mileage rate in GBP per mile.'),
  ('threshold_miles', '10', 'Miles above expected route mileage before driver explanation and admin review are required.'),
  ('week_starts_on', 'wednesday', 'Mileage submission week starts Wednesday and ends Tuesday.'),
  ('submission_due_day', 'tuesday', 'Weekly mileage forms are submitted every Tuesday.'),
  ('payment_window', 'thursday-friday', 'Mileage is normally paid Thursday or Friday.')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  description = VALUES(description),
  updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS mileage_expected_routes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  driver_name VARCHAR(190) NOT NULL DEFAULT '',
  route_date DATE NOT NULL,
  starting_location VARCHAR(255) NOT NULL,
  ending_location VARCHAR(255) NOT NULL,
  expected_mileage DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  route_source ENUM('manual','import','access_care_planning','optimised') NOT NULL DEFAULT 'manual',
  route_reference VARCHAR(190) NULL,
  route_payload JSON NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_expected_routes_user_date (user_id, route_date),
  INDEX idx_expected_routes_date_source (route_date, route_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO role_permissions (role_key, permission_key, is_allowed)
VALUES
  ('director', 'mileage.claims', 1),
  ('manager', 'mileage.claims', 1),
  ('care_coordinator', 'mileage.claims', 1),
  ('carer', 'mileage.claims', 1)
ON DUPLICATE KEY UPDATE
  is_allowed = VALUES(is_allowed),
  updated_at = CURRENT_TIMESTAMP;

CREATE OR REPLACE VIEW mileage_weekly_carer_totals AS
SELECT
  user_id,
  driver_name,
  submission_week_start AS week_start,
  submission_week_end AS week_end,
  COUNT(*) AS entry_count,
  ROUND(SUM(claimed_mileage), 2) AS total_claimed_mileage,
  ROUND(SUM(lunch_home_mileage_deduction), 2) AS total_lunch_home_deduction,
  ROUND(SUM(midday_payable_mileage), 2) AS total_midday_payable_mileage,
  ROUND(SUM(adjusted_claimed_mileage), 2) AS total_adjusted_claimed_mileage,
  ROUND(SUM(expected_system_mileage), 2) AS total_expected_system_mileage,
  ROUND(SUM(passenger_pickup_mileage), 2) AS total_passenger_pickup_mileage,
  ROUND(SUM(expected_system_mileage + passenger_pickup_mileage), 2) AS total_expected_with_pickup_mileage,
  ROUND(SUM(COALESCE(final_payable_mileage, 0)), 2) AS total_final_payable_mileage,
  ROUND(SUM(COALESCE(final_payable_amount, 0)), 2) AS total_payable_amount,
  SUM(threshold_flag) AS flagged_count,
  SUM(admin_status = 'pending_review') AS pending_review_count,
  SUM(admin_status IN ('approved','adjusted')) AS approved_count,
  SUM(admin_status = 'rejected') AS rejected_count
FROM mileage_entries
WHERE deleted_at IS NULL
GROUP BY user_id, driver_name, submission_week_start, submission_week_end;

CREATE OR REPLACE VIEW mileage_flagged_exceptions AS
SELECT
  id,
  user_id,
  driver_name,
  work_date,
  submission_week_start,
  submission_week_end,
  claimed_mileage,
  lunch_home_mileage_deduction,
  midday_payable_mileage,
  midday_mileage_reason,
  adjusted_claimed_mileage,
  expected_system_mileage,
  passenger_pickup_mileage,
  ROUND(expected_system_mileage + passenger_pickup_mileage, 2) AS expected_total_mileage,
  difference_from_system,
  driver_explanation,
  admin_status,
  admin_notes,
  final_payable_mileage,
  final_payable_amount,
  submitted_at,
  reviewed_at
FROM mileage_entries
WHERE deleted_at IS NULL
  AND threshold_flag = 1;
