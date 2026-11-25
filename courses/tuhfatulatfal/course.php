<?php
// Manifest course Tuḥfatulaṭfāl (hafalan matan manzumah). Status 'draft': sudah bisa diikuti, tetapi materi dan format belajarnya belum dibuat.
// Saat materinya siap: tambahkan boot.php (kode PHP), handler API, hook finish/profile, data di data/tuhfatulatfal/,
// dan modul klien assets/js/courses/tuhfatulatfal.js; lalu ubah status menjadi 'ready'. Panduan lengkap: courses/README.md.
return [
    'id' => 'tuhfatulatfal', 'name' => 'Tuḥfatulaṭfāl', 'subtitle' => 'Hafalan matan manzumah', 'icon' => 'scroll', 'color' => '#a560e8', 'order' => 2, 'status' => 'draft',
    'description' => 'Hafalan matan manzumah Tuḥfatulaṭfāl. Materi dan format belajarnya sedang disiapkan.',
];
