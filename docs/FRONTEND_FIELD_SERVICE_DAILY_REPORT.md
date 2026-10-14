# گزارش روزانهٔ تکنسین میدانی برای سفارش سازمانی

این سند قرارداد فرانتِ اپ تکنسین و اپ سازمان است. همهٔ مسیرهای زیر زیر `/api` هستند و به‌جز لینک موقت عکس، هدر `Authorization: Bearer <token>` و `Accept: application/json` می‌خواهند. گزارش روزانه فقط برای سفارشی فعال است که صاحب آن حساب سازمانی باشد و تکنسین فعلی به آن تخصیص یافته باشد.

## اپ تکنسین

1. هنگام باز کردن سفارش، `GET /api/technician/orders/{order}/field-service` را بزنید. پاسخ شامل اطلاعات فقط‌خواندنی سفارش و سازمان، آدرس/واحد/طبقه/اتاق، `work_status` و تاریخچهٔ `visits` است. `location.number` پلاک ساختمان و `location.room` شمارهٔ اتاق است. اگر سازمان در آدرس ثبت‌شده اتاقی ندارد، `room=null` برمی‌گردد.
2. دکمهٔ «شروع» باید `POST /api/technician/orders/{order}/field-service/start` را صدا بزند. بدنه می‌تواند `latitude` و `longitude` عددی داشته باشد؛ هر دو را با هم بفرستید یا هیچ‌کدام را نفرستید. زمان ورود فقط توسط سرور ثبت می‌شود. `visit_id` را از پاسخ بگیرید. برای یک سفارش فقط یک گزارش باز/منتظر بررسی مجاز است.
3. وضعیت محل را از `normal`, `problem`, `broken`, `needs_inspection` بگیرید. برای `problem` و `broken`، `problem_description` الزامی است. `actions` آرایهٔ ۱ تا ۲۰ متن کوتاه است؛ UI می‌تواند «افزودن اقدام» داشته باشد.
4. عکس اختیاری را با `POST /api/technician/orders/{order}/field-service/{visit}/photos` به‌صورت multipart با کلیدهای `photo` و `kind` بفرستید. `kind` یکی از `before`, `after`, `problem`, `part`, `other` است. JPEG/PNG/WebP تا ۵ مگابایت، حداکثر ۱۲ عکس در هر بازدید. حذف عکس قبل از تأیید: `DELETE /api/technician/orders/{order}/field-service/{visit}/photos/{photo}`.
5. دکمهٔ «اتمام و ثبت گزارش» باید `PUT /api/technician/orders/{order}/field-service/{visit}` را با بدنهٔ نمونهٔ زیر بزند. زمان خروج و مدت حضور را سرور حساب می‌کند. وقتی `has_remaining_work=true` است، `remaining_action` و `continuation_owner` الزامی‌اند و `outcome` نمی‌تواند `completed` باشد. وقتی false است، `outcome=completed` بفرستید.

```json
{
  "site_condition": "problem",
  "problem_description": "باتری دستگاه معیوب بود",
  "actions": ["اتصالات بررسی شد", "باتری تعویض شد", "تست اولیه انجام شد"],
  "has_remaining_work": true,
  "remaining_action": "تست نهایی زیر بار",
  "continuation_owner": "next_technician",
  "continuation_note": "پس از رسیدن قطعه، کابل خروجی را بررسی کنید",
  "outcome": "revisit_required"
}
```

`continuation_owner`: `same_technician`, `next_technician`, `support`, `organization`, `waiting_for_part`.

`outcome`: `completed`, `revisit_required`, `waiting_for_part`, `waiting_for_organization`, `needs_inspection`.

اگر سازمان `request_changes` بزند، وضعیت گزارش `changes_requested` می‌شود. همان `visit_id` را ویرایش و دوباره با PUT بالا ثبت کنید؛ بازدید جدید برای اصلاح نسازید.

## اپ سازمان

