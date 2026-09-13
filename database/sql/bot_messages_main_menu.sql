-- HORTON: Main menu bot messages/buttons
-- Target table: bot_messages
-- Locale: fa
-- Safe to re-run: existing keys are replaced.

DELETE FROM bot_messages
WHERE locale = 'fa'
  AND `key` IN (
    'menu.home',
    'menu.coming_soon',
    'menu.back_button',
    'menu.renew',
    'menu.shop',
    'menu.test_account',
    'menu.wallet',
    'menu.services',
    'menu.plans',
    'menu.referral',
    'menu.tutorials',
    'menu.support',
    'menu.representative',
    'menu.currency_irr',
    'menu.no_username',
    'menu.phone_not_registered'
  );

INSERT INTO bot_messages
    (`key`, `locale`, `text`, `type`, `button_type`, `description`, `is_active`, `created_at`, `updated_at`)
VALUES
    ('menu.home', 'fa', 'سلام {name} 🌷\n\nشناسه کاربری: {username}\nشماره تلفن: {phone}\nموجودی کیف پول: {balance}\n\nاز منوی زیر یکی از گزینه‌ها را انتخاب کنید:', 'message', NULL, 'متن منوی اصلی کاربر', 1, NOW(), NOW()),
    ('menu.coming_soon', 'fa', 'این بخش به‌زودی فعال خواهد شد.', 'message', NULL, 'پیام موقت بخش‌های در حال توسعه', 1, NOW(), NOW()),
    ('menu.back_button', 'fa', '🔙 بازگشت', 'button', 'inline', 'دکمه بازگشت به منوی اصلی', 1, NOW(), NOW()),
    ('menu.renew', 'fa', '🔄 تمدید سرویس', 'button', 'inline', 'دکمه تمدید سرویس', 1, NOW(), NOW()),
    ('menu.shop', 'fa', '🛍 فروشگاه', 'button', 'inline', 'دکمه فروشگاه', 1, NOW(), NOW()),
    ('menu.test_account', 'fa', '🧪 اکانت تست', 'button', 'inline', 'دکمه اکانت تست', 1, NOW(), NOW()),
    ('menu.wallet', 'fa', '💰 کیف پول', 'button', 'inline', 'دکمه کیف پول', 1, NOW(), NOW()),
    ('menu.services', 'fa', '📦 سرویس‌های من', 'button', 'inline', 'دکمه سرویس‌های کاربر', 1, NOW(), NOW()),
    ('menu.plans', 'fa', '📋 پلن‌ها', 'button', 'inline', 'دکمه مشاهده پلن‌ها', 1, NOW(), NOW()),
    ('menu.referral', 'fa', '👥 دعوت دوستان', 'button', 'inline', 'دکمه دعوت و معرفی دوستان', 1, NOW(), NOW()),
    ('menu.tutorials', 'fa', '📚 آموزش‌ها', 'button', 'inline', 'دکمه آموزش‌ها', 1, NOW(), NOW()),
    ('menu.support', 'fa', '🎧 پشتیبانی', 'button', 'inline', 'دکمه پشتیبانی', 1, NOW(), NOW()),
    ('menu.representative', 'fa', '👤 نمایندگی', 'button', 'inline', 'دکمه نمایندگی', 1, NOW(), NOW()),
    ('menu.currency_irr', 'fa', 'تومان', 'message', NULL, 'واحد نمایش موجودی کیف پول', 1, NOW(), NOW()),
    ('menu.no_username', 'fa', 'ثبت نشده', 'message', NULL, 'Fallback قدیمی برای username؛ نباید مانع اجرای منو شود', 1, NOW(), NOW()),
    ('menu.phone_not_registered', 'fa', 'ثبت نشده', 'message', NULL, 'Fallback برای شماره تلفن ثبت‌نشده', 1, NOW(), NOW());
