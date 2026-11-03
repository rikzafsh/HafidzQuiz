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
const toast = m => { const t = el('div', { class: 'toast' }, m); document.body.append(t); setTimeout(() => t.remove(), 2200); };
let keys = null; // penangan keyboard milik layar yang sedang tampil
const onEnter = fn => { keys = e => { if (e.key === 'Enter') { e.preventDefault(); if (!e.repeat) fn(); } }; };
addEventListener('keydown', e => { if (keys && !e.ctrlKey && !e.metaKey && !e.altKey && !e.target.matches('input,textarea')) keys(e); });
const mount = (...n) => { keys = null; app.replaceChildren(...n); scrollTo(0, 0); };
let me = null;
addEventListener('unhandledrejection', e => e.reason?.status === 401 ? authView() : toast(e.reason?.message || 'Terjadi kesalahan'));

const logout = async () => { await api('logout', {}); me = null; authView(); };
const store = { get: k => { try { return localStorage.getItem(k); } catch { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch {} } };
let devOn = store.get('hq_dev') === '1', redraw = null;   // devOn: mode developer sementara (hanya efektif bila server DEV_MODE = true)
const bar = back => el('header', { class: 'top' },
  back ? el('button', { class: 'ico', onclick: back, 'aria-label': 'Kembali' }, '←') : el('b', { class: 'brand' }, 'HafidzQuiz'),
  el('span', { class: 'sp' }),
  me.dev ? el('button', { class: 'chip dev' + (devOn ? ' on' : ''), title: 'Mode developer sementara: buka level terkunci untuk pengetesan',
    onclick: () => { devOn = !devOn; store.set('hq_dev', devOn ? '1' : '0'); redraw?.(); } }, devOn ? '🛠 ON' : '🛠') : '',
  el('span', { class: 'chip' }, '🔥 ' + me.streak), el('span', { class: 'chip' }, '⭐ ' + me.xp), el('span', { class: 'chip', title: 'Dirham' }, '🪙 ' + me.dirham),
  el('a', { class: 'chip', href: 'index.php', title: 'Muroja’ah bebas' }, '📖'),
  el('button', { class: 'ico', onclick: logout, title: 'Keluar' }, '⎋'));

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
  mount(bar(), el('div', { class: 'wrap' }, el('h2', {}, 'Pilih Juz'), el('p', { class: 'mut' }, 'Boleh mulai dari juz mana saja.'),
    el('div', { class: 'grid' }, d.juz.map(j => el('button', { class: 'juz', onclick: () => pathView(j.juz) },
      el('b', {}, 'Juz ' + j.juz), el('small', {}, `${j.done}/${j.total} thumn`), el('i', {}, el('u', { style: `width:${100 * j.done / j.total}%` })))))));
}

