<div class="space-y-6">
    <!-- کل آمار -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-3xl font-bold text-blue-600">{{ $stats['total_responses'] }}</div>
            <div class="text-gray-600">کل پاسخ‌ها</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $stats['app_ratings']['خوب'] }}</div>
            <div class="text-gray-600">امتیاز خوب اپ</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $stats['tech_ratings']['خوب'] }}</div>
            <div class="text-gray-600">امتیاز خوب تکنسین</div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $stats['support_ratings']['خوب'] }}</div>
            <div class="text-gray-600">امتیاز خوب پشتیبانی</div>
        </div>
    </div>

    <!-- آمار تفصیلی -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- امتیازات اپلیکیشن -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">امتیازات اپلیکیشن</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-green-600">خوب</span>
                    <span class="font-bold">{{ $stats['app_ratings']['خوب'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-yellow-600">متوسط</span>
                    <span class="font-bold">{{ $stats['app_ratings']['متوسط'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-600">ضعیف</span>
                    <span class="font-bold">{{ $stats['app_ratings']['ضعیف'] }}</span>
                </div>
            </div>
        </div>

        <!-- امتیازات تکنسین -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">امتیازات تکنسین</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-green-600">خوب</span>
                    <span class="font-bold">{{ $stats['tech_ratings']['خوب'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-yellow-600">متوسط</span>
                    <span class="font-bold">{{ $stats['tech_ratings']['متوسط'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-600">ضعیف</span>
                    <span class="font-bold">{{ $stats['tech_ratings']['ضعیف'] }}</span>
                </div>
            </div>
        </div>

        <!-- امتیازات پشتیبانی -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">امتیازات پشتیبانی</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-green-600">خوب</span>
                    <span class="font-bold">{{ $stats['support_ratings']['خوب'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-yellow-600">متوسط</span>
                    <span class="font-bold">{{ $stats['support_ratings']['متوسط'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-red-600">ضعیف</span>
                    <span class="font-bold">{{ $stats['support_ratings']['ضعیف'] }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(count($stats['latest_responses']) > 0)
    <!-- آخرین پاسخ‌ها -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">آخرین نظرسنجی‌ها</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">کاربر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اپلیکیشن</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تکنسین</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">پشتیبانی</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['latest_responses'] as $response)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $response['user_name'] }}<br>
                            <span class="text-gray-500">{{ $response['user_phone'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $response['app_rate'] === 'خوب' ? 'bg-green-100 text-green-800' : 
                                   ($response['app_rate'] === 'متوسط' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $response['app_rate'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $response['tech_rate'] === 'خوب' ? 'bg-green-100 text-green-800' : 
                                   ($response['tech_rate'] === 'متوسط' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $response['tech_rate'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $response['support_rate'] === 'خوب' ? 'bg-green-100 text-green-800' : 
                                   ($response['support_rate'] === 'متوسط' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $response['support_rate'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Morilog\Jalali\Jalalian::forge($response['created_at'])->format('Y/m/d - H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>