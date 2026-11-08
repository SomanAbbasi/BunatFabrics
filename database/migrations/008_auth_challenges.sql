CREATE TABLE IF NOT EXISTS auth_challenges (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    purpose VARCHAR(20) NOT NULL,
    secret_hash VARCHAR(255) NOT NULL,
    payload VARCHAR(190) NULL,
    account_stamp CHAR(64) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX auth_challenge_user (user_id, purpose),
    INDEX auth_challenge_expiry (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
