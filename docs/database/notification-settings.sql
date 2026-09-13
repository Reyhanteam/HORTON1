-- HORTON notification runtime settings.
-- Values are stored in bot_settings and read through SettingsStore.

INSERT INTO bot_settings (`key`, `value`, `type`, `group`, `is_public`, `created_at`, `updated_at`) VALUES
('notifications.enabled','true','boolean','notifications',0,NOW(),NOW()),
('notifications.service_expiry.3_days.enabled','true','boolean','notifications',0,NOW(),NOW()),
('notifications.service_expiry.3_days.hours','72','integer','notifications',0,NOW(),NOW()),
('notifications.service_expiry.24_hours.enabled','true','boolean','notifications',0,NOW(),NOW()),
('notifications.service_expiry.24_hours.hours','24','integer','notifications',0,NOW(),NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `group` = VALUES(`group`),
    `is_public` = VALUES(`is_public`),
    `updated_at` = NOW();
