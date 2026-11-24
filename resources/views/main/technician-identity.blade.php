<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>احراز هویت تکنسین لوپ</title></head>
<body style="margin:0;background:#0c2037;color:white;font-family:Tahoma,Arial;text-align:center;padding:48px 20px">
<main style="max-width:440px;margin:auto;border:1px solid #ddb94f;border-radius:20px;padding:32px">
@if ($identity)
<div style="color:#44d995;font-size:48px" aria-label="تأیید شده">✓</div><h1>{{ $identity['name'] }}</h1><p>تکنسین تأییدشدهٔ لوپ</p><p dir="ltr">{{ $identity['personnel_code'] }}</p><p>{{ $identity['technician_type'] }}</p>
@else
<h1>هویت تأیید نشد</h1><p>این کارت معتبر نیست یا دسترسی تکنسین غیرفعال شده است.</p>
@endif
</main></body></html>
