'use strict';
// Kerangka aplikasi: menu (sidebar/tab bawah), bilah atas dengan pemilih kursus, katalog kursus, layar masuk, dan profil.
const logout = async () => { await api('logout', {}); me = null; authView(); };
// Menu utama = menu kursus aktif (mis. Belajar, Mushaf) + Profil. PC: sidebar berlabel | tablet: sidebar ikon | HP: tab bawah.
const courseNav = () => [...(Courses[me.course]?.nav ?? []), ['profile', 'Profil', 'user', () => profileView()]];
const nav = active => el('nav', { class: 'side', 'aria-label': 'Menu utama' }, el('b', { class: 'brand' }, 'HafidzQuiz'),
  courseNav().map(([id, label, icon, go]) => el('button', { class: 'nv' + (id === active ? ' on' : ''), 'aria-label': label, 'aria-current': id === active ? 'page' : null, onclick: go }, ic(icon, 30), el('span', { class: 'lb' }, label))));
const shell = (active, ...kids) => mount(el('div', { class: 'shell' }, nav(active), el('main', { class: 'main' }, ...kids)));
const topbar = back => el('header', { class: 'top' },
  back ? el('button', { class: 'ico', onclick: back, 'aria-label': 'Kembali' }, ic('arrowLeft', 22)) : el('b', { class: 'brand mob' }, 'HafidzQuiz'),
  el('span', { class: 'sp' }),
  me.dev ? el('button', { class: 'chip dev' + (devOn ? ' on' : ''), title: 'Mode developer sementara: buka level terkunci untuk pengetesan',
    onclick: () => { devOn = !devOn; store.set('hq_dev', devOn ? '1' : '0'); redraw?.(); } }, ic('cog', 16), devOn ? 'DEV ON' : 'DEV') : '',
  courseChip(),
  el('span', { class: 'chip', title: 'Streak harian' }, ic('flame', 18), me.streak),
  el('span', { class: 'chip', title: 'Dirham' }, ic('coin', 18), me.dirham));

function authView(mode = 'login') {
  const u = el('input', { placeholder: 'Nama pengguna', autocomplete: 'username', maxlength: 30 });
  const p = el('input', { type: 'password', placeholder: 'Kata sandi', autocomplete: mode === 'login' ? 'current-password' : 'new-password' });
  const err = el('p', { class: 'err' });
  const go = async () => { try { me = (await api(mode, { username: u.value, password: p.value })).user; homeView(); } catch (e) { err.textContent = e.message; } };
  u.addEventListener('keydown', e => e.key === 'Enter' && p.focus());
  p.addEventListener('keydown', e => e.key === 'Enter' && go());
  mount(el('div', { class: 'wrap auth' }, el('h1', {}, 'HafidzQuiz'), el('p', { class: 'mut' }, 'Hafalan Al-Qur’an, selangkah demi selangkah.'), u, p, err,
    el('button', { class: 'btn', onclick: go }, mode === 'login' ? 'Masuk' : 'Daftar'),
    el('button', { class: 'link', onclick: () => authView(mode === 'login' ? 'register' : 'login') }, mode === 'login' ? 'Belum punya akun? Daftar' : 'Sudah punya akun? Masuk')));
  u.focus();
}

