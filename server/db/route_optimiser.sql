-- Route Optimiser schema
-- Run this in phpMyAdmin after selecting the Facilitate database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS route_clients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(160) NOT NULL,
  address_line_1 VARCHAR(190) NOT NULL,
  address_line_2 VARCHAR(190) NOT NULL DEFAULT '',
  town_city VARCHAR(120) NOT NULL,
  county VARCHAR(120) NOT NULL DEFAULT '',
  postcode VARCHAR(24) NOT NULL,
  notes TEXT NULL,
  preferred_call_type VARCHAR(80) NOT NULL DEFAULT '',
  area_zone VARCHAR(80) NOT NULL DEFAULT '',
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY route_clients_name_idx (full_name),
  KEY route_clients_postcode_idx (postcode),
  KEY route_clients_active_idx (is_active),
  KEY route_clients_zone_idx (area_zone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_name VARCHAR(160) NOT NULL,
  run_date DATE NOT NULL,
  shift_label VARCHAR(80) NOT NULL DEFAULT '',
  assigned_carer_account_id INT UNSIGNED NULL,
  assigned_carer_name VARCHAR(160) NOT NULL DEFAULT '',
  notes TEXT NULL,
  first_call_client_id INT UNSIGNED NULL,
  optimisation_method VARCHAR(40) NOT NULL DEFAULT 'postcode_heuristic',
  generated_at DATETIME NULL,
  manual_override TINYINT(1) NOT NULL DEFAULT 0,
  created_by_account_id INT UNSIGNED NULL,
  updated_by_account_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY route_runs_date_idx (run_date),
  KEY route_runs_shift_idx (shift_label),
  KEY route_runs_first_call_idx (first_call_client_id),
  KEY route_runs_carer_idx (assigned_carer_account_id),
  CONSTRAINT fk_route_runs_first_call
    FOREIGN KEY (first_call_client_id) REFERENCES route_clients(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS route_run_stops (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  run_id INT UNSIGNED NOT NULL,
  client_id INT UNSIGNED NOT NULL,
  route_order INT UNSIGNED NOT NULL,
  is_first_call TINYINT(1) NOT NULL DEFAULT 0,
  manual_override TINYINT(1) NOT NULL DEFAULT 0,
  segment_method VARCHAR(40) NOT NULL DEFAULT '',
  segment_label VARCHAR(190) NOT NULL DEFAULT '',
  segment_distance_km DECIMAL(10,2) NULL,
  segment_score DECIMAL(10,2) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY route_run_stops_run_order_unique (run_id, route_order),
  UNIQUE KEY route_run_stops_run_client_unique (run_id, client_id),
  KEY route_run_stops_client_idx (client_id),
  CONSTRAINT fk_route_run_stops_run
    FOREIGN KEY (run_id) REFERENCES route_runs(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_route_run_stops_client
    FOREIGN KEY (client_id) REFERENCES route_clients(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
