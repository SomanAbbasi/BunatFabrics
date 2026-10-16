-- ---------------------------------------------------------------------------
-- Bunat Fabrics — schema
-- ---------------------------------------------------------------------------
-- Run by database/migrate.php. Safe to re-run: everything is IF NOT EXISTS.
-- ---------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `bunat_fabrics`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `bunat_fabrics`;

CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(190) NOT NULL,
    `password`   VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    `role`       ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
