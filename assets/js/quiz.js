'use strict';
// Mesin sesi kuis generik (dipakai semua course): menerima soal tanpa kunci, memeriksa jawaban lewat server, dan menampilkan hasil.
async function session(unit, level, lesson, juz, kind = 'Thumn', opts = {}) {
  const goBack = typeof juz === 'function' ? juz : () => pathView(juz);   // tujuan setelah sesi: fungsi (course lain) atau nomor juz (Tahfidz)
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
    el('div', { class: 'sh' }, el('button', { class: 'ico', 'aria-label': 'Keluar', onclick: () => confirm('Keluar? Progres sesi ini tidak disimpan.') && goBack() }, ic('close', 24)), pbw, hr),
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
      el('button', { class: 'btn', onclick: retry }, 'Coba lagi'), el('p'), el('button', { class: 'btn alt', onclick: () => goBack() }, 'Kembali')));
    onEnter(retry);
  };
  async function finish() {
    let r; try { r = await api('finish', { attempt }); } catch (e) { toast(e.message); return goBack(); }   // skor, XP, dan waktu semuanya dari server
    me.xp = r.xp; me.streak = r.streak; { const mc = courseOf(me.course); if (mc && r.courseXp != null) mc.xp = r.courseXp; }
    const head = jump ? 'Lompat berhasil!' : !r.passed ? 'Belum lulus' : r.unitDone ? `${kind} selesai!` : r.levelDone ? `Level ${r.level} selesai!` : `Lesson ${r.lesson} dari ${r.lessons} selesai!`;
    mount(el('div', { class: 'wrap big' }, el('div', { class: 'em' }, ic(!r.passed ? 'sad' : jump ? 'rocket' : r.unitDone ? 'trophy' : r.levelDone ? 'medal' : 'party', 84)),
      el('h2', {}, head),
      el('p', { class: 'mut' }, !r.passed ? 'Ujian akhir butuh skor minimal 80%.' : jump ? `${r.skipped} thumn dilewati dan ditandai selesai.` : stars(r.stars, 20)),
      el('div', { class: 'stat' },
        el('div', { class: 'sc xp' }, el('small', {}, 'TOTAL XP'), el('b', {}, ic('bolt', 22), r.gain)),
        el('div', { class: 'sc time' }, el('small', {}, 'WAKTU'), el('b', {}, ic('timer', 22), fmtTime(r.secs))),
        el('div', { class: 'sc acc' }, el('small', {}, 'AKURASI'), el('b', {}, ic('target', 22), r.pct + '%'))),
      el('p', { class: 'mut inl' }, r.bonus ? `Termasuk bonus ${r.bonus} XP · ` : '', ic('flame', 18), `Streak ${r.streak} hari`),
      el('button', { class: 'btn', onclick: () => goBack() }, 'Lanjut'), el('p'), r.passed ? '' : el('button', { class: 'btn alt', onclick: retry }, 'Ulangi')));
    onEnter(r.passed ? () => goBack() : retry);
  }
  next();
}
