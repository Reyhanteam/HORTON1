-- Permissions used by the notification/broadcast admin endpoints.
-- Assign these permissions to the appropriate existing admin role in the dashboard.

INSERT INTO permissions (`name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
('View notifications','notifications.view','View generated notifications and delivery state.',NOW(),NOW()),
('View broadcasts','broadcasts.view','View broadcast definitions and delivery counters.',NOW(),NOW()),
('Create broadcasts','broadcasts.create','Create and schedule broadcasts.',NOW(),NOW()),
('Send broadcasts','broadcasts.send','Queue a broadcast for delivery.',NOW(),NOW()),
('Cancel broadcasts','broadcasts.cancel','Cancel a queued or sending broadcast.',NOW(),NOW())
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();
