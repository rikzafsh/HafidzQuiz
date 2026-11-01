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
const bar = back => el('header', { class: 'top' },
  back ? el('button', { class: 'ico', onclick: back, 'aria-label': 'Kembali' }, '←') : el('b', { class: 'brand' }, 'HafidzQuiz'),
  el('span', { class: 'sp' }), el('span', { class: 'chip' }, '🔥 ' + me.streak), el('span', { class: 'chip' }, '⭐ ' + me.xp),
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
  const d = await api('juz'); me = d.user;
  mount(bar(), el('div', { class: 'wrap' }, el('h2', {}, 'Pilih Juz'), el('p', { class: 'mut' }, 'Boleh mulai dari juz mana saja.'),
    el('div', { class: 'grid' }, d.juz.map(j => el('button', { class: 'juz', onclick: () => pathView(j.juz) },
      el('b', {}, 'Juz ' + j.juz), el('small', {}, `${j.done}/${j.total} thumn`), el('i', {}, el('u', { style: `width:${100 * j.done / j.total}%` })))))));
}

async function pathView(juz) {
  const d = await api('units', null, '&juz=' + juz); me = d.user;
  const off = [0, 38, 58, 38, 0, -38, -58, -38];
  mount(bar(juzView), el('div', { class: 'wrap' }, el('h2', {}, 'Juz ' + juz),
    d.units.map(u => el('section', { class: 'unit' },
      el('div', { class: 'uh' }, el('small', {}, `Hizb ${u.hizb} · Thumn ${u.thumn}`), el('b', {}, u.title)),
      el('div', { class: 'path' }, u.nodes.map((n, k) => el('div', { class: 'nw', style: `transform:translateX(${off[k % 8]}px)` },
        el('button', { class: 'node ' + n.state, 'aria-label': n.id === 'T' ? 'Test' : 'Lesson ' + n.id.slice(1),
          onclick: () => n.state === 'locked' ? toast('Selesaikan langkah sebelumnya dulu') : session(u.i, n.id, juz) },
          n.state === 'locked' ? '🔒' : n.id === 'T' ? '🏆' : n.state === 'done' ? '✓' : n.id.slice(1)),
        el('div', { class: 'stars' }, n.state === 'done' ? '★'.repeat(n.stars) + '☆'.repeat(3 - n.stars) : ''))))))));
}

async function session(thumn, node, juz) {
  const d = await api('quiz', null, `&thumn=${thumn}&node=${node}`);
  const total = d.questions.length, queue = [...d.questions];
  let hearts = 5, done = 0, ok = 0;
  const pb = el('u'), hr = el('span', { class: 'hearts' }), body = el('div'), foot = el('div', { class: 'foot' });
  const upd = () => { pb.style.width = 100 * done / total + '%'; hr.textContent = '❤ ' + hearts; };
  mount(el('div', { class: 'wrap sess' },
    el('div', { class: 'sh' }, el('button', { class: 'ico', onclick: () => confirm('Keluar? Progres sesi ini tidak disimpan.') && pathView(juz) }, '✕'), el('div', { class: 'pb' }, pb), hr),
    body, foot));

  const next = () => { upd(); queue.length ? ask(queue[0]) : finish(); };
  const judge = (q, c) => {
    if (c) { done++; if (!q.miss) ok++; queue.shift(); } else { hearts--; q.miss = true; queue.push(queue.shift()); }
    upd(); body.querySelectorAll('button').forEach(b => b.disabled = true);
    const go = () => hearts <= 0 ? lose() : next();
    foot.replaceChildren(el('div', { class: 'fb ' + (c ? 'good' : 'bad') }, el('b', {}, c ? 'Benar! ✓' : 'Kurang tepat'),
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

  const retry = () => session(thumn, node, juz);
  const lose = () => {
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, '💔'), el('h2', {}, 'Nyawa habis'), el('p', { class: 'mut' }, 'Tenang, ulangi lagi — muroja’ah itu proses.'),
      el('button', { class: 'btn', onclick: retry }, 'Coba lagi'), el('p'), el('button', { class: 'btn alt', onclick: () => pathView(juz) }, 'Kembali')));
    onEnter(retry);
  };
  async function finish() {
    const r = await api('complete', { thumn, node, correct: ok, total });
    me.xp = r.xp; me.streak = r.streak;
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, r.passed ? '🎉' : '😕'),
      el('h2', {}, r.passed ? (node === 'T' ? 'Test lulus!' : 'Lesson selesai!') : 'Belum lulus'),
      el('p', { class: 'mut' }, r.passed ? '★'.repeat(r.stars) + '☆'.repeat(3 - r.stars) : 'Test butuh skor minimal 80%.'),
      el('div', { class: 'stat' }, el('div', {}, el('small', {}, 'Skor'), el('b', {}, r.pct + '%')), el('div', {}, el('small', {}, 'XP'), el('b', {}, '+' + r.gain)), el('div', {}, el('small', {}, 'Streak'), el('b', {}, '🔥' + r.streak))),
      el('button', { class: 'btn', onclick: () => pathView(juz) }, 'Lanjut'), el('p'), r.passed ? '' : el('button', { class: 'btn alt', onclick: retry }, 'Ulangi')));
    onEnter(r.passed ? () => pathView(juz) : retry);
  }
  next();
}

(async () => { try { me = (await api('me')).user; juzView(); } catch (e) { authView(); } })();
