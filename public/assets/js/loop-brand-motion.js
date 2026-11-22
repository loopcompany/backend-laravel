(() => {
  'use strict';
  const dialog = document.getElementById('loop-brand-motion');
  const video = dialog?.querySelector('video');
  if (!dialog || !video) return;
  let opener;
  const cleanup = () => {
    video.pause();
    try { video.currentTime = 0; } catch {}
    opener?.focus();
  };
  const close = () => {
    if (typeof dialog.close === 'function') dialog.close();
    else { dialog.removeAttribute('open'); cleanup(); }
  };
  document.querySelectorAll('[data-loop-brand-motion]').forEach((button) => {
    button.addEventListener('click', () => {
      opener = button;
      const portrait = window.matchMedia('(max-width: 600px)').matches;
      const source = portrait ? video.dataset.portrait : video.dataset.landscape;
      dialog.style.maxWidth = portrait ? '480px' : '960px';
      if (video.getAttribute('src') !== source) video.src = source;
      video.muted = false;
      video.volume = 1;
      if (typeof dialog.showModal === 'function') dialog.showModal();
      else dialog.setAttribute('open', '');
      dialog.querySelector('[data-loop-brand-close]')?.focus();
      // The user's click grants audible playback. Media is not loaded during
      // normal page loading; the original hero remains immediately available.
      video.play().catch(() => {});
    });
  });
  dialog.querySelector('[data-loop-brand-close]')?.addEventListener('click', close);
  dialog.addEventListener('close', cleanup);
  dialog.addEventListener('click', (event) => { if (event.target === dialog) close(); });
})();
