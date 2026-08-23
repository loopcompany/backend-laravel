<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <!-- تغییر مهم: ثابت کردن عرض برای جلوگیری از به‌هم‌ریختگی در موبایل -->
    <meta name="viewport" content="width=1000">
    <title>جزئیات سفارش</title>

    <!-- لود کردن کتابخانه‌های مورد نیاز -->
    <script src="{{ asset('assets/js/jspdf.umd.min.js') }}"></script>
    <script src="{{ asset('assets/js/html2canvas.min.js') }}"></script>

    <style>
        @import url('https://cdn.fontcdn.ir/Font/Persian/Vazir/Vazir.css');

        body {
            font-family: 'Vazir', Tahoma, sans-serif;
            background-color: #525659;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 13px;
            display: flex;
            justify-content: center;
        }

        /* عرض ثابت برای حفظ ساختار گریدها */
        .invoice-container {
            width: 1000px;
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            padding-top: 0px;
        }

        /* استایل‌های اصلی شما */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-right,
        .header-left {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: stretch;
        }

        .date-box {
            font-size: 16px;
            font-weight: bold;
        }

        .logo-container {
            align-items: center;
            justify-content: center;
            display: flex;
        }

        .logo-container img {
            width: 70%;
            height: auto;
        }

        .company-info h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .title-area {
            text-align: center;
            margin-bottom: 40px;
        }

        .title-area h1 {
            color: #0b1c3f;
            margin: 0;
            font-size: 28px;
        }

        .title-area p {
            color: #666;
            margin-top: 5px;
        }

        .section-card {
            border: 2px solid #fbb713;
            border-radius: 20px;
            padding: 0;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: 7px 8px 14px 0px #c1c0c069;
        }

        .section-title {
            background-color: #0b1c3f;
            color: #fff;
            padding: 5px 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            width: fit-content;
            border-bottom-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .section-title i {
            color: #fbb713;
            font-size: 1.5rem
        }

        .info-label i {
            color: #fbb713;
            font-size: 1.5rem;
            margin-left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 25px 0px 15px;
        }

        .grid-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            margin-inline: 15px;
        }

        .center-row {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: 25px;

        }

        .center-row i {
            color: #fbb713;
            font-size: 1.5rem;
            margin-left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-value {
            font-weight: bold;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            text-align: center;
        }

        th {
            color: #666;
            font-weight: normal;
            padding: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .table-footer {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .table-footer td {
            border-top: 2px solid #e0e0e0;
        }

        .footer-area {
            text-align: center;
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            border: 2px solid #fbb713;
            border-radius: 20px;
            padding-bottom: 20px;
        }

        .footer-text h2 {
            color: #0b1c3f;
            margin: 0 0 10px 0;
            padding-top: 20px;
        }

        .footer-text p {
            color: #fbb713;
            margin: 0;
            font-size: 16px;
        }

        .qr-code {
            background: #0b1c3f;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-code img {
            width: 80px;
            height: 80px;
        }

        .badge img {
            width: 50px;
            height: auto;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
</head>

<body>

    <div class="invoice-container" id="content-to-pdf">

        <!-- هدر -->
        <div class="header">
            <div class="header-right">
                <div style="display:flex;align-items: center;">
                    <img src="{{ asset('assets/images/reciept/calendar.png') }}" style="height:30px; width:30px" />
                    <div class="info-label">تاریخ صدور / نمایش</div>
                </div>
                <div class="date-box">{{ \Morilog\Jalali\Jalalian::forge($order->created_at)->format('Y/m/d') }}</div>
            </div>
            <div class="logo-container">
                <img src="{{ asset('assets/images/reciept/loop-header.png') }}" alt="LOOP Logo">
            </div>
            <div class="header-left company-info">
                <img src="{{ asset('assets/images/reciept/logo.png') }}"
                    style="height:40px;width:40px;object-fit: contain;" />
                <h3>حلقه بی نهایت<br>رایانه ایرانیان</h3>
                <div class="info-label">شناسه ملی : 14011370142</div>
            </div>
        </div>

        <!-- عنوان -->
        <div class="title-area">
            <div style="display:flex;justify-content: center;align-items: center;">
                <h1>{{ $page_title }}</h1>
                <img style="height: 60px;width: 60px;" src="{{ asset('assets/images/reciept/' . $status_image) }}" />
            </div>
            <p>{{ $order_status_label }}</p>
        </div>

        <!-- مشخصات سفارش -->
        <div class="section-card">
            <div class="section-title">
                <i class="bi bi-list-check"></i>
                مشخصات سفارش
            </div>
            <div class="grid-2col">
                <div>
                    <div class="info-row">
                        <span class="info-label"> <i class="bi bi-list-ul"></i> شماره سفارش</span> <span
                            class="info-value">{{ $order->id }}</span>
                    </div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-person"></i> کد کاربری</span> <span
                            class="info-value">{{ $order->user?->code }}</span></div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-person"></i> نام کاربری</span> <span
                            class="info-value">{{ $order->user?->name . ' ' . $order->user?->last_name }}</span></div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-phone"></i> ثبت سفارش</span> <span
                            class="info-value">{{ $order->platform != 'web' ? 'اپلیکیشن' : 'سایت' }}</span></div>
                </div>
                <div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-buildings"></i> وضعیت کاربری</span>
                        <span class="info-value">{{ $user_type }}</span>
                    </div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-calendar4-week"></i> تاریخ ثبت
                            سفارش</span> <span
                            class="info-value">{{ \Morilog\Jalali\Jalalian::forge($order->created_at)->format('Y/m/d') }}</span>
                    </div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-clock"></i> ساعت ثبت سفارش</span>
                        <span
                            class="info-value">{{ \Morilog\Jalali\Jalalian::forge($order->created_at)->format('H:i') }}</span>
                    </div>
                    <div class="info-row"><span class="info-label"> <i class="bi bi-box"></i> وضعیت سفارش</span> <span
                            class="info-value">{{ $extra_status_label }}</span></div>
                </div>
            </div>
        </div>


        <div class="section-card">
            <div class="section-title"> <i class="bi bi-buildings-fill"></i> مشخصات کاربر</div>
            <div style="margin-top: 15px; margin-bottom: 15px;" class="grid-3col">
                <div class="center-row"
                    style="border-left-color: #fbb713;border-left-width: 2px;border-left-style: solid;">
                    <div>
                        <span class="info-label">{{ $name_label }}</span> <span
                            class="info-value">{{ $order->user?->account_type == 'individual' ? $order->user?->name . ' ' . $order->user?->last_name : $order->user?->organization?->organization_name }}</span>
                    </div>
                </div>
                <div class="center-row"
                    style="border-left-color: #fbb713;border-left-width: 2px;border-left-style: solid;">
                    <i class="bi bi-person-vcard"></i>
                    <div>
                        <span class="info-label"> {{ $shenase_label }}</span> <span
                            class="info-value">{{ $shenase_value }}</span>
                    </div>
                </div>
                <div class="center-row">
                    <i class="bi bi-telephone"></i>
                    <div>
                        <span class="info-label"> شماره تماس</span> <span
                            class="info-value">{{ $order->user?->phone }}</span>
                    </div>
                </div>

            </div>
        </div>
        <div class="section-card">
            <div class="section-title">
                <i class="bi bi-laptop"></i>
                مشخصات محصول
            </div>
            <div class="grid-3col" style="margin-top: 15px;margin-bottom: 15px;">
                <div style="border-left-color: #fbb713;border-left-width: 2px;border-left-style: solid;"><span
                        class="info-label">نوع محصول</span><br><b>{{ $order->category?->title }}</b></div>
                <div style="border-left-color: #fbb713;border-left-width: 2px;border-left-style: solid;"><span
                        class="info-label">برند</span><br><b style="color: #4CAF50;">
                        @if ($brandSelectedOption?->image_path && $brandDetail?->value)
                            <img src="{{ asset('storage/' . $brandSelectedOption?->image_path) }}"
                                style="height: 50px; width: 50;">
                        @elseif($brandSelectedOption?->title && $brandDetail?->value)
                            {{ $brandSelectedOption?->title }}
                        @else
                            -
                        @endif
                    </b></div>
                <div><span
                        class="info-label">مدل</span><br><b>{{ $modelSelectedOption?->title ?? ($modelDetail?->value ?? '-') }}</b>
                </div>
            </div>
        </div>


        <!-- اقلام درخواستی -->
        @if (count($order->extra_services) > 0)
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-box-seam"></i>
                    اقلام درخواستی
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>کالا</th>
                            <th>برند</th>
                            <th>مدل</th>
                            <th>گارانتی</th>
                            <th>مهلت تست</th>
                            <th>بارکد</th>
                            <th>تعداد</th>
                            <th>قیمت واحد (تومان)</th>
                            <th>قیمت کل (تومان)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->extra_services as $extra_service)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $extra_service->title }}</td>
                                <td>{{ $extra_service->brand }}</td>
                                <td>{{ $extra_service->model }}</td>
                                <td>{{ $extra_service->warranty }}</td>
                                <td>{{ $extra_service->test_duration }}</td>
                                <td>{{ $extra_service->barcode }}</td>
                                <td>{{ $extra_service->number }}</td>
                                <td>{{ number_format($extra_service->unit_price) }}</td>
                                <td>{{ number_format($extra_service->price) }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-footer">
                            <td colspan="9" style="text-align: left;">جمع کل اقلام سفارش (تومان)</td>
                            <td>{{ number_format($order->extra_price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- خدمات درخواستی -->
        @if (!$groupedDetails->isEmpty())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-box-seam"></i> خدمات درخواستی</div>
                <table>
                    <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>عنوان</th>
                            <th>شرح خدمات درخواستی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupedDetails as $fieldId => $details)
                            @php
                                $field = $details->first()->field;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $field->title }}</td>
                                <td>
                                    @foreach ($details->filter(fn($detail) => $detail->field && $detail->fieldDetail) as $detail)
                                        @switch($detail->field->type)
                                            @case('input')
                                                {{ $detail->fieldDetail->second_title ?? $detail->fieldDetail->title }}:
                                                {{ $detail->value }}
                                            @break

                                            @case('checkbox')
                                                {{ $detail->fieldDetail->title }}
                                                @if ($detail->fieldDetail->has_counter && $detail->value > 1)
                                                    <span>×{{ $detail->value }}</span>
                                                @endif
                                            @break

                                            @case('radioButton')
                                                {{ $detail->fieldDetail->title }}
                                                @if ($detail->fieldDetail->has_counter && $detail->value > 1)
                                                    <span>×{{ $detail->value }}</span>
                                                @endif
                                            @break

                                            @case('counter')
                                                {{ $detail->fieldDetail->title }}: {{ $detail->value }}
                                            @break
                                        @endswitch

                                        @if (!$loop->last)
                                            /
                                        @endif
                                    @endforeach


                                </td>

                            </tr>
                        @endforeach
                        <tr class="table-footer">
                            <td colspan="2" style="text-align: center;">جمع کل مبلغ خدمات درخواستی (تومان)</td>
                            <td>{{ $order->technician_price ? number_format($order->technician_price) : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- مشخصات تحویل و پرداخت -->
        <div class="grid-2col">
            <div class="section-card" style="display: flex;flex-direction: column;justify-content: space-between;">
                <div class="section-title"> <i class="bi bi-truck"></i> مشخصات تحویل</div>
                <div class="info-row" style="flex: 1;align-items: center;"><span class="info-label">
                        <i class="bi bi-person"></i>
                        نام تحویل
                        گیرنده</span> <span class="info-value">{{ $order->delivery_reports?->name ?? '-' }}</span>
                </div>
                <div class="info-row" style="flex: 1;align-items: center;"><span class="info-label">
                        <i class="bi bi-calendar4-week"></i>
                        تاریخ
                        تحویل</span>
                    <span
                        class="info-value">{{ $order->delivery_reports ? \Morilog\Jalali\Jalalian::forge($order->delivery_reports?->created_at)->format('Y/m/d') : '-' }}</span>
                </div>
                <div class="info-row" style="flex: 1;align-items: center;"><span class="info-label">
                        <i class="bi bi-check-circle" style="color: green"></i>
                        وضعیت
                        تحویل</span>
                    <span class="info-value text-green">{{ $order->shipment_status ?? '-' }}</span>
                </div>
            </div>
            <div class="section-card">
                <div class="section-title"> <i class="bi bi-credit-card"></i> مشخصات پرداخت</div>
                <div class="info-row"><span class="info-label">
                        <i class="bi bi-currency-dollar"></i>
                        مبلغ کل (تومان)</span> <span
                        class="info-value">{{ number_format((int) $order->technician_price + (int) $order->extra_price) }}</span>
                </div>
                <div class="info-row"><span class="info-label">
                        <i class="bi bi-tag"></i>
                        کد تشویقی</span> <span
                        class="info-value">{{ $order->discountUse?->discount_code ?? '-' }}</span>
                </div>
                <div class="info-row"><span class="info-label">
                        <i class="bi bi-dash-circle"></i>
                        مبلغ کسر شده (تومان)</span> <span
                        class="info-value">{{ number_format($order->discount_price) ?? '-' }}</span></div>
                <div class="info-row" style="font-size: 16px;"><span class="info-label">
                        <i class="bi bi-currency-dollar"></i>
                        مبلغ قابل پرداخت
                        (تومان)</span> <span class="info-value"
                        style="font-size: 18px;">{{ number_format($order->payment_price()) }}</span></div>
                <div class="info-row"><span class="info-label">
                        <i class="bi bi-check-circle" style="color: green"></i>
                        وضعیت پرداخت</span>
                    <span class="info-value">{{ $payment_status_label }}</span>
                </div>

                <div class="info-row"><span class="info-label"><i class="bi bi-bank"></i> روش پرداخت</span>
                    <span class="info-value">{{ $payment_way }}</span>
                </div>
            </div>
        </div>

        <!-- فوتر -->
        <div class="footer-area">
            <div class="qr-code">
                <img src="{{ asset('assets/images/reciept/qr.png') }}" alt="QR Code">
            </div>
            <div class="footer-text">
                <h2>با تشکر از اعتماد شما</h2>
                <p>با لوپ تا بی نهایت در کنار شما هستیم</p>
            </div>
            <div class="badge">
                <img src="{{ asset('assets/images/reciept/star-image.png') }}" alt="Badge"
                    style="height:100%;width: 50px;">
            </div>
        </div>

    </div>

    <script>
        window.jsPDF = window.jspdf.jsPDF;

        async function generateAndDownloadPdf() {
            const element = document.getElementById('content-to-pdf');

            // صبر برای لود کامل فونت‌ها
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            // صبر برای لود کامل عکس‌ها
            const images = Array.from(element.querySelectorAll('img'));
            await Promise.all(images.map(img => {
                if (img.complete) return Promise.resolve();
                return new Promise(resolve => {
                    img.onload = resolve;
                    img.onerror = resolve;
                });
            }));

            // کمی مکث برای رندر نهایی
            await new Promise(resolve => setTimeout(resolve, 500));

            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0
            });

            const imgData = canvas.toDataURL('image/jpeg', 1.0);
            const pdfWidth = canvas.width;
            const pdfHeight = canvas.height;

            const pdf = new jsPDF({
                orientation: pdfWidth > pdfHeight ? 'landscape' : 'portrait',
                unit: 'px',
                format: [pdfWidth, pdfHeight]
            });

            pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('invoice-{{ $order->id }}.pdf');
        }

        window.addEventListener('load', function() {
            // صفحه اول نمایش داده می‌شود، بعد PDF دانلود می‌شود
            setTimeout(() => {
                generateAndDownloadPdf();
            }, 1000);
        });
    </script>

</body>

</html>
