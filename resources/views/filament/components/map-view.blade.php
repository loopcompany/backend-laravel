<div style="height: 200px; width: 100%; border-radius: 6px; overflow: hidden;">
    <div id="map-{{ $getRecord()?->id }}" style="height: 100%; width: 100%;"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapId = 'map-{{ $getRecord()?->id }}';
        const mapElement = document.getElementById(mapId);
        
        if (mapElement && !mapElement.classList.contains('leaflet-container')) {
            const lat = {{ $getRecord()?->latitude ?? 35.6892 }};
            const lng = {{ $getRecord()?->longitude ?? 51.3890 }};
            const radius = {{ $getRecord()?->radius ?? 10 }};
            
            const map = L.map(mapId, {
                center: [lat, lng],
                zoom: 12,
                scrollWheelZoom: false,
                dragging: false,
                zoomControl: false
            });
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            
            L.marker([lat, lng]).addTo(map);
            
            L.circle([lat, lng], {
                radius: radius * 1000,
                color: 'blue',
                fillColor: '#30b9ff',
                fillOpacity: 0.2
            }).addTo(map);
        }
    });
</script>