`GET /api/organization/orders/{order}/field-service` فقط برای حساب سازمانیِ صاحب سفارش است. `visits` از جدید به قدیم می‌آید و شامل اقدامات، کار باقیمانده، مسئول ادامه، زمان‌های حضور، عکس‌ها و تاریخچهٔ نظر سازمان است. سازمان این فیلدها را ویرایش نمی‌کند.

برای بررسی گزارش با وضعیت `submitted`:

```http
POST /api/organization/orders/{order}/field-service/{visit}/review
Content-Type: application/json

{"decision":"approve"}
```

یا:

```json
{"decision":"request_changes","note":"تست نهایی ثبت نشده است"}
```

برای درخواست اصلاح، `note` الزامی است. تأیید یک گزارش یعنی فعالیت ثبت‌شده تأیید شده است. اگر `has_remaining_work=true` باشد، کار همچنان باز می‌ماند و بازدید بعدی مجاز است. تنها تأیید گزارش با `outcome=completed` و بدون کار باقیمانده، سفارش را به وضعیت انجام‌شده می‌برد.

`work_status` برای نوار وضعیت: `awaiting_visit`, `in_progress`, `awaiting_approval`, `changes_requested`, `follow_up_required`, `completed`.

## عکس و خطاها

در هر عکس، `url` یک لینک امضاشدهٔ ۱۵ دقیقه‌ای است و برای نمایش به Bearer token نیاز ندارد. پس از انقضا، GET گزارش را تکرار کنید تا URL تازه بگیرید. عکس‌ها روی دیسک خصوصی Laravel ذخیره می‌شوند.

کدهای مهم: 401 بدون ورود، 403 کاربر نامرتبط، 404 سفارش/بازدید ناموجود، 409 ترتیب اشتباه عملیات یا گزارشِ قفل‌شده، 422 ورودی نامعتبر. برای 422 از فیلد `errors` پاسخ استفاده کنید.

## تست دستی لوکال

1. Docker Desktop را اجرا کنید. سپس در ریشهٔ پروژه:

```bash
docker compose up --build -d
docker compose exec app php artisan field-service:demo
```

خروجیِ دستور دوم `order_id`, `technician_token`, `organization_token` می‌دهد. توکن‌ها فقط همان لحظه چاپ می‌شوند و دادهٔ ساختگی در دیتابیس محلی Docker قرار می‌گیرد. دستور در production اجرا نمی‌شود.

2. با `technician_token`، GET گزارش، POST شروع، آپلود عکس اختیاری و PUT ثبت گزارش را به‌ترتیب اجرا کنید. در بار اول `has_remaining_work=true` بفرستید.
3. با `organization_token`، GET همان سفارش را بزنید؛ ابتدا `request_changes` با توضیح، بعد از اصلاح تکنسین `approve` را تست کنید. باید `follow_up_required` بماند و وضعیت سفارش ۲ نشود.
4. بازدید دوم را با توکن تکنسین شروع کنید؛ این بار `has_remaining_work=false` و `outcome=completed` بفرستید. پس از تأیید سازمان، `work_status=completed` و وضعیت سفارش ۲ می‌شود.
5. با ادمین لوکال وارد `http://localhost:8000/admin/orders/{order_id}` شوید. جدول «گزارش‌های روزانهٔ تکنسین میدانی» و جزئیات هر بازدید، عکس‌ها و تاریخچهٔ بررسی را ببینید.

در سرور بعد از انتقال کد، `php artisan migrate --force` لازم است. برای این قابلیت build فرانت Laravel لازم نیست؛ اپ‌های موبایل و فرانت سازمان باید فرم و نمایش داده‌های بالا را پیاده‌سازی کنند.

برای ثبت اتاق در آدرس سازمان، API موجودِ ایجاد/ویرایش آدرس اکنون فیلد اختیاری `room` (متن، حداکثر ۱۰۰ نویسه) را می‌پذیرد و در پاسخ آدرس برمی‌گرداند.
