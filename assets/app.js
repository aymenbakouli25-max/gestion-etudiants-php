(() => {
  'use strict';

  const root = document.documentElement;
  const menu = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.main-nav');
  const theme = document.querySelector('.theme-toggle');

  if (localStorage.getItem('est-theme') === 'dark') {
    root.classList.add('dark');
  }

  menu?.addEventListener('click', () => {
    const open = nav?.classList.toggle('open') ?? false;
    menu.setAttribute('aria-expanded', String(open));
  });

  theme?.addEventListener('click', () => {
    root.classList.toggle('dark');
    localStorage.setItem('est-theme', root.classList.contains('dark') ? 'dark' : 'light');
  });

  document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      button.classList.add('is-loading');
      button.dataset.originalLabel = button.textContent ?? '';
      button.textContent = 'Chargement...';
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
      if (!window.confirm(element.dataset.confirm)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
    window.setTimeout(() => {
      alert.classList.add('is-hidden');
    }, 5000);
  });

  document.querySelectorAll('[data-academic-filter]').forEach((control) => {
    const list = document.querySelector(`[data-academic-list="${control.dataset.academicFilter}"]`);
    if (!list) return;
    const items = [...list.querySelectorAll('[data-search-text]')];
    const empty = list.querySelector('.filter-empty');
    const filter = () => {
      const value = (control.value ?? '').trim().toLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const matches = !value || item.dataset.searchText.includes(value);
        item.hidden = !matches;
        if (matches) visible += 1;
      });
      if (empty) empty.hidden = visible !== 0;
    };
    control.addEventListener('input', filter);
    control.addEventListener('change', filter);
  });
})();
