@php
    $record = $getRecord();
    $lat = $record?->latitude ?? 35.6892;
    $lng = $record?->longitude ?? 51.3890;
    $rad = $record?->radius ?? 10000;
    $mapId = 'map-picker-' . md5($getStatePath());
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-2" x-data="{
        latitude: @js($lat),
        longitude: @js($lng),
        radius: @js($rad),
        mapId: @js($mapId),
        map: null,
        marker: null,
        circle: null,
        initialized: false,
        initMap() {
            if (typeof L === 'undefined' || this.initialized) return;
            
            const mapElement = document.getElementById(this.mapId);
            if (!mapElement) return;
            
            this.initialized = true;
            this.map = L.map(this.mapId).setView([this.latitude, this.longitude], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);
            
            this.marker = L.marker([this.latitude, this.longitude], { draggable: true }).addTo(this.map);
            this.circle = L.circle([this.latitude, this.longitude], {
                radius: this.radius,
                color: 'blue',
                fillColor: '#30b9ff',
                fillOpacity: 0.2
            }).addTo(this.map);
            
            this.marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                this.latitude = pos.lat;
                this.longitude = pos.lng;
                this.circle.setLatLng(pos);
                $wire.set('data.latitude', this.latitude);
                $wire.set('data.longitude', this.longitude);
            });
            
            this.map.on('click', (e) => {
                this.latitude = e.latlng.lat;
                this.longitude = e.latlng.lng;
                this.marker.setLatLng(e.latlng);
                this.circle.setLatLng(e.latlng);
                $wire.set('data.latitude', this.latitude);
                $wire.set('data.longitude', this.longitude);
            });
        }
    }" x-init="setTimeout(() => initMap(), 500)">
        <div>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                موقعیت روی نقشه
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                برای تغییر موقعیت، مارکر را بکشید یا روی نقشه کلیک کنید
            </p>
        </div>
        
        <div wire:ignore>
            <div :id="mapId" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;" class="z-0"></div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mt-2">
            <div>
                <label class="text-xs text-gray-600 dark:text-gray-400">عرض جغرافیایی</label>
                <div class="text-sm font-mono mt-1" x-text="latitude?.toFixed(6) || '-'"></div>
            </div>
            <div>
                <label class="text-xs text-gray-600 dark:text-gray-400">طول جغرافیایی</label>
                <div class="text-sm font-mono mt-1" x-text="longitude?.toFixed(6) || '-'"></div>
            </div>
        </div>
    </div>
</x-dynamic-component>

<script>
    document.addEventListener('livewire:load', function () {
        Livewire.hook('message.processed', (message, component) => {
            // Update circle radius when radius field changes
            const radiusInput = document.querySelector('input[name="radius"]');
            if (radiusInput) {
                radiusInput.addEventListener('input', function() {
                    const radius = parseFloat(this.value) || 10000;
                    const circles = document.querySelectorAll('.leaflet-interactive[stroke="blue"]');
                    // This is a workaround - better to use Alpine store
                });
            }
        });
    });
</script>
