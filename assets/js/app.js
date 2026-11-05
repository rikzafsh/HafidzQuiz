'use strict';
const app = document.getElementById('app');
const el = (t, a = {}, ...c) => {
  const e = document.createElement(t);
  for (const k in a) { if (a[k] == null || a[k] === false) continue; k === 'class' ? e.className = a[k] : k.startsWith('on') ? e.addEventListener(k.slice(2), a[k]) : e.setAttribute(k, a[k]); }
  c.flat(9).forEach(x => e.append(x instanceof Node ? x : String(x ?? '')));
  return e;
};
const api = async (a, body, qs = '') => {
  const r = await fetch('api.php?a=' + a + qs, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {});
  let j; try { j = JSON.parse(await r.text()); } catch { throw Object.assign(new Error('Respons server tidak valid'), { status: r.status || 500 }); }
  if (!r.ok || j.error) throw Object.assign(new Error(j.error || 'Terjadi kesalahan'), { status: r.status });
  return j;
};
const toast = m => { const t = el('div', { class: 'toast' }, m); document.body.append(t); setTimeout(() => t.remove(), 2400); };

// ---------- Ikon SVG (flat design, tanpa emoji) ----------
const HEART = '<path fill="#ff4b4b" d="M12 21.5S3 15.6 3 9.4A5 5 0 0 1 12 6.6 5 5 0 0 1 21 9.4c0 6.2-9 12.1-9 12.1z"/>';
const STAR = '12,2.5 14.9,8.6 21.5,9.4 16.6,13.9 17.9,20.5 12,17.2 6.1,20.5 7.4,13.9 2.5,9.4 9.1,8.6';
const ICONS = {
  flame: `<path fill="#ff9600" d="M12 2.2c1.2 3.8 6.8 6.4 6.8 12.1a6.8 6.8 0 0 1-13.6 0c0-3.1 1.7-4.9 3.2-6.5.3 1.5 1 2.5 2 3C9.9 7.9 10.4 4.8 12 2.2z"/><path fill="#ffc800" d="M12 21.6a4 4 0 0 1-4-4c0-2.1 2.1-3.3 2.8-5.2 1.4 1 5.2 2.7 5.2 5.2a4 4 0 0 1-4 4z"/>`,
  bolt: `<polygon fill="#ffc800" points="13.5,1.8 4.8,13.2 10.8,13.2 9.6,22.2 19.2,10.2 13,10.2"/>`,
  coin: `<circle cx="12" cy="12" r="10" fill="#ffc800"/><circle cx="12" cy="12" r="6.6" fill="none" stroke="#e39b00" stroke-width="2"/><rect x="11" y="8" width="2" height="8" rx="1" fill="#e39b00"/>`,
  heart: HEART,
  heartBroken: HEART + `<path d="M12 6.6l-2.2 4.2 3 2.2-2.4 5.2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>`,
  lock: `<path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" fill="none" stroke="#8b99a1" stroke-width="2.6" stroke-linecap="round"/><rect x="4.5" y="10" width="15" height="11.5" rx="3" fill="#8b99a1"/><circle cx="12" cy="14.6" r="1.8" fill="#fff"/><rect x="11.2" y="15.2" width="1.6" height="3.4" rx=".8" fill="#fff"/>`,
  unlock: `<path d="M8 10.5V8a4 4 0 0 1 7.6-1.7" fill="none" stroke="#8b99a1" stroke-width="2.6" stroke-linecap="round"/><rect x="4.5" y="10" width="15" height="11.5" rx="3" fill="#8b99a1"/><circle cx="12" cy="14.6" r="1.8" fill="#fff"/><rect x="11.2" y="15.2" width="1.6" height="3.4" rx=".8" fill="#fff"/>`,
  check: `<path d="M5 12.8l4.6 4.6L19 7.6" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>`,
  trophy: `<path d="M7 3.5h10V10a5 5 0 0 1-10 0z" fill="#ffc800"/><path d="M7 5.5H3.8V8a3.6 3.6 0 0 0 3.6 3.6M17 5.5h3.2V8a3.6 3.6 0 0 1-3.6 3.6" fill="none" stroke="#ffc800" stroke-width="2"/><rect x="10.8" y="14.5" width="2.4" height="3.6" fill="#e39b00"/><rect x="7.5" y="18" width="9" height="3" rx="1.2" fill="#e39b00"/>`,
  repeat: `<path d="M20 12a8 8 0 1 1-2.6-5.9" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/><polygon points="21,2.8 21,9.6 14.2,9.6" fill="currentColor"/>`,
  ff: `<polygon fill="currentColor" points="3,5 12,12 3,19"/><polygon fill="currentColor" points="12,5 21,12 12,19"/>`,
  star: `<polygon fill="#ffc800" points="${STAR}"/>`,
  starO: `<polygon fill="currentColor" opacity=".22" points="${STAR}"/>`,
  book: `<path d="M3 5h7a2 2 0 0 1 2 2v13a2 2 0 0 0-2-2H3z" fill="#1cb0f6"/><path d="M21 5h-7a2 2 0 0 0-2 2v13a2 2 0 0 1 2-2h7z" fill="#1790c8"/>`,
  logout: `<g fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M15 8l4 4-4 4M19 12H9"/></g>`,
  cog: `<g stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1"/></g><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="2.6"/>`,
  chart: `<rect x="4" y="12" width="4" height="8" rx="1" fill="#1cb0f6"/><rect x="10" y="6" width="4" height="14" rx="1" fill="#58cc02"/><rect x="16" y="9" width="4" height="11" rx="1" fill="#ff9600"/>`,
  arrowLeft: `<path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>`,
  close: `<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>`,
  target: `<circle cx="12" cy="12" r="10" fill="#ff4b4b"/><circle cx="12" cy="12" r="6.8" fill="#fff"/><circle cx="12" cy="12" r="3.6" fill="#ff4b4b"/>`,
  timer: `<rect x="9.5" y="1.8" width="5" height="2.6" rx="1" fill="#1cb0f6"/><circle cx="12" cy="13.2" r="8.4" fill="#1cb0f6"/><circle cx="12" cy="13.2" r="5.6" fill="#fff"/><path d="M12 13.2V9.6M12 13.2l2.6 1.6" stroke="#1cb0f6" stroke-width="1.8" stroke-linecap="round" fill="none"/>`,
  home: `<path d="M3 11.5L12 3l9 8.5" fill="none" stroke="#ff9600" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 10.5V20a1 1 0 0 0 1 1h4v-6h3v6h4a1 1 0 0 0 1-1v-9.5L12 4.2z" fill="#ffc800"/>`,
  user: `<circle cx="12" cy="8" r="4.4" fill="#a560e8"/><path d="M3.5 21.5a8.5 8.5 0 0 1 17 0z" fill="#a560e8"/>`,
  party: `<path d="M3.5 21l5-13.5 8.5 8.5z" fill="#ffc800"/><path d="M3.5 21l3-8 5 5z" fill="#ff9600"/><circle cx="15" cy="5" r="1.6" fill="#ff4b4b"/><circle cx="20" cy="9" r="1.4" fill="#1cb0f6"/><rect x="11" y="3" width="3" height="3" rx=".6" fill="#58cc02" transform="rotate(20 12 4)"/><rect x="17.5" y="14.5" width="3" height="3" rx=".6" fill="#a560e8" transform="rotate(25 19 16)"/><path d="M12.5 9.5c2-2.5 4-2 5-3.5" stroke="#ff4b4b" fill="none" stroke-width="1.6" stroke-linecap="round"/>`,
  medal: `<path d="M7 2.5h4l2 6-3.5 1.5z" fill="#1cb0f6"/><path d="M17 2.5h-4l-2 6 3.5 1.5z" fill="#ff4b4b"/><circle cx="12" cy="15" r="6.6" fill="#ffc800"/><circle cx="12" cy="15" r="4.4" fill="#e39b00"/><polygon fill="#ffc800" points="12,11.8 13.1,13.9 15.4,14.2 13.7,15.8 14.1,18.1 12,17 9.9,18.1 10.3,15.8 8.6,14.2 10.9,13.9"/>`,
  sad: `<circle cx="12" cy="12" r="10" fill="#ffc800"/><circle cx="8.6" cy="10" r="1.5" fill="#6b4a00"/><circle cx="15.4" cy="10" r="1.5" fill="#6b4a00"/><path d="M8 17c1-1.8 2.4-2.6 4-2.6s3 .8 4 2.6" stroke="#6b4a00" stroke-width="1.8" fill="none" stroke-linecap="round"/>`,
  rocket: `<path d="M12 2c3.6 2.2 5.2 6 4.4 10.6L14.6 15h-5.2l-1.8-2.4C6.8 8 8.4 4.2 12 2z" fill="#1cb0f6"/><circle cx="12" cy="9" r="2.2" fill="#fff"/><path d="M7.6 12.6L4 15.5v3.2l4.4-1.6zM16.4 12.6l3.6 2.9v3.2l-4.4-1.6z" fill="#ff4b4b"/><path d="M10 16.2h4L12 22z" fill="#ff9600"/>`,
  zzz: `<g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h6l-6 7h6"/><path d="M13 12h5l-5 6h5"/></g>`,
  mountain: `<path d="M1.5 20.5L9 7l4.4 7.4 2.6-3.6 6.5 9.7z" fill="currentColor"/>`,
  flag: `<path d="M6 21V3.5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M6.5 4.5h11.5l-2.6 4.2 2.6 4.2H6.5z" fill="#ff4b4b"/>`,
  mushaf: `<rect x="4" y="2.5" width="16" height="19" rx="2.4" fill="#2f9e6b"/><rect x="4" y="2.5" width="3.8" height="19" rx="1.8" fill="#1f7a50"/><circle cx="13.8" cy="10.4" r="4.2" fill="none" stroke="#ffd23f" stroke-width="1.7"/><path d="M13.8 8l.9 1.8 2 .3-1.4 1.4.3 2-1.8-.9-1.8.9.3-2-1.4-1.4 2-.3z" fill="#ffd23f"/><rect x="9.6" y="17" width="8.4" height="1.7" rx=".85" fill="#fff" opacity=".55"/>`,
  search: `<circle cx="10.5" cy="10.5" r="6.4" fill="none" stroke="currentColor" stroke-width="2.8"/><path d="M15.4 15.4L21 21" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>`,
  shield: `<path d="M32 4l24 8v22c0 16-11 28-24 34C19 62 8 50 8 34V12z" fill="#ffc800"/><path d="M32 10l18 6v18c0 12-8 22-18 27-10-5-18-15-18-27V16z" fill="#e39b00" opacity=".4"/>`,
  avatar: `<circle cx="60" cy="52" r="30" fill="#2f8fc7"/><path d="M0 120c0-34 26-50 60-50s60 16 60 50z" fill="#2f8fc7"/><rect x="55" y="38" width="10" height="28" rx="3" fill="#cfeaff" opacity=".9"/><rect x="46" y="47" width="28" height="10" rx="3" fill="#cfeaff" opacity=".9"/>`,
};
const ic = (n, s = 20, cls = '') => {
  const e = document.createElement('span'); e.className = 'i ' + cls; e.style.cssText = `width:${s}px;height:${s}px`;
  e.innerHTML = `<svg viewBox="${n === 'shield' ? '0 0 64 72' : n === 'avatar' ? '0 0 120 120' : '0 0 24 24'}" aria-hidden="true">${ICONS[n] || ''}</svg>`; return e;
};
const stars = (n, s = 14) => el('span', { class: 'stk' }, [0, 1, 2].map(i => ic(i < n ? 'star' : 'starO', s)));

