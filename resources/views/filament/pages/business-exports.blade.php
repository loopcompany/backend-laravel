<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">گزارش‌های بیزنسی</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                گزارش موردنظر و بازه زمانی را انتخاب کنید. فایل Excel شامل خلاصه و جزئیات گزارش خواهد بود.
            </p>

            <form wire:submit="generate" class="mt-6 grid gap-4 md:grid-cols-4">
                <label class="block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">نوع گزارش</span>
                    <select wire:model="report" class="fi-input mt-2 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                        @foreach ($this->reportOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">از تاریخ</span>
                    <input wire:model="from" type="date" class="fi-input mt-2 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تا تاریخ</span>
                    <input wire:model="until" type="date" class="fi-input mt-2 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-white/5">
                </label>

                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" class="fi-btn fi-btn-color-primary w-full justify-center">
                        <span wire:loading.remove>دریافت خروجی Excel</span>
                        <span wire:loading>در حال آماده‌سازی...</span>
                    </button>
                </div>
            </form>

            @error('report') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
            @error('from') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
            @error('until') <p class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach (['orders' => 'سفارش و درآمد', 'users' => 'مشتریان', 'user_transactions' => 'مالی و کیف پول', 'technicians' => 'تکنسین‌ها', 'discounts' => 'تخفیف و معرف', 'organizations' => 'سازمان‌ها'] as $key => $title)
                <button type="button" wire:click="$set('report', '{{ $key }}')" class="rounded-xl bg-white p-5 text-right shadow-sm ring-1 ring-gray-950/5 transition hover:ring-primary-500 dark:bg-gray-900 dark:ring-white/10">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">گزارش آماده</span>
                    <span class="mt-1 block font-bold text-gray-950 dark:text-white">{{ $title }}</span>
                </button>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