// ---------- Kursus: pemilih (dropdown ala Duolingo) dan katalog "Tambah kursus baru" ----------
const courseOf = id => (me?.courses || []).find(c => c.id === id);
const closeDd = () => { ddClose?.(); ddClose = null; };
const homeView = () => { const m = Courses[me.course]; if (m) return m.home(); mount(el('div', { class: 'wrap' }, el('p', { class: 'err' }, 'Modul kursus tidak ditemukan.'))); };
const switchCourse = async id => { closeDd(); if (id === me.course) return; me = (await api('course_switch', { course: id })).user; homeView(); };
const courseChip = () => {
  const c = courseOf(me.course) || { icon: 'book', xp: 0, name: 'Kursus' };
  const b = el('button', { class: 'chip course', 'aria-haspopup': 'menu', 'aria-expanded': 'false', title: `Kursus: ${c.name} (XP kursus)`, onclick: e => { e.stopPropagation(); ddClose ? closeDd() : openDd(b); } },
    ic(c.icon, 22), String(c.xp ?? 0), ic('caret', 14));
  return b;
};
const ddLeft = r => {   // rata kanan dengan chip, tetapi selalu di dalam layar (lebar dropdown = min(300, layar - 16), sama dengan CSS)
  const w = Math.min(300, innerWidth - 16); return Math.round(Math.min(Math.max(8, r.right - w), innerWidth - w - 8));
};
const openDd = anchor => {
  closeDd(); const r = anchor.getBoundingClientRect();
  const dd = el('div', { class: 'cdd', role: 'menu', style: `top:${r.bottom + 8}px;left:${ddLeft(r)}px`, onclick: e => e.stopPropagation() },
    el('div', { class: 'cdh' }, 'KURSUS SAYA'),
    el('div', { class: 'cdl' }, me.courses.map(c => el('button', { class: 'cdi' + (c.id === me.course ? ' on' : ''), role: 'menuitem', onclick: () => switchCourse(c.id) },
      ic(c.icon, 32), el('span', {}, c.name), c.id === me.course ? ic('check', 18) : ''))),
    el('button', { class: 'cda', onclick: () => { closeDd(); openCatalog(); } }, ic('plus', 26), 'Tambah kursus baru'));
  const off = e => { if (e.type === 'keydown' && e.key !== 'Escape') return; closeDd(); };
  document.body.append(dd); anchor.setAttribute('aria-expanded', 'true');
  document.addEventListener('click', off); document.addEventListener('keydown', off);   // klik pembuka sudah dihentikan (stopPropagation) di tombolnya, jadi aman didaftarkan langsung
  ddClose = () => { dd.remove(); anchor.setAttribute('aria-expanded', 'false'); document.removeEventListener('click', off); document.removeEventListener('keydown', off); };
};
async function openCatalog() {
  const r = await api('courses'), prevKeys = keys;
  const close = () => { ov.remove(); keys = prevKeys; };
  const enroll = async c => { try { me = (await api('course_enroll', { course: c.id })).user; close(); toast(`Kamu bergabung di kursus ${c.name}`); homeView(); } catch (e) { toast(e.message); } };
  const card = c => el('div', { class: 'ccard' }, ic(c.icon, 48),
    el('div', { class: 'cbody' }, el('b', {}, c.name), el('small', { class: 'mut' }, c.subtitle), el('p', {}, c.description), c.status === 'draft' ? el('span', { class: 'cbadge' }, 'Segera hadir') : ''),
    el('button', { class: 'btn', onclick: () => enroll(c) }, 'Mulai belajar'));
  const ov = el('div', { class: 'mnv-ov', onclick: close }, el('div', { class: 'mnv cat', role: 'dialog', 'aria-label': 'Tambah kursus baru', onclick: e => e.stopPropagation() },
    el('h3', {}, 'Tambah kursus baru'),
    r.available.length ? el('div', { class: 'clist' }, r.available.map(card)) : el('p', { class: 'mut' }, 'Kamu sudah mengikuti semua kursus yang tersedia.'),
    el('div', { class: 'mact' }, el('button', { class: 'btn alt', onclick: close }, 'Tutup'))));
  document.body.append(ov); keys = e => { if (e.key === 'Escape') close(); };
}

