# راهنمای کامل قیمت سفارش برای فرانت

این سند قرارداد فعلی بک‌اند برای نمایش و پرداخت قیمت سفارش را توضیح می‌دهد. این قرارداد برای سفارش‌های عادی، شرکتی و سازمانی کاربرد دارد؛ تفاوت سفارش سازمانی در فیلدهای `service_schedule` و نوع حساب کاربر است، نه در endpoint اصلی قیمت.

## خلاصهٔ مهم

در سیستم فعلی قیمت سفارش چند مرحله دارد:

1. فرانت هنگام ساخت سفارش یک `total_price` اولیه می‌فرستد.
2. بک‌اند این مقدار را در `orders.pakar_price` ذخیره می‌کند.
3. بعد از پذیرش سفارش، تکنسین می‌تواند `technician_price` را تعیین کند.
4. خدمات اضافی به `extra_price` اضافه می‌شوند.
5. هنگام پرداخت، تخفیف‌ها و پیش‌پرداخت اعمال می‌شوند.
6. مبلغ واقعی پرداخت توسط بک‌اند محاسبه می‌شود؛ فرانت نباید مبلغ نهایی را فقط از `total_price` محاسبه یا به‌صورت قطعی نمایش دهد.

> در وضعیت فعلی، بک‌اند `total_price` را از روی `steps` یا قرارداد سازمان دوباره محاسبه نمی‌کند. مقدار اولیه از فرانت دریافت و ذخیره می‌شود.

## واحد مبلغ

- همهٔ مبلغ‌ها عدد صحیح هستند.
- واحد فعلی مبلغ‌ها تومان است.
- مبلغ اعشاری ارسال نکنید.
- مقدارهای مبلغی می‌توانند `null` باشند؛ در UI مقدار `null` را «هنوز تعیین نشده» نمایش دهید، نه صفر قطعی.

## فیلدهای مهم سفارش

| فیلد | کاربرد | زمان مقداردهی |
|---|---|---|
| `pakar_price` | قیمت اولیه/پایهٔ سفارش | هنگام ثبت سفارش |
| `technician_price` | قیمت تعیین‌شده توسط تکنسین | بعد از بررسی یا پذیرش تکنسین |
| `extra_price` | مجموع خدمات اضافی انتخاب‌شده | بعد از ثبت یا تغییر خدمات اضافی |
| `discount_price` | مبلغ تخفیف ذخیره‌شده هنگام پرداخت | معمولاً بعد از پرداخت |
| `referral_discount_percent` | درصد تخفیف کد معرف | هنگام ثبت سفارش با کد معرف |
| `promo_discount_percent` | درصد کد تخفیف جدید | هنگام ثبت سفارش با `promo_code` |
| `loop_cost_estimate` | هزینهٔ تقریبی اعلام‌شده توسط لوپ | در صورت وجود برآورد |
| `prepayment` | درصد پیش‌پرداخت | در صورت فعال بودن پیش‌پرداخت |
| `prepayment_payment_status` | وضعیت پیش‌پرداخت؛ `0` پرداخت نشده، `1` پرداخت شده | بعد از پرداخت پیش‌پرداخت |
| `payment_status` | وضعیت پرداخت نهایی؛ `0` پرداخت نشده، `1` پرداخت شده | بعد از پرداخت نهایی |
| `promo_code_id` | شناسهٔ کد تخفیف جدید | در صورت استفاده |

## چرخهٔ قیمت

### مرحلهٔ اول: ساخت سفارش

Endpoint:

```http
POST /api/orders/submit
```

Headerهای لازم:

```http
Authorization: Bearer USER_TOKEN
Accept: application/json
Content-Type: application/json
```

نمونهٔ body:

```json
{
  "address_id": 12,
  "category_id": 4,
  "total_price": 1500000,
  "is_urgent": false,
  "is_fixed": false,
  "description": "توضیحات سفارش",
  "date": "2026-09-25",
  "time": "10:00",
  "platform": "android",
  "female_count": 0,
  "male_count": 1,
  "unspecified_count": 0,
  "steps": [],
  "file_paths": []
}
```

قواعد مهم:

- `total_price` الزامی، عددی و بزرگ‌تر یا مساوی صفر است.
- بک‌اند مقدار `total_price` را در `pakar_price` ذخیره می‌کند.
- `steps` برای ذخیرهٔ انتخاب‌های فرم و جزئیات سفارش استفاده می‌شود؛ جمع قیمت `steps` در این endpoint جایگزین `total_price` نمی‌شود.
- در این مرحله معمولاً `technician_price` و `extra_price` هنوز `null` هستند.

