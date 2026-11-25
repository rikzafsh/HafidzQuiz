'use strict';
// Modul course Tuḥfatulaṭfāl (hafalan matan manzumah). Saat ini hanya halaman "segera hadir"; materi dan format belajarnya menyusul.
Courses.tuhfatulatfal = {
  nav: [['learn', 'Belajar', 'home', () => Courses.tuhfatulatfal.home()]],
  home() {
    redraw = () => Courses.tuhfatulatfal.home();
    const c = courseOf('tuhfatulatfal') || { name: 'Tuḥfatulaṭfāl', subtitle: '', description: '' };
    shell('learn', topbar(), el('div', { class: 'wrap soon' }, ic('scroll', 96), el('h2', {}, c.name), el('p', { class: 'mut' }, c.subtitle),
      el('p', {}, 'Materi dan format belajar kursus ini sedang disiapkan. Kamu sudah terdaftar, dan kursus ini akan siap dipelajari begitu materinya tersedia.'),
      courseOf('tahfidz') ? el('button', { class: 'btn', onclick: () => switchCourse('tahfidz') }, 'Kembali ke Tahfidz') : ''));
  },
  summary: () => 'Segera hadir',
};
