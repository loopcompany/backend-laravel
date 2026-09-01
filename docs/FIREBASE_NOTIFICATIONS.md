# راهنمای اعلان Firebase (FCM)

این پروژه از Firebase Cloud Messaging با API جدید HTTP v1 استفاده می‌کند. کلید Service Account فقط روی بک‌اند قرار می‌گیرد و اپلیکیشن فرانت فقط Firebase Client SDK و توکن دستگاه را مدیریت می‌کند.

## ۱. تنظیم بک‌اند

در Firebase Console:

1. یک Project بسازید یا پروژه موجود را انتخاب کنید.
2. از Project settings > General، مقدار Project ID را بردارید.
3. از Project settings > Service accounts > Firebase Admin SDK یک Private key جدید بسازید.
4. فایل JSON دانلودشده را خارج از `public` و خارج از repository قرار دهید؛ مثلاً در `/etc/mycorner/firebase-service-account.json`.

در `.env` بک‌اند:

```env
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=your-firebase-project-id
FIREBASE_CREDENTIALS_PATH=/etc/mycorner/firebase-service-account.json
FIREBASE_HTTP_TIMEOUT=15
```

به‌جای مسیر فایل، برای Docker/Secrets Manager می‌توان کل JSON را در `FIREBASE_CREDENTIALS_JSON` قرار داد. در این حالت `FIREBASE_CREDENTIALS_PATH` لازم نیست. هیچ‌کدام از این مقادیر نباید به فرانت ارسال یا commit شوند.

سپس migration را اجرا کنید:

```bash
php artisan migrate
php artisan config:clear
```

برای پیامک تأیید/رد تکنسین، دو template در SMS.ir بسازید و متغیرهای زیر را داشته باشید:

- تأیید: `NAME` و مقدار `SMSIR_TECHNICIAN_APPROVED_TEMPLATE_ID`
- رد: `NAME` و `REASON` و مقدار `SMSIR_TECHNICIAN_REJECTED_TEMPLATE_ID`

این دو مقدار در `.env` اجباری نیستند؛ اگر خالی باشند فقط Push ارسال می‌شود و در log هشدار ثبت می‌شود.

## ۲. ثبت توکن از فرانت

بعد از login موفق و هر بار که Firebase توکن جدید می‌دهد، این درخواست را ارسال کنید:

```http
POST /api/notifications/device-token
Authorization: Bearer {sanctum_token}
Content-Type: application/json
Accept: application/json
```

```json
{
  "token": "FCM_DEVICE_TOKEN",
  "platform": "android",
  "device_id": "optional-installation-id",
  "app_version": "1.0.0"
}
```

مقدار `platform` فقط یکی از `android`، `ios` یا `web` است. `device_id` و `app_version` اختیاری هستند. هر کاربر می‌تواند چند توکن برای چند دستگاه داشته باشد. اگر یک دستگاه با حساب دیگری وارد شود، توکن به حساب جدید منتقل می‌شود.

پاسخ موفق:

```json
{
  "success": true,
  "message": "توکن اعلان با موفقیت ثبت شد.",
  "data": { "id": 12, "platform": "android" }
}
```

نکته مهم: توکن را فقط پس از login/دریافت Bearer token ثبت کنید. اگر کاربر logout کرد، بهتر است قبل یا هنگام logout آن را حذف کنید:

