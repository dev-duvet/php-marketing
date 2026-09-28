(() => {
  'use strict';

  // Mobile navigation
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  // Gallery filters: filter in place, keep the URL shareable. Links still work without JS.
  const filters = document.querySelector('[data-gallery-filters]');
  const grid = document.querySelector('[data-gallery]');
  if (filters && grid) {
    const empty = document.querySelector('[data-gallery-empty]');
    const apply = (category) => {
      let shown = 0;
      grid.querySelectorAll('[data-category]').forEach((card) => {
        const match = !category || card.dataset.category === category;
        card.hidden = !match;
        if (match) shown++;
      });
      filters.querySelectorAll('[data-filter]').forEach((link) => {
        link.classList.toggle('is-active', link.dataset.filter === category);
      });
      if (empty) empty.hidden = shown > 0;
    };
    filters.addEventListener('click', (e) => {
      const link = e.target.closest('[data-filter]');
      if (!link) return;
      e.preventDefault();
      apply(link.dataset.filter);
      history.replaceState(null, '', link.href);
    });
  }

  // Quantity steppers
  document.querySelectorAll('[data-qty]').forEach((wrap) => {
    const input = wrap.querySelector('input');
    wrap.querySelectorAll('[data-step]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const min = Number(input.min || 0);
        const max = Number(input.max || 99);
        const next = Math.min(max, Math.max(min, Number(input.value || 0) + Number(btn.dataset.step)));
        input.value = String(next);
      });
    });
  });
})();
