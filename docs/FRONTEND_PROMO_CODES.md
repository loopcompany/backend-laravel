# راهنمای فرانت: کد تخفیف جدید

این قابلیت از سیستم قدیمی تخفیف‌های باشگاه و جم جداست. کد را ادمین در پنل می‌سازد و هر کاربر برای هر کد فقط یک‌بار می‌تواند در ثبت سفارش استفاده کند.

## چرخه استفاده

1. فرانت مقدار کد را از کاربر می‌گیرد.
2. برای نمایش نتیجه فوری، کد را با API بررسی می‌کند.
3. اگر پاسخ معتبر بود، درصد تخفیف را در صفحه سفارش نمایش می‌دهد.
4. هنگام ثبت نهایی سفارش، همان کد را در فیلد `promo_code` می‌فرستد.
5. سرور کد را دوباره به‌صورت اتمیک بررسی می‌کند و در صورت موفقیت، مصرف کد را برای همان کاربر ثبت می‌کند.

> پاسخ API بررسی، کد را مصرف نمی‌کند. مصرف فقط در ثبت موفق سفارش انجام می‌شود؛ بنابراین فرانت نباید صرفاً با پاسخ check وضعیت کد را مصرف‌شده فرض کند.

## احراز هویت

همه APIهای زیر با توکن Sanctum کار می‌کنند:

```http
Authorization: Bearer USER_TOKEN
Accept: application/json
Content-Type: application/json
```

## بررسی کد

```http
POST /api/promo-codes/check
```

نمونه درخواست:

```json
{
  "code": "SUMMER20"
}
```

نمونه پاسخ موفق `200`:

```json
{
  "success": true,
  "valid": true,
  "message": "کد تخفیف معتبر است.",
  "data": {
    "code": "SUMMER20",
    "discount_percent": 20,
    "expires_at": "2026-10-01T20:30:00.000000Z"
  }
}
```

`expires_at` در کد بدون تاریخ انقضا `null` است.

خطاهای مهم:

| HTTP | `error_code` | معنی |
|---:|---|---|
| 404 | `PROMO_CODE_NOT_FOUND` | کد وجود ندارد |
| 409 | `PROMO_CODE_INACTIVE` | کد غیرفعال است |
| 409 | `PROMO_CODE_EXPIRED` | کد منقضی شده است |
| 409 | `PROMO_CODE_ALREADY_USED` | همین کاربر قبلاً از کد استفاده کرده است |

## ثبت سفارش

در endpoint فعلی ثبت سفارش، فیلد زیر را اضافه کنید:

```http
POST /api/orders/submit
```

نمونه body حداقلی:

```json
{
  "address_id": 12,
  "category_id": 4,
  "total_price": 1000000,
  "date": "2026-09-25",
  "time": "10:00",
  "platform": "android",
  "promo_code": "SUMMER20"
}
```

`promo_code` اختیاری است. اگر کاربر کد وارد نکرد، این فیلد را حذف کنید یا `null` بفرستید.

نمونه پاسخ موفق ثبت سفارش:

```json
{
  "success": true,
  "message": "سفارش شما با موفقیت ثبت شد.",
  "data": {
    "order_id": 1234,
    "order": {
      "id": 1234,
      "promo_code_id": 7,
      "promo_discount_percent": 20
    }
  }
}
```

در صورت نامعتبر بودن کد هنگام ثبت سفارش، پاسخ `409` و `error_code` برابر `INVALID_PROMO_CODE` است. فرانت باید مبلغ را از پاسخ محاسبات پرداخت یا اطلاعات سفارش نهایی دریافت کند و مبلغ تخفیف را خودش مرجع نهایی در نظر نگیرد؛ محاسبه نهایی همیشه سمت سرور انجام می‌شود.

## رفتار یک‌بارمصرف

- هر کاربر می‌تواند هر کد را فقط یک‌بار استفاده کند.
- بررسی چندباره کد قبل از ثبت سفارش مشکلی ندارد.
- بعد از ثبت موفق سفارش، استفاده ثبت می‌شود؛ حتی اگر پرداخت سفارش بعداً انجام شود.
- هم‌زمانی چند درخواست نیز با محدودیت یکتای دیتابیس کنترل می‌شود.
- این کد جدید با `discount_code` قدیمی و `referral_code` متفاوت است. برای جلوگیری از ابهام، فرانت کد جدید را فقط در `promo_code` بفرستد.

## نمونه Axios

```js
export async function checkPromoCode(code, token) {
  const response = await axios.post(
    `${uri}/promo-codes/check`,
    { code },
    {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    }
  );

  return response.data;
}

export async function submitOrder(payload, promoCode, token) {
  const body = {
    ...payload,
    ...(promoCode ? { promo_code: promoCode } : {}),
  };

  const response = await axios.post(`${uri}/orders/submit`, body, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  return response.data;
}
```

## پنل ادمین

مسیر منوی پنل: `مدیریت تخفیف‌ها ← کدهای تخفیف جدید`

ادمین می‌تواند موارد زیر را تنظیم کند:

- کد خوانا و یکتا که به‌صورت خودکار توسط سیستم تولید می‌شود؛
- درصد تخفیف بین ۱ تا ۱۰۰؛
- فعال یا غیرفعال بودن؛
- تاریخ انقضای اختیاری؛
- مشاهده تعداد استفاده‌ها.

پس از deploy، migration را اجرا کنید و برای نقش‌های پنل مجوزهای جدید را sync کنید:

```bash
php artisan migrate --force
php artisan permissions:sync
php artisan optimize:clear
```
