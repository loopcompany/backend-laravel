@php
    $districts = $getDistricts();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="districtPicker({
            state: $wire.$entangle('{{ $statePath }}'),
            districts: JSON.parse(document.getElementById('{{ $statePath }}-data').textContent),
        })"
        x-init="init()"
        class="fi-district-picker"
    >
        <style>
            /* Clicking an SVG path focuses it in some browsers, which paints a
               rectangle around the district. The checkbox list stays keyboard
               accessible, so nothing is lost by suppressing it here. */
            .fi-district-picker .leaflet-interactive:focus,
            .fi-district-picker .leaflet-container:focus,
            .fi-district-picker path:focus-visible {
                outline: none;
            }
        </style>

        <div class="mb-3 flex flex-wrap items-center gap-2">
            <button type="button" x-on:click="selectAll()"
                class="fi-btn fi-btn-size-sm rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10">
                انتخاب همه
            </button>
            <button type="button" x-on:click="clearAll()"
                class="fi-btn fi-btn-size-sm rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-white/10">
                حذف همه
            </button>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                <span x-text="selected.length" class="font-semibold text-primary-600 dark:text-primary-400"></span>
                منطقه از
                <span x-text="districts.length"></span>
                منطقه انتخاب شده است
            </span>

            <span x-show="selected.length === 0" class="text-sm font-medium text-danger-600 dark:text-danger-400">
                — هیچ منطقه‌ای انتخاب نشده، سرویس در هیچ نقطه‌ای فعال نخواهد بود
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- map --}}
            <div class="lg:col-span-2">
                <div
                    x-ref="map"
                    style="height: 520px;"
                    class="w-full rounded-xl border border-gray-300 dark:border-white/10 z-0"
                ></div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    برای انتخاب یا حذف هر منطقه، روی آن کلیک کنید.
                </p>
            </div>

            {{-- checkbox list, kept in sync with the map --}}
            <div class="rounded-xl border border-gray-300 dark:border-white/10">
                <div class="border-b border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 dark:border-white/10 dark:text-gray-200">
                    فهرست مناطق
                </div>
                <div class="max-h-[470px] overflow-y-auto p-1">
                    <template x-for="d in districts" :key="d.key">
                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                            x-on:mouseenter="highlight(d.key, true)"
                            x-on:mouseleave="highlight(d.key, false)"
                        >
                            <input
                                type="checkbox"
                                class="fi-checkbox-input rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-white/20 dark:bg-white/5"
                                :checked="selected.includes(d.key)"
                                x-on:change="toggle(d.key)"
                            />
                            <span class="text-gray-700 dark:text-gray-200" x-text="d.title"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        @if (empty($districts))
            <p class="mt-3 text-sm text-danger-600 dark:text-danger-400">
                هیچ منطقه‌ای با مرز ثبت‌شده در پایگاه داده وجود ندارد.
                ابتدا دستور <code>php artisan migrate</code> (یا <code>php artisan db:seed --class=TehranDistrictSeeder</code>) را اجرا کنید.
            </p>
        @endif
    </div>

    {{-- Polygon data lives in its own script tag rather than an x-data attribute:
         it is ~37KB and does not belong in an HTML attribute. --}}
    <script type="application/json" id="{{ $statePath }}-data">@json($districts)</script>
</x-dynamic-component>

<script>
    if (! window.__districtPickerRegistered) {
        window.__districtPickerRegistered = true;

        document.addEventListener('alpine:init', () => {
            Alpine.data('districtPicker', ({ state, districts }) => ({
                state,
                districts,
                map: null,
                layers: {},

                get selected() {
                    return Array.isArray(this.state) ? this.state : [];
                },

                init() {
                    if (typeof L === 'undefined') {
                        console.error('District picker: Leaflet (L) is not available.');
                        return;
                    }

                    this.map = L.map(this.$refs.map, { scrollWheelZoom: false });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap',
                        maxZoom: 18,
                    }).addTo(this.map);

                    const group = L.featureGroup();

                    this.districts.forEach((d) => {
                        const layer = L.geoJSON(d.geometry, {
                            style: this.styleFor(d.key, false),
                        });

                        layer.bindTooltip(d.title, { sticky: true, direction: 'top' });
                        layer.on('click', () => this.toggle(d.key));
                        layer.on('mouseover', () => this.highlight(d.key, true));
                        layer.on('mouseout', () => this.highlight(d.key, false));

                        this.layers[d.key] = layer;
                        layer.addTo(group);
                    });

                    group.addTo(this.map);

                    if (this.districts.length) {
                        this.map.fitBounds(group.getBounds(), { padding: [12, 12] });
                    } else {
                        this.map.setView([35.6892, 51.3890], 11);
                    }

                    // Leaflet mismeasures a container that was hidden (tabs, lazy
                    // Livewire render) while it initialised.
                    setTimeout(() => this.map.invalidateSize(), 200);

                    // Keep the map in step when the checkbox list changes it.
                    this.$watch('state', () => this.restyleAll());
                },

                styleFor(key, hovered) {
                    const on = this.selected.includes(key);

                    return {
                        color: on ? '#047857' : '#6b7280',
                        weight: hovered ? 3 : (on ? 2 : 1),
                        fillColor: on ? '#10b981' : '#9ca3af',
                        fillOpacity: on ? (hovered ? 0.55 : 0.45) : (hovered ? 0.25 : 0.12),
                    };
                },

                restyleAll() {
                    Object.entries(this.layers).forEach(([key, layer]) => {
                        layer.setStyle(this.styleFor(key, false));
                    });
                },

                highlight(key, on) {
                    this.layers[key]?.setStyle(this.styleFor(key, on));
                },

                toggle(key) {
                    const next = this.selected.includes(key)
                        ? this.selected.filter((x) => x !== key)
                        : [...this.selected, key];

                    // Replace the array rather than mutating it, so Livewire sees the change.
                    this.state = next;
                },

                selectAll() {
                    this.state = this.districts.map((d) => d.key);
                },

                clearAll() {
                    this.state = [];
                },
            }));
        });
    }
</script>
