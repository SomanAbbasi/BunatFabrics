-- ---------------------------------------------------------------------------
-- Bunat Fabrics — seed data
-- ---------------------------------------------------------------------------
-- Passwords below are bcrypt hashes (never plain text).
--   admin@gmail.com  ->  Admin@123
--   demo@bunat.com   ->  Demo@123
--
-- migrate.php also re-syncs the admin account from ADMIN_EMAIL/ADMIN_PASSWORD
-- in .env, so those values always stay the source of truth.
-- Safe to re-run: duplicates are updated in place.
-- ---------------------------------------------------------------------------

USE `bunat_fabrics`;

INSERT INTO `users` (`name`, `email`, `password`, `role`)
VALUES
    (
        'Site Admin',
        'admin@gmail.com',
        '$2y$10$hDY1S3PJ51oLPp0/jLrsmeCi3xtXzUjLPGw1y4ojMpFt6UVYiiuKC',
        'admin'
    ),
    (
        'Demo User',
        'demo@bunat.com',
        '$2y$10$saHrMRCTbNWsmnquL1yqLudlSoRlqSYk1ZIQ67UDV5MzDFkXtfN9a',
        'user'
    )
ON DUPLICATE KEY UPDATE
    `name`     = VALUES(`name`),
    `password` = VALUES(`password`),
    `role`     = VALUES(`role`);
