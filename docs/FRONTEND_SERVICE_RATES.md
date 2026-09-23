# راهنمای دریافت نرخ خدمات و محاسبه مبلغ سفارش برای فرانت

این سند قرارداد فعلی API برای نمایش نرخ خدمات، دریافت گزینه‌های شرطی، محاسبه مبلغ پایه و ثبت سفارش را توضیح می‌دهد.

## خلاصهٔ فرآیند

فرانت باید این مراحل را انجام دهد:

1. شناسهٔ دسته‌بندی خدمت (`categoryId`) را مشخص کند.
2. فرم و نرخ گزینه‌های آن دسته‌بندی را از `POST /api/steps/fetch` بگیرد.
3. در صورت انتخاب گزینه‌ای که مرحلهٔ شرطی دارد، `POST /api/steps/fetch-conditional` را صدا بزند.
4. قیمت گزینه‌های انتخاب‌شده را از `field_details[].price` بخواند.
5. مبلغ پایه را محاسبه و در `total_price` قرار دهد.
6. همان انتخاب‌ها را در `steps` و مبلغ محاسبه‌شده را در `POST /api/orders/submit` ارسال کند.

> واحد همهٔ مبلغ‌ها در این API تومان است و باید به‌صورت عدد صحیح ارسال شوند؛ برای مثال `200000` یعنی ۲۰۰ هزار تومان.

## تنظیمات مشترک درخواست‌ها

در مثال‌های زیر، مقدار `BASE_URL` دامنهٔ اصلی API پروژه است:

```text
BASE_URL = https://example.com
```

تمام مسیرهای این سند با `/api` شروع می‌شوند. درخواست‌های مربوط به مراحل و ثبت سفارش نیاز به توکن کاربر دارند:

```http
Authorization: Bearer USER_TOKEN
Accept: application/json
Content-Type: application/json
```

اگر توکن ارسال نشود، پاسخ معمولاً `401 Unauthorized` خواهد بود.

## ۱. پیدا کردن دسته‌بندی خدمت

اگر فرانت هنوز شناسهٔ دسته‌بندی را ندارد، می‌تواند فهرست دسته‌بندی‌ها را دریافت کند:

```http
GET /api/categories
```

از `id` دسته‌بندی موردنظر به‌عنوان `categoryId` استفاده شود. مقدار `categoryId` باید در جدول دسته‌بندی‌ها وجود داشته باشد.

## ۲. دریافت مراحل و نرخ خدمات

### Endpoint

```http
POST /api/steps/fetch
```

### Body

```json
{
  "categoryId": 4
}
```

### نمونهٔ درخواست با JavaScript

```js
const response = await fetch(`${BASE_URL}/api/steps/fetch`, {
  method: 'POST',
  headers: {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ categoryId: 4 }),
});

const steps = await response.json();
```

### شکل واقعی پاسخ

پاسخ یک آرایه از گروه‌های مرحله‌ای است. هر گروه می‌تواند چند فیلد داشته باشد و هر فیلد می‌تواند چند گزینهٔ دارای قیمت داشته باشد:

```json
[
  [
    {
      "id": 10,
      "title": "نوع سرویس",
      "type": "radioButton",
      "is_required": 1,
      "field_details": [
        {
          "id": 101,
          "title": "سرویس معمولی",
          "price": 200000,
          "show_price": 1,
          "affect_on_price": 1,
          "value": 0,
          "type": "radioButton",
          "user_descriptions": ""
        },
        {
          "id": 102,
          "title": "سرویس ویژه",
          "price": 350000,
          "show_price": 1,
          "affect_on_price": 1,
          "value": 0,
          "type": "radioButton",
          "user_descriptions": ""
        }
      ]
    }
  ],
  [
    {
      "id": 11,
      "title": "تعداد",
      "type": "counter",
      "is_required": 1,
      "field_details": [
        {
          "id": 103,
          "title": "تعداد دستگاه",
          "price": 100000,
          "show_price": 1,
          "affect_on_price": 1,
          "value": 0,
          "type": "counter",
          "user_descriptions": ""
        }
      ]
    }
  ]
]
```