// ---------- Keyboard, layar, navigasi ----------
let keys = null; // penangan keyboard milik layar yang sedang tampil
const onEnter = fn => { keys = e => { if (e.key === 'Enter') { e.preventDefault(); if (!e.repeat) fn(); } }; };
addEventListener('keydown', e => { if (keys && !e.ctrlKey && !e.metaKey && !e.altKey && !e.target.matches('input,textarea,select')) keys(e); });
const mount = (...n) => { keys = null; app.replaceChildren(...n); scrollTo(0, 0); };
let me = null;
addEventListener('unhandledrejection', e => e.reason?.status === 401 ? authView() : toast(e.reason?.message || 'Terjadi kesalahan'));
const logout = async () => { await api('logout', {}); me = null; authView(); };
const store = { get: k => { try { return localStorage.getItem(k); } catch { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch {} } };
let devOn = store.get('hq_dev') === '1', redraw = null;   // devOn: mode developer sementara (hanya efektif bila server DEV_MODE = true)

// Menu utama: Belajar (utama), Mushaf, dan Profil. PC: sidebar berlabel | tablet: sidebar ikon | HP: tab bawah.
const NAV = [['learn', 'Belajar', 'home', () => juzView()], ['mushaf', 'Mushaf', 'mushaf', () => mushafView()], ['profile', 'Profil', 'user', () => profileView()]];
const nav = active => el('nav', { class: 'side', 'aria-label': 'Menu utama' }, el('b', { class: 'brand' }, 'HafidzQuiz'),
  NAV.map(([id, label, icon, go]) => el('button', { class: 'nv' + (id === active ? ' on' : ''), 'aria-label': label, 'aria-current': id === active ? 'page' : null, onclick: go }, ic(icon, 30), el('span', { class: 'lb' }, label))));
const shell = (active, ...kids) => mount(el('div', { class: 'shell' }, nav(active), el('main', { class: 'main' }, ...kids)));
const topbar = back => el('header', { class: 'top' },
  back ? el('button', { class: 'ico', onclick: back, 'aria-label': 'Kembali' }, ic('arrowLeft', 22)) : el('b', { class: 'brand mob' }, 'HafidzQuiz'),
  el('span', { class: 'sp' }),
  me.dev ? el('button', { class: 'chip dev' + (devOn ? ' on' : ''), title: 'Mode developer sementara: buka level terkunci untuk pengetesan',
    onclick: () => { devOn = !devOn; store.set('hq_dev', devOn ? '1' : '0'); redraw?.(); } }, ic('cog', 16), devOn ? 'DEV ON' : 'DEV') : '',
  el('span', { class: 'chip', title: 'Streak harian' }, ic('flame', 18), me.streak),
  el('span', { class: 'chip', title: 'Total XP' }, ic('bolt', 18), me.xp),
  el('span', { class: 'chip', title: 'Dirham' }, ic('coin', 18), me.dirham));

function authView(mode = 'login') {
  const u = el('input', { placeholder: 'Nama pengguna', autocomplete: 'username', maxlength: 30 });
  const p = el('input', { type: 'password', placeholder: 'Kata sandi', autocomplete: mode === 'login' ? 'current-password' : 'new-password' });
  const err = el('p', { class: 'err' });
  const go = async () => { try { me = (await api(mode, { username: u.value, password: p.value })).user; juzView(); } catch (e) { err.textContent = e.message; } };
  u.addEventListener('keydown', e => e.key === 'Enter' && p.focus());
  p.addEventListener('keydown', e => e.key === 'Enter' && go());
  mount(el('div', { class: 'wrap auth' }, el('h1', {}, 'HafidzQuiz'), el('p', { class: 'mut' }, 'Hafalan Al-Qur’an, selangkah demi selangkah.'), u, p, err,
    el('button', { class: 'btn', onclick: go }, mode === 'login' ? 'Masuk' : 'Daftar'),
    el('button', { class: 'link', onclick: () => authView(mode === 'login' ? 'register' : 'login') }, mode === 'login' ? 'Belum punya akun? Daftar' : 'Sudah punya akun? Masuk')));
  u.focus();
}

async function juzView() {
  redraw = () => juzView();
  const d = await api('juz'); me = d.user;
  shell('learn', topbar(), el('div', { class: 'wrap' }, el('h2', {}, 'Belajar'), el('p', { class: 'mut' }, 'Pilih juz. Boleh mulai dari juz mana saja.'),
    el('div', { class: 'grid' }, d.juz.map(j => el('button', { class: 'juz', onclick: () => pathView(j.juz) },
      el('b', {}, 'Juz ' + j.juz), el('small', {}, `${j.done}/${j.total} thumn`), el('i', {}, el('u', { style: `width:${100 * j.done / j.total}%` })))))));
}

// ---------- Mushaf: ayat disusun seperti halaman mushaf; tiap halaman terbagi 5 bagian berwarna yang mengalir di dalam baris ----------
const arNum = n => String(n).replace(/\d/g, d => '٠١٢٣٤٥٦٧٨٩'[d]);
let mushafMeta = null;
async function mushafView(page, target = null) {
  page = page || +store.get('hq_mushaf') || 1;
  redraw = () => mushafView(page);
  mushafMeta ??= await api('mushaf_meta');
  let d; try { d = await api('mushaf', null, '&page=' + page); } catch (e) { if (page !== 1) { toast(e.message); return mushafView(1); } throw e; }
  store.set('hq_mushaf', d.page); const max = d.maxPage, go = (p, t = null) => mushafView(p, t);

  const secNodes = sec => {   // spanduk surah bersifat blok: bagian dipecah di sekitarnya dengan warna yang sama
    const out = []; let cur = el('span', { class: 'sec s' + sec.n }); out.push(cur);
    sec.items.forEach(it => {
      if (it.t === 'surah') {
        out.push(el('div', { class: 'sb' }, el('b', {}, 'سورة ' + it.arabic), it.basmalah ? el('div', { class: 'bsm' }, 'بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ') : ''));
        cur = el('span', { class: 'sec s' + sec.n }); out.push(cur); return;
      }
      cur.append(el('span', { class: 'ay', 'data-s': it.surah, 'data-a': it.ayah },
        it.mark ? el('span', { class: 'tm', title: `Awal Hizb ${it.tm.hizb} · Thumn ${it.tm.thumn}` }, '۞ ') : '',
        it.text, ' ', it.end ? el('span', { class: 'an' }, arNum(it.ayah)) : '', ' '));
    });
    return out.filter(n => n.tagName !== 'SPAN' || n.childNodes.length);
  };
  const pageEl = el('article', { class: 'mpage', 'aria-label': 'Halaman ' + d.page },
    el('header', { class: 'mph', dir: 'rtl' }, el('span', {}, d.juz ? 'الجزء ' + arNum(d.juz) : ''), el('span', {}, 'سورة ' + d.surah.arabic)),
    el('div', { class: 'mtext', dir: 'rtl', lang: 'ar' }, d.sections.flatMap(secNodes)),
    el('footer', { class: 'mpf' }, arNum(d.page)),
    el('div', { class: 'mmeta' }, [d.juz && `Juz ${d.juz}`, d.hizb && `Hizb ${d.hizb}`, d.thumn && `Thumn ${d.thumn}`, d.surah.latin].filter(Boolean).join(' · ')));

  // Navigasi: surah + ayat, juz, hizb, thumn, atau nomor halaman
  const openNav = () => {
    const meta = mushafMeta, prevKeys = keys; let mode = 'surah';
    const close = () => { ov.remove(); keys = prevKeys; };
    const fld = (label, input, hint) => el('div', { class: 'fld' }, el('label', {}, label), input, hint ? el('small', { class: 'mut' }, hint) : '');
    const select = (opts, val) => el('select', {}, opts.map(([v, t]) => el('option', { value: v, selected: String(v) === String(val) ? '' : null }, t)));
    const range = (n, f) => [...Array(n)].map((_, i) => [i + 1, f(i + 1)]);
    const modes = {
      surah: () => {
        const s = select(meta.surahs.map(x => [x.n, `${x.n}. ${x.latin}${x.arabic ? ' — ' + x.arabic : ''}`]), d.surah.n), a = el('input', { type: 'number', min: 1, value: 1, inputmode: 'numeric' });
        const hint = el('small', { class: 'mut' }), upd = () => { const m = meta.surahs[s.value - 1].ayat; a.max = m; hint.textContent = `Surah ini punya ${m} ayat.`; }; s.onchange = upd; upd();
        return { els: [fld('Surah', s), el('div', { class: 'fld' }, el('label', {}, 'Ayat'), a, hint)], q: () => `&type=surah&surah=${s.value}&ayah=${a.value || 1}` };
      },
      juz: () => { const s = select(range(30, n => 'Juz ' + n), d.juz); return { els: [fld('Juz', s)], q: () => `&type=juz&n=${s.value}` }; },
      hizb: () => { const s = select(range(60, n => 'Hizb ' + n), d.hizb); return { els: [fld('Hizb', s)], q: () => `&type=hizb&n=${s.value}` }; },
      thumn: () => { const h = select(range(60, n => 'Hizb ' + n), d.hizb), t = select(range(8, n => 'Thumn ' + n), d.thumn); return { els: [fld('Hizb', h), fld('Thumn', t)], q: () => `&type=thumn&hizb=${h.value}&thumn=${t.value}` }; },
      page: () => { const p = el('input', { type: 'number', min: 1, max: 604, value: d.page, inputmode: 'numeric' }); return { els: [fld('Nomor halaman', p, `Data tersedia sampai halaman ${max}.`)], q: () => `&type=page&page=${p.value || 1}` }; },
    };
    const LBL = { surah: 'Surah', juz: 'Juz', hizb: 'Hizb', thumn: 'Thumn', page: 'Halaman' };
    const msg = el('p', { class: 'err', role: 'alert' }), body = el('div'), tabs = el('div', { class: 'mtabs', role: 'tablist' });
    let cur;
    const draw = () => {
      cur = modes[mode](); msg.textContent = ''; body.replaceChildren(...cur.els);
      tabs.replaceChildren(...Object.keys(LBL).map(k => el('button', { class: 'mtab' + (k === mode ? ' on' : ''), role: 'tab', 'aria-selected': k === mode, onclick: () => { mode = k; draw(); } }, LBL[k])));
      body.querySelector('input,select')?.focus();
    };
    const submit = async () => { try { const r = await api('mushaf_find', null, cur.q()); close(); go(r.page, r.target); } catch (e) { msg.textContent = e.message; } };
    const ov = el('div', { class: 'mnv-ov', onclick: close }, el('div', { class: 'mnv', role: 'dialog', 'aria-label': 'Navigasi mushaf', onclick: e => e.stopPropagation() },
      el('h3', {}, 'Pergi ke…'), tabs, body, msg,
      el('div', { class: 'mact' }, el('button', { class: 'btn alt', onclick: close }, 'Batal'), el('button', { class: 'btn', onclick: submit }, 'Pergi'))));
    document.body.append(ov); draw();
    keys = e => { if (e.key === 'Escape') close(); else if (e.key === 'Enter' && !e.target.matches?.('button')) { e.preventDefault(); submit(); } };
    ov.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.target.matches('button')) { e.preventDefault(); submit(); } else if (e.key === 'Escape') close(); });
  };

  const pin = el('input', { type: 'number', min: 1, max, value: d.page, 'aria-label': 'Nomor halaman', inputmode: 'numeric' });
  let jumped = false;   // Enter dan 'change' bisa terpicu bersamaan: proses satu kali saja
  const jumpTo = () => { if (jumped) return; jumped = true; go(Math.min(max, Math.max(1, +pin.value || 1))); };
  pin.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); jumpTo(); } });
  pin.addEventListener('change', jumpTo);
  const nextB = el('button', { class: 'ibtn', 'aria-label': 'Halaman berikutnya', title: 'Halaman berikutnya (←)', disabled: d.page >= max ? '' : null, onclick: () => go(d.page + 1) }, ic('arrowLeft', 22));
  const prevB = el('button', { class: 'ibtn flip', 'aria-label': 'Halaman sebelumnya', title: 'Halaman sebelumnya (→)', disabled: d.page <= 1 ? '' : null, onclick: () => go(d.page - 1) }, ic('arrowLeft', 22));
  shell('mushaf', topbar(), el('div', { class: 'wrap mwrap' },
    el('div', { class: 'mtool' }, nextB, el('div', { class: 'pg' }, pin, el('span', { class: 'mut' }, '/ ' + max)), prevB,
      el('button', { class: 'btn navb', onclick: openNav }, ic('search', 20), 'Navigasi')),
    max < 604 ? el('p', { class: 'mut mnote' }, `Data mushaf baru tersedia sampai halaman ${max}.`) : '',
    pageEl, el('div', { class: 'mtool mbot' }, nextB.cloneNode(true), prevB.cloneNode(true))));
  document.querySelectorAll('.mbot .ibtn').forEach((b, i) => b.addEventListener('click', () => go(d.page + (i ? -1 : 1))));
  keys = e => { if (e.key === 'ArrowLeft' && d.page < max) go(d.page + 1); else if (e.key === 'ArrowRight' && d.page > 1) go(d.page - 1); };   // arah baca mushaf: kanan ke kiri
  if (target) { const n = pageEl.querySelector(`.ay[data-s="${target.surah}"][data-a="${target.ayah}"]`); if (n) { n.classList.add('hl'); n.scrollIntoView({ block: 'center' }); } }
}

