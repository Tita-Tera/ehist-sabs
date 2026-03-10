-- Run this if you already have the DB and need notifications or password_reset_tokens.
-- New installs: use schema.sql which already includes these.

CREATE TABLE IF NOT EXISTS `notifications` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `type`           VARCHAR(32) NOT NULL DEFAULT 'info',
    `title`          VARCHAR(255) NOT NULL,
    `body`           TEXT,
    `reference_type` VARCHAR(64) NULL,
    `reference_id`   INT UNSIGNED NULL,
    `read_at`        DATETIME NULL DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `read_at` (`read_at`),
    CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255) NOT NULL,
    `token`      VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `email` (`email`),
    KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
