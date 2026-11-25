'use strict';
// Inti klien: pembantu DOM, panggilan API, state global (me), keyboard, dan registry modul course (Courses).
const app = document.getElementById('app');
const el = (t, a = {}, ...c) => {
  const e = document.createElement(t);
  for (const k in a) { if (a[k] == null || a[k] === false) continue; k === 'class' ? e.className = a[k] : k.startsWith('on') ? e.addEventListener(k.slice(2), a[k]) : e.setAttribute(k, a[k]); }
  c.flat(9).forEach(x => e.append(x instanceof Node ? x : String(x ?? '')));
  return e;
};
const api = async (a, body, qs = '') => {
  const r = await fetch('api.php?a=' + a + qs + (me?.course ? '&course=' + encodeURIComponent(me.course) : ''), body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {});
  let j; try { j = JSON.parse(await r.text()); } catch { throw Object.assign(new Error('Respons server tidak valid'), { status: r.status || 500 }); }
  if (!r.ok || j.error) throw Object.assign(new Error(j.error || 'Terjadi kesalahan'), { status: r.status });
  return j;
};
const toast = m => { const t = el('div', { class: 'toast' }, m); document.body.append(t); setTimeout(() => t.remove(), 2400); };

// ---------- Keyboard, layar, navigasi ----------
let keys = null; // penangan keyboard milik layar yang sedang tampil
const onEnter = fn => { keys = e => { if (e.key === 'Enter') { e.preventDefault(); if (!e.repeat) fn(); } }; };
addEventListener('keydown', e => { if (keys && !e.ctrlKey && !e.metaKey && !e.altKey && !e.target.matches('input,textarea,select')) keys(e); });
let leaveScreen = null;   // dipanggil saat layar diganti (mis. melepas pendengar 'resize' milik layar tertentu)
let ddClose = null;       // menutup dropdown pemilih kursus bila sedang terbuka
const mount = (...n) => { keys = null; leaveScreen?.(); leaveScreen = null; ddClose?.(); app.replaceChildren(...n); scrollTo(0, 0); };
let me = null;
addEventListener('unhandledrejection', e => e.reason?.status === 401 ? authView() : toast(e.reason?.message || 'Terjadi kesalahan'));
const store = { get: k => { try { return localStorage.getItem(k); } catch { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch {} } };
let devOn = store.get('hq_dev') === '1', redraw = null;   // devOn: mode developer sementara (hanya efektif bila server DEV_MODE = true)

const fmtTime = s => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');

// Registry modul course. Setiap berkas assets/js/courses/<id>.js mendaftarkan modulnya, mis.:
//   Courses.<id> = { nav: [[idMenu, label, ikon, fungsi]], home(), statCards?(stats, card), achievements?(stats), summary?(stats), profileActions?() }
// 'me.course' (id kursus aktif) menentukan modul yang dipakai untuk layar Belajar dan menu.
const Courses = {};