// ---- Tombol oktagon dengan cincin ----
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
  const popup = (row, u, n, ox) => {
    closePop();
    const forced = n.state === 'locked' && devOn, lock = n.state === 'locked' && !devOn, done = n.state === 'done', kind = KIND[u.kind];
    const start = () => session(u.i, n.level, n.lesson, juz, kind);
    const btn = lock ? '' : el('button', { class: 'pbtn', onclick: start }, `${done ? 'Latih lagi' : forced ? 'Mulai (dev)' : 'Mulai'} +${n.xp} XP`);
    const dev = devOn ? el('div', { class: 'devrow' },
      el('button', { onclick: async () => { await api('dev_mark', { unit: u.i, level: n.level }); reload(); } }, 'Tandai selesai s.d. level ini'),
      el('button', { onclick: async () => { if (confirm('Hapus semua progres unit ini (termasuk chest)?')) { await api('dev_reset', { unit: u.i }); reload(); } } }, 'Reset unit')) : '';
    pop = el('div', { class: 'pop' + (lock ? ' lk' : ''), style: `--ox:${ox}px`, onclick: e => e.stopPropagation() },
      el('b', {}, `Level ${n.level} dari ${n.levels} · ${n.phase}`),
      el('span', {}, lock ? 'Selesaikan level sebelumnya dulu.' : `${done ? 'Selesai · ' : ''}Lesson ${n.lesson} dari ${n.lessons}`),
      lock ? '' : el('small', {}, [n.cumul ? `Lesson ulasan: soal dari awal hizb sampai Thumn ${u.thumn} ini.` : PHASE[n.phase], n.exam ? ` Ujian akhir ${kind.toLowerCase()}: 15 soal, lulus dengan skor ≥ 80%.` : '']),
      forced ? el('small', {}, '🛠 Mode developer: level ini sebenarnya masih terkunci.') : '',
      lock ? '' : el('small', {}, `Bonus +${n.bonus} XP jika semua benar di percobaan pertama`), btn, dev);
    pop._start = lock ? null : start; pop._key = u.i + n.id;
    row.after(pop); btn?.focus?.({ preventScroll: true }); pop.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  };
  const cls = u => u.kind === 'hizb' ? 9 : u.kind === 'juz' ? 10 : (u.thumn - 1) % 8 + 1;   // thumn: pelangi 1-8 | hizb: abu kebiruan | juz: emas
  const nodeRow = (u, n, k) => {
    const ox = off[k % 8], locked = n.state === 'locked';
    const b = el('button', { class: 'node ' + n.state + (locked && devOn ? ' dev' : ''), 'aria-label': `Level ${n.level}`,
      onclick: ev => { ev.stopPropagation(); pop && pop._key === u.i + n.id ? closePop() : popup(row, u, n, ox); } },
      el('span', { class: 'ic' }, locked ? (devOn ? '🔓' : '🔒') : n.state === 'done' ? '✓' : n.final ? '🏆' : n.level));
    b.insertAdjacentHTML('afterbegin', nodeSvg(locked, n.state === 'done' ? 100 : 100 * n.done / n.lessons));
    const row = el('div', { class: 'row' }, el('div', { class: 'nw', style: `transform:translateX(${ox}px)` }, b,
      el('div', { class: 'stars' }, n.state === 'done' ? '★'.repeat(n.stars) + '☆'.repeat(3 - n.stars) : n.state === 'open' ? `${n.done}/${n.lessons}` : '')));
    return row;
  };
  const chestRow = (u, c, k) => {   // chest: terkunci -> siap dibuka -> terbuka (10-20 dirham)
    const b = el('button', { class: 'chest ' + c.state, 'aria-label': `Chest setelah level ${c.after}`, onclick: async ev => {
      ev.stopPropagation(); closePop();
      if (c.state === 'locked') return toast(`Selesaikan Level ${c.after} untuk membuka chest ini`);
      if (c.state === 'opened') return toast(`Chest sudah dibuka: +${c.amount} dirham`);
      const r = await api('claim', { unit: u.i, chest: c.after }); toast(`🎉 Chest terbuka! +${r.amount} dirham`); reload();
    } });
    b.insertAdjacentHTML('afterbegin', chestSvg(c.state === 'opened'));
    return el('div', { class: 'row' }, el('div', { class: 'nw', style: `transform:translateX(${off[k % 8]}px)` }, b,
      el('div', { class: 'stars' }, c.state === 'ready' ? 'Buka!' : c.state === 'opened' ? `+${c.amount} 🪙` : `Level ${c.after}`)));
  };
  const rowsOf = u => { const r = []; u.nodes.forEach(n => { r.push(['n', n]); const c = (u.chests || []).find(c => c.after === n.level); if (c) r.push(['c', c]); }); return r; };
  mount(bar(juzView), el('div', { class: 'wrap', onclick: closePop }, el('h2', {}, 'Juz ' + juz),
    d.units.map(u => el('section', { class: 'unit u' + cls(u) },
      el('div', { class: 'uh' }, el('small', {}, u.label), el('b', {}, u.title)),
      u.empty ? el('p', { class: 'err' }, u.bad ? 'Titik awal thumn ini tidak konsisten di data/thumn.json (kemungkinan salah ketik), jadi ayatnya digabung ke thumn sebelumnya.' : 'Tidak ada ayat pada unit ini — periksa data/thumn.json dan file surahnya.') : '',
      el('div', { class: 'path' }, rowsOf(u).map(([t, x], k) => t === 'n' ? nodeRow(u, x, k) : chestRow(u, x, k)))))));
  keys = e => { if (e.key === 'Escape') closePop(); else if (e.key === 'Enter' && pop?._start) { e.preventDefault(); pop._start(); } };
  document.querySelector('.node.open')?.scrollIntoView({ block: 'center' });
}