نمونهٔ پاسخ موفق:

```json
{
  "success": true,
  "message": "سفارش شما با موفقیت ثبت شد.",
  "data": {
    "order_id": 1234,
    "order": {
      "id": 1234,
      "pakar_price": 1500000,
      "technician_price": null,
      "extra_price": null,
      "discount_price": null,
      "payment_status": 0,
      "status": 0
    }
  }
}
```

### مرحلهٔ دوم: قیمت تکنسین

تکنسین قیمت نهایی خدمات خود را ثبت می‌کند:

```http
POST /api/technician/orders/{orderId}/technician-description
```

نمونهٔ body:

```json
{
  "technician_des": "هزینهٔ انجام خدمت اعلام شد.",
  "date": "2026-09-25",
  "time": "10:00",
  "technician_price": 1300000
}
```

درخواست فقط توسط تکنسین مجاز همان سفارش پذیرفته می‌شود و `technician_price` باید عدد صحیح بزرگ‌تر یا مساوی صفر باشد.

نمونهٔ پاسخ:

```json
{
  "success": true,
  "data": {
    "order_id": 1234,
    "technician_price": 1300000,
    "date": "2026-09-25",
    "time": "10:00"
  }
}
```

پس از این مرحله، برای سفارش معمولی مبلغ پایهٔ پرداخت معمولاً `technician_price` است؛ اگر این مقدار وجود نداشته باشد، محاسبه به `pakar_price` برمی‌گردد.

### مرحلهٔ سوم: خدمات اضافی

تکنسین خدمات اضافی را دریافت و ثبت می‌کند.

دریافت فهرست خدمات برای تکنسین:

```http
GET /api/technician/extra-services?category_id=4&order_id=1234
```

ثبت خدمات انتخاب‌شده توسط تکنسین:

```http
POST /api/technician/submit-extra-services
```

نمونهٔ body:

```json
{
  "order_id": 1234,
  "extras": [
    {
      "id": 7,
      "extraDetailId": 18
    },
    {
      "id": 9,
      "extraDetailId": null,
      "price": 250000
    }
  ]
}
```

منطق قیمت خدمت اضافی:

- اگر `extraDetailId` وجود داشته باشد، قیمت از همان `extra_service_details.price` خوانده می‌شود.
- اگر جزئیات وجود نداشته باشد و `recommended_price` بیشتر از صفر باشد، قیمت پیشنهادی استفاده می‌شود.
- در غیر این صورت مقدار `price` ارسالی استفاده می‌شود.
- بک‌اند مجموع موارد ثبت‌شده را محاسبه و در `orders.extra_price` ذخیره می‌کند.
- ارسال آرایهٔ خالی، خدمات اضافی قبلی را حذف و `extra_price` را صفر می‌کند.

نمایش خدمات اضافی برای صاحب سفارش:

```http
GET /api/orders/{orderId}/extra-services
```

نمونهٔ پاسخ:

```json
{
  "success": true,
  "data": [
    {
      "order_id": 1234,
      "extra_service_id": 7,
      "extra_service_detail_id": 18,
      "price": 200000,
      "title": "خدمت اضافی"
    }
  ]
}
```

### مرحلهٔ چهارم: تخفیف‌ها

#### کد تخفیف جدید ادمین

بررسی کد:

```http
POST /api/promo-codes/check
```

```json
{
  "code": "LOOP-HPUQTK"
}
```

در ثبت سفارش نیز باید همان کد را بفرستید:

```json
{
  "promo_code": "LOOP-HPUQTK"
}
```

ویژگی‌ها:

- درصد تخفیف از پنل ادمین تعیین می‌شود.
- هر کاربر برای هر کد فقط یک‌بار می‌تواند استفاده کند.
- بررسی کد آن را مصرف نمی‌کند؛ مصرف هنگام ثبت موفق سفارش ثبت می‌شود.
- کد جدید با سیستم قدیمی `discount_code` متفاوت است.
- درصد تخفیف جدید روی مبلغ پایه به‌علاوهٔ خدمات اضافی اعمال می‌شود.

#### کد معرف

در ثبت سفارش:

```json
{
  "referral_code": "LOOP-ABC123"
}
```

درصد آن در `referral_discount_percent` ذخیره می‌شود و هنگام پرداخت روی مبلغ پایه و خدمات اضافی اعمال می‌گردد.

