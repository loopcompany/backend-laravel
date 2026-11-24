(() => {
  document.querySelectorAll('.store-btn[href="#"]').forEach((link) => link.addEventListener('click', (event) => event.preventDefault()));
  const languageButton = document.querySelector('[data-loop-language-open]');
  const languageDialog = document.getElementById('loop-language-dialog');
  if (languageButton && languageDialog) {
    languageButton.addEventListener('click', () => languageDialog.showModal());
    languageDialog.addEventListener('close', () => languageButton.focus());
    languageDialog.addEventListener('click', (event) => {
      if (event.target === languageDialog) languageDialog.close();
      const link = event.target.closest('[data-loop-language-options] a');
      if (link) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', link.hreflang);
        link.href = url.href;
      }
    });
  }
  const header = document.querySelector('[data-loop-header]');
  const drawer = document.getElementById('loop-mobile-menu');
  const toggle = document.querySelector('[data-loop-menu-open]');
  if (!header || !drawer || !toggle) return;
  const panel = drawer.querySelector('[role="dialog"]');
  let previousFocus, previousOverflow, previousRootOverflow;
  const close = () => {
    if (drawer.hidden) return;
    drawer.hidden = true;
    document.body.style.overflow = previousOverflow;
    document.documentElement.style.overflow = previousRootOverflow;
    toggle.setAttribute('aria-expanded', 'false');
    previousFocus?.focus();
  };
  toggle.addEventListener('click', () => {
    previousFocus = document.activeElement;
    previousOverflow = document.body.style.overflow;
    previousRootOverflow = document.documentElement.style.overflow;
    drawer.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    panel.querySelector('[data-loop-menu-close]').focus();
  });
  drawer.querySelectorAll('[data-loop-menu-close]').forEach((button) => button.addEventListener('click', close));
  drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
  document.addEventListener('keydown', (event) => {
    if (drawer.hidden) return;
    if (event.key === 'Escape') { event.preventDefault(); close(); }
    if (event.key !== 'Tab') return;
    const elements = [...panel.querySelectorAll('a,button,summary')].filter((element) => element.getClientRects().length);
    const first = elements[0], last = elements[elements.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
  const desktop = window.matchMedia('(min-width:1100px)');
  desktop.addEventListener('change', (event) => { if (event.matches) close(); });
  const update = () => header.classList.toggle('is-scrolled', window.scrollY > 20);
  window.addEventListener('scroll', update, { passive: true });
  update();
})();
