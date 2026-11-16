(function () {
  if (window.loopAdminRealtime) return;
  const element = document.getElementById('loop-realtime-config');
  if (!element || !window.LoopRealtime) return;
  const config = JSON.parse(element.textContent);
  if (!config.enabled) return;
  let timer;
  let refreshing = false;
  let pending = false;
  const refresh = async () => {
    timer = null;
    if (refreshing) { pending = true; return; }
    refreshing = true;
    try {
      const components = window.Livewire?.all?.() || [];
      await Promise.allSettled(components.map((component) => {
        const wire = component.$wire;
        if (!wire) return null;
        return /widgets\..*chart/.test(component.name)
          ? wire.$call('updateChartData')
          : wire.$refresh();
      }));
    } finally {
      refreshing = false;
      if (pending) { pending = false; timer = setTimeout(refresh, 100); }
    }
  };
  const client = window.LoopRealtime.createRealtimeClient({
    config: () => config,
    authUrl: '/realtime/admin/auth',
    credentials: 'same-origin',
    headers: () => ({ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }),
    onEvent: () => { if (!timer) timer = setTimeout(refresh, 100); },
  });
  window.loopAdminRealtime = client;
  client.start();
  window.addEventListener('pagehide', () => client.stop());
  window.addEventListener('pageshow', () => client.start());
})();
