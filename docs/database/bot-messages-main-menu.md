# پیام‌ها و دکمه‌های منوی اصلی ربات

این سند فهرست کلیدهایی است که `MainMenuController` برای نمایش منوی اصلی از جدول `bot_messages` می‌خواند.

منبع کد: `app/Telegram/Controllers/MainMenuController.php`

## Message keys

| Key | Type | متن پیشنهادی |
|---|---|---|
| `menu.home` | message | `سلام {name} 🌷\n\nشناسه کاربری: {username}\nشماره تلفن: {phone}\nموجودی کیف پول: {balance}\n\nاز منوی زیر یکی از گزینه‌ها را انتخاب کنید:` |
| `menu.coming_soon` | message | `این بخش به‌زودی فعال خواهد شد.` |
| `menu.currency_irr` | message | `تومان` |
| `menu.no_username` | message | `ثبت نشده` |
| `menu.phone_not_registered` | message | `ثبت نشده` |

## Button keys

| Key | متن پیشنهادی | نوع |
|---|---|---|
| `menu.renew` | `🔄 تمدید سرویس` | inline |
| `menu.shop` | `🛍 فروشگاه` | inline |
| `menu.test_account` | `🧪 اکانت تست` | inline |
| `menu.wallet` | `💰 کیف پول` | inline |
| `menu.services` | `📦 سرویس‌های من` | inline |
| `menu.plans` | `📋 پلن‌ها` | inline |
| `menu.referral` | `👥 دعوت دوستان` | inline |
| `menu.tutorials` | `📚 آموزش‌ها` | inline |
| `menu.support` | `🎧 پشتیبانی` | inline |
| `menu.representative` | `👤 نمایندگی` | inline |
| `menu.back_button` | `🔙 بازگشت` | inline |

## اجرای SQL

برای وارد کردن همه موارد، فایل زیر را در phpMyAdmin در بخش **SQL** اجرا کنید:

`database/sql/bot_messages_main_menu.sql`

اسکریپت قبل از درج، کلیدهای همین بخش را برای زبان `fa` حذف می‌کند؛ بنابراین اجرای مجدد آن باعث ایجاد رکورد تکراری نمی‌شود.

> توجه: جدول `bot_messages` در migration اصلی با کلید یکتای `(key, locale)` ساخته شده و migration بعدی ستون‌های `type`، `button_type` و `description` را اضافه می‌کند. بنابراین این SQL باید بعد از اجرای migrationهای پروژه اجرا شود.
