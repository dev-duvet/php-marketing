(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // Sidebar (mobile)
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  const setSidebar = (open) => {
    if (!sidebar || !toggle) return;
    sidebar.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
  };
  toggle?.addEventListener('click', () => setSidebar(!sidebar.classList.contains('is-open')));
  document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setSidebar(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

  // Auto-submit selects (filters, kanban fallback, order status)
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.requestSubmit());
  });

  // Caption character counter
  document.querySelectorAll('textarea[data-counter]').forEach((area) => {
    const out = document.querySelector(`[data-counter-for="${area.id}"]`);
    const update = () => { if (out) out.textContent = `${area.value.length}/${area.maxLength}`; };
    area.addEventListener('input', update);
    update();
  });

  // Range output
  document.querySelectorAll('input[data-range]').forEach((range) => {
    const out = range.closest('.field')?.querySelector('[data-range-output]');
    range.addEventListener('input', () => { if (out) out.textContent = `${range.value}%`; });
  });

  // Dropzones: show chosen file names and highlight on drag
  document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type=file]');
    const list = zone.querySelector('[data-dropzone-files]');
    input.addEventListener('change', () => {
      list.textContent = [...input.files].map((f) => f.name).join(', ');
    });
    ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, () => zone.classList.add('is-over')));
    ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, () => zone.classList.remove('is-over')));
  });

  // Colour pickers mirror their hex inputs
  document.querySelectorAll('[data-color-sync]').forEach((picker) => {
    const text = document.getElementById(picker.dataset.colorSync);
    picker.addEventListener('input', () => { text.value = picker.value.toUpperCase(); });
    text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
  });

  // Confirmation dialogs
  document.querySelectorAll('[data-open-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => document.getElementById(btn.dataset.openDialog)?.showModal());
  });
  document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('dialog')?.close());
  });

  // Tabs
  document.querySelectorAll('[data-tabs]').forEach((wrap) => {
    const tabs = [...wrap.querySelectorAll('[role=tab]')];
    const select = (tab) => {
      tabs.forEach((t) => {
        const on = t === tab;
        t.setAttribute('aria-selected', String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
    };
    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => select(tab));
      tab.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        const next = tabs[(i + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
        select(next);
        next.focus();
      });
    });
  });

  // Leads kanban drag and drop (the per-card select remains as a keyboard-friendly fallback)
  const kanban = document.querySelector('[data-kanban]');
  if (kanban) {
    let dragged = null;
    kanban.addEventListener('dragstart', (e) => {
      dragged = e.target.closest('[data-lead]');
      if (!dragged) return;
      dragged.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    kanban.addEventListener('dragend', () => dragged?.classList.remove('is-dragging'));
    kanban.querySelectorAll('[data-dropzone-list]').forEach((list) => {
      list.addEventListener('dragover', (e) => { e.preventDefault(); list.classList.add('is-over'); });
      list.addEventListener('dragleave', () => list.classList.remove('is-over'));
      list.addEventListener('drop', async (e) => {
        e.preventDefault();
        list.classList.remove('is-over');
        if (!dragged) return;
        const col = list.closest('[data-status]');
        const from = dragged.closest('[data-status]');
        if (col === from) return;
        const status = col.dataset.status;
        const body = new URLSearchParams({ status, _token: csrf });
        try {
          const res = await fetch(`/admin/leads/${dragged.dataset.lead}/status`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-Token': csrf },
            body,
          });
          if (!res.ok) throw new Error('Request failed');
          list.prepend(dragged);
          const select = dragged.querySelector('select[name=status]');
          if (select) select.value = status;
          [col, from].forEach((c) => { c.querySelector('[data-count]').textContent = c.querySelectorAll('[data-lead]').length; });
        } catch {
          alert('Could not move that lead. Please refresh and try again.');
        }
      });
    });
  }
})();
