-- Apply this migration to existing installations before deploying the updated API.
-- The table stores only a SHA-256 key derived from normalized email and remote IP.
CREATE TABLE IF NOT EXISTS auth_rate_limits (
    key_hash CHAR(64) NOT NULL PRIMARY KEY,
    window_started_at DATETIME NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_auth_rate_limits_window (window_started_at)
) ENGINE=InnoDB;
