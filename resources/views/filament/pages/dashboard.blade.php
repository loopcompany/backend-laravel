<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        خوش آمدید به پنل مدیریت لوپ
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        مدیریت سیستم تعمیرات هوشمند دیجیتال
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        تاریخ امروز: {{ safe_jalali_date(now(), 'Y/m/d') }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ safe_jalali_date(now(), 'l') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Widgets Grid -->
        <div class="space-y-6">
            @foreach ($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>