// ---------- Profil ----------
const ACH = [
  { icon: 'flame', title: 'Api Semangat', steps: [3, 7, 14, 30, 100, 365], text: n => `Mencapai streak ${n} hari`, val: s => s.streak },
  { icon: 'bolt', title: 'Penuntut Ilmu', steps: [100, 500, 1000, 5000, 10000, 30000], text: n => `Mengumpulkan ${n} XP`, val: s => s.xp },
  { icon: 'book', title: 'Penghafal', steps: [1, 4, 8, 16, 48, 160, 480], text: n => `Menyelesaikan ${n} thumn`, val: s => s.thumnDone },
  { icon: 'coin', title: 'Pengumpul Dirham', steps: [20, 100, 300, 1000], text: n => `Mengumpulkan ${n} dirham`, val: s => s.dirham },
];
async function profileView() {
  redraw = () => profileView();
  const d = await api('profile'); me = d.user; const s = { ...d.stats, streak: me.streak, xp: me.xp, dirham: me.dirham };
  const joined = d.joined ? new Date(d.joined.replace(' ', 'T')).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : null;
  const card = (icon, val, lbl, cls = '') => el('div', { class: 'sc2 ' + cls }, ic(icon, 34), el('div', {}, el('b', {}, val), el('small', {}, lbl)));
  const badge = (a) => {
    const v = a.val(s), lv = a.steps.filter(n => v >= n).length, next = a.steps[lv], pct = next ? Math.min(100, 100 * v / next) : 100;
    return el('div', { class: 'ach' }, el('div', { class: 'bdg' }, ic('shield', 64), el('span', { class: 'bi' }, ic(a.icon, 26)), el('small', {}, lv ? 'LEVEL ' + lv : 'BELUM')),
      el('div', { class: 'at' }, el('b', {}, a.title), el('span', {}, a.text(next ?? a.steps.at(-1))),
        el('div', { class: 'ap' }, el('u', { style: `width:${pct}%` }), el('i', {}, next ? `${v}/${next}` : 'Level tertinggi'))));
  };
  shell('profile', topbar(), el('div', { class: 'wrap' },
    el('div', { class: 'pbanner' }, ic('avatar', 150)),
    el('h2', { class: 'pname' }, me.name), el('p', { class: 'mut' }, joined ? 'Bergabung ' + joined : 'Pengguna HafidzQuiz'),
    el('h3', {}, 'Statistik'),
    el('div', { class: 'stats' }, card('flame', me.streak, 'Streak (hari)', 'gold'), card('bolt', me.xp, 'Total XP'), card('coin', me.dirham, 'Dirham'),
      card('check', `${s.thumnDone}/${s.thumnTotal}`, 'Thumn selesai' + (s.thumnSkipped ? ` (${s.thumnSkipped} dilewati)` : ''))),
    el('h3', {}, 'Pencapaian'), el('div', { class: 'achs' }, ACH.map(badge)),
    el('div', { class: 'pact' },
      el('a', { class: 'btn alt', href: 'index.php' }, ic('book', 22), 'Muroja’ah bebas'),
      me.admin ? el('a', { class: 'btn alt', href: 'admin.php' }, ic('chart', 22), 'Laporan masukan (admin)') : '',
      el('button', { class: 'btn alt', onclick: logout }, ic('logout', 22), 'Keluar'))));
}

