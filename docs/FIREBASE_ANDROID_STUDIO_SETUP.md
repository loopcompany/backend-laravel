# راه‌اندازی Firebase Studio برای اپ فرانت و بک‌اند

> نکته: منظور این راهنما **Firebase Studio** است، نه Android Studio. آدرس محیط آنلاین آن [studio.firebase.google.com](https://studio.firebase.google.com) است. Firebase Studio یک محیط توسعه ابری است و خودش جایگزین Firebase Console یا EAS Build نمی‌شود.

این راهنما برای اتصال پروژه فرانت موجود به همان Firebase Project بک‌اند و فعال‌کردن ارسال Push Notification نوشته شده است.

نکته مهم: فایل‌های Firebase کلاینت با فایل Service Account بک‌اند فرق دارند.

- اپ Android فقط `google-services.json` را لازم دارد.
- بک‌اند فقط Project ID و Service Account JSON را لازم دارد.
- Service Account JSON نباید داخل Firebase Studio، اپ موبایل یا repository فرانت قرار بگیرد.

## نتیجه نهایی

باید این ارتباط برقرار شود:

```text
Android App
  └── google-services.json
        └── همان Firebase Project
              ├── FIREBASE_PROJECT_ID در بک‌اند
              └── Service Account JSON در سرور بک‌اند
```

## مرحله اول: ورود به Firebase Studio

1. با همان Google Account که به پروژه Firebase دسترسی دارد، وارد [Firebase Studio](https://studio.firebase.google.com) شوید.
2. اگر پروژه فرانت در GitHub است، گزینه Import/Clone repository را انتخاب کنید.
3. اگر پروژه به‌صورت فایل ZIP است، آن را به Workspace وارد کنید.
4. Workspace را به **Firebase Project موجود** وصل کنید؛ اجازه ندهید Firebase Studio به‌صورت خودکار پروژه جدید بسازد، مگر اینکه عمداً محیط جداگانه برای تست می‌خواهید.

برای اتصال دستی Workspace به Firebase Project، از داخل Firebase Studio گزینه اتصال به Firebase Project را انتخاب کنید و Project ID موجود را وارد/انتخاب کنید. اتصال Workspace به پروژه برای استفاده از سرویس‌های Firebase لازم است، اما به‌تنهایی FCM را داخل اپ پیاده‌سازی نمی‌کند. [راهنمای رسمی اتصال Firebase Studio به پروژه](https://firebase.google.com/docs/studio/firebase-projects)

## مرحله دوم: انتخاب یا ساخت Firebase Project

1. با Google Account وارد [Firebase Console](https://console.firebase.google.com) شوید.
2. اگر پروژه Firebase از قبل ساخته شده، همان پروژه را انتخاب کنید.
3. اگر نمی‌دانید بک‌اند به کدام پروژه وصل است، روی سرور مقدار `FIREBASE_PROJECT_ID` را بررسی کنید.
4. اگر مقدار وجود ندارد، قبل از ساخت پروژه جدید با مسئول پروژه هماهنگ کنید؛ ساخت پروژه جدید باعث می‌شود اپ و بک‌اند به دو پروژه متفاوت وصل شوند.

برای پیدا کردن Project ID:

```text
Firebase Console → Project settings → General → Project ID
```

این مقدار نمونه‌ای شبیه زیر دارد:

```env
FIREBASE_PROJECT_ID=clpiran-loop-12345
```

Project ID با Project Name، API Key، Sender ID و Android App ID فرق دارد.

## مرحله سوم: ثبت اپ Android در Firebase

در Firebase Console به مسیر زیر بروید:

```text
Project settings → General → Your apps → Add app → Android
```

در قسمت Android package name مقدار دقیق زیر را وارد کنید:

```text
com.clpiran.loop
```

این مقدار باید با `applicationId` پروژه Android یکی باشد. در پروژه Native معمولاً در یکی از این فایل‌ها قرار دارد:

```text
app/build.gradle
app/build.gradle.kts
```

مثال:

```gradle
android {
    defaultConfig {
        applicationId "com.clpiran.loop"
    }
}
```

App nickname اختیاری است. SHA-1 و SHA-256 برای Firebase Authentication و Google Sign-In مهم هستند؛ اگر Firebase فقط برای FCM استفاده می‌شود، در مرحله اول می‌توان آن‌ها را بعداً اضافه کرد.

سپس روی Register app بزنید و فایل زیر را دانلود کنید:

```text
google-services.json
```

نام فایل نباید به `google-services (2).json` یا نام دیگری تغییر کند.

## حالت A: پروژه Native Android در Firebase Studio

اگر کد فرانت یک پروژه Native Android است، Firebase Studio می‌تواند فایل‌ها و کد پروژه را ویرایش کند؛ اما برای اجرای Native و ساخت APK باید Android SDK/Gradle یا سرویس Build مناسب در اختیار پروژه باشد. مراحل Firebase Assistant مربوط به Android Studio را انجام ندهید؛ در Firebase Studio کافی است فایل `google-services.json` را در مسیر درست پروژه قرار دهید و Gradle پروژه را تنظیم کنید.

### تنظیم پروژه Native داخل Workspace

1. پروژه Android را در Workspace باز کنید.
2. فایل `google-services.json` را در مسیر زیر قرار دهید:

```text
android-project/app/google-services.json
```

3. در Gradle پروژه، Google Services plugin و Firebase Messaging را تنظیم کنید.
4. Gradle Sync/Build را اجرا کنید.

طبق مستندات Firebase، فایل JSON باید در ریشه ماژول `app` باشد و Google Services Gradle Plugin آن را پردازش کند. [راهنمای رسمی Firebase](https://firebase.google.com/docs/android/google-services-plugin-and-file)

### بررسی تنظیم Gradle

در فایل‌های Gradle پروژه مطمئن شوید:

- Google Services Gradle Plugin فعال است.
- dependency مربوط به Firebase Messaging اضافه شده است.
- `google-services.json` داخل `app/` قرار دارد.

نسخه plugin و dependency باید با نسخه Gradle و Android Gradle Plugin پروژه سازگار باشد. اگر پروژه Native است، نسخه‌های سازگار را از مستندات همان نسخه پروژه انتخاب کنید.

### دریافت توکن در Android Native

اپ باید بعد از login و گرفتن permission، FCM registration token را دریافت کند و به بک‌اند بفرستد. در Android Native معمولاً از این API استفاده می‌شود:

```kotlin
FirebaseMessaging.getInstance().token
    .addOnCompleteListener { task ->
        if (!task.isSuccessful) return@addOnCompleteListener

        val fcmToken = task.result
        // ارسال fcmToken به POST /api/notifications/device-token
    }
```

همچنین تغییر توکن باید مدیریت شود؛ چون توکن ممکن است بعد از نصب مجدد، پاک‌شدن اطلاعات اپ یا تغییر وضعیت Firebase عوض شود.

## حالت B: پروژه Expo / React Native با EAS

اگر پروژه فرانت Expo است و تیم با `eas build` کار می‌کند، Firebase Studio فقط محیط کدنویسی و Workspace است. تنظیم اصلی باید در `app.json` یا `app.config.js` باشد و Build واقعی توسط EAS انجام شود:

```json
{
  "expo": {
    "android": {
      "package": "com.clpiran.loop",
      "googleServicesFile": "./google-services.json"
    }
  }
}
```

فایل را در ریشه پروژه Expo قرار دهید و Development Build جدید بسازید:

```bash
eas build --profile development --platform android
```

اگر پروژه از React Native Firebase برای گرفتن FCM token استفاده می‌کند، پکیج‌های موردنیاز و config plugin باید در پروژه فرانت نصب و تنظیم شوند. Firebase Studio به‌تنهایی Development Build تولید نمی‌کند؛ برای ساخت باید EAS Build یا یک pipeline CI اجرا شود. [راهنمای Expo برای Firebase](https://docs.expo.dev/guides/using-firebase/)

در هر دو حالت، `ExpoPushToken[...]` برای این بک‌اند قابل استفاده نیست؛ چون بک‌اند مستقیماً به FCM HTTP v1 پیام می‌فرستد. باید FCM registration token ارسال شود.

## مرحله چهارم: فعال‌کردن FCM در Firebase

در Firebase Console مسیر زیر را بررسی کنید:

```text
Project settings → Cloud Messaging
```

Firebase Cloud Messaging API یا FCM HTTP v1 باید فعال باشد.

برای دستگاه واقعی Android، دستگاه باید Google Play services داشته باشد. Android Emulator نیز باید image دارای Google APIs/Google Play داشته باشد. [راهنمای رسمی FCM Android](https://firebase.google.com/docs/cloud-messaging/android/get-started)

## مرحله پنجم: ساخت Service Account برای بک‌اند

این مرحله فقط برای بک‌اند است و نباید داخل Firebase Studio یا اپ موبایل انجام شود.

1. در Firebase Console وارد همان پروژه شوید.
2. به مسیر زیر بروید:

```text
Project settings → Service accounts
```

3. روی Generate new private key بزنید.
4. فایل JSON دانلودشده را در سرور، خارج از `public` و خارج از Git نگهداری کنید.
5. مسیر واقعی فایل روی سرور را در `.env` قرار دهید.

برای FCM HTTP v1، Service Account باید اجازه ارسال پیام FCM داشته باشد. در صورت خطای 401 یا 403، نقش `Firebase Cloud Messaging API Admin` را در Google Cloud IAM برای آن Service Account بررسی کنید. [راهنمای رسمی FCM HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api)

## مقادیر موردنیاز `.env` بک‌اند

در این پروژه فقط این مقادیر را تنظیم کنید:

```env
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=clpiran-loop-12345
FIREBASE_CREDENTIALS_PATH=/secure/path/firebase-service-account.json
FIREBASE_HTTP_TIMEOUT=15
```

### توضیح هر مقدار

| مقدار | از کجا می‌آید؟ | محرمانه است؟ |
| --- | --- | --- |
| `FIREBASE_ENABLED` | مقدار دستی `true` | خیر |
| `FIREBASE_PROJECT_ID` | Firebase Console یا `project_info.project_id` داخل فایل‌های Firebase | خیر، اما باید با پروژه یکی باشد |
| `FIREBASE_CREDENTIALS_PATH` | مسیر فایل Service Account روی همان سرور | خود مسیر محرمانه نیست، فایل مقصد محرمانه است |
| `FIREBASE_CREDENTIALS_JSON` | کل محتوای Service Account JSON به‌عنوان Secret | بله |
| `FIREBASE_HTTP_TIMEOUT` | مقدار پیشنهادی `15` ثانیه | خیر |

به‌جای `FIREBASE_CREDENTIALS_PATH` می‌توان از Secret Manager یا Docker Secret استفاده کرد:

```env
FIREBASE_ENABLED=true
FIREBASE_PROJECT_ID=clpiran-loop-12345
FIREBASE_CREDENTIALS_JSON={...service-account-json...}
FIREBASE_HTTP_TIMEOUT=15
```

هم‌زمان از `FIREBASE_CREDENTIALS_PATH` و `FIREBASE_CREDENTIALS_JSON` استفاده نکنید؛ یکی را انتخاب کنید.

این موارد برای `.env` بک‌اند لازم نیستند:

- `google-services.json`
- Firebase API Key
- Android package name
- `mobilesdk_app_id`
- Expo Project ID

## مرحله ششم: اجرای migration و queue

روی سرور بک‌اند اجرا کنید:

```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

چون ارسال اعلان‌ها با Job انجام می‌شود، queue worker نیز باید فعال باشد:

```bash
php artisan queue:work --tries=3
```

## تست نهایی

1. اپ Android را با Development Build روی گوشی واقعی نصب کنید.
2. وارد حساب کاربری شوید.
3. permission اعلان را قبول کنید.
4. FCM token را دریافت کنید.
5. درخواست زیر را با Bearer token ارسال کنید:

```http
POST /api/notifications/device-token
Authorization: Bearer {sanctum_token}
Content-Type: application/json
Accept: application/json
```

```json
{
  "token": "FCM_REGISTRATION_TOKEN",
  "platform": "android",
  "device_id": "optional-device-id",
  "app_version": "1.0.0"
}
```

پاسخ موفق باید HTTP `200` باشد و در جدول `firebase_device_tokens` رکورد ساخته شود.

اگر اعلان ارسال نشد، به‌ترتیب این موارد را بررسی کنید:

1. Project ID فرانت و بک‌اند یکی باشد.
2. package name دقیقاً `com.clpiran.loop` باشد.
3. `google-services.json` مربوط به همان Project باشد.
4. توکن، FCM token واقعی باشد و `ExpoPushToken` نباشد.
5. `FIREBASE_ENABLED=true` باشد.
6. Service Account JSON معتبر و قابل خواندن باشد.
7. FCM HTTP v1 فعال و Service Account مجاز باشد.
8. queue worker فعال باشد.

## چه اکانت‌هایی لازم است؟

- Google Account با دسترسی به Firebase Project
- حساب/دسترسی Firebase Studio برای Workspace
- Firebase Project؛ ترجیحاً همان پروژه‌ای که بک‌اند استفاده می‌کند
- Expo Account فقط اگر پروژه با Expo/EAS ساخته می‌شود
- Apple Developer Account فقط برای iOS
- Google Play Console برای Development APK لازم نیست و فقط هنگام انتشار در Google Play لازم می‌شود.
