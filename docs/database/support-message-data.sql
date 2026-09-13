-- HORTON support Telegram message/button catalog (fa)
-- Runtime Telegram UI text is read from bot_messages.
-- FAQ/Tutorial answers themselves live in support_contents and are managed from the Admin Dashboard.

INSERT INTO bot_messages (`key`, `locale`, `type`, `button_type`, `description`, `text`, `is_active`, `created_at`, `updated_at`) VALUES
('support.faq_gate','fa','message',NULL,'Support entry: ask whether FAQ was reviewed','قبل از ارسال درخواست پشتیبانی، بهتر است ابتدا سوالات متداول را مطالعه کنید. آیا سوالات متداول را مطالعه کرده‌اید؟',1,NOW(),NOW()),
('support.faq_title','fa','message',NULL,'FAQ heading','سوالات متداول',1,NOW(),NOW()),
('support.faq_empty','fa','message',NULL,'Empty FAQ fallback','در حال حاضر سوال متداولی ثبت نشده است. می‌توانید درخواست پشتیبانی خود را ارسال کنید.',1,NOW(),NOW()),
('support.choose_department','fa','message',NULL,'Department selection prompt','لطفاً بخش موردنظر برای پیگیری درخواست خود را انتخاب کنید.',1,NOW(),NOW()),
('support.choose_sensitivity','fa','message',NULL,'Sensitivity selection prompt','میزان حساسیت درخواست را مشخص کنید.',1,NOW(),NOW()),
('support.write_message','fa','message',NULL,'Support message prompt','حالا پیام خود را ارسال کنید. پیام می‌تواند شامل متن، عکس، ویدیو یا فایل باشد.',1,NOW(),NOW()),
('support.created','fa','message',NULL,'Ticket created confirmation','درخواست پشتیبانی شما با شماره #{ticket_id} ثبت شد. پاسخ تیم پشتیبانی پس از بررسی برای شما ارسال می‌شود.',1,NOW(),NOW()),
('support.no_departments','fa','message',NULL,'No active support departments','در حال حاضر هیچ بخش پشتیبانی فعالی وجود ندارد. لطفاً بعداً دوباره تلاش کنید.',1,NOW(),NOW()),
('support.no_tickets','fa','message',NULL,'No tickets','هنوز درخواست پشتیبانی ثبت نکرده‌اید.',1,NOW(),NOW()),
('support.ticket_list','fa','message',NULL,'Ticket list','درخواست‌های پشتیبانی شما:\n{tickets}',1,NOW(),NOW()),
('support.faq_not_read','fa','button',NULL,'FAQ gate negative button','خیر، مطالعه نکردم',1,NOW(),NOW()),
('support.faq_read','fa','button',NULL,'FAQ gate positive button','بله، مطالعه کردم',1,NOW(),NOW()),
('support.continue','fa','button',NULL,'Continue to support after FAQ','ادامه و ارسال درخواست',1,NOW(),NOW()),
('support.sensitivity_low','fa','button',NULL,'Low sensitivity','کم',1,NOW(),NOW()),
('support.sensitivity_normal','fa','button',NULL,'Normal sensitivity','عادی',1,NOW(),NOW()),
('support.sensitivity_high','fa','button',NULL,'High sensitivity','زیاد',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE
    `type` = VALUES(`type`),
    `button_type` = VALUES(`button_type`),
    `description` = VALUES(`description`),
    `text` = VALUES(`text`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();
