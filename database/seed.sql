-- Seed data for testing bookings: one provider + one service.
-- Run once after schema.sql. Provider password: provider123

INSERT INTO `users` (`role_id`, `email`, `password`, `name`)
VALUES (2, 'provider@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Provider')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO `services` (`provider_id`, `name`, `description`, `duration_min`)
SELECT id, 'Consultation', '30-min consultation', 30 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;

-- Sample time slots for the test provider (adjust dates for your tests)
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '09:00:00', '09:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, CURDATE(), '10:00:00', '10:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
INSERT INTO `time_slots` (`provider_id`, `slot_date`, `start_time`, `end_time`, `is_available`)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '14:00:00', '14:30:00', 1 FROM `users` WHERE email = 'provider@example.com' LIMIT 1;
