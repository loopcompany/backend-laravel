@php
    $statePath = $getStatePath();
    $state = $getState() ?? [];
    
    // اگر state داره، از اونجا بخون
    $latitude = $state['latitude'] ?? null;
    $longitude = $state['longitude'] ?? null;
    
    // اگر نداشت، از رکورد بخون
    if (!$latitude || !$longitude) {
        $record = $getRecord();
        if ($record) {
            $latitude = data_get($record, $getLatitudeField()) ?? data_get($record, 'latitude');
            $longitude = data_get($record, $getLongitudeField()) ?? data_get($record, 'longitude');
        }
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @once
        @push('styles')
            <link rel="stylesheet" href="https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.css" />
        @endpush
        @push('scripts')
            <script src="https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.js"></script>
        @endpush
    @endonce

    <div
        wire:ignore
        x-data="neshanMapPicker({
            latitude: @js($latitude ?? 35.6895),
            longitude: @js($longitude ?? 51.3381),
            statePath: @js($statePath)
        })"
        x-init="initMap()"
        x-on:modal-open.window="map?.invalidateSize()"
        class="neshan-map-picker-wrapper" style="margin-top: 90px"
    >
        {{-- نقشه --}}
        <div class="neshan-map-wrap">
            {{-- جستجو --}}
            <div class="neshan-search-box">
                <input
                    x-ref="searchInput"
                    type="text"
                    placeholder="جستجو مکان (مثلاً میدان آزادی، ولیعصر...)"
                    @input.debounce.400ms="doSearch($event.target.value)"
                    @keydown.escape="clearResults(); $refs.searchInput.blur()"
                >
                <div x-ref="searchStatus" class="neshan-status" x-text="searchStatusText"></div>
                <div
                    x-ref="searchResults"
                    class="neshan-results"
                    x-show="showResults"
                    x-transition
                >
                    <template x-for="(item, idx) in searchItems" :key="idx">
                        <div class="neshan-result-item" @click="selectResult(item)">
                            <div class="neshan-result-title" x-text="item.title || 'بدون عنوان'"></div>
                            <div class="neshan-result-sub" x-text="item.address || item.region || ''"></div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-ref="mapContainer" style="width: 100%; height: 400px; border-radius: 10px;"></div>
        </div>

        {{-- دکمه انتخاب مجدد --}}
        <div
            x-show="selectedLat && selectedLng"
            style="display: flex; gap: 10px; justify-content: center; margin: 15px 0;"
            x-transition
        >
            <button
                type="button"
                @click="resetMarker"
                class="btn btn-warning btn-sm"
                style="display: inline-flex; align-items: center; gap: 5px;"
            >
                <i class="bi bi-arrow-clockwise"></i>
                انتخاب مجدد موقعیت
            </button>
        </div>

        {{-- پیام راهنما --}}
        <div
            x-show="!selectedLat || !selectedLng"
            style="padding: 10px; background-color: #e7f3ff; border: 1px solid #2196F3; border-radius: 5px; text-align: center; margin: 15px 0; font-size: 14px; color: #1976D2;"
            x-transition
        >
            <i class="bi bi-info-circle"></i>
            <strong>روی نقشه کلیک کنید تا موقعیت را انتخاب کنید</strong>
        </div>

        {{-- نمایش مختصات انتخاب شده --}}
        <div
            x-show="selectedLat && selectedLng"
            style="padding: 10px; background-color: #ffffff5b; border-radius: 5px; text-align: center; font-size: 14px;"
        >
            <strong>موقعیت انتخاب شده:</strong><br>
            <span>عرض جغرافیایی: <code x-text="selectedLat"></code></span> |
            <span>طول جغرافیایی: <code x-text="selectedLng"></code></span>
        </div>
    </div>

    <style>
        .neshan-map-wrap {
            position: relative;
        }

        .neshan-search-box {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 999;
            max-width: 520px;
            margin: 0 auto;
        }

        .neshan-search-box input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            outline: none;
            background: #888888;
            font-size: 13px;
        }

        .neshan-status {
            margin-top: 6px;
            font-size: 12px;
            color: #555;
            min-height: 16px;
        }

        .neshan-results {
            margin-top: 8px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .neshan-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f2f2f2;
        }

        .neshan-result-item:last-child {
            border-bottom: none;
        }

        .neshan-result-item:hover {
            background: #f7f7f7;
        }

        .neshan-result-title {
            font-weight: 600;
            font-size: 13px;
        }

        .neshan-result-sub {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</x-dynamic-component>
