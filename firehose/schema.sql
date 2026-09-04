-- overheard: MariaDB schema for the firehose consumer.
-- Run once against an empty database: mysql -u root -p overheard < schema.sql

CREATE TABLE IF NOT EXISTS fragments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_uri VARCHAR(255) NOT NULL,
  author_did VARCHAR(255) NOT NULL,
  text TEXT NOT NULL,
  captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_source_uri (source_uri),
  KEY idx_captured_at (captured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS haiku (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  line1 VARCHAR(255) NOT NULL,
  line2 VARCHAR(255) NOT NULL,
  line3 VARCHAR(255) NOT NULL,
  source_uris JSON NOT NULL,
  captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_captured_at (captured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Single row holding the Jetstream cursor, so a restart resumes roughly
-- where it left off instead of replaying the firehose from now.
CREATE TABLE IF NOT EXISTS consumer_state (
  id TINYINT UNSIGNED PRIMARY KEY,
  last_cursor BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Retention: fragments carry a public DID + public post text, but there's
-- no reason to keep the raw feed indefinitely once haiku have been pulled
-- from it. A cron entry running something like the line below keeps only
-- a rolling window of raw fragments while `haiku` (the actual output)
-- accumulates permanently:
--
--   DELETE FROM fragments WHERE captured_at < NOW() - INTERVAL 30 DAY;
