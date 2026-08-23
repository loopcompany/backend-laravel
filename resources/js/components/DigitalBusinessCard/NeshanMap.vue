<template>
  <div class="neshan-map-wrapper">
    <div ref="mapEl" class="neshan-map"></div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  latitude: {
    type: [Number, String],
    required: true,
  },
  longitude: {
    type: [Number, String],
    required: true,
  },
  zoom: {
    type: Number,
    default: 16,
  },
});

const mapEl = ref(null);
let map = null;
let marker = null;

const apiKey = import.meta.env.VITE_NESHAN_API_KEY || 'web.a7d38181a0094e0092a578bcc81b7641';

function getLatLng() {
  const lat = Number(props.latitude);
  const lng = Number(props.longitude);

  if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
    return null;
  }

  return { lat, lng };
}

function destroyMap() {
  if (map) {
    map.remove();
    map = null;
    marker = null;
  }
}

async function initMap() {
  await nextTick();

  const coords = getLatLng();

  if (!mapEl.value || !coords) {
    destroyMap();
    return;
  }

  if (!window.L) {
    console.error('Neshan Leaflet SDK is not loaded. window.L is missing.');
    return;
  }

  destroyMap();

  map = new window.L.Map(mapEl.value, {
    key: apiKey,
    maptype: 'neshan',
    poi: true,
    traffic: false,
    center: [coords.lat, coords.lng],
    zoom: props.zoom,
  });

  marker = window.L.marker([coords.lat, coords.lng]).addTo(map);

  setTimeout(() => {
    if (map) {
      map.invalidateSize();
    }
  }, 250);
}

onMounted(() => {
  initMap();
});

watch(
  () => [props.latitude, props.longitude, props.zoom],
  () => {
    initMap();
  },
);

onBeforeUnmount(() => {
  destroyMap();
});
</script>

<style scoped>
.neshan-map-wrapper {
  width: 100%;
  border-radius: 16px;
  overflow: hidden;
  background: rgb(15 23 42 / 0.08);
}

.neshan-map {
  width: 100%;
  height: 220px;
  min-height: 220px;
}
</style>
