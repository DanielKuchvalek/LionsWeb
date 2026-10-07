/* LIONS – administrace */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const { api, csrf } = window.ADMIN || {};

  /* Menu na mobilu */
  $('[data-side-toggle]')?.addEventListener('click', e => { e.stopPropagation(); $('[data-side]').classList.toggle('is-open'); });
  document.addEventListener('click', e => { if (!e.target.closest('[data-side]')) $('[data-side]')?.classList.remove('is-open'); });

  /* Potvrzení mazání */
  document.addEventListener('submit', e => {
    const f = e.target;
    if (f.dataset.confirm && !confirm(f.dataset.confirm)) e.preventDefault();
    if (f.dataset.busy) { const b = $('button', f); if (b) { b.disabled = true; b.textContent = f.dataset.busy; } }
  });

  /* Upozornění na neuložené změny */
  $$('form.editor').forEach(form => {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  /* Vrátit původní text */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-reset]');
    if (!b) return;
    const field = b.closest('.af').querySelector('textarea, input');
    field.value = b.dataset.reset;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    b.remove();
  });

  /* Kopírování cesty */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    navigator.clipboard?.writeText(b.dataset.copy);
    const t = b.textContent; b.textContent = 'zkopírováno ✓'; setTimeout(() => (b.textContent = t), 1500);
  });

  /* Opakovatelné řádky */
  $$('[data-repeater]').forEach(rep => {
    const rows = $('.repeater__rows', rep);
    const tpl = $('template', rep);
    $('[data-row-add]', rep)?.addEventListener('click', () => {
      const i = Date.now() % 1e7;
      rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', i));
      rows.lastElementChild.querySelector('input, textarea')?.focus();
    });
    rep.addEventListener('click', e => {
      if (e.target.closest('[data-row-del]')) e.target.closest('.repeater__row').remove();
    });
  });

  /* ================= Knihovna médií (modal) ================= */
  const modal = $('[data-media-modal]');
  let onPick = null, pickType = 'image', searchTimer;

  const renderGrid = items => {
    const grid = $('[data-mm-grid]', modal);
    grid.innerHTML = '';
    if (!items.length) { grid.innerHTML = '<p class="muted">Nic nenalezeno.</p>'; return; }
    items.forEach(m => {
      const b = document.createElement('button');
      b.type = 'button';
      b.title = m.path;
      b.innerHTML = (m.type === 'video' ? `<video src="${m.url}#t=0.5" preload="metadata" muted></video>` : `<img src="${m.url}" loading="lazy" alt="">`)
        + `<span>${m.name.replace(/</g, '&lt;')}</span>`;
      b.addEventListener('click', () => { onPick?.(m); closeModal(); });
      grid.appendChild(b);
    });
  };
  const loadMedia = async () => {
    const q = $('[data-mm-search]', modal).value.trim();
    const res = await fetch(`${api}?action=media&type=${pickType}&q=${encodeURIComponent(q)}`);
    renderGrid(await res.json());
  };
  const openModal = (type, cb) => {
    pickType = type; onPick = cb;
    modal.hidden = false;
    $('[data-mm-search]', modal).value = '';
    $('[data-mm-status]', modal).textContent = '';
    $('[data-mm-grid]', modal).innerHTML = '<p class="muted">Načítám…</p>';
    loadMedia();
  };
  const closeModal = () => { modal.hidden = true; onPick = null; };
  if (modal) {
    $('[data-mm-close]', modal).addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
    $('[data-mm-search]', modal).addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadMedia, 250); });
    $('[data-mm-upload]', modal).addEventListener('change', async e => {
      const file = e.target.files[0];
      if (!file) return;
      const status = $('[data-mm-status]', modal);
      status.textContent = `Nahrávám ${file.name}…`;
      const fd = new FormData();
      fd.append('file', file);
      fd.append('_csrf', csrf);
      try {
        const res = await fetch(`${api}?action=upload`, { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.ok) { status.textContent = data.message; return; }
        status.textContent = 'Nahráno.';
        onPick?.({ path: data.path, url: data.url, type: file.type.startsWith('video') ? 'video' : 'image', name: file.name });
        closeModal();
      } catch { status.textContent = 'Nahrání se nezdařilo.'; }
      e.target.value = '';
    });
  }

  /* Jeden obrázek */
  $$('[data-media-pick]').forEach(box => {
    const input = $('[data-media-input]', box);
    const preview = $('.mp__preview', box);
    const type = box.dataset.mediaPick;
    const show = m => {
      input.value = m ? m.path : '';
      preview.innerHTML = !m ? '<span class="mp__empty">nevybráno</span>'
        : type === 'video' ? `<video src="${m.url}#t=0.5" muted></video>` : `<img src="${m.url}" alt="">`;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    };
    $('[data-media-open]', box).addEventListener('click', () => openModal(type, show));
    $('[data-media-clear]', box).addEventListener('click', () => show(null));
  });

  /* Seznam obrázků – přidávání, mazání, přetahování */
  $$('[data-media-list]').forEach(box => {
    const grid = $('.ml__grid', box);
    const value = $('[data-ml-value]', box);
    const sync = () => {
      value.value = $$('figure', grid).map(f => f.dataset.path).join('\n');
      value.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const add = m => {
      grid.insertAdjacentHTML('beforeend', `<figure data-path="${m.path}" draggable="true"><img src="${m.url}" alt=""><button type="button" title="Odebrat" data-ml-remove>×</button></figure>`);
      sync();
    };
    $('[data-ml-add]', box).addEventListener('click', () => openModal('image', add));
    grid.addEventListener('click', e => { if (e.target.closest('[data-ml-remove]')) { e.target.closest('figure').remove(); sync(); } });
    let drag = null;
    $$('figure', grid).forEach(f => (f.draggable = true));
    grid.addEventListener('dragstart', e => { drag = e.target.closest('figure'); drag?.classList.add('is-drag'); });
    grid.addEventListener('dragend', () => { drag?.classList.remove('is-drag'); drag = null; sync(); });
    grid.addEventListener('dragover', e => {
      e.preventDefault();
      const over = e.target.closest('figure');
      if (!drag || !over || over === drag) return;
      const r = over.getBoundingClientRect();
      over.parentNode.insertBefore(drag, e.clientX > r.left + r.width / 2 ? over.nextSibling : over);
    });
  });

  /* Nahrávání přetažením na stránce Média */
  const dz = $('[data-dropzone]');
  if (dz) {
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('is-over'); }));
    dz.addEventListener('drop', e => {
      const input = $('input[type="file"]', dz);
      input.files = e.dataTransfer.files;
      dz.submit();
    });
  }
})();
