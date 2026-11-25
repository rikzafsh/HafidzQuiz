'use strict';
// Modul course Tahfidz (hafalan Al-Qur'an): daftar juz, peta belajar (thumn/level/chest/lompat), dan Mushaf. Sesi kuis memakai quiz.js.
async function juzView() {
  redraw = () => juzView();
  const d = await api('juz'); me = d.user;
  shell('learn', topbar(), el('div', { class: 'wrap' }, el('h2', {}, 'Belajar'), el('p', { class: 'mut' }, 'Pilih juz. Boleh mulai dari juz mana saja.'),
    el('div', { class: 'grid' }, d.juz.map(j => el('button', { class: 'juz', onclick: () => pathView(j.juz) },
      el('b', {}, 'Juz ' + j.juz), el('small', {}, `${j.done}/${j.total} thumn`), el('i', {}, el('u', { style: `width:${100 * j.done / j.total}%` })))))));
}

// ---------- Mushaf: ayat disusun seperti halaman mushaf. Tiap halaman tampil penuh dalam satu layar (huruf menyesuaikan), bisa 1 atau 2 halaman ----------
const arNum = n => String(n).replace(/\d/g, d => '٠١٢٣٤٥٦٧٨٩'[d]);
const FR = ['', '1/8', '1/4', '3/8', '1/2', '5/8', '3/4', '7/8'];
const rubLabel = tm => tm.thumn === 1 ? ['Awal', `Hizb ${tm.hizb}`] : [FR[tm.thumn - 1], `Hizb ${tm.hizb}`];   // thumn ke-t dimulai pada (t-1)/8 hizb
let mushafMeta = null;
async function mushafView(page, target = null) {
  page = page || +store.get('hq_mushaf') || 1;
  redraw = () => mushafView(page);
  mushafMeta ??= await api('mushaf_meta');
  const max = mushafMeta.maxPage, twoOK = () => innerWidth >= 760;                     // dua halaman hanya muat di layar lebar
  const wantTwo = store.get('hq_mushaf_two') === '1' && twoOK();
  const first = wantTwo ? (page % 2 ? page : page - 1) : page;                          // mushaf terbuka: halaman ganjil di KANAN, genap di KIRI
  const nums = wantTwo ? [first, first + 1] : [first], step = nums.length;
  const res = await Promise.all(nums.map(n => n > max ? null : api('mushaf', null, '&page=' + n).catch(e => ({ _err: e }))));
  if (!res[0] || res[0]._err) { if (page !== 1) { toast(res[0]?._err?.message || 'Halaman belum tersedia.'); return mushafView(1); } throw res[0]?._err ?? new Error('Data mushaf kosong.'); }
  store.set('hq_mushaf', first); const d = res[0];

  const tmData = tm => JSON.stringify({ hizb: tm.hizb, thumn: tm.thumn });
  const secNodes = sec => {   // spanduk surah bersifat blok: bagian dipecah di sekitarnya dengan warna yang sama
    const out = []; let cur = el('span', { class: 'sec s' + sec.n }); out.push(cur);
    sec.items.forEach(it => {
      if (it.t === 'surah') {
        out.push(el('div', { class: 'sb' }, el('b', {}, 'سورة ' + it.arabic), it.basmalah ? el('div', { class: 'bsm' }, 'بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ') : ''));
        cur = el('span', { class: 'sec s' + sec.n }); out.push(cur); return;
      }
      cur.append(el('span', { class: 'ay', 'data-s': it.surah, 'data-a': it.ayah },
        it.tm ? (it.mark ? el('span', { class: 'tm', 'data-tm': tmData(it.tm), title: `Awal Hizb ${it.tm.hizb} · Thumn ${it.tm.thumn}` }, '۞ ') : el('span', { class: 'ta', 'data-tm': tmData(it.tm) })) : '',
        it.text, ' ', it.end ? el('span', { class: 'an' }, arNum(it.ayah)) : '', ' '));
    });
    return out.filter(n => n.tagName !== 'SPAN' || n.childNodes.length);
  };
  const card = (pd, n) => {
    if (!pd || pd._err) return el('article', { class: 'mpage empty ' + (n % 2 ? 'odd' : 'even') }, el('p', {}, `Halaman ${n} belum tersedia.`), el('small', {}, `Data mushaf baru sampai halaman ${max}.`));
    return el('article', { class: 'mpage ' + (n % 2 ? 'odd' : 'even'), 'aria-label': 'Halaman ' + n },
      el('header', { class: 'mph', dir: 'rtl' }, el('span', {}, pd.juz ? 'الجزء ' + arNum(pd.juz) : ''), el('span', {}, 'سورة ' + pd.surah.arabic)),
      el('div', { class: 'mbody' }, el('div', { class: 'mtext' }, el('div', { class: 'mflow', dir: 'rtl', lang: 'ar' }, pd.sections.flatMap(secNodes)))),
      el('footer', { class: 'mpf' }, el('b', {}, arNum(n)), el('small', {}, [pd.juz && `Juz ${pd.juz}`, pd.hizb && `Hizb ${pd.hizb}`, pd.thumn && `Thumn ${pd.thumn}`, pd.surah.latin].filter(Boolean).join(' · '))));
  };
  const book = el('div', { class: 'mbook' + (wantTwo ? ' two' : ''), dir: 'rtl' }, nums.map((n, i) => card(res[i], n)));

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
    const submit = async () => { try { const r = await api('mushaf_find', null, cur.q()); close(); mushafView(r.page, r.target); } catch (e) { msg.textContent = e.message; } };
    const ov = el('div', { class: 'mnv-ov', onclick: close }, el('div', { class: 'mnv', role: 'dialog', 'aria-label': 'Navigasi mushaf', onclick: e => e.stopPropagation() },
      el('h3', {}, 'Pergi ke…'), tabs, body, msg,
      el('div', { class: 'mact' }, el('button', { class: 'btn alt', onclick: close }, 'Batal'), el('button', { class: 'btn', onclick: submit }, 'Pergi'))));
    document.body.append(ov); draw();
    keys = e => { if (e.key === 'Escape') close(); };
    ov.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.target.matches('button')) { e.preventDefault(); submit(); } else if (e.key === 'Escape') close(); });
  };

  const go = (p, t = null) => mushafView(p, t);
  const pin = el('input', { type: 'number', min: 1, max, value: d.page, 'aria-label': 'Nomor halaman', inputmode: 'numeric' });
  let jumped = false;   // Enter dan 'change' bisa terpicu bersamaan: proses satu kali saja
  const jumpTo = () => { if (jumped) return; jumped = true; go(Math.min(max, Math.max(1, +pin.value || 1))); };
  pin.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); jumpTo(); } });
  pin.addEventListener('change', jumpTo);
  const nextB = el('button', { class: 'ibtn', 'aria-label': 'Halaman berikutnya', title: 'Berikutnya (←)', disabled: first + step > max ? '' : null, onclick: () => go(first + step) }, ic('arrowLeft', 22));
  const prevB = el('button', { class: 'ibtn flip', 'aria-label': 'Halaman sebelumnya', title: 'Sebelumnya (→)', disabled: first - step < 1 ? '' : null, onclick: () => go(first - step) }, ic('arrowLeft', 22));
  const setMode = two => () => { store.set('hq_mushaf_two', two ? '1' : '0'); go(first); };
  const seg = twoOK() ? el('div', { class: 'seg', role: 'group', 'aria-label': 'Jumlah halaman' },
    el('button', { class: !wantTwo ? 'on' : '', 'aria-pressed': String(!wantTwo), title: 'Satu halaman', onclick: setMode(false) }, ic('page1', 22)),
    el('button', { class: wantTwo ? 'on' : '', 'aria-pressed': String(wantTwo), title: 'Dua halaman', onclick: setMode(true) }, ic('page2', 22))) : '';
  shell('mushaf', topbar(), el('div', { class: 'wrap mwrap' },
    el('div', { class: 'mtool' }, nextB, el('div', { class: 'pg' }, pin, el('span', { class: 'mut', title: `Data mushaf baru tersedia sampai halaman ${max}.` }, '/ ' + max)), prevB, seg,
      el('button', { class: 'btn navb', 'aria-label': 'Navigasi', onclick: openNav }, ic('search', 20), el('span', { class: 'lbl' }, 'Navigasi'))),
    book));
  keys = e => { if (e.key === 'ArrowLeft' && first + step <= max) go(first + step); else if (e.key === 'ArrowRight' && first - step >= 1) go(first - step); };   // arah baca mushaf: kanan ke kiri
  document.body.classList.add('mfit');

  // ---- tata letak: setiap halaman penuh dalam satu layar; ukuran huruf dicari agar seluruh teks pas ----
  const pagesEls = [...book.querySelectorAll('.mpage')];
  const fitText = pg => {   // cari huruf terbesar yang membuat seluruh teks muat; bila perlu rapatkan jarak baris, dan terakhir izinkan huruf < 11px
    const box = pg.querySelector('.mtext'), flow = pg.querySelector('.mflow'); if (!box) return;
    const hi = Math.min(46, pg.clientWidth * .09 + 8);
    const attempt = (lh, minFs) => {
      flow.style.lineHeight = lh; flow.style.fontSize = minFs + 'px'; if (flow.offsetHeight > box.clientHeight) return false;
      let lo = minFs, h = hi; for (let i = 0; i < 11; i++) { const m = (lo + h) / 2; flow.style.fontSize = m + 'px'; if (flow.offsetHeight <= box.clientHeight) lo = m; else h = m; }
      flow.style.fontSize = lo + 'px'; return true;
    };
    if (!(attempt(2.15, 11) || attempt(2, 11) || attempt(1.85, 11))) attempt(1.8, 7);
    box.classList.toggle('ov', flow.offsetHeight > box.clientHeight); pg.dataset.fs = parseFloat(flow.style.fontSize).toFixed(1);
  };
  const placeNotes = pg => {   // keterangan pembagian di margin luar, sejajar dengan baris tanda ۞
    pg.querySelectorAll('.mn').forEach(n => n.remove()); const body = pg.querySelector('.mbody'); if (!body) return;
    const br = body.getBoundingClientRect(); let lastBottom = -1e9;
    pg.querySelectorAll('[data-tm]').forEach(m => {
      const tm = JSON.parse(m.dataset.tm), [a, b2] = rubLabel(tm), r = m.getBoundingClientRect();
      const n = el('div', { class: 'mn', title: `${a} ${b2}`, 'data-tm': m.dataset.tm, 'aria-label': `${a} ${b2}` }, el('b', {}, a), el('span', {}, b2));
      body.append(n); let y = r.top + r.height / 2 - br.top - n.offsetHeight / 2; y = Math.max(y, lastBottom + 3); n.style.top = y + 'px'; lastBottom = y + n.offsetHeight;
    });
  };
  const layout = () => {
    const top = book.getBoundingClientRect().top, sb = document.querySelector('.side').getBoundingClientRect();
    const barH = sb.top > innerHeight / 2 ? innerHeight - sb.top : 0;                        // tab bawah (HP) memakan ruang
    const avail = Math.max(240, innerHeight - top - barH - 10), cw = book.clientWidth, gap = 14, n = pagesEls.length;
    const pw = Math.min((cw - gap * (n - 1)) / n, avail * .8);
    book.style.height = avail + 'px';
    pagesEls.forEach(p => { p.style.width = pw + 'px'; p.style.height = avail + 'px'; p.style.setProperty('--mw', Math.max(38, Math.min(58, pw * .13)) + 'px'); });
    pagesEls.forEach(p => { fitText(p); placeNotes(p); });
  };
  let tmr; const onResize = () => { clearTimeout(tmr); tmr = setTimeout(() => { (store.get('hq_mushaf_two') === '1' && twoOK()) !== wantTwo ? go(first) : layout(); }, 120); };
  addEventListener('resize', onResize); leaveScreen = () => { removeEventListener('resize', onResize); clearTimeout(tmr); document.body.classList.remove('mfit'); };
  layout(); requestAnimationFrame(layout); document.fonts?.ready.then(layout);
  if (target) { const n = book.querySelector(`.ay[data-s="${target.surah}"][data-a="${target.ayah}"]`); if (n) n.classList.add('hl'); }
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

// ---------- Pendaftaran modul course ----------
Courses.tahfidz = {
  nav: [['learn', 'Belajar', 'home', () => juzView()], ['mushaf', 'Mushaf', 'mushaf', () => mushafView()]],
  home: () => juzView(),
  statCards: (s, card) => [card('check', `${s.thumnDone ?? 0}/${s.thumnTotal ?? 480}`, 'Thumn selesai' + (s.thumnSkipped ? ` (${s.thumnSkipped} dilewati)` : ''))],
  achievements: s => [{ icon: 'book', title: 'Penghafal', steps: [1, 4, 8, 16, 48, 160, 480], text: n => `Menyelesaikan ${n} thumn`, val: () => s.thumnDone ?? 0 }],
  summary: s => `${s.thumnDone ?? 0}/${s.thumnTotal ?? 480} thumn selesai`,
  profileActions: () => [el('a', { class: 'btn alt', href: 'index.php' }, ic('book', 22), 'Muroja’ah bebas')],
};
