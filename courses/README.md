# Panduan menambah course

HafidzQuiz memakai arsitektur **platform + course**. Platform (`src/`, `api.php`, `assets/js/{core,shell,quiz,icons}.js`) mengurus akun, XP, streak, dirham, pendaftaran course, dan mesin sesi kuis. Setiap course (`courses/<id>/`) berisi materi dan aturan belajarnya sendiri. Course baru **tidak perlu mengubah kode platform**.

```
courses/
├── README.md                     panduan ini
├── tahfidz/                      course "Tahfidz" (siap)
│   ├── course.php                manifest (wajib)
│   ├── boot.php                  memuat semua kode course (dimuat hanya saat course dipakai)
│   ├── data.php                  data Al-Qur'an: surah(), thumns(), thumnAyat(), ...
│   ├── gen.php                   pembuat soal
│   ├── units.php                 struktur belajar: unit, level, lesson, chest, lompat, penerapan hasil sesi
│   ├── mushaf.php                logika halaman mushaf
│   ├── handlers.php              aksi API course + hook finish & profile
│   └── tools/                    alat baris perintah (konverter dan diagnostik data)
└── tuhfatulatfal/                course "Tuḥfatulaṭfāl" (draft)
    └── course.php                manifest saja; materi menyusul
assets/js/courses/
├── tahfidz.js                    modul klien Tahfidz
└── tuhfatulatfal.js              modul klien Tuḥfatulaṭfāl ("segera hadir")
```

Setiap folder `courses/<id>/` yang berisi `course.php` otomatis terdaftar (`src/courses.php`), dan setiap berkas `assets/js/courses/*.js` otomatis dimuat (`app.php`).

---

## Langkah menambah course baru

### 1. Manifest: `courses/<id>/course.php`

```php
<?php
return [
    'id' => 'contoh', 'name' => 'Contoh', 'subtitle' => 'Deskripsi singkat',
    'icon' => 'scroll',            // kunci ikon di assets/js/icons.js
    'color' => '#a560e8', 'order' => 3,
    'status' => 'ready',           // 'draft' = bisa diikuti, tetapi materi belum ada ("Segera hadir")
    'description' => 'Penjelasan yang tampil di katalog "Tambah kursus baru".',
    'boot'    => 'boot.php',       // berkas yang memuat kode PHP course
    'api'     => 'contoh_api',     // fungsi penangan aksi API course
    'finish'  => 'contoh_finish',  // hook: menerapkan hasil sesi kuis
    'profile' => 'contoh_profile', // hook: statistik untuk halaman profil
];
```

Course berstatus `draft` cukup berisi manifest (tanpa `boot`/`api`/`finish`/`profile`), seperti `tuhfatulatfal`.

### 2. Kode PHP

- **`boot.php`**: `require_once` semua berkas course. Dimuat registry hanya saat course dipakai (`courseLoad()`), sehingga course lain tidak terbebani.
- **Penangan API** `function contoh_api(string $aksi, array $user, array $in, array $course): bool`:
  `switch ($aksi)`; setiap aksi yang dikenal berakhir dengan `out([...])` (yang menghentikan eksekusi). Mengembalikan `false` bila aksi tidak dikenal. Aksi dipanggil klien lewat `api('nama_aksi')` dan **otomatis membawa `?course=<id>`** (kursus aktif pengguna). Platform sudah memastikan pengguna **sudah bergabung** di course itu (403 bila belum).
- **Sesi kuis**: buat soal sendiri (`gen.php` course), lalu `newAttempt($user['id'], 'contoh', 'lesson', $unit, $level, $lesson, $soal)`. Pemeriksaan jawaban (`check`), nyawa, dan `finish` ditangani platform. Format soal yang didukung mesin kuis: `choice`, `fill`, dan `match` (lihat `courses/tahfidz/gen.php`); tipe baru memerlukan penambahan di `assets/js/quiz.js` dan `quiz_check()`.
- **Hook `finish`** `function contoh_finish(array $user, array $attempt, int $benarPercobaanPertama, int $totalSoal): array`: hitung XP/bintang/progres, simpan progres, lalu panggil **`award($user, 'contoh', $xp)`** (menambah XP global + XP course + streak). Kembalikan array hasil (`passed`, `gain`, `xp`, `courseXp`, `streak`, `pct`, ...) yang ditampilkan layar hasil.
- **Hook `profile`** `function contoh_profile(array $user): array`: kembalikan statistik course (dikirim ke klien sebagai `stats`).
- **Progres**: tabel `progress(user_id, course, unit, node, stars, best)`. Gunakan `courseProgress($uid, 'contoh')` untuk membaca (hasilnya `["unit:node" => stars]`), dan isi `course` pada setiap INSERT/UPDATE/DELETE. `unit` adalah ID unit di dalam course (bebas ditentukan course), `node` adalah kunci bebas (mis. `V1.2`).

### 3. Modul klien: `assets/js/courses/<id>.js`

```js
'use strict';
Courses.contoh = {
  nav: [['learn', 'Belajar', 'home', () => Courses.contoh.home()]],   // menu course; "Profil" ditambahkan platform
  home() { redraw = () => Courses.contoh.home(); shell('learn', topbar(), el('div', { class: 'wrap' }, /* isi layar */)); },
  statCards: (s, card) => [card('check', s.selesai, 'Selesai')],      // opsional: kartu statistik profil (s = stats dari hook profile)
  achievements: s => [{ icon: 'book', title: 'Judul', steps: [1, 5, 10], text: n => `Selesai ${n}`, val: () => s.selesai }],  // opsional
  summary: s => `${s.selesai} selesai`,                               // opsional: ringkasan di "Kursus saya" (profil)
  profileActions: () => [],                                           // opsional: tombol tambahan di profil
};
```

Pembantu global yang tersedia: `el()`, `api()`, `ic()`, `toast()`, `mount()`, `shell()`, `topbar()`, `session(unit, level, lesson, kembali, jenis, opsi)` (mesin kuis; `kembali` = fungsi tujuan setelah sesi), `courseOf()`, `switchCourse()`, `me`, `store`.
`api('aksi')` otomatis menambahkan `&course=<kursus aktif>`.

### 4. Aset dan dokumentasi

- Ikon baru: tambahkan ke `ICONS` di `assets/js/icons.js`.
- Data materi: simpan di `data/<id>/` (jangan mencampur dengan data Tahfidz di `data/`).
- Perbarui `README.md` dan `knowledge.md`.

---

## Aturan penting

- **Isolasi**: progres, sesi (`attempts`), dan masukan (`feedback`) selalu menyimpan `course`. XP dihitung global (`users.xp`) **dan** per course (`enrollments.xp`); streak, dirham, dan pencapaian lintas course bersifat global.
- **Keamanan**: jangan mengirim kunci jawaban ke klien; kunci disimpan di `attempts.qjson` dan diperiksa server.
- **Nama fungsi PHP bersifat global**: awali dengan id course (`contoh_...`, atau `t...` seperti di Tahfidz) agar tidak bentrok dengan course lain.
- **Alat baris perintah** course diletakkan di `courses/<id>/tools/` dan dimulai dengan:
  `require dirname(__DIR__, 3) . '/src/bootstrap.php'; courseLoad(courseGet('<id>'));`
