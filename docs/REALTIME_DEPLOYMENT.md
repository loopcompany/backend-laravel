# راه‌اندازی به‌روزرسانی لحظه‌ای لوپ

سرویس Laravel Reverb روی یک پردازش دائمی اجرا می‌شود. اپ مشتری، اپ تکنسین و کد مشترک Electron، تنظیم اتصال را از API احراز هویت‌شده می‌گیرند. پنل مدیریت تنظیمات همان سرور را در صفحهٔ خودش دریافت می‌کند.

## تنظیم سرور

در فایل خصوصی `.env` بک‌اند، مقادیر زیر را تنظیم کنید. برای کلید و راز دو مقدار مستقل تصادفی بسازید. راز فقط روی سرور می‌ماند.

```dotenv
BROADCAST_CONNECTION=reverb
REALTIME_ENABLED=true
REVERB_APP_ID=loop-production
REVERB_APP_KEY=<random-public-app-key>
REVERB_APP_SECRET=<random-private-secret>

REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

REALTIME_HOST=clpiran.com
REALTIME_PORT=443
REALTIME_SCHEME=wss
REVERB_ALLOWED_ORIGINS=clpiran.com,user-panel.clpiran.com,tech-panel.clpiran.com,loop
```

مبدأهای وب را با دامنه‌های واقعی پنل‌ها تطبیق دهید. مقدار `loop` برای مبدأ `app://loop` نسخهٔ Electron است. درخواست اتصال اپ بومی با مبدأ دامنهٔ API ارسال می‌شود. اجازهٔ ارسال رویداد مستقیم از کلاینت به سایر کلاینت‌ها غیرفعال است.

پس از انتشار کد و نصب وابستگی‌های قفل‌شدهٔ Composer، این فرمان‌ها در پوشهٔ بک‌اند اجرا می‌شوند.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan config:cache
php artisan reverb:start --host=127.0.0.1 --port=8080
```

فرمان آخر باید با Supervisor یا سرویس معادل هاست مدیریت شود تا بعد از ریبوت یا خطا دوباره اجرا شود. برای اجرای زیر Supervisor، پشتیبانی مسیر واقعی PHP و پوشهٔ بک‌اند حساب `khayyamt` را جایگزین می‌کند.

```ini
[program:loop-reverb]
directory=<backend-directory>
command=<php-binary> artisan reverb:start --host=127.0.0.1 --port=8080
user=khayyamt
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=<private-log-directory>/reverb.log
```

در انتشار بعدی، پس از به‌روزرسانی تنظیمات، `php artisan reverb:restart` اجرا شود. Supervisor پردازش را دوباره راه می‌اندازد.

## پراکسی WSS

ساب‌دامین `ws.clpiran.com` باید گواهی TLS معتبر داشته باشد و درخواست‌های وب‌سوکت را به سرویس داخلی بفرستد. نمونهٔ بخش مربوط در Nginx که پشتیبانی هاست در vhost ساب‌دامین قرار می‌دهد چنین است.

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Proto https;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_read_timeout 120s;
    proxy_send_timeout 120s;
}
```

در Apache نیز پشتیبانی باید پراکسی WebSocket معادل را روی همین ساب‌دامین تنظیم کند. پورت ۸۰۸۰ فقط روی loopback گوش می‌دهد. API ارسال رویداد Reverb از بک‌اند به `127.0.0.1` فراخوانی می‌شود.

## متن تیکت هاست

«برای حساب khayyamt و پروژهٔ Laravel دامنه clpiran.com به اجرای دائمی Laravel Reverb با PHP سازگار با پروژه نیاز داریم. لطفاً امکان اجرای پردازش تحت کاربر هاست، راه‌اندازی خودکار آن و پراکسی WebSocket امن روی ساب‌دامین ws.clpiran.com با TLS معتبر را فراهم کنید. سرویس روی 127.0.0.1:8080 اجرا می‌شود و اتصال عمومی از طریق WSS روی 443 است. لطفاً مسیر PHP، شیوهٔ مدیریت و ری‌استارت پردازش و امکان تنظیم Upgrade/Connection و timeout پراکسی را اعلام کنید.»

اگر پلن اشتراکی اجازهٔ پردازش دائمی و پراکسی WSS ندهد، اجرای Reverb به یک سرویس یا سرور دارای این امکانات نیاز دارد.

## رفتار برنامه‌ها و کنترل دسترسی

- هر کاربر و تکنسین فقط کانال خصوصی خودش را می‌گیرد. احراز کانال با توکن Sanctum انجام می‌شود.
- کانال‌های ادمین با نشست ادمین و مجوز همان حوزه کنترل می‌شوند.
- پیام وب‌سوکت فقط نام حوزهٔ تغییر را دارد. متن پیام، نشانی، اطلاعات مالی یا دادهٔ مشتری دیگر در آن قرار نمی‌گیرد. صفحهٔ فعال اطلاعات را از API دارای کنترل دسترسی دریافت می‌کند.
- رویداد پس از commit دیتابیس منتشر می‌شود. rollback رویداد نمی‌فرستد و قطع سرویس وب‌سوکت عملیات ثبت سفارش یا پرداخت را ناموفق نمی‌کند.
- پس از اتصال مجدد، صفحه‌های فعال دوباره همگام می‌شوند. چند تغییر نزدیک به هم ادغام می‌شوند.
- ارسال دادهٔ سفارش، گفتگو و پرداخت با درخواست دوره‌ای ۵ یا ۱۵ ثانیه‌ای انجام نمی‌شود. heartbeat و تلاش اتصال مجدد، ترافیک نگهداری اتصال وب‌سوکت هستند.
- یادآوری و ثبت انقضای پیش‌رسید به Cron معمول Laravel نیاز دارند. `php artisan schedule:run` هر دقیقه اجرا شود.

## تأیید بعد از انتشار

فرانت‌ها نیز باید با همین سورس دوباره ساخته و منتشر شوند. برای وب مشتری `npx expo export --platform web` و برای وب تکنسین همین فرمان در مخزن خودش اجرا می‌شود. اپ نصب‌شدهٔ اندروید و Electron نیز به انتشار نسخهٔ جدید نیاز دارند. ساخت نسخهٔ Electron فعلاً با ناسازگاری قبلی overrideها و سورس مشتری متوقف است؛ پذیرش دسته‌جمعی هش‌های override، این ناسازگاری را حل نمی‌کند.

یک سفارش آزمایشی را هم‌زمان در پنل ادمین، حساب مشتری و تکنسین باز کنید. قیمت را تغییر دهید و تصمیم مشتری، ارجاع، پرداخت آزمایشی، گزارش تحویل، چت و پاسخ پشتیبانی را بررسی کنید. Network مرورگر باید اتصال WSS با کد 101 و درخواست API بعد از رویداد را نشان دهد. در حالت بی‌تغییری، درخواست دوره‌ای داده نباید وجود داشته باشد. اتصال را قطع و دوباره برقرار کنید و همگام‌شدن صفحه را بررسی کنید.

در تست محلی، Reverb واقعی، امضای کانال، انتشار تغییر ثبت‌شده و نرسیدن رویداد به حساب دیگر بررسی شدند. این نتیجه جای بررسی مسیر TLS و پراکسی هاست را نمی‌گیرد.
