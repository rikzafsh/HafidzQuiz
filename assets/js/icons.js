'use strict';
// Ikon SVG flat design (tanpa emoji). ic(nama, ukuran) membuat elemen ikon; stars(n) membuat deretan 3 bintang.
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
  page1: `<rect x="6" y="3" width="12" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`,
  page2: `<rect x="2.5" y="4" width="9" height="16" rx="1.8" fill="none" stroke="currentColor" stroke-width="2.2"/><rect x="12.5" y="4" width="9" height="16" rx="1.8" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M5 9h4M5 13h4M15 9h4M15 13h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>`,
  search: `<circle cx="10.5" cy="10.5" r="6.4" fill="none" stroke="currentColor" stroke-width="2.8"/><path d="M15.4 15.4L21 21" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>`,
  scroll: `<rect x="5" y="3" width="14" height="18" rx="2.4" fill="#f6e7bc"/><rect x="5" y="3" width="14" height="4" rx="2" fill="#d9b66a"/><rect x="5" y="17" width="14" height="4" rx="2" fill="#d9b66a"/><path d="M8.5 10.5h7M8.5 13.5h5" stroke="#b98a3e" stroke-width="1.6" stroke-linecap="round"/>`,
  plus: `<path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>`,
  caret: `<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>`,
  shield: `<path d="M32 4l24 8v22c0 16-11 28-24 34C19 62 8 50 8 34V12z" fill="#ffc800"/><path d="M32 10l18 6v18c0 12-8 22-18 27-10-5-18-15-18-27V16z" fill="#e39b00" opacity=".4"/>`,
  avatar: `<circle cx="60" cy="52" r="30" fill="#2f8fc7"/><path d="M0 120c0-34 26-50 60-50s60 16 60 50z" fill="#2f8fc7"/><rect x="55" y="38" width="10" height="28" rx="3" fill="#cfeaff" opacity=".9"/><rect x="46" y="47" width="28" height="10" rx="3" fill="#cfeaff" opacity=".9"/>`,
};
const ic = (n, s = 20, cls = '') => {
  const e = document.createElement('span'); e.className = 'i ' + cls; e.style.cssText = `width:${s}px;height:${s}px`;
  e.innerHTML = `<svg viewBox="${n === 'shield' ? '0 0 64 72' : n === 'avatar' ? '0 0 120 120' : '0 0 24 24'}" aria-hidden="true">${ICONS[n] || ''}</svg>`; return e;
};
const stars = (n, s = 14) => el('span', { class: 'stk' }, [0, 1, 2].map(i => ic(i < n ? 'star' : 'starO', s)));
