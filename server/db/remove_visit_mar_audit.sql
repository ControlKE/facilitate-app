-- Optional cleanup for the removed Visit & MAR Audit feature.
-- Run in phpMyAdmin after selecting the Facilitate database.
-- If your database name is different locally, update the USE line first.

USE facilitatecareservices_co_ukfacilitate;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DELETE FROM role_permissions
WHERE permission_key = 'audit.visit_mar';

DROP TABLE IF EXISTS audit_findings;
DROP TABLE IF EXISTS audits;
