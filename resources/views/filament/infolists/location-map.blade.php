{{-- @php
    $order = $getRecord();
    $latitude = $order->user_address?->latitude;
    $longitude = $order->user_address?->longitude;
@endphp

@if($latitude && $longitude)
    <div class="locationMap rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <iframe
            width="100%"
            height="450"
            style="border:0"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}&hl=fa&z=15&output=embed">
        </iframe>
        
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <span class="font-medium">مختصات جغرافیایی:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $latitude }}, {{ $longitude }}</span>
                </div>
            </div>
            
            @if($order->user_address?->address)
                <div class="mt-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">آدرس:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $order->user_address->address }}</span>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="text-center text-gray-500 py-8">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <p>موقعیت جغرافیایی برای این سفارش ثبت نشده است.</p>
    </div>
@endif --}}