async function session(unit, level, lesson, juz, kind = 'Thumn') {
  const d = await api('quiz', null, `&unit=${unit}&level=${level}&lesson=${lesson}`);
  const total = d.questions.length, queue = [...d.questions];
  let hearts = 5, done = 0, ok = 0;
  const pb = el('u'), hr = el('span', { class: 'hearts' }), body = el('div'), foot = el('div', { class: 'foot' });
  const upd = () => { pb.style.width = 100 * done / total + '%'; hr.textContent = '❤ ' + hearts; };
  mount(el('div', { class: 'wrap sess' },
    el('div', { class: 'sh' }, el('button', { class: 'ico', onclick: () => confirm('Keluar? Progres sesi ini tidak disimpan.') && pathView(juz) }, '✕'), el('div', { class: 'pb' }, pb), hr),
    body, foot));

  const next = () => { upd(); queue.length ? ask(queue[0]) : finish(); };
  const judge = (q, c, label) => {
    if (c) { done++; if (!q.miss) ok++; queue.shift(); } else { hearts--; q.miss = true; queue.push(queue.shift()); }
    upd(); body.querySelectorAll('button').forEach(b => b.disabled = true);
    const go = () => hearts <= 0 ? lose() : next();
    foot.replaceChildren(el('div', { class: 'fb ' + (c ? 'good' : 'bad') }, el('b', {}, label || (c ? 'Benar! ✓' : 'Kurang tepat')),
      el('div', { class: 'ayat sm', dir: 'rtl' }, q.full),
      el('button', { class: 'btn ' + (c ? '' : 'red'), onclick: go }, 'Lanjut')));
    onEnter(go);
  };

  function ask(q) {
    let check;
    const btn = el('button', { class: 'btn', onclick: () => judge(q, check()) }, 'Periksa'); btn.disabled = true;
    const title = el('p', { class: 'qt' }, q.title);
    if (q.t === 'choice') {
      let sel = null;
      const show = q.tokens ? el('div', { class: 'ayat', dir: 'rtl' }, q.tokens.map((w, i) => [el('span', { class: i === q.mark ? 'mk' : '' }, w), ' ']))
        : q.show ? el('div', { class: 'ayat', dir: 'rtl' }, q.show) : '';
      const os = q.opts.map((o, i) => el('button', { class: 'opt ' + q.mode, dir: q.mode === 'ar' ? 'rtl' : null,
        onclick: () => { sel = o; os.forEach(x => x.classList.remove('sel')); os[i].classList.add('sel'); btn.disabled = false; } },
        el('span', { class: 'kn' }, i + 1), q.mode === 'har' ? '◌' + o : o));
      body.replaceChildren(title, show, el('div', { class: 'opts ' + q.mode }, os),
        el('p', { class: 'hint' }, `Tekan 1–${os.length} atau tombol panah untuk memilih · Enter untuk memeriksa`));
      check = () => sel === q.ans;
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
      // Kolom KIRI = akhir ayat / nomor ayat (tombol 1-5), kolom KANAN = awal ayat (tombol Q W E R T). Satu grid -> tinggi tiap baris sama.
      const rtl = q.rmode === 'ar';
      let sel = null, remaining = q.left.length, mistakes = 0, locked = false;
      const items = { L: [], R: [] };   // L = awal ayat (q.left), R = akhir/nomor (q.right)
      const mk = (s, list) => list.map((it, i) => {
        const ar = s === 'L' || rtl;
        const b = el('button', { class: 'mi ' + (ar ? 'ar' : 'num'), dir: ar ? 'rtl' : null, onclick: () => pick(s, i) },
          el('span', { class: 'kn' }, s === 'R' ? i + 1 : 'QWERT'[i]), it.text);
        items[s].push(b); return b;
      });
      const pick = (s, i) => {
        const b = items[s][i]; if (locked || b.classList.contains('ok')) return;
        if (!sel || sel.s === s) { sel && items[sel.s][sel.i].classList.remove('sel'); sel = { s, i }; b.classList.add('sel'); return; }
        const [li, ri] = s === 'R' ? [sel.i, i] : [i, sel.i]; items[sel.s][sel.i].classList.remove('sel'); sel = null;
        if (q.left[li].id === q.right[ri].id) {
          items.L[li].classList.add('ok'); items.R[ri].classList.add('ok');
          if (--remaining === 0) { locked = true; q.miss = mistakes > 0; judge(q, true, mistakes ? `Selesai · ${mistakes} kesalahan` : 'Sempurna! ✓'); }
        } else {
          mistakes++; hearts--; upd();
          [items.L[li], items.R[ri]].forEach(x => { x.classList.add('bad'); setTimeout(() => x.classList.remove('bad'), 450); });
          if (hearts <= 0) { locked = true; setTimeout(lose, 600); }
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
      const sl = q.ans.map(() => null), area = el('div', { class: 'ayat', dir: 'rtl' }), pool = el('div', { class: 'tiles', dir: 'rtl' });
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
      check = () => sl.every((t, i) => q.opts[t] === q.ans[i]);
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

  const retry = () => session(unit, level, lesson, juz, kind);
  const lose = () => {
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, '💔'), el('h2', {}, 'Nyawa habis'), el('p', { class: 'mut' }, 'Tenang, ulangi lagi — muroja’ah itu proses.'),
      el('button', { class: 'btn', onclick: retry }, 'Coba lagi'), el('p'), el('button', { class: 'btn alt', onclick: () => pathView(juz) }, 'Kembali')));
    onEnter(retry);
  };
  async function finish() {
    const r = await api('complete', { unit, level, lesson, correct: ok, total });
    me.xp = r.xp; me.streak = r.streak;
    const stars = '★'.repeat(r.stars) + '☆'.repeat(3 - r.stars);
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, !r.passed ? '😕' : r.unitDone ? '🏅' : r.levelDone ? '🎊' : '🎉'),
      el('h2', {}, !r.passed ? 'Belum lulus' : r.unitDone ? `${kind} selesai!` : r.levelDone ? `Level ${r.level} selesai!` : `Lesson ${r.lesson} dari ${r.lessons} selesai!`),
      el('p', { class: 'mut' }, !r.passed ? 'Ujian akhir butuh skor minimal 80%.' : stars),
      el('div', { class: 'stat' }, el('div', {}, el('small', {}, 'Skor'), el('b', {}, r.pct + '%')),
        el('div', {}, el('small', {}, r.bonus ? `XP (termasuk bonus ${r.bonus})` : 'XP'), el('b', {}, '+' + r.gain)),
        el('div', {}, el('small', {}, 'Streak'), el('b', {}, '🔥' + r.streak))),
      el('button', { class: 'btn', onclick: () => pathView(juz) }, 'Lanjut'), el('p'), r.passed ? '' : el('button', { class: 'btn alt', onclick: retry }, 'Ulangi')));
    onEnter(r.passed ? () => pathView(juz) : retry);
  }
  next();
}

(async () => { try { me = (await api('me')).user; juzView(); } catch (e) { authView(); } })();