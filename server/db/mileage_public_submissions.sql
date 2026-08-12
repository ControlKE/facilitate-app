-- Adds support for public (unauthenticated) driver mileage submissions --
-- single entries and photo-of-paper-form uploads -- landing directly in
-- mileage_entries so they appear in the existing Admin Mileage Review /
-- Weekly Breakdown screens as new entries awaiting office review.
-- Run in phpMyAdmin after selecting the Facilitate database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS source ENUM('office','driver_portal') NOT NULL DEFAULT 'office' AFTER driver_name;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS submitter_phone VARCHAR(40) NULL AFTER source;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS submitter_email VARCHAR(191) NULL AFTER submitter_phone;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) NULL AFTER submitter_email;

-- Raw odometer readings as written on the paper form. Recorded for
-- reference only -- odometer_start/odometer_end (used by the payable
-- mileage calculation) are always driven by the "Mileage" figure the
-- driver enters directly, matching the office's existing paper-form
-- process. These two columns are not processed anywhere.
ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS driver_odometer_start DECIMAL(10,2) NULL AFTER photo_path;

ALTER TABLE mileage_entries
  ADD COLUMN IF NOT EXISTS driver_odometer_end DECIMAL(10,2) NULL AFTER driver_odometer_start;

ALTER TABLE mileage_entries
  ADD INDEX IF NOT EXISTS idx_mileage_entries_source (source);

-- Table is normally created on demand by messageRoutingHelper.php; create it
-- here too so this migration works even before that code has run once.
CREATE TABLE IF NOT EXISTS message_routing_settings (
    category_key VARCHAR(80) NOT NULL,
    recipient_emails TEXT NOT NULL,
    updated_by_account_id INT UNSIGNED NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (category_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the "Mileage Submissions" notification category so the office can
-- assign a recipient from the existing Email Routing settings screen
-- (User Management > Inbox Email Routing) without extra setup.
INSERT INTO message_routing_settings (category_key, recipient_emails)
SELECT 'mileage_submissions', recipient_emails
FROM message_routing_settings
WHERE category_key = 'general_enquiries'
ON DUPLICATE KEY UPDATE recipient_emails = VALUES(recipient_emails);