نرخ خدمت از این مسیر خوانده می‌شود:

```text
steps[groupIndex][fieldIndex].field_details[detailIndex].price
```

برای هر گزینه، این فیلدها مهم هستند:

| فیلد | کاربرد |
|---|---|
| `id` | شناسهٔ گزینه؛ هنگام ساخت `steps` حفظ شود. |
| `title` | عنوان قابل نمایش در اپ. |
| `type` | نوع فیلد مانند `radioButton`، `checkbox`، `counter` یا `input`. |
| `price` | قیمت واحد گزینه، به تومان. |
| `value` | مقدار اولیه؛ فرانت باید مقدار انتخاب‌شدهٔ کاربر را جایگزین کند. |
| `show_price` | آیا قیمت در UI نمایش داده شود یا نه. این فیلد به‌تنهایی به معنی رایگان بودن نیست. |
| `affect_on_price` | مشخص می‌کند گزینه از نظر تنظیمات برای اثرگذاری روی قیمت در نظر گرفته شده است. |
| `is_required` | اجباری بودن فیلد. |

## ۳. دریافت گزینه‌های شرطی

اگر بعد از انتخاب یک گزینه، فیلدهای دیگری باید نمایش داده شوند، این مسیر را صدا بزنید:

```http
POST /api/steps/fetch-conditional
```

### Body

```json
{
  "categoryId": 4,
  "fieldId": 10,
  "fieldDetailId": 101
}
```

پارامترها:

| پارامتر | توضیح |
|---|---|
| `categoryId` | شناسهٔ دسته‌بندی فعلی. |
| `fieldId` | شناسهٔ فیلدی که گزینه از آن انتخاب شده است. |
| `fieldDetailId` | شناسهٔ گزینهٔ انتخاب‌شده. |

پاسخ این endpoint نیز با همان ساختار آرایه‌ای `steps` برمی‌گردد و قیمت گزینه‌های شرطی نیز از `field_details[].price` خوانده می‌شود.

اگر برای انتخاب انجام‌شده فیلد شرطی تعریف نشده باشد، پاسخ آرایهٔ خالی خواهد بود:

```json
[]
```

## ۴. محاسبهٔ مبلغ پایه در فرانت

فرانت باید فقط گزینه‌های انتخاب‌شده را در محاسبه وارد کند.

فرمول کلی:

```text
مبلغ پایه = مجموع (قیمت هر گزینه × مقدار انتخاب‌شده)
```

برای `radioButton` و `checkbox` مقدار گزینهٔ انتخاب‌شده معمولاً `1` و گزینه‌های انتخاب‌نشده `0` است. برای `counter` مقدار انتخاب‌شده تعداد است.

### نمونهٔ محاسبه

```text
سرویس معمولی: 200000 × 1 = 200000
تعداد دستگاه: 100000 × 2 = 200000
مبلغ پایه: 400000 تومان
```

### نمونهٔ کد JavaScript

```js
function calculateBasePrice(steps) {
  return steps.reduce((total, group) => {
    return total + group.reduce((groupTotal, field) => {
      if (!Array.isArray(field.field_details)) {
        return groupTotal;
      }

      return groupTotal + field.field_details.reduce((fieldTotal, detail) => {
        const price = Number(detail.price ?? 0);
        const value = Number(detail.value ?? 0);
        const affectsPrice = detail.affect_on_price === 1
          || detail.affect_on_price === true
          || detail.affect_on_price === '1';

        if (!affectsPrice || !Number.isFinite(price) || !Number.isFinite(value)) {
          return fieldTotal;
        }

        return fieldTotal + (price * value);
      }, 0);
    }, 0);
  }, 0);
}

const totalPrice = calculateBasePrice(selectedSteps);
```