```http
DELETE /api/notifications/device-token
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

```json
{ "token": "FCM_DEVICE_TOKEN", "platform": "android" }
```

## ۳. کارهایی که فرانت باید انجام دهد

فرانت باید Firebase Client SDK را برای پلتفرم خود راه‌اندازی کند، permission اعلان را بگیرد، با `getToken()` توکن FCM را بگیرد و آن را به endpoint بالا بفرستد. همچنین listener تغییر توکن (`onTokenRefresh` یا معادل پلتفرم) باید دوباره endpoint ثبت توکن را صدا بزند.

برای پیام‌های foreground و background، handler پلتفرم باید payload زیر را پشتیبانی کند:

```json
{
  "notification": {
    "title": "عنوان اعلان",
    "body": "متن اعلان"
  },
  "data": {
    "type": "order_status",
    "order_id": "123",
    "screen": "order-detail"
  }
}
```

همه مقادیر `data` از سمت بک‌اند به صورت string ارسال می‌شوند. در زمان tap روی اعلان، فرانت باید با توجه به `type` یا `screen` مسیر مناسب را باز کند. این قرارداد را ثابت نگه دارید و برای مقادیر داخلی/حساس از `data` استفاده نکنید.

برای Android، کانال اعلان و آیکن پیش‌فرض را در اپ تعریف کنید. برای iOS، APNs را در Firebase فعال کنید و permission/foreground presentation را تنظیم کنید. برای Web، VAPID key و service worker لازم است.

## ۴. ارسال اعلان از بک‌اند

در هر Service/Job/Observer که رویداد اعلان در آن رخ می‌دهد:

```php
use App\Services\FirebaseNotificationService;

app(FirebaseNotificationService::class)->sendToUser(
    $userOrTechnician,
    'وضعیت سفارش تغییر کرد',
    'سفارش شما آماده بررسی است.',
    [
        'type' => 'order_status',
        'order_id' => $order->id,
        'screen' => 'order-detail',
    ],
    [
        'android' => [
            'priority' => 'HIGH',
            'notification' => ['channel_id' => 'orders'],
        ],
    ]
);
```

متد `sendToUser` همه دستگاه‌های ثبت‌شده کاربر یا تکنسین را هدف می‌گیرد و نتیجه‌ای مانند زیر برمی‌گرداند:

```php
['sent' => 2, 'failed' => 0, 'removed' => 0]
```

برای topic کنترل‌شده توسط بک‌اند می‌توان از `sendToTopic($topic, $title, $body, $data, $options)` استفاده کرد. topic را مستقیماً از ورودی آزاد کاربر نپذیرید.

ارسال‌های خودکار سفارش داخل `SendFirebaseNotificationJob` قرار گرفته‌اند تا درخواست اصلی منتظر Firebase نماند. در production باید queue worker فعال باشد؛ سرویس توکن نامعتبر FCM را به‌صورت خودکار از جدول حذف می‌کند.

در حال حاضر این پروژه به‌صورت خودکار برای این رویدادها اعلان می‌فرستد: ثبت سفارش، لغو سفارش توسط مدیر، تخصیص سفارش به تکنسین، تغییر زمان/توضیحات سفارش، ثبت/آپلود درخواست سازمانی، نیاز به مدرک، پاسخ درخواست/قرارداد سازمانی و تأیید/رد پروفایل سازمانی. برای رویدادهای جدید، همین سرویس را در Service/Job مربوط به رویداد صدا بزنید.

اعلان‌های مربوط به ثبت‌نام تکنسین، ثبت درخواست و آپلود مدارک، علاوه بر push در صورت وجود توکن، در Bell پنل Filament برای همه ادمین‌های فعال ذخیره می‌شوند.

تغییر وضعیت ثبت‌نام تکنسین به «تأیید» یا «رد» از هر مسیر پنل (ویرایش، تأیید تکی یا تأیید گروهی) به‌صورت خودکار SMS و در صورت فعال بودن Firebase، Push ارسال می‌کند.

## ۵. خطاهای رایج

- `401/403` از Google: فایل Service Account، project ID یا دسترسی Firebase Cloud Messaging را بررسی کنید.
- `FIREBASE notifications are disabled`: مقدار `FIREBASE_ENABLED=true` را تنظیم و `php artisan config:clear` را اجرا کنید.
- اعلان ثبت می‌شود ولی نمی‌رسد: معتبر بودن توکن، فعال بودن FCM/APNs، permission کاربر و handler foreground را بررسی کنید.
- در production فایل Service Account را داخل `public`، image عمومی Docker یا git قرار ندهید.