#### کد تخفیف قدیمی

فیلد قدیمی همچنان وجود دارد:

```json
{
  "discount_code": "OLD-CODE"
}
```

استعلام این سیستم با endpoint زیر انجام می‌شود:

```http
POST /api/orders/check-discount
```

نمونهٔ body:

```json
{
  "discount_code": "OLD-CODE",
  "category_id": 4
}
```

این سیستم به باشگاه/جم و دسته‌بندی سفارش وابسته است و با `promo_code` جدید یکی نیست. اگر اپ از سیستم جدید استفاده می‌کند، برای استعلام و ثبت سفارش از `promo_code` استفاده کنید و این endpoint را با آن ترکیب نکنید.

## فرمول مبلغ نهایی سفارش معمولی

به‌صورت مفهومی:

```text
base = technician_price ?? pakar_price
subtotal = base + extra_price

old_discount = بیشترین مقدار تخفیف سیستم قدیمی و discount_price
promo_discount = subtotal × promo_discount_percent ÷ 100
referral_discount = subtotal × referral_discount_percent ÷ 100

payable = subtotal
         - old_discount
         - promo_discount
         - referral_discount
```

مبلغ نهایی هیچ‌وقت کمتر از صفر برگردانده نمی‌شود.

> `discount_price` مبلغ است، نه درصد. درصدها در فیلدهای جداگانه ذخیره می‌شوند.

## حداقل مبلغ

برای دریافت حداقل مبلغ فعلی:

```http
GET /api/min-price
```

نمونهٔ پاسخ:

```json
{
  "data": {
    "id": 3,
    "price": 200000
  }
}
```

طبق منطق فعلی پرداخت، اگر مبلغ بعد از تخفیف کمتر از `min_price.price` شود، مقدار `200000` به مبلغ اضافه می‌شود. این رفتار فعلی بک‌اند است؛ فرانت نباید آن را به‌عنوان یک floor ساده پیاده‌سازی کند و باید مبلغ نهایی را از پرداخت بک‌اند بگیرد.

## پیش‌پرداخت سازمانی و سفارش‌های خاص

اگر سفارش `loop_cost_estimate` و `prepayment` داشته باشد و پیش‌پرداخت هنوز پرداخت نشده باشد:

```text
prepayment_amount = loop_cost_estimate × prepayment ÷ 100
```

در این حالت مبلغ endpoint پرداخت، مبلغ پیش‌پرداخت است؛ نه مبلغ کامل سفارش.

فیلدهای مرتبط:

```json
{
  "loop_cost_estimate": 10000000,
  "prepayment": 30,
  "prepayment_payment_status": 0
}
```

بعد از پرداخت پیش‌پرداخت، `prepayment_payment_status` برابر `1` می‌شود و در پرداخت بعدی، مبلغ پیش‌پرداخت از مبلغ باقی‌مانده کم می‌شود.

## سفارش سازمانی

سفارش سازمانی نیز از همین endpoint استفاده می‌کند:

```http
POST /api/orders/submit
```

نمونهٔ بخش سازمانی body:

```json
{
  "category_id": 3,
  "total_price": 5000000,
  "service_schedule": {
    "type": "long_term",
    "long_term": {
      "duration": "یک ماه",
      "date": "2026-10-01",
      "time": "09:00"
    }
  }
}
```

نکات مهم برای فرانت سازمانی:

- `service_schedule` زمان‌بندی سرویس است، نه منبع قیمت.
- `total_price` فعلاً از فرانت می‌آید و در `pakar_price` ذخیره می‌شود.
- قرارداد سازمانی در مسیر فعلی به‌صورت خودکار قیمت سفارش را محاسبه نمی‌کند.
- اگر قیمت تکنسین تعیین شود، `technician_price` مبنای نهایی قرار می‌گیرد.
- اگر پیش‌پرداخت فعال باشد، منطق `loop_cost_estimate` و `prepayment` اجرا می‌شود.

## پرداخت با کیف پول

Endpoint:

```http
POST /api/wallet/pay-order
```

Body:

```json
{
  "orderId": 1234
}
```

بک‌اند مبلغ قابل پرداخت را خودش محاسبه می‌کند و از موجودی کسر می‌کند.

نمونهٔ پاسخ موفق:

```json
{
  "success": true,
  "message": "سفارش شما با موفقیت پرداخت شد.",
  "data": {
    "order_id": 1234,
    "paid_amount": 1275000,
    "remaining_balance": 3000000,
    "transaction_id": 4567
  }
}
```

