-- Allows Google-only customers without manufacturing a local password.
-- Repeating MODIFY preserves all existing hashes and is safe after partial migration.
ALTER TABLE users MODIFY password VARCHAR(255) NULL COMMENT 'bcrypt hash; NULL for Google-only accounts';