// ---------- Profil ----------
const ACH = [   // pencapaian lintas kursus; pencapaian khusus kursus disediakan modul kursusnya (Courses.<id>.achievements)
  { icon: 'flame', title: 'Api Semangat', steps: [3, 7, 14, 30, 100, 365], text: n => `Mencapai streak ${n} hari`, val: s => s.streakPeak },
  { icon: 'bolt', title: 'Penuntut Ilmu', steps: [100, 500, 1000, 5000, 10000, 30000], text: n => `Mengumpulkan ${n} XP`, val: s => s.xp },
  { icon: 'coin', title: 'Pengumpul Dirham', steps: [20, 100, 300, 1000], text: n => `Mengumpulkan ${n} dirham`, val: s => s.dirhamTotal },
];
async function profileView() {
  redraw = () => profileView();
  const d = await api('profile'); me = d.user; const s = { xp: me.xp, streakPeak: me.streakPeak, dirhamTotal: me.dirhamTotal };
  const joined = d.joined ? new Date(d.joined.replace(' ', 'T')).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : null;
  const card = (icon, val, lbl, cls = '', sub = '') => el('div', { class: 'sc2 ' + cls }, ic(icon, 34), el('div', {}, el('b', {}, val), el('small', {}, lbl), sub ? el('small', { class: 'sub' }, sub) : ''));
  const badge = a => {
    const v = a.val(s), lv = a.steps.filter(n => v >= n).length, next = a.steps[lv], pct = next ? Math.min(100, 100 * v / next) : 100;
    return el('div', { class: 'ach' }, el('div', { class: 'bdg' }, ic('shield', 64), el('span', { class: 'bi' }, ic(a.icon, 26)), el('small', {}, lv ? 'LEVEL ' + lv : 'BELUM')),
      el('div', { class: 'at' }, el('b', {}, a.title), el('span', {}, a.text(next ?? a.steps.at(-1))),
        el('div', { class: 'ap' }, el('u', { style: `width:${pct}%` }), el('i', {}, next ? `${v}/${next}` : 'Level tertinggi'))));
  };
  const mods = d.courses.map(c => [c, Courses[c.id], c.stats && !Array.isArray(c.stats) ? c.stats : {}]);
  const cards = [card('flame', me.streak, 'Streak (hari)', 'gold', `Terpanjang: ${me.streakPeak}`), card('bolt', me.xp, 'Total XP'), card('coin', me.dirham, 'Dirham', '', me.dirhamTotal > me.dirham ? `Total didapat: ${me.dirhamTotal}` : ''),
    ...mods.flatMap(([c, m, st]) => m?.statCards?.(st, card) ?? [])];
  const achs = [...ACH, ...mods.flatMap(([c, m, st]) => m?.achievements?.(st) ?? [])];
  const actions = [...(Courses[me.course]?.profileActions?.() ?? []), me.admin ? el('a', { class: 'btn alt', href: 'admin.php' }, ic('chart', 22), 'Laporan masukan (admin)') : '',
    el('button', { class: 'btn alt', onclick: logout }, ic('logout', 22), 'Keluar')];
  shell('profile', topbar(), el('div', { class: 'wrap' },
    el('div', { class: 'pbanner' }, ic('avatar', 150)),
    el('h2', { class: 'pname' }, me.name), el('p', { class: 'mut' }, joined ? 'Bergabung ' + joined : 'Pengguna HafidzQuiz'),
    el('h3', {}, 'Statistik'), el('div', { class: 'stats' }, cards),
    el('h3', {}, 'Kursus saya'),
    el('div', { class: 'mycourses' }, mods.map(([c, m, st]) => el('div', { class: 'mc' + (c.id === me.course ? ' on' : '') }, ic(c.icon, 36),
      el('div', {}, el('b', {}, c.name), el('small', {}, m?.summary?.(st) ?? (c.status === 'draft' ? 'Segera hadir' : c.subtitle))), el('span', { class: 'mxp' }, ic('bolt', 16), String(c.xp ?? 0))))),
    el('h3', {}, 'Pencapaian'), el('div', { class: 'achs' }, achs.map(badge)),
    el('div', { class: 'pact' }, actions)));
}

