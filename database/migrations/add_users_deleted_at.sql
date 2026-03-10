-- Add soft-delete support to users (run once if schema was created before this column existed)
-- Ignore error if column already exists.
ALTER TABLE `users` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `name`;
ALTER TABLE `users` ADD KEY `deleted_at` (`deleted_at`);