// ---------- Peta belajar ----------
const oct = (cx, cy, r) => {
  const v = [...Array(8)].map((_, k) => { const a = (-67.5 + 45 * k) * Math.PI / 180; return [cx + r * Math.cos(a), cy + r * Math.sin(a)]; });
  return [[cx, cy - r * Math.cos(Math.PI / 8)], ...v].map(p => p.map(x => x.toFixed(1)).join(',')).join(' ');
};
const nodeSvg = (locked, pct) => {
  const L = 16 * 52 * Math.sin(Math.PI / 8);
  return `<svg viewBox="0 0 112 120" aria-hidden="true">${locked ? '' :
    `<polygon class="trk" points="${oct(56, 54, 52)}"/><polygon class="prg" points="${oct(56, 54, 52)}" stroke-dasharray="${(pct / 100 * L).toFixed(1)} ${L.toFixed(1)}"/>`}` +
    `<polygon class="shd" points="${oct(56, 62, 40)}"/><polygon class="face" points="${oct(56, 54, 40)}"/></svg>`;
};
const PHASE = {
  Dasar: 'Melengkapi ayat, menyambung ayat, dan menebak ayat sesudahnya.',
  Menengah: 'Ditambah menebak ayat sebelumnya, nomor ayat, dan harakat.',
  Mahir: 'Ditambah mencocokkan ayat, i’rab, kata yang mirip, dan kata terakhir ayat.',
};
const KIND = { thumn: 'Thumn', hizb: 'Hizb', juz: 'Juz' };
const fmtTime = s => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
const chestSvg = open => `<svg viewBox="0 0 72 64" aria-hidden="true"><rect x="8" y="30" width="56" height="28" rx="6" fill="#9a5b1c"/><rect x="8" y="30" width="56" height="8" fill="#7d4812"/>` +
  (open ? `<g transform="rotate(-30 8 30)"><path d="M8 30V22a14 14 0 0 1 14-14h28a14 14 0 0 1 14 14v8z" fill="#c27a26"/></g><circle cx="26" cy="27" r="5.5" fill="#ffd23f"/><circle cx="38" cy="23" r="5.5" fill="#ffc400"/><circle cx="49" cy="28" r="5.5" fill="#ffd23f"/>`
    : `<path d="M8 30V22a14 14 0 0 1 14-14h28a14 14 0 0 1 14 14v8z" fill="#c27a26"/><rect x="31" y="24" width="10" height="16" rx="3" fill="#ffd23f"/><circle cx="36" cy="32" r="2.4" fill="#7d4812"/>`) + `</svg>`;

