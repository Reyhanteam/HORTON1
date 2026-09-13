-- HORTON notification message catalog (fa)
-- Runtime Telegram text is read from bot_messages through BotMessageStore.
-- Execute this file manually in phpMyAdmin when initializing notification content.

INSERT INTO bot_messages (`key`, `locale`, `type`, `button_type`, `description`, `text`, `is_active`, `created_at`, `updated_at`) VALUES
('notifications.account.registration_success.title','fa','message',NULL,'Notification title: registration success','ثبت‌نام با موفقیت انجام شد',1,NOW(),NOW()),
('notifications.account.registration_success.body','fa','message',NULL,'Notification body: registration success','سلام {name} 🌷 حساب شما با موفقیت فعال شد و می‌توانید از ربات استفاده کنید.',1,NOW(),NOW()),
('notifications.account.phone_verification_success.title','fa','message',NULL,'Notification title: phone verification success','تأیید شماره تلفن',1,NOW(),NOW()),
('notifications.account.phone_verification_success.body','fa','message',NULL,'Notification body: phone verification success','شماره تلفن شما با موفقیت تأیید شد.',1,NOW(),NOW()),
('notifications.account.status_changed.title','fa','message',NULL,'Notification title: account status changed','تغییر وضعیت حساب',1,NOW(),NOW()),
('notifications.account.status_changed.body','fa','message',NULL,'Notification body: account status changed','وضعیت حساب شما به «{status}» تغییر کرد.',1,NOW(),NOW()),

('notifications.payment.success.title','fa','message',NULL,'Notification title: payment success','پرداخت موفق',1,NOW(),NOW()),
('notifications.payment.success.body','fa','message',NULL,'Notification body: payment success','پرداخت شما به مبلغ {amount} {currency} با موفقیت انجام شد. شماره پرداخت: {payment_id}',1,NOW(),NOW()),
('notifications.payment.failed.title','fa','message',NULL,'Notification title: payment failed','پرداخت ناموفق',1,NOW(),NOW()),
('notifications.payment.failed.body','fa','message',NULL,'Notification body: payment failed','پرداخت شما برای سفارش {order_id} ناموفق بود. مبلغ: {amount} {currency}',1,NOW(),NOW()),
('notifications.payment.pending.title','fa','message',NULL,'Notification title: manual payment pending','پرداخت در انتظار بررسی',1,NOW(),NOW()),
('notifications.payment.pending.body','fa','message',NULL,'Notification body: manual payment pending','رسید پرداخت دستی شما ثبت شد و در انتظار بررسی است. سفارش: {order_id} — مبلغ: {amount} {currency}',1,NOW(),NOW()),
('notifications.payment.manual_approved.title','fa','message',NULL,'Notification title: manual payment approved','تأیید پرداخت دستی',1,NOW(),NOW()),
('notifications.payment.manual_approved.body','fa','message',NULL,'Notification body: manual payment approved','پرداخت دستی شما تأیید شد و سفارش {order_id} وارد فرایند انجام شد.',1,NOW(),NOW()),
('notifications.payment.manual_rejected.title','fa','message',NULL,'Notification title: manual payment rejected','رد پرداخت دستی',1,NOW(),NOW()),
('notifications.payment.manual_rejected.body','fa','message',NULL,'Notification body: manual payment rejected','پرداخت دستی شما تأیید نشد. در صورت نیاز دوباره پرداخت را انجام دهید یا با پشتیبانی تماس بگیرید.',1,NOW(),NOW()),

('notifications.wallet.credited.title','fa','message',NULL,'Notification title: wallet credited','افزایش موجودی کیف پول',1,NOW(),NOW()),
('notifications.wallet.credited.body','fa','message',NULL,'Notification body: wallet credited','مبلغ {amount} به کیف پول شما اضافه شد. موجودی جدید: {balance_after}',1,NOW(),NOW()),
('notifications.wallet.debited.title','fa','message',NULL,'Notification title: wallet debited','کاهش موجودی کیف پول',1,NOW(),NOW()),
('notifications.wallet.debited.body','fa','message',NULL,'Notification body: wallet debited','مبلغ {amount} از کیف پول شما کسر شد. موجودی جدید: {balance_after}',1,NOW(),NOW()),