قوانین پیاده‌سازی:

- `null` یا قیمت خالی را صفر فرض کنید، اما در UI آن را با «قیمت تعیین نشده» از صفر واقعی تفکیک کنید.
- مبلغ را اعشاری ارسال نکنید.
- مبلغ منفی ارسال نکنید.
- در `radioButton` فقط گزینهٔ انتخاب‌شده مقدار `1` داشته باشد.
- در `checkbox` هر گزینهٔ فعال مقدار `1` و هر گزینهٔ غیرفعال مقدار `0` داشته باشد.
- در `counter` مقدار `value` باید تعداد باشد، نه قیمت نهایی.
- `show_price = 0` فقط به معنی عدم نمایش قیمت است؛ منطق قیمت را از `price` و تنظیم `affect_on_price` بخوانید.

## ۵. ساختار `steps` برای ثبت سفارش

هنگام ثبت سفارش، ساختار انتخاب‌ها را شبیه پاسخ API حفظ کنید؛ فقط `value`ها را با انتخاب کاربر پر کنید:

```json
[
  [
    {
      "id": 10,
      "type": "radioButton",
      "field_details": [
        {
          "id": 101,
          "value": 1,
          "price": 200000,
          "user_descriptions": ""
        },
        {
          "id": 102,
          "value": 0,
          "price": 350000,
          "user_descriptions": ""
        }
      ]
    }
  ],
  [
    {
      "id": 11,
      "type": "counter",
      "field_details": [
        {
          "id": 103,
          "value": 2,
          "price": 100000,
          "user_descriptions": ""
        }
      ]
    }
  ]
]
```

بک‌اند فقط جزئیات دارای `value` غیرخالی/غیرصفر را در `order_details` ذخیره می‌کند. بنابراین شناسه‌ها و مقدارهای انتخاب‌شده را درست ارسال کنید.

## ۶. ثبت سفارش

### Endpoint

```http
POST /api/orders/submit
```

### نمونهٔ Body