async function pathView(juz) {
  redraw = () => pathView(juz);
  const d = await api('units', null, '&juz=' + juz); me = d.user;
  const off = [0, 38, 58, 38, 0, -38, -58, -38];
  let pop = null;
  const closePop = () => { pop?.remove(); pop = null; };
  const reload = async () => { const y = scrollY; await pathView(juz); scrollTo(0, y); };
  const place = (row, key, start, btn) => { pop._start = start; pop._key = key; row.after(pop); btn?.focus?.({ preventScroll: true }); pop.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); };
  const popup = (row, u, n, ox) => {
    closePop();
    const forced = n.state === 'locked' && devOn, lock = n.state === 'locked' && !devOn, done = n.state === 'done', kind = KIND[u.kind];
    const start = () => session(u.i, n.level, n.lesson, juz, kind);
    const btn = lock ? '' : el('button', { class: 'pbtn', onclick: start }, `${done ? 'Latih lagi' : forced ? 'Mulai (dev)' : 'Mulai'} +${n.xp} XP`);
    const dev = devOn ? el('div', { class: 'devrow' },
      el('button', { onclick: async () => { await api('dev_mark', { unit: u.i, level: n.level }); reload(); } }, 'Tandai selesai s.d. level ini'),
      el('button', { onclick: async () => { if (confirm('Hapus semua progres unit ini (termasuk chest & lompat)?')) { await api('dev_reset', { unit: u.i }); reload(); } } }, 'Reset unit')) : '';
    const desc = n.review ? `Ulasan ${n.reviewName}: soal dari awal hizb sampai Thumn ${u.thumn} ini (kumulatif).` : PHASE[n.phase];
    pop = el('div', { class: 'pop' + (lock ? ' lk' : ''), style: `--ox:${ox}px`, onclick: e => e.stopPropagation() },
      el('b', {}, n.review ? `Level ${n.level} · ${n.phase}` : `Level ${n.level} dari ${n.levels} · ${n.phase}`),
      el('span', {}, lock ? 'Selesaikan level sebelumnya dulu.' : `${n.skipped ? 'Dilewati lewat tes lompat · ' : done ? 'Selesai · ' : ''}Lesson ${n.lesson} dari ${n.lessons}`),
      lock ? '' : el('small', {}, [desc, n.exam ? ` Ujian akhir ${kind.toLowerCase()}: 15 soal, lulus dengan skor ≥ 80%.` : '']),
      forced ? el('small', { class: 'dv' }, ic('cog', 14), 'Mode developer: level ini sebenarnya masih terkunci.') : '',
      lock ? '' : el('small', {}, `Bonus +${n.bonus} XP jika semua benar di percobaan pertama`), btn, dev);
    place(row, u.i + n.id, lock ? null : start, btn);
  };
  const popupJump = (row, u) => {   // "Lompat ke sini": tes 25 soal, semua harus benar
    closePop();
    const start = () => session(u.i, 0, 0, juz, 'Thumn', { jump: true });
    const btn = el('button', { class: 'pbtn', onclick: start }, 'Mulai tes +20 XP');
    pop = el('div', { class: 'pop', style: '--ox:0px', onclick: e => e.stopPropagation() },
      el('b', {}, 'Lompat ke sini?'), el('span', {}, `Tes 25 soal dari ${u.skip} thumn yang dilewati.`),
      el('small', {}, 'Semua jawaban harus benar. Satu salah berarti tes gagal (tanpa nyawa).'),
      el('small', {}, 'Jika berhasil, thumn yang dilewati ditandai selesai (tanpa chest) dan thumn ini terbuka.'), btn);
    place(row, 'J' + u.i, start, btn);
  };
  const cls = u => u.kind === 'hizb' ? 9 : u.kind === 'juz' ? 10 : (u.thumn - 1) % 8 + 1;   // thumn: pelangi 1-8 | hizb: abu kebiruan | juz: emas
  const jumpRow = u => {
    const b = el('button', { class: 'node jumpn open', 'aria-label': 'Lompat ke thumn ini', onclick: ev => { ev.stopPropagation(); pop && pop._key === 'J' + u.i ? closePop() : popupJump(row, u); } },
      el('span', { class: 'ic' }, ic('ff', 34)));
    b.insertAdjacentHTML('afterbegin', nodeSvg(false, 0));
    const row = el('div', { class: 'row' }, el('div', { class: 'nw' }, el('div', { class: 'jbub' }, 'LOMPAT KE SINI?'), b));
    return row;
  };
  const nodeRow = (u, n, k) => {
    const ox = off[k % 8], locked = n.state === 'locked';
    const b = el('button', { class: 'node ' + n.state + (locked && devOn ? ' dev' : ''), 'aria-label': n.review ? n.phase : `Level ${n.level}`,
      onclick: ev => { ev.stopPropagation(); pop && pop._key === u.i + n.id ? closePop() : popup(row, u, n, ox); } },
      el('span', { class: 'ic' }, locked ? ic(devOn ? 'unlock' : 'lock', 32) : n.state === 'done' ? ic('check', 34) : n.review ? ic('repeat', 32) : n.final ? ic('trophy', 34) : String(n.level)));
    b.insertAdjacentHTML('afterbegin', nodeSvg(locked, n.state === 'done' ? 100 : 100 * n.done / n.lessons));
    const row = el('div', { class: 'row' }, el('div', { class: 'nw', style: `transform:translateX(${ox}px)` }, b,
      el('div', { class: 'stars' }, n.skipped ? ic('ff', 16) : n.state === 'done' ? stars(n.stars) : n.state === 'open' ? `${n.done}/${n.lessons}` : '')));
    return row;
  };
  const chestRow = (u, c, k) => {   // chest: terkunci -> siap dibuka -> terbuka (10-20 dirham)
    const b = el('button', { class: 'chest ' + c.state, 'aria-label': `Chest setelah level ${c.after}`, onclick: async ev => {
      ev.stopPropagation(); closePop();
      if (c.state === 'locked') return toast(`Selesaikan Level ${c.after} untuk membuka chest ini`);
      if (c.state === 'opened') return toast(`Chest sudah dibuka: +${c.amount} dirham`);
      const r = await api('claim', { unit: u.i, chest: c.after }); toast(`Chest terbuka! +${r.amount} dirham`); reload();
    } });
    b.insertAdjacentHTML('afterbegin', chestSvg(c.state === 'opened'));
    return el('div', { class: 'row' }, el('div', { class: 'nw', style: `transform:translateX(${off[k % 8]}px)` }, b,
      el('div', { class: 'stars' }, c.state === 'ready' ? 'Buka!' : c.state === 'opened' ? ['+' + c.amount, ic('coin', 14)] : `Level ${c.after}`)));
  };
  const rowsOf = u => { const r = []; u.nodes.forEach(n => { r.push(['n', n]); const c = (u.chests || []).find(c => c.after === n.level && !n.review); if (c) r.push(['c', c]); }); return r; };
  shell('learn', topbar(juzView), el('div', { class: 'wrap', onclick: closePop }, el('h2', {}, 'Juz ' + juz),
    d.units.map(u => el('section', { class: 'unit u' + cls(u) },
      el('div', { class: 'uh' }, el('small', {}, u.label), el('b', {}, u.title)),
      u.empty ? el('p', { class: 'err' }, u.bad ? 'Titik awal thumn ini tidak konsisten di data/thumn.json (kemungkinan salah ketik), jadi ayatnya digabung ke thumn sebelumnya.' : 'Tidak ada ayat pada unit ini — periksa data/thumn.json dan file surahnya.') : '',
      el('div', { class: 'path' }, u.jump ? jumpRow(u) : '', rowsOf(u).map(([t, x], k) => t === 'n' ? nodeRow(u, x, k) : chestRow(u, x, k)))))));
  keys = e => { if (e.key === 'Escape') closePop(); else if (e.key === 'Enter' && pop?._start) { e.preventDefault(); pop._start(); } };
  document.querySelector('.node.open:not(.jumpn)')?.scrollIntoView({ block: 'center' });
}

