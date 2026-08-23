@php
    $lat = $getRecord()?->latitude ?? 35.6892;
    $lng = $getRecord()?->longitude ?? 51.3890;
    $rad = $getRecord()?->radius ?? 10;
    $mapId = 'map-picker-' . uniqid();
@endphp

<div class="space-y-2">
    <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            موقعیت روی نقشه
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            برای تغییر موقعیت، مارکر را بکشید یا روی نقشه کلیک کنید
        </p>
    </div>
    
    <div id="{{ $mapId }}" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #e5e7eb;" class="z-0"></div>
    
    <div class="grid grid-cols-2 gap-4 mt-2">
        <div>
            <label class="text-xs text-gray-600 dark:text-gray-400">عرض جغرافیایی</label>
            <div class="text-sm font-mono mt-1" id="{{ $mapId }}-lat">{{ number_format($lat, 6) }}</div>
        </div>
        <div>
            <label class="text-xs text-gray-600 dark:text-gray-400">طول جغرافیایی</label>
            <div class="text-sm font-mono mt-1" id="{{ $mapId }}-lng">{{ number_format($lng, 6) }}</div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            if (typeof L === 'undefined') {
                console.error('Leaflet not loaded');
                return;
            }
            
            const mapId = '{{ $mapId }}';
            const mapElement = document.getElementById(mapId);
            
            if (!mapElement) {
                console.error('Map element not found');
                return;
            }
            
            if (mapElement._leaflet_id) {
                return; // Map already initialized
            }
            
            const latInput = document.querySelector('input[name="latitude"]');
            const lngInput = document.querySelector('input[name="longitude"]');
            const radiusInput = document.querySelector('input[name="radius"]');
            
            if (!latInput || !lngInput) {
                console.error('Latitude or Longitude input not found');
                return;
            }
            
            let lat = parseFloat(latInput.value) || {{ $lat }};
            let lng = parseFloat(lngInput.value) || {{ $lng }};
            let radius = parseFloat(radiusInput?.value) || {{ $rad }};
            
            const map = L.map(mapId).setView([lat, lng], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            
            const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            const circle = L.circle([lat, lng], {
                radius: radius * 1000,
                color: 'blue',
                fillColor: '#30b9ff',
                fillOpacity: 0.2
            }).addTo(map);
            
            function updatePosition(newLat, newLng) {
                lat = newLat;
                lng = newLng;
                latInput.value = lat;
                lngInput.value = lng;
                latInput.dispatchEvent(new Event('input', { bubbles: true }));
                lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                document.getElementById(mapId + '-lat').textContent = lat.toFixed(6);
                document.getElementById(mapId + '-lng').textContent = lng.toFixed(6);
                marker.setLatLng([lat, lng]);
                circle.setLatLng([lat, lng]);
            }
            
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updatePosition(pos.lat, pos.lng);
            });
            
            map.on('click', function(e) {
                updatePosition(e.latlng.lat, e.latlng.lng);
            });
            
            if (radiusInput) {
                radiusInput.addEventListener('input', function() {
                    radius = parseFloat(this.value) || 10;
                    circle.setRadius(radius * 1000);
                });
            }
        }, 500);
    });
</script>
