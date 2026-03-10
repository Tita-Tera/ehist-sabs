-- Admin and Service Provider accounts + time slots for booking.
-- Run after schema.sql. Log in with the email and password below.

-- Admin: email admin@example.com, password admin1234
INSERT INTO `users` (`role_id`, `email`, `password`, `name`)
VALUES (1, 'admin@example.com', '$2y$10$UrK4WRDWqx0mdXFqW1bprOlSc/9f72HbvoWSzEuAWC02FwZL80sqm', 'Admin')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`), `role_id` = VALUES(`role_id`);

-- Service Provider: email provider@example.com, password provider1234
INSERT INTO `users` (`role_id`, `email`, `password`, `name`)
VALUES (2, 'provider@example.com', '$2y$10$HjQlZtaK7g1oH26/Hwd.6ufj77Row47u97tr1dll7.YVXrAxKXEdq', 'Provider')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`), `role_id` = VALUES(`role_id`);

-- Service for the provider (insert only if this provider has no services yet)
INSERT INTO `services` (`provider_id`, `name`, `description`, `duration_min`)
SELECT u.id, 'Consultation', '30-min consultation', 30 FROM `users` u
WHERE u.email = 'provider@example.com' AND NOT EXISTS (SELECT 1 FROM `services` s WHERE s.provider_id = u.id) LIMIT 1;

-- Remove old time slots for this provider so re-running seed gives a clean set
DELETE FROM `time_slots` WHERE `provider_id` IN (SELECT id FROM (SELECT id FROM `users` WHERE email = 'provider@example.com') AS u);

-- Time slots: today and next 7 days, multiple slots per day
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '10:00:00', '10:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '11:00:00', '11:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '14:00:00', '14:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '15:00:00', '15:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '10:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '14:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00', '11:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', '10:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '15:00:00', '15:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '14:00:00', '14:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00:00', '10:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '11:00:00', '11:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
