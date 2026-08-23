<div class="p-6">
    <div class="flex flex-col items-center justify-center space-y-4">
        @if($record->file_path)
            <div class="bg-white p-4 rounded-lg shadow-md">
                <img src="{{ Storage::url($record->file_path) }}" 
                     alt="{{ $record->title }}" 
                     class="max-w-full h-auto"
                     style="max-width: 400px;">
            </div>

            <div class="w-full max-w-md space-y-3">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">اطلاعات QR Code</h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">عنوان:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $record->title }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">نوع:</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                @switch($record->type)
                                    @case('text') متن ساده @break
                                    @case('url') لینک وبسایت @break
                                    @case('email') ایمیل @break
                                    @case('phone') شماره تلفن @break
                                    @case('sms') پیامک @break
                                    @case('wifi') WiFi @break
                                    @case('vcard') کارت ویزیت @break
                                    @default {{ $record->type }} @break
                                @endswitch
                            </span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">اندازه:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $record->size }} پیکسل</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">فرمت:</span>
                            <span class="font-medium text-gray-900 dark:text-white uppercase">{{ $record->format }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">محتوا</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 break-all">{{ $record->content }}</p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ Storage::url($record->file_path) }}" 
                       download="{{ Str::slug($record->title) }}.{{ $record->format }}"
                       class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        دانلود
                    </a>
                    
                    <button onclick="printQrCode()" 
                            class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        چاپ
                    </button>
                </div>
            </div>
        @else
            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>QR Code هنوز تولید نشده است</p>
            </div>
        @endif
    </div>
</div>

<script>
function printQrCode() {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html dir="rtl">
            <head>
                <title>چاپ QR Code - {{ $record->title }}</title>
                <style>
                    body {
                        margin: 0;
                        padding: 20px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                        font-family: Tahoma, Arial, sans-serif;
                    }
                    img {
                        max-width: 100%;
                        height: auto;
                    }
                    h2 {
                        margin: 20px 0 10px;
                        text-align: center;
                    }
                    p {
                        margin: 5px 0;
                        text-align: center;
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
                <img src="{{ Storage::url($record->file_path) }}" alt="{{ $record->title }}">
                <h2>{{ $record->title }}</h2>
                <p>{{ $record->content }}</p>
            </body>
        </html>
    `);
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
    }, 250);
}
</script>
