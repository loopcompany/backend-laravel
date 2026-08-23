@extends('layout.technician.header')

@section('title', 'QR Code من')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">QR Code پروفایل من</h4>
                <div class="page-title-left">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('web.technician.dashboard') }}">داشبورد</a></li>
                        <li class="breadcrumb-item active">QR Code</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center p-5">
                    <h5 class="card-title mb-4">QR Code پروفایل عمومی شما</h5>
                    
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle ml-2"></i>
                        کاربران با اسکن این کد QR می‌توانند اطلاعات عمومی شما را مشاهده کنند
                    </div>

                    <!-- QR Code Container -->
                    <div class="qr-code-container mb-4">
                        <div id="qrcode" class="d-inline-block"></div>
                    </div>

                    <!-- Profile URL -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">لینک پروفایل عمومی:</label>
                        <div class="input-group">
                            <input type="text" 
                                   id="profileUrl" 
                                   class="form-control text-center" 
                                   value="{{ route('web.technician.public', ['id' => Auth::guard('technician')->user()->id]) }}" 
                                   readonly>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="copyUrl()"
                                    title="کپی لینک">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">این لینک را با دیگران به اشتراک بگذارید</small>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <button class="btn btn-primary" onclick="downloadQR()">
                            <i class="fas fa-download ml-2"></i>
                            دانلود QR Code
                        </button>
                        <button class="btn btn-success" onclick="printQR()">
                            <i class="fas fa-print ml-2"></i>
                            چاپ QR Code
                        </button>
                        <a href="{{ route('web.technician.public', ['id' => Auth::guard('technician')->user()->id]) }}" 
                           target="_blank" 
                           class="btn btn-info">
                            <i class="fas fa-external-link-alt ml-2"></i>
                            مشاهده پروفایل عمومی
                        </a>
                    </div>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">راهنمای استفاده</h6>
                    <ul class="mb-0">
                        <li class="mb-2">کد QR خود را دانلود کنید و در کارت ویزیت، بروشور یا پوستر خود قرار دهید</li>
                        <li class="mb-2">کاربران با اسکن کد QR به صفحه پروفایل عمومی شما هدایت می‌شوند</li>
                        <li class="mb-2">در صفحه پروفایل عمومی، اطلاعات شخصی، مهارت‌ها و آمار فعالیت شما نمایش داده می‌شود</li>
                        <li class="mb-0">می‌توانید لینک پروفایل را نیز مستقیماً با دیگران به اشتراک بگذارید</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
    // تولید QR Code
    const profileUrl = "{{ route('web.technician.public', ['id' => Auth::guard('technician')->user()->id]) }}";
    
    const qrcode = new QRCode(document.getElementById("qrcode"), {
        text: profileUrl,
        width: 256,
        height: 256,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    // کپی کردن URL
    function copyUrl() {
        const urlInput = document.getElementById('profileUrl');
        urlInput.select();
        document.execCommand('copy');
        
        // نمایش پیام موفقیت
        alert('لینک کپی شد!');
    }

    // دانلود QR Code
    function downloadQR() {
        const canvas = document.querySelector('#qrcode canvas');
        if (canvas) {
            const url = canvas.toDataURL('image/png');
            const link = document.createElement('a');
            link.download = 'technician-qrcode-{{ Auth::guard("technician")->user()->referral_code ?? "profile" }}.png';
            link.href = url;
            link.click();
        }
    }

    // چاپ QR Code
    function printQR() {
        const printWindow = window.open('', '_blank');
        const canvas = document.querySelector('#qrcode canvas');
        
        if (canvas) {
            const imgData = canvas.toDataURL('image/png');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html dir="rtl">
                <head>
                    <title>چاپ QR Code</title>
                    <style>
                        body {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            min-height: 100vh;
                            margin: 0;
                            font-family: 'Vazir', sans-serif;
                        }
                        .print-container {
                            text-align: center;
                            padding: 40px;
                        }
                        h2 {
                            margin-bottom: 20px;
                        }
                        img {
                            margin: 20px 0;
                        }
                        .info {
                            margin-top: 20px;
                            font-size: 14px;
                            color: #666;
                        }
                        @media print {
                            body {
                                padding: 0;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-container">
                        <h2>پروفایل تکنسین - {{ Auth::guard('technician')->user()->name }}</h2>
                        <img src="${imgData}" alt="QR Code">
                        <div class="info">
                            <p>کد پرسنلی: {{ Auth::guard('technician')->user()->referral_code }}</p>
                            <p>برای مشاهده پروفایل کامل، این کد را اسکن کنید</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }
    }
</script>

<style>
    .qr-code-container {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        display: inline-block;
    }
    
    #qrcode {
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .input-group {
        max-width: 600px;
        margin: 0 auto;
    }
</style>
@endsection
