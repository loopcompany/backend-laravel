@php
    $order = $getRecord();
    $groupedDetails = $order->details->groupBy('field_id');
@endphp

<div class="space-y-4">
    @if($groupedDetails->isEmpty())
        <div class="text-center text-gray-500 py-8">
            <p>هیچ جزئیاتی برای این سفارش ثبت نشده است.</p>
        </div>
    @else
        @foreach ($groupedDetails as $fieldId => $details)
            @php
                $field = $details->first()->field;
            @endphp
            
            @if($field)
            <div class="orderCart rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                <div class="orderTitle bg-purple-100 dark:bg-purple-900/30 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <p class="text-purple-700 dark:text-purple-300 font-bold text-base m-0">
                        {{ $field->title }}
                    </p>
                </div>
                
                <div class="p-4 space-y-2">
                    @foreach ($details as $detail)
                        @if($detail->field && $detail->fieldDetail)
                        @switch($detail->field->type)
                            @case('input')
                                <div class="flex items-start gap-2 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400 font-medium">
                                        {{ $detail->fieldDetail->second_title ?? $detail->fieldDetail->title }}:
                                    </span>
                                    <span class="text-gray-900 dark:text-gray-100">
                                        {{ $detail->value }}
                                    </span>
                                </div>
                            @break

                            @case('checkbox')
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-900 dark:text-gray-100">
                                        {{ $detail->fieldDetail->title }}
                                        @if($detail->fieldDetail->has_counter && $detail->value > 1)
                                            <span class="text-gray-600 dark:text-gray-400">×{{ $detail->value }}</span>
                                        @endif
                                    </span>
                                </div>
                            @break

                            @case('radioButton')
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="3"></circle>
                                    </svg>
                                    <span class="text-gray-900 dark:text-gray-100">
                                        {{ $detail->fieldDetail->title }}
                                        @if($detail->fieldDetail->has_counter && $detail->value > 1)
                                            <span class="text-gray-600 dark:text-gray-400">×{{ $detail->value }}</span>
                                        @endif
                                    </span>
                                </div>
                            @break

                            @case('counter')
                                <div class="flex items-start gap-2 text-sm">
                                    <span class="text-gray-600 dark:text-gray-400 font-medium">
                                        {{ $detail->fieldDetail->title }}:
                                    </span>
                                    <span class="text-gray-900 dark:text-gray-100 font-semibold">
                                        {{ $detail->value }}
                                    </span>
                                </div>
                                @if($detail->user_descriptions)
                                <p class="text-gray-900 dark:text-gray-100 font-semibold">
                                    <span>توضیحات: </span>
                                    {{ $detail->user_descriptions ?? '-' }}
                                </p>
                                @endif
                            @break

                            @default
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $detail->value }}
                                </div>
                        @endswitch
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    @endif
</div>

<style>
    .orderCart {
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }
    
    .dark .orderCart {
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
    }
</style>
