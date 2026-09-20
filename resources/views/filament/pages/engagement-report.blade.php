<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">تعامل و Engagement</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        بازدید صفحات، دانلودهای ثبت‌شده و ورود کاربران پنل کاربری در این بخش نمایش داده می‌شود.
                    </p>
                </div>

                <form wire:submit="refreshReport" class="flex flex-wrap items-end gap-3">
                    <label>
                        <span class="block text-xs text-gray-500">از تاریخ</span>
                        <input wire:model.defer="from" type="date" class="fi-input mt-1 rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                    </label>
                    <label>
                        <span class="block text-xs text-gray-500">تا تاریخ</span>
                        <input wire:model.defer="until" type="date" class="fi-input mt-1 rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                    </label>
                    <button type="submit" class="fi-btn fi-btn-color-primary">اعمال بازه</button>
                </form>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['label' => 'بازدید صفحات', 'value' => $this->stats['page_views'], 'color' => 'text-primary-600'],
                ['label' => 'دانلودها', 'value' => $this->stats['downloads'], 'color' => 'text-success-600'],
                ['label' => 'ورود کاربران', 'value' => $this->stats['user_logins'], 'color' => 'text-warning-600'],
                ['label' => 'کاربران یکتای واردشده', 'value' => $this->stats['unique_logged_users'], 'color' => 'text-info-600'],
                ['label' => 'بازدید عمومی', 'value' => $this->stats['public_visits'], 'color' => 'text-gray-700'],
            ] as $stat)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold {{ $stat['color'] }}">{{ number_format($stat['value']) }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 p-5 font-bold dark:border-white/10">روند روزانه</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-right dark:bg-white/5"><tr><th class="p-3">تاریخ</th><th class="p-3">بازدید صفحه</th><th class="p-3">دانلود</th><th class="p-3">ورود</th></tr></thead>
                        <tbody>
                            @forelse ($this->dailyStats as $row)
                                <tr class="border-t border-gray-100 dark:border-white/10"><td class="p-3">{{ $row['date'] }}</td><td class="p-3">{{ number_format($row['page_views']) }}</td><td class="p-3">{{ number_format($row['downloads']) }}</td><td class="p-3">{{ number_format($row['logins']) }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-5 text-center text-gray-500">داده‌ای وجود ندارد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="font-bold">صفحات پرتکرار</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse ($this->topPages as $row)
                            <li class="flex justify-between gap-3"><span class="truncate" dir="ltr">{{ $row['path'] }}</span><span class="font-bold">{{ number_format($row['total']) }}</span></li>
                        @empty <li class="text-gray-500">داده‌ای وجود ندارد.</li> @endforelse
                    </ul>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h3 class="font-bold">دانلودهای پرتکرار</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        @forelse ($this->topDownloads as $row)
                            <li class="flex justify-between gap-3"><span class="truncate" dir="ltr">{{ $row['path'] }}</span><span class="font-bold">{{ number_format($row['total']) }}</span></li>
                        @empty <li class="text-gray-500">داده‌ای وجود ندارد.</li> @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 p-5 font-bold dark:border-white/10">آخرین رویدادها</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-right dark:bg-white/5"><tr><th class="p-3">نوع</th><th class="p-3">نوع کاربر</th><th class="p-3">مسیر</th><th class="p-3">IP</th><th class="p-3">زمان</th></tr></thead>
                    <tbody>
                        @forelse ($this->recentEvents as $event)
                            <tr class="border-t border-gray-100 dark:border-white/10"><td class="p-3">{{ $this->eventLabel($event->event_type) }}</td><td class="p-3">{{ $event->user_type }}</td><td class="p-3" dir="ltr">{{ $event->path }}</td><td class="p-3" dir="ltr">{{ $event->ip_address }}</td><td class="p-3">{{ $event->created_at?->format('Y-m-d H:i:s') }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="p-5 text-center text-gray-500">هنوز رویدادی ثبت نشده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