```json
{
  "address_id": 12,
  "category_id": 4,
  "total_price": 400000,
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

در نمونهٔ واقعی، آرایهٔ `steps` باید با انتخاب‌های مرحلهٔ قبل پر شود.

فیلدهای اجباری فعلی:

| فیلد | نوع | توضیح |
|---|---|---|
| `address_id` | integer | شناسهٔ آدرس کاربر. |
| `category_id` | integer | شناسهٔ دسته‌بندی خدمت. |
| `total_price` | number | مبلغ پایهٔ محاسبه‌شده توسط فرانت. |
| `date` | `Y-m-d` | تاریخ اجرای خدمت. |
| `platform` | string | مانند `android` یا `ios`. |

فیلدهای اختیاری شامل `time`، توضیحات، تعداد تکنسین‌ها، کد تخفیف، کد معرف، `service_schedule` و فایل‌ها هستند.

### پاسخ موفق

```json
{
  "success": true,
  "message": "سفارش شما با موفقیت ثبت شد.",
  "data": {
    "order_id": 1234,
    "order": {
      "id": 1234,
      "pakar_price": 400000,
      "technician_price": null,
      "extra_price": null,
      "discount_price": null,
      "payment_status": 0,
      "status": 0
    }
  }
}
```

در وضعیت فعلی، بک‌اند `total_price` ارسالی را به‌عنوان مبلغ پایه در `orders.pakar_price` ذخیره می‌کند و آن را از روی `steps` دوباره محاسبه یا اعتبارسنجی نمی‌کند. بنابراین محاسبهٔ فرانت باید از روی نرخ‌های دریافتی از API انجام شود و دستکاری مبلغ توسط کاربر باید در لایهٔ احراز هویت/اعتبارسنجی آتی در نظر گرفته شود.

## ۷. تفاوت نرخ خدمات با سایر مبلغ‌ها

### `GET /api/min-price`

این endpoint نرخ یک خدمت نیست؛ فقط حداقل مبلغ مجاز سیستم/اتحادیه را برمی‌گرداند. برای نمایش قیمت گزینه‌های خدمت از آن استفاده نکنید.

### `pakar_price`

مبلغ پایه‌ای است که هنگام ساخت سفارش از `total_price` می‌آید.

### `technician_price`

قیمت اعلام‌شده یا نهایی‌شده توسط تکنسین در مراحل بعدی سفارش است و در زمان دریافت مراحل اولیهٔ خدمت مشخص نیست.

### `extra_price`

مجموع خدمات اضافی است که ممکن است بعداً توسط تکنسین اضافه شود و بخشی از نرخ اولیهٔ `field_details[].price` نیست.

### تخفیف‌ها

کد تخفیف، کد معرف و سایر تخفیف‌ها در مرحلهٔ ثبت سفارش یا پرداخت اعمال می‌شوند. فرانت می‌تواند مبلغ تخمینی را نمایش دهد، اما مبلغ نهایی پرداخت را از پاسخ endpoint پرداخت/سفارش بعدی دریافت کند.

## ۸. خطاهای متداول

| وضعیت | علت احتمالی | اقدام فرانت |
|---|---|---|
| `401` | توکن وجود ندارد یا منقضی شده است. | refresh/login و ارسال دوبارهٔ Bearer Token. |
| `422` در `fetch` | `categoryId` ارسال نشده، عدد نیست یا وجود ندارد. | مقدار معتبر دسته‌بندی ارسال شود. |
| `422` در conditional | یکی از شناسه‌ها معتبر نیست. | `fieldId` و `fieldDetailId` را از همان پاسخ API بردارید. |
| `422` در submit | فیلد اجباری ناقص است یا `total_price` عدد نیست. | body و نوع داده‌ها بررسی شود. |
| مبلغ صفر | هیچ گزینهٔ مؤثری انتخاب نشده یا `value`ها پر نشده‌اند. | وضعیت انتخاب‌ها و `affect_on_price` بررسی شود. |
| قیمت نمایش داده نمی‌شود | `show_price` صفر است یا `price` خالی است. | عدم نمایش را با رایگان بودن اشتباه نگیرید. |

## ۹. چک‌لیست پیاده‌سازی فرانت

- [ ] دریافت `categoryId` معتبر.
- [ ] فراخوانی `POST /api/steps/fetch` با Bearer Token.
- [ ] نگهداری `id` فیلد و `id` گزینه‌ها.
- [ ] نمایش `price` در صورت فعال بودن `show_price`.
- [ ] پر کردن `value` برای گزینه‌های انتخاب‌شده.
- [ ] دریافت مراحل شرطی بعد از انتخاب گزینهٔ والد.
- [ ] محاسبهٔ `total_price` از قیمت و مقدار گزینه‌ها.
- [ ] ارسال همان `steps` به همراه `total_price` در `/api/orders/submit`.
- [ ] نمایش مبلغ نهایی پرداخت بر اساس پاسخ endpoint پرداخت، نه صرفاً محاسبهٔ اولیه.

## مراجع داخل بک‌اند

- مسیرها: [`routes/api.php`](../routes/api.php)
- کنترلر مراحل: [`app/Http/Controllers/StepController.php`](../app/Http/Controllers/StepController.php)
- منطق آماده‌سازی مراحل و قیمت‌ها: [`app/Services/StepService.php`](../app/Services/StepService.php)
- کوئری فیلدها و گزینه‌ها: [`app/Repositories/StepRepository.php`](../app/Repositories/StepRepository.php)
- اعتبارسنجی ثبت سفارش: [`app/Http/Requests/SubmitOrderRequest.php`](../app/Http/Requests/SubmitOrderRequest.php)
- ذخیرهٔ سفارش: [`app/Services/OrderService.php`](../app/Services/OrderService.php)
