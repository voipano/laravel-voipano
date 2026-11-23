# پکیج رسمی Laravel ویپانو

[![Tests](https://github.com/voipano/laravel-voipano/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/voipano/laravel-voipano/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/voipano/laravel)](https://packagist.org/packages/voipano/laravel)
[![License](https://img.shields.io/github/license/voipano/laravel-voipano)](LICENSE)
[![Documentation](https://img.shields.io/badge/docs-EN%20%7C%20FA-blue)](README.fa.md)

[English README](README.md) | [کالکشن Postman](docs/voipano-webservice.postman_collection.json)

پکیج Laravel برای وب‌سرویس عمومی ویپانو (API v1) با پشتیبانی از تمام **۲۳ درخواست** تعریف‌شده در کالکشن مورخ `2026.11.21`. بدون نیاز به نوشتن دستی درخواست‌های HTTP می‌توانید مخاطبین، گروه‌ها، گزارش تماس، فایل‌های ضبط، وبهوک، OTP صوتی و تماس‌های خروجی را مدیریت کنید.

## سازگاری

- **PHP 8.2 و بالاتر**؛ پشتیبانی امنیتی PHP 8.2 در پایان دسامبر ۲۰۲۶ به اتمام می‌رسد. برای پروژه‌های جدید PHP 8.3 یا بالاتر توصیه می‌شود.
- **Laravel 12** با PHP 8.2+ و **Laravel 13** با PHP 8.3+.
- Composer 2.

## نصب

پکیج منتشرشده را از Packagist نصب کنید:

```bash
composer require voipano/laravel:^1.0
```

پکیج به‌صورت خودکار در Laravel ثبت می‌شود. فقط توکن API را در فایل `.env` پروژه خود قرار دهید:

```dotenv
VOIPANO_API_TOKEN=your-private-token
VOIPANO_SERVICE_ID=
```

برای تنظیمات بیشتر می‌توانید فایل پیکربندی را منتشر کنید (اختیاری):

```bash
php artisan vendor:publish --tag=voipano-config
```

## شروع سریع

```php
use Voipano\Laravel\Facades\Voipano;

$services = Voipano::services()->all();
$contacts = Voipano::contacts()->all(['page' => 1, 'per_page' => 20]);

Voipano::contacts()->create([
    'primary_number' => '09120000000',
    'first_name' => 'Ali',
]);

$reports = Voipano::callReports()->all(['page' => 1]);

// درخواست‌های زیر تماس واقعی برقرار می‌کنند؛ فقط در جریان کاری مجاز اجرا شوند.
// Voipano::calls()->otp('09120000000', ['purpose' => 'login']);
// Voipano::calls()->clickToCall('09120000000', '09350000000');
// Voipano::calls()->secureCall('09120000000', '09350000000');
```

برای سیستم‌های چندمشتری یا چندسرویس:

```php
$client = Voipano::withToken($customerToken)->forService($serviceId);
$contacts = $client->contacts()->all();
```

`withToken` و `forService` یک نمونه مستقل از کلاینت می‌سازند و تنظیمات سایر کاربران را تغییر نمی‌دهند.

## امکانات

| بخش | متدهای پکیج |
| --- | --- |
| سرویس‌ها | `services()->all()` |
| مخاطبین | `contacts()->all()`, `find()`, `create()`, `update()`, `updatePut()`, `delete()`, `bulkImport()` |
| گروه‌های مخاطبین | `contactGroups()->all()`, `create()`, `delete()` |
| موسیقی انتظار | `musicOnHolds()->all()` |
| گزارش تماس | `callReports()->all()` |
| صندوق صوتی | `voicemailInboxes()->all()` |
| فایل‌های صوتی | `recordings()->call()`, `downloadCall()`, `voicemail()`, `downloadVoicemail()` |
| مدیریت وبهوک | `webhooks()->get()`, `configure()`, `route()`, `configureLegacy()` |
| تماس‌ها | `calls()->otp()`, `clickToCall()`, `secureCall()` |

نتیجه درخواست‌های JSON به شکل آرایه PHP برگردانده می‌شود. خطاهای HTTP به Exceptionهای مستقل تبدیل می‌شوند. **درخواست‌های تماس و OTP به‌طور خودکار تکرار نمی‌شوند** تا تماس تکراری ایجاد نشود.

## دانلود فایل ضبط

```php
$path = Voipano::recordings()->downloadCall(
    'cdr-id', 'call-details-record-id', storage_path('app/private/call.wav')
);
```

مسیر پوشه باید وجود داشته باشد؛ فایل مقصد نباید از قبل وجود داشته باشد. فایل‌های صوتی را در مسیر خصوصی نگه دارید. رابطه دقیق شناسه‌های دانلود ضبط باید با سمت سرور بررسی شود.

## دریافت وبهوک

دریافت وبهوک به‌صورت پیش‌فرض **غیرفعال** است، چون کالکشن روش امضای درخواست‌های ورودی را مشخص نکرده است. برای فعال‌سازی امن، ابتدا رابط `Voipano\Laravel\Webhooks\Verifier` را با پروتکل امضای واقعی پیاده‌سازی و تست کنید؛ سپس مقدارهای `VOIPANO_WEBHOOK_RECEIVER_ENABLED` و `VOIPANO_WEBHOOK_SECRET` را تنظیم کنید. تأییدکننده پیش‌فرض همه درخواست‌ها را رد می‌کند.

## توسعه و آزمایش

```bash
composer install
composer validate --strict --no-check-publish
composer test
```

تست‌های همراه پکیج از درخواست‌های ساختگی استفاده می‌کنند و تماس واقعی برقرار نمی‌کنند. در کالکشن Postman نمونه پاسخ‌های واقعی و قرارداد امضای وبهوک موجود نیست؛ سازگاری با پاسخ‌های زنده API باید با محیط مجاز آزمایش شود.

مجوز انتشار: MIT؛ [فایل LICENSE](LICENSE).