// ---------- Sesi kuis: soal diterima TANPA kunci; jawaban diperiksa, dan skor/nyawa/waktu dihitung, oleh server ----------
async function session(unit, level, lesson, juz, kind = 'Thumn', opts = {}) {
  const jump = !!opts.jump;
  const d = await api('quiz', null, jump ? `&unit=${unit}&jump=1` : `&unit=${unit}&level=${level}&lesson=${lesson}`);
  const attempt = d.attempt, total = d.total, queue = [...d.questions];
  let hearts = d.hearts, done = 0, run = 0, busy = false;      // tes lompat: satu salah = gagal
  const pb = el('u'), pbw = el('div', { class: 'pb' }, pb), hr = el('span', { class: 'hearts' }), runLbl = el('div', { class: 'run' }), body = el('div'), foot = el('div', { class: 'foot' });
  const upd = () => {
    pb.style.width = 100 * done / total + '%'; pbw.classList.toggle('hot', run >= 3);
    hr.replaceChildren(jump ? ic('target', 22) : ic(hearts > 0 ? 'heart' : 'heartBroken', 24), jump ? `${done}/${total}` : String(hearts)); runLbl.textContent = run >= 2 ? `${run} BERTURUT-TURUT` : '';
  };
  mount(el('div', { class: 'wrap sess' }, runLbl,
    el('div', { class: 'sh' }, el('button', { class: 'ico', 'aria-label': 'Keluar', onclick: () => confirm('Keluar? Progres sesi ini tidak disimpan.') && pathView(juz) }, ic('close', 24)), pbw, hr),
    body, foot));

  const fbRow = (q, c) => {   // "terlalu mudah" / "terlalu sulit" / "laporkan" -> tersimpan untuk laporan admin (soal & jawaban diambil server dari sesi ini)
    const row = el('div', { class: 'fbx' });
    const send = (k, extra = {}) => api('feedback', { attempt, qi: q.i, kind: k, ...extra });
    const mkBtn = (k, icon, txt) => { const b = el('button', { class: 'fbb', onclick: async () => { b.disabled = true; try { await send(k); b.replaceChildren(ic('check', 14), 'Terkirim'); } catch (e) { b.disabled = false; toast(e.message); } } }, ic(icon, 16), txt); return b; };
    const rep = el('button', { class: 'fbb', onclick: () => {
      const sel = el('select', {}, ['Soal atau kunci jawaban keliru', 'Teks Arab keliru', 'Soal membingungkan', 'Lainnya'].map(t => el('option', {}, t)));
      const note = el('input', { placeholder: 'Catatan (opsional)', maxlength: 300 });
      const go = el('button', { class: 'fbb send', onclick: async () => { go.disabled = true; try { await send('report', { reason: sel.value, note: note.value }); row.replaceChildren(el('span', { class: 'fbok' }, ic('check', 14), 'Terima kasih, laporan terkirim')); } catch (e) { go.disabled = false; toast(e.message); } } }, 'Kirim');
      row.replaceChildren(sel, note, go); note.focus();
    } }, ic('flag', 16), 'Laporkan');
    row.append(mkBtn('easy', 'zzz', 'Terlalu mudah'), mkBtn('hard', 'mountain', 'Terlalu sulit'), rep);
    return row;
  };
  const next = () => { upd(); queue.length ? ask(queue[0]) : finish(); };
  const judge = (q, res, label) => {   // res = hasil pemeriksaan dari server
    q.full = res.full ?? q.full; hearts = res.hearts; const c = res.ok;
    if (c) { done++; if (!q.miss) run++; else run = 0; queue.shift(); } else { q.miss = true; run = 0; queue.push(queue.shift()); }
    upd(); body.querySelectorAll('button').forEach(b => b.disabled = true);
    const go = () => hearts <= 0 ? lose() : next();
    foot.replaceChildren(el('div', { class: 'fb ' + (c ? 'good' : 'bad') }, el('b', {}, label || (c ? 'Benar!' : 'Kurang tepat')),
      el('div', { class: 'ayat sm', dir: 'rtl' }, q.full), fbRow(q, c),
      el('button', { class: 'btn ' + (c ? '' : 'red'), onclick: go }, 'Lanjut')));
    onEnter(go);
  };

  function ask(q) {
    let ua = () => null;
    const btn = el('button', { class: 'btn', onclick: async () => {
      if (busy) return; busy = true; btn.disabled = true;
      try { judge(q, await api('check', { attempt, qi: q.i, answer: ua() })); } catch (e) { btn.disabled = false; toast(e.message); } finally { busy = false; }
    } }, 'Periksa'); btn.disabled = true;
    const title = el('p', { class: 'qt' }, q.title);
    if (q.t === 'choice') {
      let sel = null; ua = () => sel;
      const show = q.tokens ? el('div', { class: 'ayat', dir: 'rtl' }, q.tokens.map((w, i) => [el('span', { class: i === q.mark ? 'mk' : '' }, w), ' ']))
        : q.show ? el('div', { class: 'ayat', dir: 'rtl' }, q.show) : '';
      const os = q.opts.map((o, i) => el('button', { class: 'opt ' + q.mode, dir: q.mode === 'ar' ? 'rtl' : null,
        onclick: () => { sel = o; os.forEach(x => x.classList.remove('sel')); os[i].classList.add('sel'); btn.disabled = false; } },
        el('span', { class: 'kn' }, i + 1), q.mode === 'har' ? '◌' + o : o));
      body.replaceChildren(title, show, el('div', { class: 'opts ' + q.mode }, os),
        el('p', { class: 'hint' }, `Tekan 1–${os.length} atau tombol panah untuk memilih · Enter untuk memeriksa`));
      const cols = q.mode === 'ar' ? 1 : 2;
      keys = e => {
        const k = e.key;
        if (k === 'Enter') { e.preventDefault(); if (!e.repeat && !btn.disabled) btn.click(); }
        else if (/^[1-9]$/.test(k) && +k <= os.length) { e.preventDefault(); os[k - 1].click(); }
        else if (k.startsWith('Arrow')) {
          e.preventDefault();
          const c = os.findIndex(x => x.classList.contains('sel')), n = os.length;
          const dl = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: cols, ArrowUp: -cols }[k];
          os[c < 0 ? 0 : (c + dl + n) % n].click();
        }
      };
    } else if (q.t === 'match') {
      // Kolom KIRI = akhir ayat / nomor ayat (tombol 1-5), kolom KANAN = awal ayat (tombol Q W E R T). Tiap pasangan diperiksa server.
      const rtl = q.rmode === 'ar';
      let sel = null, remaining = q.left.length, locked = false;
      const items = { L: [], R: [] };   // L = awal ayat (q.left), R = akhir/nomor (q.right)
      const mk = (s, list) => list.map((it, i) => {
        const ar = s === 'L' || rtl;
        const b = el('button', { class: 'mi ' + (ar ? 'ar' : 'num'), dir: ar ? 'rtl' : null, onclick: () => pick(s, i) },
          el('span', { class: 'kn' }, s === 'R' ? i + 1 : 'QWERT'[i]), it.text);
        items[s].push(b); return b;
      });
      const pick = async (s, i) => {
        const b = items[s][i]; if (locked || b.classList.contains('ok')) return;
        if (!sel || sel.s === s) { sel && items[sel.s][sel.i].classList.remove('sel'); sel = { s, i }; b.classList.add('sel'); return; }
        const [li, ri] = s === 'R' ? [sel.i, i] : [i, sel.i]; items[sel.s][sel.i].classList.remove('sel'); sel = null; locked = true;
        let r; try { r = await api('check', { attempt, qi: q.i, l: li, r: ri }); } catch (e) { locked = false; return toast(e.message); }
        if (r.ok) {
          items.L[li].classList.add('ok'); items.R[ri].classList.add('ok'); locked = false;
          if (r.done) { locked = true; q.miss = r.mistakes > 0; judge(q, r, r.mistakes ? `Selesai · ${r.mistakes} kesalahan` : 'Sempurna!'); }
        } else {
          hearts = r.hearts; run = 0; upd();
          [items.L[li], items.R[ri]].forEach(x => { x.classList.add('bad'); setTimeout(() => x.classList.remove('bad'), 450); });
          if (hearts <= 0) setTimeout(lose, 600); else locked = false;
        }
      };
      const A = mk('R', q.right), B = mk('L', q.left);                       // A = kiri, B = kanan
      body.replaceChildren(title, el('div', { class: 'match' }, A.flatMap((a, i) => [a, B[i]])),
        el('p', { class: 'hint' }, 'Ketuk satu di tiap kolom · atau tekan 1–5 (kiri) dan Q W E R T (kanan)'));
      btn.textContent = 'Cocokkan semua pasangan'; btn.disabled = true;
      keys = e => {
        const k = e.key.toLowerCase(), ri = /^[1-9]$/.test(k) ? +k - 1 : -1, li = k.length === 1 ? 'qwert'.indexOf(k) : -1;
        if (ri >= 0 && ri < items.R.length) { e.preventDefault(); pick('R', ri); }
        else if (li >= 0 && li < items.L.length) { e.preventDefault(); pick('L', li); }
      };
    } else {
      const sl = q.blanks.map(() => null), area = el('div', { class: 'ayat', dir: 'rtl' }), pool = el('div', { class: 'tiles', dir: 'rtl' });
      ua = () => sl.map(t => q.opts[t]);
      const live = () => btn.isConnected;
      const place = k => { const t = sl.indexOf(null); if (!live() || sl.includes(k) || t < 0) return; sl[t] = k; paint(); };
      // Seret-lepas (mouse & sentuh). Jika tidak bergeser >6px dianggap ketukan biasa.
      const drag = (e, word, onTap, onDrop) => {
        if (!live() || e.button > 0) return; e.preventDefault();
        const x0 = e.clientX, y0 = e.clientY; let ghost = null;
        const hit = ev => document.elementFromPoint(ev.clientX, ev.clientY)?.closest('[data-b]');
        const mv = ev => {
          if (!ghost) { if (Math.hypot(ev.clientX - x0, ev.clientY - y0) < 6) return; ghost = el('div', { class: 'tile ghost' }, word); document.body.append(ghost); }
          ghost.style.left = ev.clientX + 'px'; ghost.style.top = ev.clientY + 'px';
          area.querySelectorAll('.hot').forEach(x => x.classList.remove('hot')); hit(ev)?.classList.add('hot');
        };
        const up = ev => {
          removeEventListener('pointermove', mv); removeEventListener('pointerup', up); removeEventListener('pointercancel', up);
          const moved = !!ghost, t = moved ? hit(ev) : null; ghost?.remove();
          moved ? onDrop(t ? +t.dataset.b : null) : onTap();
          paint();
        };
        addEventListener('pointermove', mv); addEventListener('pointerup', up); addEventListener('pointercancel', up);
      };
      const paint = () => {
        area.replaceChildren(...q.tokens.flatMap((w, i) => {
          const b = q.blanks.indexOf(i); if (b < 0) return [w + ' '];
          const t = sl[b];
          return [el('button', { class: 'slot' + (t == null ? '' : ' f'), 'data-b': b,
            onpointerdown: t == null ? null : e => drag(e, q.opts[t], () => { sl[b] = null; },
              to => { if (to == null) sl[b] = null; else if (to !== b) [sl[b], sl[to]] = [sl[to], sl[b]]; }) }, t == null ? '＿＿＿' : q.opts[t]), ' '];
        }));
        pool.replaceChildren(...q.opts.map((w, k) => el('button', { class: 'tile' + (sl.includes(k) ? ' used' : ''),
          onpointerdown: e => drag(e, w, () => place(k), to => { if (to != null) sl[to] = k; }) }, el('span', { class: 'kn' }, k + 1), w)));
        btn.disabled = sl.includes(null);
      };
      body.replaceChildren(title, area, pool,
        el('p', { class: 'hint' }, 'Seret kata ke kotak, atau tekan angka untuk mengisi · Backspace menghapus · Enter memeriksa'));
      paint();
      keys = e => {
        const k = e.key;
        if (k === 'Enter') { e.preventDefault(); if (!e.repeat && !btn.disabled) btn.click(); }
        else if (/^[1-9]$/.test(k) && +k <= q.opts.length) { e.preventDefault(); place(+k - 1); }
        else if (k === 'Backspace') {
          e.preventDefault();
          const last = sl.reduce((m, v, i) => v == null ? m : i, -1);
          if (last >= 0) { sl[last] = null; paint(); }
        }
      };
    }
    foot.replaceChildren(btn);
  }

  const retry = () => session(unit, level, lesson, juz, kind, opts);
  const lose = () => {
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, ic(jump ? 'target' : 'heartBroken', 84)), el('h2', {}, jump ? 'Tes lompat belum berhasil' : 'Nyawa habis'),
      el('p', { class: 'mut' }, jump ? 'Semua 25 soal harus dijawab benar. Satu kesalahan menggagalkan tes.' : 'Tenang, ulangi lagi — muroja’ah itu proses.'),
      el('button', { class: 'btn', onclick: retry }, 'Coba lagi'), el('p'), el('button', { class: 'btn alt', onclick: () => pathView(juz) }, 'Kembali')));
    onEnter(retry);
  };
  async function finish() {
    let r; try { r = await api('finish', { attempt }); } catch (e) { toast(e.message); return pathView(juz); }   // skor, XP, dan waktu semuanya dari server
    me.xp = r.xp; me.streak = r.streak;
    const head = jump ? 'Lompat berhasil!' : !r.passed ? 'Belum lulus' : r.unitDone ? `${kind} selesai!` : r.levelDone ? `Level ${r.level} selesai!` : `Lesson ${r.lesson} dari ${r.lessons} selesai!`;
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, ic(!r.passed ? 'sad' : jump ? 'rocket' : r.unitDone ? 'trophy' : r.levelDone ? 'medal' : 'party', 84)),
      el('h2', {}, head),
      el('p', { class: 'mut' }, !r.passed ? 'Ujian akhir butuh skor minimal 80%.' : jump ? `${r.skipped} thumn dilewati dan ditandai selesai.` : stars(r.stars, 20)),
      el('div', { class: 'stat' },
        el('div', { class: 'sc xp' }, el('small', {}, 'TOTAL XP'), el('b', {}, ic('bolt', 22), r.gain)),
        el('div', { class: 'sc time' }, el('small', {}, 'WAKTU'), el('b', {}, ic('timer', 22), fmtTime(r.secs))),
        el('div', { class: 'sc acc' }, el('small', {}, 'AKURASI'), el('b', {}, ic('target', 22), r.pct + '%'))),
      el('p', { class: 'mut inl' }, r.bonus ? `Termasuk bonus ${r.bonus} XP · ` : '', ic('flame', 18), `Streak ${r.streak} hari`),
      el('button', { class: 'btn', onclick: () => pathView(juz) }, 'Lanjut'), el('p'), r.passed ? '' : el('button', { class: 'btn alt', onclick: retry }, 'Ulangi')));
    onEnter(r.passed ? () => pathView(juz) : retry);
  }
  next();
}

(async () => { try { me = (await api('me')).user; juzView(); } catch (e) { authView(); } })();