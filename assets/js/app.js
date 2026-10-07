/* LIONS Handball – interakce webu (bez závislostí) */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const base = (window.LIONS && window.LIONS.base) || '';

  /* ================= Souhlas s cookies třetích stran ================= */
  const CONSENT = window.LIONS_CONSENT || { version: 1, services: [] };
  const readConsent = () => {
    const m = document.cookie.match(/(?:^|; )lions_consent=([^;]*)/);
    if (!m) return null;
    try {
      const c = JSON.parse(decodeURIComponent(m[1]));
      return c && c.v === CONSENT.version ? c : null;
    } catch { return null; }
  };
  let consent = readConsent();
  const allowed = svc => !!CONSENT.auto || (!!consent && consent.s.includes(svc));   // auto = souhlas se nevyžaduje
  const saveConsent = list => {
    const before = consent ? consent.s.slice() : [];
    consent = { v: CONSENT.version, s: [...new Set(list)], t: new Date().toISOString().slice(0, 10) };
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `lions_consent=${encodeURIComponent(JSON.stringify(consent))}; Max-Age=${180 * 86400}; Path=/; SameSite=Lax${secure}`;
    $('[data-cookiebar]')?.setAttribute('hidden', '');
    closeConsentModal();
    // odvolaný souhlas → obnovit stránku, aby se cizí obsah odstranil
    if (before.some(x => !consent.s.includes(x))) { location.reload(); return; }
    applyConsent();
  };
  const grantConsent = svc => saveConsent([...(consent ? consent.s : []), svc]);
  const runScripts = root => $$('script', root).forEach(old => {
    const s = document.createElement('script');
    [...old.attributes].forEach(a => s.setAttribute(a.name, a.value));
    s.textContent = old.textContent;
    old.replaceWith(s);
  });
  const applyConsent = () => {
    $$('[data-consent]').forEach(box => {
      const svc = box.dataset.consent;
      if (!allowed(svc)) return;
      box.classList.add('has-consent');
      if (box.dataset.src && !box.querySelector('iframe')) {
        const f = document.createElement('iframe');
        f.src = box.dataset.src;
        f.title = box.dataset.title || '';
        f.loading = 'lazy';
        f.setAttribute('allow', 'clipboard-write');
        f.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
        box.appendChild(f);
      }
      const tpl = box.querySelector(':scope > template');
      if (tpl && !box.dataset.done) {
        box.dataset.done = '1';
        const wrap = document.createElement('div');
        wrap.appendChild(tpl.content.cloneNode(true));
        box.appendChild(wrap);
        runScripts(wrap);
      }
    });
  };
  const modal = $('[data-consent-modal]');
  const openConsentModal = () => {
    if (!modal) return;
    $$('input[name]', modal).forEach(i => (i.checked = allowed(i.name)));
    modal.hidden = false;
    $('input[name]', modal)?.focus();
  };
  function closeConsentModal() { if (modal) modal.hidden = true; }
  document.addEventListener('click', e => {
    const t = e.target.closest('[data-consent-all],[data-consent-none],[data-consent-open],[data-consent-allow]');
    if (!t) { if (e.target === modal) closeConsentModal(); return; }
    e.preventDefault();
    if (t.hasAttribute('data-consent-all')) saveConsent(CONSENT.services);
    else if (t.hasAttribute('data-consent-none')) saveConsent([]);
    else if (t.hasAttribute('data-consent-open')) openConsentModal();
    else saveConsent([...(consent ? consent.s : []), t.dataset.consentAllow]);
  });
  $('[data-consent-form]')?.addEventListener('submit', e => {
    e.preventDefault();
    saveConsent($$('input[name]:checked', e.target).map(i => i.name));
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConsentModal(); });
  if (!consent && !CONSENT.auto) $('[data-cookiebar]')?.removeAttribute('hidden');
  $$('[data-consent-status]').forEach(td => { td.textContent = allowed(td.dataset.consentStatus) ? 'povoleno' : 'nepovoleno'; });

  /* ---------- Menu ---------- */
  const toggle = $('[data-nav-toggle]');
  const desktop = matchMedia('(min-width: 1240px)');
  toggle?.addEventListener('click', () => {
    const open = document.body.classList.toggle('nav-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.body.classList.remove('nav-open');
      toggle?.setAttribute('aria-expanded', 'false');
      $$('.has-sub.is-open').forEach(li => closeSub(li));
      closeLightbox();
    }
  });
  const closeSub = li => { li.classList.remove('is-open'); li.firstElementChild.setAttribute('aria-expanded', 'false'); };
  $$('.has-sub > button').forEach(btn => btn.addEventListener('click', () => {
    const li = btn.parentElement;
    const willOpen = !li.classList.contains('is-open');
    if (desktop.matches) $$('.has-sub.is-open').forEach(closeSub);
    li.classList.toggle('is-open', willOpen);
    btn.setAttribute('aria-expanded', String(willOpen));
  }));
  document.addEventListener('click', e => {
    if (desktop.matches && !e.target.closest('.has-sub')) $$('.has-sub.is-open').forEach(closeSub);
  });

  /* ---------- Loga týmů: když se nenačtou, zobrazí se iniciály ---------- */
  const crestFallback = img => {
    const s = document.createElement('span');
    s.className = img.className + ' crest--text';
    s.textContent = img.dataset.initials || '?';
    img.replaceWith(s);
  };
  document.addEventListener('error', e => {
    const t = e.target;
    if (t.tagName === 'IMG' && t.dataset.initials !== undefined) crestFallback(t);
  }, true);
  $$('img[data-initials]').forEach(img => { if (img.complete && img.naturalWidth === 0) crestFallback(img); });

  /* ---------- Odpočet do utkání ---------- */
  const countdowns = $$('[data-countdown]');
  const pad = n => String(n).padStart(2, '0');
  const tick = () => {
    const now = Date.now();
    countdowns.forEach(el => {
      const diff = Math.max(0, new Date(el.dataset.countdown).getTime() - now);
      const s = Math.floor(diff / 1000);
      el.querySelector('[data-d]').textContent = pad(Math.floor(s / 86400));
      el.querySelector('[data-h]').textContent = pad(Math.floor(s % 86400 / 3600));
      el.querySelector('[data-m]').textContent = pad(Math.floor(s % 3600 / 60));
      el.querySelector('[data-s]').textContent = pad(s % 60);
      if (diff === 0 && !el.dataset.done) {
        el.dataset.done = '1';
        el.outerHTML = '<span class="live-dot">PRÁVĚ SE HRAJE</span>';
      }
    });
  };
  if (countdowns.length) { tick(); setInterval(tick, 1000); }

  /* ---------- Živé skóre (během zápasu se samo obnovuje) ---------- */
  const liveSection = $('[data-live-section]');
  if (liveSection) {
    const scope = liveSection.dataset.liveSection;
    const params = new URLSearchParams({ scope });
    if (liveSection.dataset.team) params.set('team', liveSection.dataset.team);
    const url = `${base}/api/csh.php?${params}`;
    const soon = () => $$('[data-countdown]').some(el => new Date(el.dataset.countdown) - Date.now() < 2 * 3600e3)
      || $$('.is-live').length > 0;
    const poll = async () => {
      try {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        let changed = false;
        data.matches.forEach(m => {
          $$(`[data-match="${m.id}"]`).forEach(el => {
            const wasLive = el.classList.contains('is-live');
            if (m.state === 'live' && !wasLive) changed = true;
            if (m.state === 'finished' && wasLive) changed = true;
            const score = el.querySelector('[data-score]');
            if (score && m.home !== null) {
              const sep = document.createElement('i'); sep.textContent = ':';
              score.replaceChildren(String(Number(m.home)), sep, String(Number(m.away)));
            }
            const sh = el.querySelector('[data-score-home]'), sa = el.querySelector('[data-score-away]');
            if (sh && m.home !== null) { sh.textContent = m.home; sa.textContent = m.away; }
          });
        });
        if (changed && !document.hidden) location.reload();
        schedule(data.live);
      } catch { schedule(false); }
    };
    const schedule = live => setTimeout(poll, (live || soon()) ? 60e3 : 10 * 60e3);
    if (soon()) schedule(true);
  }

  /* ---------- Facebook: iframe vytvoříme sami podle skutečné šířky ----------
     Page Plugin se občas nevykreslí (typicky po návratu zpět na stránku z cache
     prohlížeče nebo když se načítá mimo obrazovku), proto ho vždy vložíme čerstvý. */
  const fbBoxes = $$('[data-fb]');
  const fbLoad = (box, force = false) => {
    if (!allowed('facebook')) return;
    const width = Math.max(180, Math.min(500, Math.floor(box.clientWidth - 20)));
    const old = box.querySelector('iframe');
    if (old && !force && old.dataset.w === String(width)) return;
    old?.remove();
    box.classList.remove('is-loaded');
    const params = new URLSearchParams(JSON.parse(box.dataset.fb));
    params.set('width', width);
    params.set('_', Date.now().toString(36)); // vždy nová instance, žádná polorozbitá z cache
    const f = document.createElement('iframe');
    f.src = 'https://www.facebook.com/plugins/page.php?' + params;
    f.width = width;
    f.dataset.w = String(width);
    f.title = 'Facebook';
    f.setAttribute('scrolling', 'no');
    f.setAttribute('allow', 'encrypted-media; clipboard-write');
    f.addEventListener('load', () => box.classList.add('is-loaded'));
    box.appendChild(f);
  };
  /* Facebook se načte jen po kliknutí na „Zobrazit příspěvky“ (kliknutí = souhlas s jeho cookies).
     Automaticky ho nenačítáme – widget je těžký a občas zamrzne celý prohlížeč. */
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-fb-show]');
    if (!btn) return;
    const box = btn.closest('[data-fb]');
    if (!allowed('facebook')) grantConsent('facebook');
    box.classList.add('is-open');
    btn.disabled = true;
    btn.textContent = 'Načítám…';
    fbLoad(box, true);
  });
  addEventListener('pageshow', e => { if (e.persisted) fbBoxes.forEach(b => b.classList.contains('is-open') && fbLoad(b, true)); });
  let fbRt;
  addEventListener('resize', () => { clearTimeout(fbRt); fbRt = setTimeout(() => fbBoxes.forEach(b => b.classList.contains('is-open') && fbLoad(b)), 400); });

  /* ---------- Záložky Rozpis / Výsledky ---------- */
  $$('[data-tabs]').forEach(box => {
    const tabs = $$('[role="tab"]', box);
    tabs.forEach(tab => tab.addEventListener('click', () => {
      tabs.forEach(t => {
        const on = t === tab;
        t.setAttribute('aria-selected', String(on));
        document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
      });
    }));
  });

  /* ---------- Podmenu týmu: zvýraznění aktivní sekce ---------- */
  const subLinks = $$('.subnav a');
  if (subLinks.length && 'IntersectionObserver' in window) {
    const map = new Map(subLinks.map(a => [a.getAttribute('href').slice(1), a]));
    const io = new IntersectionObserver(entries => entries.forEach(en => {
      if (en.isIntersecting) {
        subLinks.forEach(a => a.classList.remove('is-active'));
        map.get(en.target.id)?.classList.add('is-active');
      }
    }), { rootMargin: '-40% 0px -55% 0px' });
    map.forEach((_, id) => { const el = document.getElementById(id); if (el) io.observe(el); });
  }

  /* ---------- Karusel (Leo, dresy) ---------- */
  $$('[data-carousel]').forEach(c => {
    const items = [...c.children];
    if (items.length < 2 || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    let i = 0;
    setInterval(() => {
      items[i].classList.remove('is-active');
      i = (i + 1) % items.length;
      items[i].classList.add('is-active');
    }, 3500);
  });

  /* ---------- Lightbox ---------- */
  const lb = $('[data-lightbox]');
  function closeLightbox() { if (lb && !lb.hidden) { lb.hidden = true; document.body.style.overflow = ''; } }
  document.addEventListener('click', e => {
    const a = e.target.closest('[data-zoom]');
    if (a && lb) {
      e.preventDefault();
      $('img', lb).src = a.getAttribute('href');
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
    } else if (lb && !lb.hidden && (e.target === lb || e.target.closest('.lightbox__close'))) {
      closeLightbox();
    }
  });

  /* ---------- Číselná pole: jen číslice + automatický tvar (telefon za +420, rodné číslo, PSČ, rok) ---------- */
  const MASKS = {
    phone: d => {
      if (d.length > 9 && d.startsWith('00420')) d = d.slice(5);
      else if (d.length > 9 && d.startsWith('420')) d = d.slice(3);
      d = d.slice(0, 9);
      return [d.slice(0, 3), d.slice(3, 6), d.slice(6)].filter(Boolean).join(' ');
    },
    rc: d => (d = d.slice(0, 10)).length > 6 ? `${d.slice(0, 6)}/${d.slice(6)}` : d,
    psc: d => (d = d.slice(0, 5)).length > 3 ? `${d.slice(0, 3)} ${d.slice(3)}` : d,
    year: d => d.slice(0, 4),
  };
  const RC_OK = v => {
    const d = v.replace(/\D/g, '');
    if (!/^\d{10}$/.test(d)) return false;
    let m = +d.slice(2, 4) % 50; if (m > 20) m -= 20;
    const day = +d.slice(4, 6);
    if (m < 1 || m > 12 || day < 1 || day > 31) return false;
    if (d.length === 10) {
      const mod = Number(BigInt(d.slice(0, 9)) % 11n);
      return Number(BigInt(d) % 11n) === 0 || (mod === 10 && d[9] === '0');
    }
    return true;
  };
  $$('input[data-mask]').forEach(input => {
    const mask = MASKS[input.dataset.mask];
    if (!mask) return;
    const apply = () => {
      const atEnd = input.selectionStart === input.value.length;
      const out = mask(input.value.replace(/\D/g, ''));
      if (out !== input.value) {
        input.value = out;
        if (atEnd) input.setSelectionRange(out.length, out.length);
      }
      if (input.dataset.mask === 'rc') {
        input.setCustomValidity(out === '' || out.replace(/\D/g, '').length < 10 || RC_OK(out) ? '' : 'Rodné číslo není platné – zkontrolujte ho prosím.');
      }
    };
    input.addEventListener('input', apply);
    input.addEventListener('blur', apply);
  });

  /* ---------- Formuláře (odeslání bez obnovení stránky) ---------- */
  const showErrors = (form, errors = {}) => {
    $$('.field.has-error', form).forEach(f => f.classList.remove('has-error'));
    $$('.field__error', form).forEach(el => el.remove());
    Object.entries(errors).forEach(([name, msg]) => {
      const input = form.querySelector(`[name="${name}"], [name^="${name}["]`);
      const field = input?.closest('.field');
      if (!field) return;
      field.classList.add('has-error');
      const p = document.createElement('small');
      p.className = 'field__error';
      p.textContent = msg;
      field.appendChild(p);
    });
    const first = $('.has-error', form);
    first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  // Ochrana proti robotům: ověřovací hodnotu zapíše až skutečné kliknutí / psaní ve formuláři
  const humanMark = form => {
    const hc = form.querySelector('[name="_hc"]');
    if (hc && form.dataset.hc) hc.value = form.dataset.hc.split('').reverse().join('');
  };
  $$('form[data-hc]').forEach(form => {
    const mark = e => { if (e.isTrusted) humanMark(form); };
    ['pointerdown', 'keydown', 'input', 'change', 'touchstart'].forEach(ev => form.addEventListener(ev, mark, { passive: true }));
  });

  const refreshToken = async form => {
    const id = form.dataset.formId;
    const input = form.querySelector('[name="_token"]');
    if (!id || !input) return false;
    try {
      const r = await fetch(`${base}/api/token.php?form=${encodeURIComponent(id)}`, { cache: 'no-store' });
      const d = await r.json();
      if (d.ok) {
        input.value = d.token;
        if (d.hc) { form.dataset.hc = d.hc; humanMark(form); }
        return true;
      }
    } catch { /* ignore */ }
    return false;
  };

  const submitJson = async (form, status) => {
    const btn = $('button[type="submit"]', form);
    btn.disabled = true;
    status.className = 'lions-form__status';
    status.textContent = 'Odesílám…';
    const send = async () => {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, cache: 'no-store' });
      const text = await res.text();
      try { return JSON.parse(text); } catch { throw new Error('bad response'); }
    };
    try {
      let data = await send();
      // Vypršelý token (stránka dlouho otevřená / z cache) → nový token a automaticky znovu
      if (!data.ok && data.code === 'token' && await refreshToken(form)) {
        data = await send();
      }
      status.classList.add(data.ok ? 'is-ok' : 'is-error');
      status.textContent = data.message;
      showErrors(form, data.errors);
      if (data.ok) status.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return data;
    } catch {
      status.classList.add('is-error');
      status.textContent = 'Odeslání se nezdařilo. Zkontrolujte připojení k internetu a zkuste to prosím znovu.';
      return { ok: false };
    } finally {
      btn.disabled = false;
    }
  };

  $$('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!form.reportValidity()) return;
      const data = await submitJson(form, $('.lions-form__status', form));
      if (data.ok) form.reset();
    });
  });

  /* ---------- miniGYM booking ---------- */
  const gym = $('form[data-gym]');
  if (gym) {
    const dateInput = $('[name="date"]', gym);
    const timeInput = $('[name="time"]', gym);
    const box = $('[data-slots]', gym);
    const status = $('.lions-form__status', gym);
    const load = async () => {
      timeInput.value = '';
      box.innerHTML = '<p class="muted">Načítám volné termíny…</p>';
      try {
        const res = await fetch(`${base}/api/minigym.php?date=${encodeURIComponent(dateInput.value)}`);
        const data = await res.json();
        if (!data.ok) throw new Error();
        box.innerHTML = '';
        data.slots.forEach(s => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'slot';
          b.disabled = !s.free;
          b.setAttribute('aria-pressed', 'false');
          b.innerHTML = `${s.time}<small>– ${s.end}${!s.free && !s.past ? ' · obsazeno' : ''}</small>`;
          b.addEventListener('click', () => {
            $$('.slot', box).forEach(x => x.setAttribute('aria-pressed', 'false'));
            b.setAttribute('aria-pressed', 'true');
            timeInput.value = s.time;
          });
          box.appendChild(b);
        });
        if (!data.slots.some(s => s.free)) box.insertAdjacentHTML('beforeend', '<p class="muted">Tento den už nejsou volné termíny.</p>');
      } catch {
        box.innerHTML = '<p class="muted">Termíny se nepodařilo načíst.</p>';
      }
    };
    // řada dní: kliknutí nastaví datum; výběr v poli „Jiný den“ označí odpovídající den
    const dayBtns = $$('[data-day]', gym);
    const markDay = () => dayBtns.forEach(b => {
      const on = b.dataset.day === dateInput.value;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', String(on));
    });
    dayBtns.forEach(b => b.addEventListener('click', () => { dateInput.value = b.dataset.day; markDay(); load(); }));
    dateInput.addEventListener('change', () => { markDay(); load(); });
    load();
    gym.addEventListener('submit', async e => {
      e.preventDefault();
      if (!timeInput.value) {
        status.className = 'lions-form__status is-error';
        status.textContent = 'Vyberte prosím čas rezervace.';
        return;
      }
      if (!gym.reportValidity()) return;
      const data = await submitJson(gym, status);
      if (data.ok) { $$('input[name="first"],input[name="last"],input[name="email"],input[name="phone"]', gym).forEach(i => (i.value = '')); }
      load();
    });
  }

  applyConsent();

  /* ---------- Jemné animace při scrollu ---------- */
  if ('IntersectionObserver' in window) {
    const els = $$('.section-title, .match-card, .panel, .links-card, .age-card, .partner, .form-card, .fb-card, .step, .facts li');
    els.forEach(el => el.classList.add('reveal'));
    const io = new IntersectionObserver(entries => entries.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    els.forEach(el => io.observe(el));
  }

  /* ================= Anonymní statistika (administrace → Návštěvnost) =================
   * Bez cookies a bez identifikace návštěvníka: posílá se jen „na které stránce, na co, v jaké části“. */
  (() => {
    try { if (localStorage.getItem('lionsAdmin')) return; } catch { /* soukromé okno */ }
    if (navigator.webdriver) return;
    const endpoint = `${base}/api/track.php`;
    const page = (location.pathname.slice(base.length) || '/').replace(/\/{2,}/g, '/');
    const clip = (s, n) => (s || '').replace(/\s+/g, ' ').trim().slice(0, n);
    const send = data => {
      const body = JSON.stringify({ p: page, ...data });
      if (!(navigator.sendBeacon && navigator.sendBeacon(endpoint, new Blob([body], { type: 'text/plain' })))) {
        fetch(endpoint, { method: 'POST', body, keepalive: true }).catch(() => {});
      }
    };

    // zobrazení stránky + odkud návštěvník přišel + typ zařízení
    let from = '';
    try { const r = document.referrer && new URL(document.referrer); if (r && r.host !== location.host) from = r.host.replace(/^www\./, ''); } catch { /* neplatný referrer */ }
    const w = window.innerWidth;
    send({ k: 'view', t: from, l: w < 760 ? 'mobil' : w < 1100 ? 'tablet' : 'počítač' });

    // část stránky, kde se kliklo: data-track-section, menu/patička…, jinak nadpis sekce
    const sectionOf = el => {
      const own = el.closest('[data-track-section]');
      if (own) return own.dataset.trackSection;
      const named = [['.site-header', 'Menu'], ['.subnav', 'Podmenu týmu'], ['.partner-strip', 'Lišta partnerů'], ['.site-footer', 'Patička'], ['.cookiebar, .consent', 'Cookies'], ['.page-hero', 'Záhlaví stránky']];
      for (const [sel, name] of named) if (el.closest(sel)) return name;
      const sec = el.closest('section, aside, article');
      if (!sec) return 'Stránka';
      const h = sec.querySelector('h1, h2, h3');
      return clip(h ? h.textContent : (sec.id || sec.className.split(' ')[0]), 60);
    };
    const labelOf = el => clip(el.dataset.track || el.getAttribute('aria-label') || el.textContent
      || el.querySelector('img')?.alt || el.title, 80) || '(bez textu)';
    const targetOf = a => {
      const href = a.getAttribute('href') || '';
      if (/^(mailto|tel):/i.test(href)) return href.slice(0, 120);
      if (href.startsWith('#')) return page + href;
      try {
        const u = new URL(a.href);
        if (u.host === location.host) return (u.pathname.slice(base.length) || '/') + u.hash;
        return u.host.replace(/^www\./, '') + u.pathname.replace(/\/$/, '');
      } catch { return ''; }
    };

    document.addEventListener('click', e => {
      const el = e.target.closest('a[href], button, summary, [data-track]');
      if (!el || el.closest('form') && el.type !== 'submit') return;   // psaní do formuláře se neměří
      send({ k: 'click', t: el.matches('a[href]') ? targetOf(el) : (el.type === 'submit' ? 'odeslání formuláře' : ''), l: labelOf(el), s: sectionOf(el) });
    }, { capture: true, passive: true });

    // kliknutí do vloženého widgetu (Sportlyzer registrace, mapa…) – okno ztratí fokus ve prospěch iframe
    window.addEventListener('blur', () => setTimeout(() => {
      const f = document.activeElement;
      if (f && f.tagName === 'IFRAME') {
        let host = '';
        try { host = new URL(f.src).host.replace(/^www\./, ''); } catch { /* bez adresy */ }
        send({ k: 'click', t: host, l: clip(f.title, 80) || 'vložený obsah', s: sectionOf(f) });
      }
    }, 0));
  })();
})();
