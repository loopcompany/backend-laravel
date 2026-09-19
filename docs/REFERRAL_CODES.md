# کدهای معرف

## پنل ادمین

از منوی «مدیریت کاربران» وارد «کدهای معرف» شوید. با انتخاب کاربر و ذخیره فرم، یک کد خوانا با قالب `LOOP-XXXXXX` ساخته و به همان کاربر اختصاص داده می‌شود.

وضعیت کدها:

- `active` — فعال
- `sent` — فرستاده شده
- `used` — استفاده شده

ثبت و مصرف کد در فرآیند ثبت‌نام انجام نمی‌شود. کد فقط وقتی مصرف می‌شود که اپلیکیشن API زیر را برای ثبت نهایی کد فراخوانی کند.

## API ثبت و مصرف کد

```http
POST /api/referral-codes/check
Content-Type: application/json
Accept: application/json
```

### Body

```json
{
  "code": "LOOP-AB7K92"
}
```

API فقط کدهایی را قبول می‌کند که ادمین از پنل ساخته و به یک کاربر اختصاص داده است. اگر کد فعال یا فرستاده‌شده باشد، پاسخ موفق برمی‌گردد و وضعیت همان کد در همان تراکنش به `used` تغییر می‌کند.

### پاسخ موفق

```json
{
  "success": true,
  "valid": true,
  "message": "کد معرف با موفقیت ثبت و مصرف شد.",
  "data": {
    "code": "LOOP-AB7K92",
    "status": "used",
    "status_label": "استفاده شده",
    "source": "managed_referral_code",
    "referrer": {
      "id": 82,
      "name": "نام",
      "last_name": "نام خانوادگی"
    }
  }
}
```

کد ناموجود، `REFERRAL_CODE_NOT_FOUND` و کدی که قبلاً مصرف شده، `REFERRAL_CODE_USED` برمی‌گرداند.

## استقرار

```bash
php artisan migrate --force
php artisan optimize:clear
```

## Referral discount

In the admin panel, create or edit a referral code and set `discount_percent` from 0 to 100.
The mobile app must send the referral code in the authenticated order request:

```http
POST /api/orders/submit
Authorization: Bearer <sanctum-token>
Content-Type: application/json
```

```json
{
  "total_price": 1000000,
  "referral_code": "LOOP-AB7K92"
}
```

The order stores the assigned code and percentage. The percentage is calculated from the final order total when the order is paid. The existing discount-code flow remains separate and can be applied alongside the referral discount.

`POST /api/referral-codes/check` also requires the authenticated user's Sanctum token. It consumes the code for that user and returns `discount_percent`. Calling it again by the same user is safe; another user receives `REFERRAL_CODE_USED`.
