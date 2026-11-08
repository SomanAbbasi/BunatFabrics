CREATE TABLE IF NOT EXISTS auth_limits (
    bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    hits INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    INDEX auth_limit_expiry (expires_at)
) ENGINE=InnoDB;
