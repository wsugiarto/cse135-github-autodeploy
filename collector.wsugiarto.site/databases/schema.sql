CREATE DATABASE IF NOT EXISTS cse135_collector
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cse135_collector;
CREATE TABLE IF NOT EXISTS pageviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(64) NOT NULL,
  pageview_id VARCHAR(64) NOT NULL,
  page TEXT NULL,
  time_start_ms BIGINT NULL,
  user_agent TEXT NULL,
  language VARCHAR(32) NULL,
  cookies_enabled TINYINT(1) NULL,
  js_enabled TINYINT(1) NULL,
  images_enabled TINYINT(1) NULL,
  css_enabled TINYINT(1) NULL,
  screen_json JSON NULL,
  window_json JSON NULL,
  network_json JSON NULL,
  performance_json JSON NULL,
  received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_pageview (pageview_id),
  KEY idx_session (session_id)
);
CREATE TABLE IF NOT EXISTS activity_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(64) NOT NULL,
  pageview_id VARCHAR(64) NOT NULL,
  page TEXT NULL,
  reason VARCHAR(64) NULL,
  sent_ts_ms BIGINT NULL,
  events_count INT NOT NULL DEFAULT 0,
  raw_json JSON NOT NULL,
  received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_session (session_id),
  KEY idx_pageview (pageview_id)
);
CREATE TABLE IF NOT EXISTS activity_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  session_id VARCHAR(64) NOT NULL,
  pageview_id VARCHAR(64) NOT NULL,
  type VARCHAR(64) NOT NULL,
  ts_ms BIGINT NULL,
  page TEXT NULL,
  data_json JSON NULL,
  received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_type (type),
  KEY idx_pageview (pageview_id),
  CONSTRAINT fk_batch
    FOREIGN KEY (batch_id) REFERENCES activity_batches(id)
    ON DELETE CASCADE
);