<x-filament-widgets::widget> 
    <x-filament::section>
        <style>
            .grid{display:grid}
            .gap-4{gap:1rem}
            .grid-cols-1{grid-template-columns:repeat(1,minmax(0,1fr))}
            @media (min-width:1024px){
            .lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
            }
        </style>
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-4">
            @foreach($labels as $label)
                <div class="relative overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" style="overflow: visible; background: linear-gradient(180deg, {{ $label['color1'] }}, {{ $label['color2'] }}, {{ $label['color3'] }}); border: 1px solid {{ $label['color4'] }};">
                    <a class="p-6" href="{{ $label['url'] }}">
                        <div class="flex items-center justify-center">
                            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                                
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $label['title'] }}
                                    </p>
                                   
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex items-center justify-between">
                                
                                <div class="text-lg font-semibold " style="position: absolute;left: 10px;top: -10px;background: #dca82c;height: 35px;width: 35px;border-radius: 100px;display: flex;align-items: center;justify-content: center;">
                                    {{ number_format($label['stats']['value']) }}
                                </div>
                            </div>
                        </div>
                        
                        
                    </a>
                </div>
            @endforeach
        </div>
    </x-filament::section>
    <x-filament::section>
    
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-4">
            @foreach($actions as $action)
                <div class="relative overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 rtl:space-x-reverse">
                                <div class="flex-shrink-0">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg 
                                        @if($action['color'] === 'success') bg-green-500 text-green-600 dark:bg-green-500/10 dark:text-green-400
                                        @elseif($action['color'] === 'warning') bg-yellow-500 text-yellow-600 dark:bg-yellow-500/10 dark:text-yellow-400
                                        @elseif($action['color'] === 'info') bg-blue-500 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400
                                        @else bg-indigo-500 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400
                                        @endif">
                                        @svg($action['icon'], 'h-6 w-6')
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $action['title'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $action['description'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $action['stats']['label'] }}
                                </div>
                                <div class="text-lg font-semibold 
                                    @if($action['color'] === 'success') text-green-600 dark:text-green-400
                                    @elseif($action['color'] === 'warning') text-yellow-600 dark:text-yellow-400
                                    @elseif($action['color'] === 'info') text-blue-600 dark:text-blue-400
                                    @else text-indigo-600 dark:text-indigo-400
                                    @endif">
                                    {{ number_format($action['stats']['value']) }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ $action['url'] }}" 
                               class="inline-flex w-full items-center justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset transition-colors bg-primary-600 hover:bg-primary-500 ring-primary-600 focus-visible:ring-primary-600" style="background: linear-gradient(180deg, #e4fbff, #cdf4fb, #a7e1f7);color: #000;">
                                @svg('heroicon-m-arrow-left', 'ml-2 h-4 w-4 rtl:mr-2 rtl:ml-0 rtl:rotate-180')
                                {{ $action['btnTitle'] }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>