خطاهای مهم:

| HTTP | `error_code` | معنی |
|---:|---|---|
| 404 | `ORDER_NOT_FOUND` | سفارش برای این کاربر پیدا نشد |
| 409 | `ALREADY_PAID` | سفارش قبلاً پرداخت شده |
| 402 | `INSUFFICIENT_BALANCE` | موجودی کیف پول کافی نیست |
| 400 | `INVALID_PRICE` | مبلغ قابل پرداخت معتبر نیست |

## پرداخت از طریق درگاه

Endpoint:

```http
POST /api/orders/gateway-payment
```

Body:

```json
{
  "order_id": 1234,
  "linking_url": "myapp://payment-result"
}
```

نمونهٔ پاسخ موفق ایجاد لینک پرداخت:

```json
{
  "success": true,
  "message": "لینک پرداخت ایجاد شد.",
  "data": {
    "payment_url": "https://gateway.example/...?token=...",
    "transaction_id": 4567,
    "amount": 1275000
  }
}
```

مبلغ `data.amount` مبلغی است که باید برای درگاه نمایش داده یا استفاده شود. پس از بازگشت از درگاه، وضعیت سفارش را دوباره از API سفارش دریافت کنید و فقط به callback سمت موبایل اعتماد نکنید.

محدودیت‌های فعلی درگاه:

- حداقل مبلغ: `1000`
- حداکثر مبلغ: `99000000`
- پرداخت سفارش پرداخت‌شده دوباره مجاز نیست.

## دریافت سفارش و مبلغ‌ها

لیست سفارش‌ها:

```http
GET /api/orders
```

جزئیات یک سفارش:

```http
POST /api/orders/detail
```

```json
{
  "orderId": 1234
}
```

فرانت باید این فیلدها را از پاسخ بخواند:

```js
const basePrice = order.technician_price ?? order.pakar_price ?? 0;
const extraPrice = order.extra_price ?? 0;
const paymentStatus = order.payment_status;
const prepaymentStatus = order.prepayment_payment_status;
```

برای مبلغ قابل پرداخت، به‌جای محاسبهٔ قطعی در فرانت، یکی از endpointهای پرداخت را صدا بزنید. پاسخ درگاه `amount` و پاسخ کیف پول `paid_amount` را برمی‌گرداند.

## پیشنهاد جریان پیاده‌سازی در اپ

```text
ثبت اطلاعات سفارش
        ↓
محاسبه و نمایش قیمت اولیه از total_price
        ↓
POST /api/orders/submit
        ↓
دریافت order_id
        ↓
انتظار برای قیمت تکنسین و خدمات اضافی
        ↓
GET /api/orders یا POST /api/orders/detail
        ↓
نمایش technician_price + extra_price و تخفیف‌ها
        ↓
انتخاب روش پرداخت
   ┌────┴────┐
   ↓         ↓
کیف پول    درگاه
   ↓         ↓
pay-order  gateway-payment
        ↓
دریافت وضعیت نهایی سفارش
```

## نکات خطا و سازگاری

- پس از تغییر قیمت تکنسین یا خدمات اضافی، قیمت قبلی را cache نکنید.
- اگر `technician_price` یا `extra_price` تغییر کرد، دوباره جزئیات سفارش را دریافت کنید.
- ارسال `total_price` منفی یا غیرعددی باعث پاسخ `422` می‌شود.
- اگر کد تخفیف جدید نامعتبر باشد، ثبت سفارش با `409` و `INVALID_PROMO_CODE` رد می‌شود.
- اگر سفارش لغو، منقضی یا قبلاً پرداخت شده باشد، فرانت نباید دکمهٔ پرداخت را فعال نگه دارد.
- مبلغ نهایی قابل اعتماد، مبلغی است که بک‌اند هنگام شروع پرداخت محاسبه و در پاسخ پرداخت برمی‌گرداند.

## نکتهٔ فنی برای تیم محصول

در نسخهٔ فعلی، قیمت اولیهٔ سازمانی و عادی از سمت فرانت ارسال می‌شود. اگر قرار است قیمت بر اساس قرارداد سازمان، تعرفهٔ دسته‌بندی یا جمع قطعی `steps` محاسبه شود، باید یک API قیمت‌گذاری سمت بک‌اند اضافه شود؛ صرفاً تغییر UI فرانت برای این هدف کافی و امن نیست.