('notifications.order.created.title','fa','message',NULL,'Notification title: order created','ثبت سفارش',1,NOW(),NOW()),
('notifications.order.created.body','fa','message',NULL,'Notification body: order created','سفارش شما با شماره {order_id} ثبت شد. مبلغ سفارش: {amount} {currency}',1,NOW(),NOW()),
('notifications.order.processing.title','fa','message',NULL,'Notification title: order processing','در حال پردازش سفارش',1,NOW(),NOW()),
('notifications.order.processing.body','fa','message',NULL,'Notification body: order processing','سفارش {order_id} در حال پردازش و آماده‌سازی است.',1,NOW(),NOW()),
('notifications.order.completed.title','fa','message',NULL,'Notification title: order completed','تکمیل سفارش',1,NOW(),NOW()),
('notifications.order.completed.body','fa','message',NULL,'Notification body: order completed','سفارش {order_id} با موفقیت تکمیل شد.',1,NOW(),NOW()),
('notifications.order.failed.title','fa','message',NULL,'Notification title: order failed','خطا در سفارش',1,NOW(),NOW()),
('notifications.order.failed.body','fa','message',NULL,'Notification body: order failed','در پردازش سفارش {order_id} خطایی رخ داد. وضعیت سفارش: ناموفق.',1,NOW(),NOW()),

('notifications.service.expiring_3_days.title','fa','message',NULL,'Notification title: service expires in three days','یادآوری پایان سرویس',1,NOW(),NOW()),
('notifications.service.expiring_3_days.body','fa','message',NULL,'Notification body: service expires in three days','سرویس شما کمتر از سه روز دیگر به پایان می‌رسد. زمان پایان: {expires_at}',1,NOW(),NOW()),
('notifications.service.expiring_24_hours.title','fa','message',NULL,'Notification title: service expires in 24 hours','⚠️ هشدار پایان سرویس',1,NOW(),NOW()),
('notifications.service.expiring_24_hours.body','fa','message',NULL,'Notification body: service expires in 24 hours','سرویس شما کمتر از ۲۴ ساعت دیگر به پایان می‌رسد. برای جلوگیری از قطع سرویس، نسبت به تمدید آن اقدام کنید. زمان پایان: {expires_at}',1,NOW(),NOW()),
('notifications.service.expired.title','fa','message',NULL,'Notification title: service expired','پایان سرویس',1,NOW(),NOW()),
('notifications.service.expired.body','fa','message',NULL,'Notification body: service expired','سرویس شما در تاریخ {expires_at} به پایان رسید.',1,NOW(),NOW()),
('notifications.service.renewal_success.title','fa','message',NULL,'Notification title: renewal success','تمدید موفق سرویس',1,NOW(),NOW()),
('notifications.service.renewal_success.body','fa','message',NULL,'Notification body: renewal success','سرویس شما با موفقیت تمدید شد. زمان پایان جدید: {expires_at}',1,NOW(),NOW()),
('notifications.service.renewal_failed.title','fa','message',NULL,'Notification title: renewal failed','تمدید ناموفق سرویس',1,NOW(),NOW()),
('notifications.service.renewal_failed.body','fa','message',NULL,'Notification body: renewal failed','تمدید سرویس شما ناموفق بود. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.',1,NOW(),NOW()),
('notifications.service.created.title','fa','message',NULL,'Notification title: service created','سرویس آماده شد',1,NOW(),NOW()),
('notifications.service.created.body','fa','message',NULL,'Notification body: service created','سرویس شما با موفقیت ایجاد و فعال شد. زمان پایان: {expires_at}',1,NOW(),NOW()),
('notifications.service.provisioning.title','fa','message',NULL,'Notification title: provisioning in progress','در حال ساخت سرویس',1,NOW(),NOW()),
('notifications.service.provisioning.body','fa','message',NULL,'Notification body: provisioning in progress','درخواست ساخت سرویس شما ثبت شد و سرویس در حال آماده‌سازی است.',1,NOW(),NOW()),
('notifications.service.capacity_increased.title','fa','message',NULL,'Notification title: capacity increased','افزایش ظرفیت سرویس',1,NOW(),NOW()),
('notifications.service.capacity_increased.body','fa','message',NULL,'Notification body: capacity increased','ظرفیت سرویس شما با موفقیت افزایش یافت. ظرفیت جدید: {capacity}',1,NOW(),NOW()),
('notifications.service.disabled.title','fa','message',NULL,'Notification title: service disabled','غیرفعال شدن سرویس',1,NOW(),NOW()),
('notifications.service.disabled.body','fa','message',NULL,'Notification body: service disabled','سرویس شما غیرفعال شد. در صورت نیاز برای بررسی علت با پشتیبانی تماس بگیرید.',1,NOW(),NOW()),
('notifications.service.reactivated.title','fa','message',NULL,'Notification title: service reactivated','فعال شدن دوباره سرویس',1,NOW(),NOW()),
('notifications.service.reactivated.body','fa','message',NULL,'Notification body: service reactivated','سرویس شما دوباره فعال شد و قابل استفاده است.',1,NOW(),NOW()),

