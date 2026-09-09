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

## راه‌اندازی اپ فرانت با Expo/EAS

بک‌اند و اپ فرانت باید به یک Firebase Project یکسان متصل باشند. مقدار `project_id` داخل فایل‌های فرانت باید با `FIREBASE_PROJECT_ID` روی بک‌اند یکی باشد.

### فایل‌های موردنیاز از Firebase Console

از همان پروژه Firebase که بک‌اند استفاده می‌کند، در بخش Project settings > Your apps این دو اپ را ثبت کنید:

- Android با package name دقیق `com.clpiran.loop` و فایل `google-services.json`
- iOS با bundle identifier دقیق `com.clpiran.loop` و فایل `GoogleService-Info.plist`

این فایل‌ها تنظیمات Client هستند و حاوی شناسه‌های غیرمحرمانه‌اند؛ آن‌ها را در پروژه فرانت قرار دهید و هیچ‌وقت فایل Service Account JSON بک‌اند را به فرانت ندهید.

در `app.json` یا `app.config.js` پروژه Expo باید تنظیمات معادل زیر وجود داشته باشد:

```json
{
  "android": {
    "package": "com.clpiran.loop",
    "googleServicesFile": "./google-services.json"
  },
  "ios": {
    "bundleIdentifier": "com.clpiran.loop",
    "googleServicesFile": "./GoogleService-Info.plist"
  },
  "plugins": ["expo-notifications"]
}
```

### نوع توکن موردنیاز

این بک‌اند مستقیماً به FCM HTTP v1 وصل می‌شود. بنابراین:

- `ExpoPushToken[...]` را به بک‌اند ارسال نکنید.
- برای FCM مستقیم، از FCM registration token استفاده کنید؛ در React Native Firebase معمولاً `messaging().getToken()` است.
- اگر از `expo-notifications` استفاده می‌کنید، `getDevicePushTokenAsync()` توکن native می‌دهد؛ روی iOS ممکن است APNs token باشد و برای مسیر فعلی FCM کافی نیست. در این حالت یا از React Native Firebase برای دریافت FCM token استفاده کنید، یا باید ارسال APNs جداگانه در بک‌اند پیاده‌سازی شود.

نمونه جریان ثبت توکن:

```ts
await messaging().requestPermission();
const token = await messaging().getToken();

await api.post('/api/notifications/device-token', {
  token,
  platform: Platform.OS, // android یا ios
  device_id: installationId,
  app_version: appVersion,
});

const unsubscribe = messaging().onTokenRefresh(async (newToken) => {
  await registerToken(newToken);
});
```

ثبت توکن باید بعد از login و دریافت Bearer token انجام شود. هنگام logout نیز درخواست حذف توکن را ارسال کنید.

### ساخت Development Build

توکن واقعی روی Expo Go قابل اتکا نیست؛ تیم فرانت باید با EAS یک Development Build بسازد:

```bash
npm install --global eas-cli
eas login
eas build:configure
eas build --profile development --platform android
```

سپس فایل APK ساخته‌شده روی یک گوشی واقعی نصب شود، کاربر login کند، permission اعلان را قبول کند و درخواست ثبت توکن بررسی شود. تغییر در فایل‌های Firebase یا تنظیمات native نیازمند build جدید است.

## کارهای خارج از کد

### ضروری برای Android

1. یک Google Account با دسترسی به Firebase Project موجود داشته باشید؛ اگر پروژه قبلی مشخص نیست، پروژه جدید نسازید تا project اشتباه ایجاد نشود.
2. اپ Android را با package name دقیق `com.clpiran.loop` در همان Firebase Project ثبت کنید.
3. فایل `google-services.json` را دانلود و به تیم فرانت تحویل دهید.
4. در Firebase Console > Project settings > Cloud Messaging، فعال بودن Firebase Cloud Messaging API را بررسی کنید.
5. در Firebase Console > Project settings > Service accounts، برای بک‌اند Private Key بسازید و فقط به مسئول بک‌اند/سرور تحویل دهید.
6. روی سرور بک‌اند این مقادیر تنظیم شوند:

```env
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=همان project_id فایل‌های فرانت
FIREBASE_CREDENTIALS_PATH=/secure/path/firebase-service-account.json
```

### ضروری برای iOS

1. Apple Developer Program لازم است؛ برای Development Build روی دستگاه واقعی و انتشار iOS، عضویت پولی اپل موردنیاز است.
2. در Firebase همان bundle identifier دقیق `com.clpiran.loop` را ثبت کنید و `GoogleService-Info.plist` را دانلود کنید.
3. در Apple Developer یک App ID با همین Bundle ID ایجاد/بررسی کنید و قابلیت Push Notifications را فعال کنید.
4. APNs Authentication Key یا credential مربوط به اپ را در Firebase > Project settings > Cloud Messaging ثبت کنید تا FCM بتواند پیام iOS را از مسیر APNs تحویل دهد.

### حساب‌های غیرضروری در مرحله تست

- Google Play Console برای ساخت و نصب Development APK لازم نیست؛ فقط برای انتشار در Google Play لازم می‌شود.
- Apple App Store Connect برای تست Android لازم نیست.
- حساب Expo برای EAS لازم است؛ پلن رایگان برای شروع EAS Build قابل استفاده است.

## چک‌لیست تحویل و تست

- [ ] `project_id` فرانت و بک‌اند یکسان است.
- [ ] package/bundle هر دو `com.clpiran.loop` است.
- [ ] فایل‌های client config در پروژه فرانت قرار گرفته‌اند.
- [ ] فایل Service Account فقط روی بک‌اند/سرور است.
- [ ] Firebase Cloud Messaging API فعال است.
- [ ] APNs برای iOS تنظیم شده است.
- [ ] اپ با Development Build روی گوشی واقعی نصب شده است.
- [ ] بعد از login، پاسخ `200` از `POST /api/notifications/device-token` دریافت می‌شود.
- [ ] در جدول `firebase_device_tokens` رکورد مربوط به کاربر یا تکنسین ایجاد شده است.
- [ ] queue worker بک‌اند در محیطی که اعلان ارسال می‌کند فعال است.

راهنمای رسمی: [افزودن Firebase به Android](https://firebase.google.com/docs/android/setup)، [افزودن Firebase به iOS](https://firebase.google.com/docs/ios/setup)، [FCM HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api)، [توکن native در Expo](https://docs.expo.dev/push-notifications/sending-notifications-custom/) و [EAS Build](https://docs.expo.dev/build/setup/).
