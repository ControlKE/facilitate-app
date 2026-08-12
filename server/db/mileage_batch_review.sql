-- Enables week-level batch approval/rejection (submitWeek + reviewWeek
-- actions in mileage.php). mileage_submissions.status didn't previously
-- allow 'rejected' -- only individual mileage_entries did.
-- Run in phpMyAdmin after selecting the Facilitate database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE mileage_submissions
  MODIFY COLUMN status ENUM('submitted','pending_review','approved','rejected','paid','reopened') NOT NULL DEFAULT 'submitted';