('notifications.marketing.discount_applied.title','fa','message',NULL,'Notification title: discount applied','اعمال تخفیف',1,NOW(),NOW()),
('notifications.marketing.discount_applied.body','fa','message',NULL,'Notification body: discount applied','کد تخفیف {discount_code} با موفقیت روی سفارش {order_id} اعمال شد. مبلغ تخفیف: {discount_amount}',1,NOW(),NOW()),
('notifications.marketing.gift_redeemed.title','fa','message',NULL,'Notification title: gift redeemed','هدیه دریافت شد',1,NOW(),NOW()),
('notifications.marketing.gift_redeemed.body','fa','message',NULL,'Notification body: gift redeemed','کد هدیه {gift_code} با موفقیت استفاده شد. ارزش هدیه: {gift_value}',1,NOW(),NOW()),
('notifications.marketing.referral_reward.title','fa','message',NULL,'Notification title: referral reward','پاداش دعوت',1,NOW(),NOW()),
('notifications.marketing.referral_reward.body','fa','message',NULL,'Notification body: referral reward','دعوت شما با موفقیت واجد شرایط شد و پاداش ارجاع برای حساب شما ثبت شد.',1,NOW(),NOW()),
('notifications.marketing.cashback_credited.title','fa','message',NULL,'Notification title: cashback credited','دریافت کش‌بک',1,NOW(),NOW()),
('notifications.marketing.cashback_credited.body','fa','message',NULL,'Notification body: cashback credited','مبلغ {amount} کش‌بک به حساب شما اضافه شد.',1,NOW(),NOW()),

('notifications.support.ticket_replied.title','fa','message',NULL,'Notification title: support ticket replied','پاسخ پشتیبانی',1,NOW(),NOW()),
('notifications.support.ticket_replied.body','fa','message',NULL,'Notification body: support ticket replied','برای تیکت پشتیبانی شماره {ticket_id} پاسخ جدید ثبت شده است.',1,NOW(),NOW()),
('notifications.support.ticket_status_changed.title','fa','message',NULL,'Notification title: support ticket status changed','تغییر وضعیت تیکت',1,NOW(),NOW()),
('notifications.support.ticket_status_changed.body','fa','message',NULL,'Notification body: support ticket status changed','وضعیت تیکت پشتیبانی شماره {ticket_id} به «{status}» تغییر کرد.',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE
    `type` = VALUES(`type`),
    `button_type` = VALUES(`button_type`),
    `description` = VALUES(`description`),
    `text` = VALUES(`text`),
    `is_active` = VALUES(`is_active`),
    `updated_at` = NOW();

-- Recommended notification settings in bot_settings:
-- notifications.enabled = true (boolean)
-- notifications.service_expiry.3_days.enabled = true (boolean)
-- notifications.service_expiry.24_hours.enabled = true (boolean)
