# HafidzQuiz

Aplikasi web berbentuk kuis ala Duolingo untuk **menghafal Al-Qur'an**. Responsif (PC dan HP), memakai akun pengguna untuk menyimpan XP dan capaian, dan dirancang agar course lain (hafalan matan manzumah, syair, belajar aksara) bisa ditambahkan nanti. Aplikasi bersifat **multi-kursus**: pengguna dapat mengikuti beberapa kursus sekaligus dan berpindah di antaranya. Saat ini ada dua kursus: **Tahfidz** (hafalan Al-Qur'an, siap dipelajari) dan **Tuḥfatulaṭfāl** (hafalan matan manzumah, masih berstatus *segera hadir*).

> Dokumen ini dan `knowledge.md` diperbarui setiap ada permintaan baru.
> Terakhir diperbarui: 25 November 2026.

---

## Daftar isi

1. [Fitur](#fitur)
2. [Kursus (multi-course)](#kursus-multi-course)
3. [Struktur belajar](#struktur-belajar) — kursus Tahfidz
4. [Jenis soal](#jenis-soal)
5. [Hadiah, nyawa, dan aturan skor](#hadiah-nyawa-dan-aturan-skor)
6. [Mushaf](#mushaf)
7. [Profil dan administrator](#profil-dan-administrator)
8. [Teknologi dan struktur proyek](#teknologi-dan-struktur-proyek)
9. [Instalasi dan menjalankan](#instalasi-dan-menjalankan)
10. [Konfigurasi](#konfigurasi)
11. [Data](#data)
12. [API](#api)
13. [Basis data](#basis-data)
14. [Keamanan dan penilaian di server](#keamanan-dan-penilaian-di-server)
15. [Pengujian](#pengujian)
16. [Batasan dan rencana](#batasan-dan-rencana)

---

## Fitur

- **Multi-kursus**: pilih kursus lewat tombol di bilah atas (ikon kursus + XP kursus), tambah kursus baru lewat katalog, dan berpindah kapan saja. Progres tiap kursus terpisah.
- **Belajar bertingkat** (kursus Tahfidz): Juz → Hizb → Thumn (480 thumn). Setiap thumn punya 8 level × 4 lesson, bukan sekadar daftar soal.
- **Peta belajar** dengan tombol berbentuk oktagon, warna pelangi per thumn (merah = thumn 1 … ungu = thumn 8), cincin progres, dan popup "Lesson x dari y + XP" sebelum mulai.
- **Soal acak yang beragam** (11 tipe), selalu dibuat dari ayat dalam cakupan unitnya, makin sulit seiring level.
- **Unit ulasan**: ulasan Hizb (4 level) dan ulasan Juz (8 level), serta level ulasan Rub/Nisf pada Thumn 2, 4, dan 6.
- **Lompat ke sini**: lewati thumn yang terkunci dengan tes 25 soal yang semuanya harus benar.
- **Nyawa, XP, streak harian, bintang, chest berisi dirham**, dan indikator jawaban benar beruntun.
- **Menu Mushaf**: ayat disusun seperti halaman mushaf (5 bagian berwarna, tanda ۞ di awal thumn, keterangan pembagian di margin), mode 1 atau 2 halaman, navigasi ke surah/ayat/juz/hizb/thumn/halaman.
- **Profil**: statistik dan pencapaian bertingkat.
- **Masukan pengguna** ("terlalu mudah", "terlalu sulit", "laporkan") yang tersimpan dan tampil di halaman admin (`admin.php`).
- **Ramah PC**: semua soal bisa dijawab dengan keyboard; soal lengkapi ayat mendukung seret-lepas.
- **Ikon SVG flat design** (tanpa emoji), mode gelap otomatis mengikuti sistem.
- **Penilaian di server**: browser tidak pernah menerima kunci jawaban dan tidak bisa melaporkan skornya sendiri.

---

## Kursus (multi-course)

| Kursus | Id | Status | Isi |
|---|---|---|---|
| **Tahfidz** | `tahfidz` | siap | Hafalan Al-Qur'an per thumn (lihat bagian berikutnya sampai Mushaf) |
| **Tuḥfatulaṭfāl** | `tuhfatulatfal` | draft | Hafalan matan manzumah Tuḥfatulaṭfāl. Bisa diikuti, tetapi materi dan format belajarnya belum dibuat (menampilkan halaman "segera hadir"); detail teknisnya menyusul |

**Cara kerja untuk pengguna**

- Pengguna baru **otomatis mengikuti Tahfidz**. Pengguna lama (sebelum fitur ini) juga otomatis mengikuti Tahfidz dan seluruh progres serta XP-nya menjadi milik Tahfidz.
- Tombol kursus di bilah atas menampilkan ikon kursus aktif, **XP kursus itu**, dan panah. Mengkliknya membuka dropdown **"KURSUS SAYA"** (daftar kursus yang diikuti, tanda centang pada kursus aktif) dengan tombol **"Tambah kursus baru"** di bawahnya.
- **Tambah kursus baru** membuka katalog berisi kursus yang belum pernah diikuti; **"Mulai belajar"** langsung mendaftarkan pengguna dan membuka kursus itu. Kursus yang belum siap diberi lencana "Segera hadir".
- Menu (Belajar, Mushaf, Profil) menyesuaikan kursus aktif: Mushaf hanya ada di Tahfidz.
- Profil menampilkan XP total, daftar **"Kursus saya"** beserta ringkasan dan XP masing-masing, serta pencapaian.

**Cakupan data**

| Data | Cakupan |
|---|---|
| XP | Global (`users.xp`, dasar pencapaian "Penuntut Ilmu") **dan** per kursus (`enrollments.xp`, tampil di tombol kursus) |
| Streak, dirham, rekor streak, total dirham | Global (lintas kursus) |
| Progres, sesi kuis, masukan pengguna | Per kursus |

**Untuk pengembang**: course baru ditambahkan tanpa mengubah kode platform. Panduan langkah demi langkah ada di [`courses/README.md`](courses/README.md).

---

## Struktur belajar

> Bagian ini sampai **Mushaf** menjelaskan kursus **Tahfidz**.

Setiap juz berisi **19 unit**, berurutan:

```
[ Thumn 1 … Thumn 8 (hizb ganjil) ]  →  Ulasan Hizb
[ Thumn 1 … Thumn 8 (hizb genap)  ]  →  Ulasan Hizb
Ulasan Juz
```

| Unit | Level | Lesson per level | Cakupan soal |
|---|---|---|---|
| **Thumn** | 8 (+ level 9 "Ulasan" pada Thumn 2, 4, 6) | 4 | Ayat dalam thumn itu |
| **Level 9: Ulasan Rub / Nisf** | 1 (di akhir) | 4 | Dari awal hizb sampai thumn itu (kumulatif). Thumn 2 dan 6 = "Ulasan Rub", Thumn 4 = "Ulasan Nisf". Thumn 8 tidak punya level ini karena sudah ada Ulasan Hizb |
| **Ulasan Hizb** | 4 | 4 | Seluruh ayat dalam satu hizb |
| **Ulasan Juz** | 8 | 4 | Seluruh ayat dalam satu juz |

Tingkat kesulitan per level thumn:

| Level | Fase | Isi |
|---|---|---|
| 1–3 | Dasar | Melengkapi ayat, menyambung ayat, menebak ayat sesudahnya |
| 4–5 | Menengah | + ayat sebelumnya, nomor ayat, harakat akhir kata |
| 6–8 | Mahir | + pencocokan awal–akhir ayat, pencocokan awal ayat–nomor, i'rab, kata yang mirip, kata terakhir ayat |

Pemetaan kesulitan lain: Ulasan Hizb (level 1–4) memakai tingkat 3, 5, 7, 8. Level 9 (ulasan) memakai tingkat 4, 6, 7, 8 per lesson.

### Ujian akhir dan penyelesaian unit

- **Ujian akhir** = lesson ke-4 pada level terakhir unit (Thumn dan Juz: level 8, Hizb: level 4): **15 soal, lulus dengan skor ≥ 80%**.
- Unit dianggap **selesai** bila ujian akhir lulus, atau dilewati lewat tes lompat.
- Lesson lain otomatis lulus asal diselesaikan sebelum nyawa habis.

### Aturan buka-kunci

- Level terbuka berurutan; level berikutnya terbuka setelah keempat lesson level sebelumnya selesai.
- **Thumn pertama tiap juz selalu terbuka**; thumn berikutnya terbuka setelah thumn sebelumnya selesai. Pengguna bebas memilih juz mana pun, tidak harus urut dari juz 1.
- Thumn yang tidak punya ayat atau titik awalnya tidak konsisten tidak memblokir thumn berikutnya.
- Ulasan Hizb terbuka setelah 8 thumn hizb itu selesai. Ulasan Juz terbuka setelah kedua Ulasan Hizb selesai. Ulasan **tidak menahan** thumn berikutnya, dan level 9 juga tidak menahan thumn berikutnya.

### Lompat ke sini

Setiap thumn yang terkunci punya tombol biru "LOMPAT KE SINI?". Tesnya:

- 25 soal dari thumn yang akan dilewati (thumn sebelumnya di juz yang sama yang belum selesai).
- **Semua harus benar.** Satu salah langsung gagal (tanpa nyawa).
- Jika berhasil: thumn yang dilewati ditandai selesai (tanpa bintang, **tanpa chest**), thumn tujuan terbuka, dan pengguna mendapat 20 XP.

---

## Jenis soal

Semua soal Tahfidz dibuat acak di `courses/tahfidz/gen.php`. Bobot kemunculan per level ada di konstanta `LEVEL_TYPES`.

| Kode | Tipe | Deskripsi | Mulai level |
|---|---|---|---|
| `lengkapi` | Lengkapi ayat | Isi 1–3 rumpang dengan kata (ketuk, angka keyboard, atau seret-lepas). Level 1: 1 rumpang, level 2: maks 2, level 3+: maks 3 | 1 |
| `sambung` | Sambung ayat | Lanjutkan ayat dari potongan awal; titik potong di waqaf terdekat dari tengah ayat (cadangan: kata tengah) | 1 |
| `sesudah` | Ayat sesudahnya | Pilih ayat setelah ayat yang ditampilkan | 2 |
| `sebelum` | Ayat sebelumnya | Pilih ayat sebelum ayat yang ditampilkan | 4 |
| `nomor` | Nomor ayat | Ayat → nomor, atau nomor → bunyi ayat | 4 |
| `ikrab` | Harakat akhir | Pilih harakat akhir kata yang ditandai | 4 |
| `match_ends` | Pencocokan awal–akhir | Cocokkan 3–5 awal ayat dengan akhir ayatnya | 6 |
| `match_nomor` | Pencocokan awal–nomor | Cocokkan awal ayat dengan nomor ayatnya (satu surah) | 6 |
| `terakhir` | Kata terakhir | Tebak kata terakhir ayat; pengecoh = kata terakhir ayat lain yang berima mirip | 6 |
| `irab` | I'rab | Kata dengan damir, mis. رَبِّهِمْ / رَبُّهُمْ / رَبَّهُمْ (harakat damir harus menyesuaikan) | 6 |
| `mirip` | Kata mirip | Awalan و/ف, awalan kata kerja ي/ت, dan damir (هم/كم/هن/ها), mis. يعملون / تعملون, مِنْهُمْ / مِنْكُمْ, وَمَا / فَمَا | 6 |

Catatan:

- Kata yang memuat tanda khusus di luar harakat standar dilewati oleh soal `irab` dan `mirip`. Bila tidak ada kata yang cocok, soal otomatis diganti tipe lain.
- Jumlah soal per lesson: `8 + floor(jumlah_ayat/10)`, maksimal 12. Ujian akhir dan lesson ke-4 level ulasan: 15 soal.
- Soal yang dijawab salah diulang di akhir sesi dan tidak mendapat kredit percobaan pertama.

### Keyboard

| Layar | Tombol |
|---|---|
| Soal pilihan | `1`–`9` atau tombol panah memilih, `Enter` memeriksa lalu lanjut |
| Lengkapi ayat | Angka mengisi kotak kosong berikutnya, `Backspace` menghapus, `Enter` memeriksa |
| Pencocokan | `1`–`5` (kolom kiri: akhir/nomor), `Q W E R T` (kolom kanan: awal ayat) |
| Peta belajar | `Enter` memulai dari popup, `Esc` menutup popup |
| Mushaf | `←` halaman berikutnya, `→` halaman sebelumnya (arah baca mushaf) |

---

## Hadiah, nyawa, dan aturan skor

| Hal | Aturan |
|---|---|
| Nyawa | 5 per sesi. Tes lompat: 1 |
| XP lesson | 10 (ulang: 5), bonus +5 bila semua benar di percobaan pertama |
| XP ujian akhir | 20 (ulang: 10), bonus +10. Ujian yang gagal: 0 XP |
| XP tes lompat | +20 bila berhasil |
| Bintang | 100% = 3, ≥ 85% = 2, selainnya = 1 |
| Streak | Naik bila belajar di hari berurutan. Bila kemarin dan hari ini belum belajar, streak yang tampil **hangus (0)** dan mulai lagi dari 1 saat belajar. **Streak terpanjang** tetap tersimpan dan dipakai untuk pencapaian |
| Beruntun | Label "N BERTURUT-TURUT" muncul mulai 2 benar beruntun; bar menjadi oranye mulai 3 |
| Chest | Dua per unit thumn: setelah level 4 dan level 7 selesai. Isi acak **10–20 dirham**, sekali per chest. Hanya terbuka bila lesson level itu benar-benar dikerjakan (bukan lewat lompat) |
| Waktu | Dihitung server dari sesi dimulai sampai selesai, ditampilkan di layar "Lesson selesai" |

Layar "Lesson selesai" menampilkan tiga kartu: Total XP, Waktu, Akurasi, ditambah bintang dan streak.

---

## Mushaf

Menu **Mushaf** (di bawah Belajar) menampilkan ayat seperti halaman mushaf:

- Data pembagian halaman dari `data/mushaf.json` (hasil konversi Excel). Data mencakup **seluruh mushaf: 604 halaman, 3.012 bagian** (132 di antaranya terpotong di tengah ayat).
- Satu halaman = **15 baris** (format Madani). Pemenggalan baris pada ayat dan indeks kata tertentu akan ditambahkan nanti; saat ini tiap bagian mengalir bebas di dalam baris.
- Satu halaman terbagi **5 bagian** (halaman 1–2: 1 bagian) dengan latar **merah, kuning, hijau, biru muda, ungu**. Bagian mengalir di dalam baris, dan pembagiannya boleh jatuh di tengah ayat (diatur kolom `Word_Count`).
- Penanda akhir ayat berbentuk lingkaran bernomor. Spanduk surah (dengan basmalah, kecuali Al-Fatihah dan At-Taubah) muncul di awal surah.
- Tanda **۞** disisipkan di awal tiap thumn bila teks ayat belum memilikinya.
- **Keterangan pembagian** di margin luar halaman, sejajar dengan baris tanda ۞: `1/8 Hizb 1`, `1/4`, `3/8`, `1/2`, `5/8`, `3/4`, `7/8`, dan `Awal Hizb N` untuk thumn pertama. Rumusnya: thumn ke-t dimulai pada (t−1)/8 hizb.
- **1 atau 2 halaman**: mode dua halaman tampil seperti mushaf terbuka (halaman ganjil di kanan, genap di kiri) dan hanya tersedia di layar selebar ≥ 760px.
- **Satu halaman penuh dalam satu layar tanpa scroll**: tinggi halaman dihitung dari ruang layar, ukuran huruf dicari otomatis (pencarian biner, jarak baris dirapatkan bila perlu). Berlaku untuk layar dari 1366×768 sampai 320×568.
- **Navigasi**: tombol "Navigasi" membuka panel dengan tab Surah (+ayat), Juz, Hizb, Thumn, dan Halaman. Ayat tujuan disorot. Tujuan di luar data memberi pesan yang jelas.

---

## Profil dan administrator

### Menu utama

Sidebar kiri (PC, berlabel), sidebar ikon (tablet ≤ 1000px), atau tab bawah (HP ≤ 700px). Isinya mengikuti kursus aktif ditambah **Profil**: untuk Tahfidz **Belajar**, **Mushaf**, **Profil**; untuk Tuḥfatulaṭfāl **Belajar**, **Profil**. Layar kuis dan layar hasil tampil penuh tanpa menu.

### Profil

Banner dan avatar, nama, tanggal bergabung, kartu statistik (streak dengan keterangan "Terpanjang", XP, dirham dengan keterangan "Total didapat", thumn selesai), dan **pencapaian bertingkat**. Pencapaian selalu dihitung dari **nilai tertinggi yang pernah diraih**: streak terpanjang (bukan streak saat ini, yang bisa hangus) dan total dirham yang pernah didapat (tidak berkurang bila dirham kelak dibelanjakan). XP tidak pernah berkurang.


| Pencapaian | Ambang level |
|---|---|
| Api Semangat (streak terpanjang) | 3, 7, 14, 30, 100, 365 hari |
| Penuntut Ilmu (XP) | 100, 500, 1.000, 5.000, 10.000, 30.000 |
| Penghafal (thumn selesai; khusus Tahfidz, disediakan modul kursus) | 1, 4, 8, 16, 48, 160, 480 |
| Pengumpul Dirham (total dirham yang pernah didapat) | 20, 100, 300, 1.000 |

Tombol di profil: Muroja'ah bebas (kuis lama; hanya saat kursus aktif Tahfidz), Laporan masukan (khusus admin), Keluar. Kartu statistik, pencapaian, dan tombol khusus kursus disediakan modul klien masing-masing kursus.

### Halaman admin: `admin.php`

Hanya untuk akun yang namanya tercantum di `ADMIN_USERS` (`src/bootstrap.php`). Isi: ringkasan jumlah masukan, rekap per tipe soal, 10 lesson paling bermasalah, daftar masukan dengan filter (**kursus**, jenis, tipe soal, status, ID unit), tombol "Selesai" untuk menandai sudah ditangani, dan ekspor CSV. Soal dan jawaban pengguna diambil server dari catatan sesi, bukan dari kiriman browser.

---

## Teknologi dan struktur proyek

**PHP** (tanpa framework) · **SQLite** via PDO · **JavaScript** vanilla · **CSS** biasa. Tidak ada langkah build.

Arsitekturnya **platform + course**: platform mengurus akun, XP, streak, dirham, pendaftaran kursus, dan mesin sesi kuis; setiap kursus berisi materi dan aturan belajarnya sendiri.

```
HafidzQuiz/
├── app.php                     Halaman utama (shell HTML; memuat skrip JS otomatis, versi aset otomatis)
├── api.php                     Router API tipis: aksi platform + meneruskan aksi kursus ke modul kursus
├── admin.php                   Laporan masukan pengguna (lintas kursus)
├── quran_json_parser.php       Parser ayat & pemotong ayat (dipakai Tahfidz dan kuis lama)
├── README.md · knowledge.md    Dokumentasi
│
├── src/                        PLATFORM (kode bersama semua kursus)
│   ├── bootstrap.php           Sesi, zona waktu, konstanta (DEV_MODE, ADMIN_USERS, DEFAULT_COURSE), out()/fail()
│   ├── db.php                  Koneksi SQLite, skema, migrasi otomatis
│   ├── courses.php             Registry kursus, pendaftaran (enroll), progres per kursus
│   ├── users.php               Pengguna, streak, award() (pemberian XP)
│   └── quiz.php                Mesin sesi kuis generik: newAttempt, check, finish, feedback
│
├── courses/                    KURSUS (satu folder per kursus)
│   ├── README.md               Panduan menambah kursus baru
│   ├── tahfidz/                Kursus Tahfidz
│   │   ├── course.php          Manifest
│   │   ├── boot.php            Memuat kode kursus
│   │   ├── data.php            Data Al-Qur'an: surah(), thumns(), thumnAyat(), ...
│   │   ├── gen.php             Pembuat soal
│   │   ├── units.php           Unit/level/lesson, chest, lompat, penerapan hasil sesi
│   │   ├── mushaf.php          Logika halaman mushaf
│   │   ├── handlers.php        Aksi API kursus + hook finish & profile
│   │   └── tools/              Alat CLI: csv_ke_thumn.php, xlsx_ke_mushaf.php, cek_thumn.php
│   └── tuhfatulatfal/
│       └── course.php          Manifest (status draft)
│
├── assets/
│   ├── css/app.css             Gaya
│   └── js/
│       ├── core.js             Pembantu DOM, panggilan API, state, keyboard, registry Courses
│       ├── icons.js            Ikon SVG flat
│       ├── quiz.js             Mesin sesi kuis klien
│       ├── shell.js            Menu, bilah atas, pemilih kursus, katalog, layar masuk, profil
│       ├── boot.js             Titik masuk
│       └── courses/
│           ├── tahfidz.js      Modul klien Tahfidz (daftar juz, peta belajar, Mushaf)
│           └── tuhfatulatfal.js  Modul klien Tuḥfatulaṭfāl ("segera hadir")
│
├── data/
│   ├── {1..114}.json           Teks ayat per surah (dari repo asli)
│   ├── thumn.json · thumn.csv  Titik awal 480 thumn (dibaca aplikasi · sumber yang diedit)
│   └── mushaf.json · Mushaf_Page_Division.xlsx   Pembagian halaman mushaf (dibaca aplikasi · sumber yang diedit)
└── storage/                    Dibuat otomatis: hafidz.sqlite, surah_names.json, .htaccess
```

Urutan pemuatan skrip klien: `core → icons → quiz → shell → courses/* → boot`. Kode PHP kursus hanya dimuat saat kursus itu dipakai.

File dari repo asli yang tetap dipakai: `index.php`, `quiz.php`, `generate.php` (kuis lama, kini "Muroja'ah bebas"), dan `assets/js/script.js` serta `assets/css/style.css` milik kuis lama; semuanya tetap di tempatnya.

---

## Instalasi dan menjalankan

**Kebutuhan**: PHP 8.1 atau lebih baru (diuji dengan 8.3) dengan ekstensi `pdo_sqlite`, `mbstring`, `json`, dan sesi. Ekstensi `zip` hanya diperlukan untuk `courses/tahfidz/tools/xlsx_ke_mushaf.php` (tidak diperlukan bila memakai CSV).

```bash
# dari folder proyek
php -S localhost:8080
# lalu buka http://localhost:8080/app.php
```

Di hosting Apache/Nginx: letakkan proyek di folder web, pastikan folder `storage/` dapat ditulis oleh PHP. `storage/.htaccess` menolak akses langsung (Apache); untuk Nginx tambahkan aturan yang menolak `/storage/`.

Langkah pertama:

1. Buka `app.php`, pilih **Daftar**, buat akun (nama pengguna 3–30 karakter: huruf, angka, `_ . -`; kata sandi minimal 6 karakter).
2. Untuk membuka halaman admin, daftar dengan nama pengguna `admin` atau ubah `ADMIN_USERS` di `src/bootstrap.php`.
3. Database SQLite dan tabelnya dibuat otomatis saat pertama kali dipakai. Database lama dimigrasi otomatis, tanpa kehilangan data (lihat di bawah).

Setelah mengubah berkas di `assets/`, `app.php` otomatis menambahkan nomor versi aset (waktu ubah berkas) sehingga browser mengambil berkas terbaru.

### Memperbarui dari versi sebelum multi-kursus

1. **Hapus berkas lama** yang sudah dipindahkan (kalau dibiarkan tidak berbahaya, tetapi membingungkan): `core.php`, `gen.php`, `mushaf.php`, `cek_thumn.php`, `csv_ke_thumn.php`, `xlsx_ke_mushaf.php`, `thumn_cek.csv`, dan `assets/js/app.js`.
2. Unggah seluruh struktur baru (`src/`, `courses/`, `assets/js/*.js` dan `assets/js/courses/`, `api.php`, `admin.php`, `app.php`, `assets/css/app.css`).
3. **Jangan hapus `storage/`**. Saat pertama dibuka, database dimigrasi otomatis: tabel `progress` diubah ke skema per-kursus (kolom `thumn` → `unit` + `course`), semua pengguna lama didaftarkan ke Tahfidz dengan XP lamanya sebagai XP Tahfidz, dan `attempts`/`feedback` lama diberi kursus `tahfidz`. Progres, chest, XP, streak, dan dirham tetap utuh (diuji dengan database format lama).
4. Tekan Ctrl+F5 di browser.

---

## Konfigurasi

Di `src/bootstrap.php`:

| Konstanta | Fungsi |
|---|---|
| `DEV_MODE` (`true`/`false`) | **Mode developer sementara.** Saat `true`, muncul tombol DEV di bilah atas: membuka level terkunci untuk pengetesan, serta tombol "Tandai selesai s.d. level ini" dan "Reset unit" di popup. **Ubah ke `false` sebelum dipakai pengguna sebenarnya**: tombol hilang dan endpoint `dev_*` menolak dengan 403 |
| `ADMIN_USERS` | Daftar nama pengguna yang boleh membuka `admin.php` |
| `DEFAULT_COURSE` | Kursus yang otomatis diikuti pengguna baru (`tahfidz`) |
| `date_default_timezone_set('Asia/Jakarta')` | Zona waktu untuk streak harian |

Di `courses/tahfidz/data.php`: `AYAT_COUNT` (jumlah ayat 114 surah, untuk validasi data thumn).

---

## Data

### Teks ayat: `data/{nomor surah}.json`

Larik objek ayat. Bidang yang dipakai:

```json
{
  "id": 1, "surah_id": 1, "ayah": 1, "page": 1,
  "arabic": "بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ",
  "arabic_words": ["بِسْمِ", "اللّٰهِ", "الرَّحْمٰنِ", "الرَّحِيْمِ"],
  "surah": { "transliteration": "Al-Fatihah", "num_ayah": 7, "arabic": " الفاتحة" }
}
```

`arabic_words` boleh kosong (diganti pemecahan berdasarkan spasi). "Kata" dihitung hanya token yang memuat ≥ 2 huruf dasar; tanda waqaf yang berdiri sendiri bukan kata dan ikut kata sebelumnya.

### Thumn: `data/thumn.json`

480 entri, **urutan file = urutan hizb/thumn**. Kunci: `hizb_number`, `thumn`, `sura_number`, `starting_aya`, `startingPage` (+ `juz`, `nisf`, `rub`). Rentang ayat sebuah thumn = dari titik awalnya sampai sebelum titik awal thumn berikutnya yang valid. Entri yang ayatnya tidak ada atau urutannya melanggar dikecualikan (algoritma rantai naik terpanjang) dan ditandai di peta; ayatnya digabung ke thumn sebelumnya.

Cara memperbarui: edit `data/thumn.csv` (pemisah `;` atau `,`; kolom `No;Juz;Hizb;Nisf;Rub;Thumn;No_Surah;Ayat_Awal`), lalu:

```bash
php courses/tahfidz/tools/csv_ke_thumn.php        # validasi dulu; tidak menimpa thumn.json bila ada kesalahan
php courses/tahfidz/tools/cek_thumn.php           # diagnostik tambahan (opsional)
```

### Mushaf: `data/mushaf.json`

Dibuat dari Excel. Format Excel (`data/Mushaf_Page_Division.xlsx`), satu baris = satu bagian:

| Kolom | Arti |
|---|---|
| `No` | Nomor urut |
| `Page` | Nomor halaman (boleh rumus) |
| `Section` | Nomor bagian di halaman (boleh rumus) |
| `Surah`, `Ayat_End` | Ujung bagian itu |
| `Word_Count` | Opsional. Bagian berakhir **setelah kata ke-N** ayat `Ayat_End`, dihitung **kumulatif dari awal ayat**. Kosong = berakhir di akhir ayat |

Contoh: ayat 2:102 terpotong tiga → baris dengan `Word_Count` 23, baris dengan 51, lalu baris tanpa `Word_Count`.

Berkas Excel pengguna memiliki **3 sheet**; konverter hanya membaca **sheet pertama** (Sheet1: pembagian halaman, kini lengkap 604 halaman). **Sheet2** (topik/subtopik per ayat, baru 2:6–5:110) dan **Sheet3** (tema per ayat, baru 2:6–5:119) disiapkan untuk fitur berikutnya dan **belum dipakai aplikasi**.

```bash
php courses/tahfidz/tools/xlsx_ke_mushaf.php                 # membaca data/Mushaf_Page_Division.xlsx
php courses/tahfidz/tools/xlsx_ke_mushaf.php data/file.csv   # atau CSV
```

Skrip memvalidasi: halaman berurutan tanpa lubang, bagian berurutan, ayat ada, posisi tidak mundur, `Word_Count` kumulatif dan kurang dari jumlah kata ayat. Hasilnya mencetak ringkasan dan daftar bagian yang terpotong di tengah ayat untuk dicek ulang.

---

## API

Semua lewat `api.php?a=<aksi>`; kecuali `register`/`login`, semuanya butuh login. Aksi **platform** ditangani `api.php`; aksi **khusus kursus** diteruskan ke modul kursus lewat manifestnya dan menerima parameter `?course=<id>` (klien otomatis mengirim kursus aktif). Pengguna yang belum bergabung di kursus itu ditolak (403). Respons JSON; kesalahan berbentuk `{"error": "..."}` dengan kode HTTP yang sesuai.

| Aksi | Metode | Fungsi |
|---|---|---|
**Aksi platform**

| Aksi | Metode | Fungsi |
|---|---|---|
| `register`, `login`, `logout`, `me` | POST/GET | Akun dan sesi (`me`/`login` memuat `course`, `courses`, `xp` tiap kursus) |
| `courses` | GET | Kursus yang diikuti (`mine`), yang tersedia (`available`), dan kursus aktif (`current`) |
| `course_enroll` | POST | Bergabung ke kursus (`course`); langsung menjadi kursus aktif. 404 bila tak dikenal, 409 bila sudah bergabung |
| `course_switch` | POST | Pindah ke kursus yang sudah diikuti (`course`). 403 bila belum bergabung |
| `check` | POST | Memeriksa satu jawaban (atau satu pasangan) di server |
| `finish` | POST | Menutup sesi; skor dan waktu dihitung server, hasil diterapkan oleh kursus pemilik sesi (hook `finish`) |
| `feedback` | POST | Menyimpan masukan (soal diambil dari sesi di server; tersimpan dengan kursusnya) |
| `profile` | GET | Statistik profil + daftar kursus beserta statistiknya (hook `profile`) |

**Aksi kursus Tahfidz** (`courses/tahfidz/handlers.php`)

| Aksi | Metode | Fungsi |
|---|---|---|
| `juz` | GET | Daftar 30 juz dan progresnya |
| `units&juz=N` | GET | Unit, level, chest, tombol lompat untuk satu juz |
| `quiz&unit=U&level=L&lesson=S` | GET | Membuat sesi kuis; mengirim soal **tanpa kunci** dan token `attempt` |
| `quiz&unit=U&jump=1` | GET | Sesi tes lompat 25 soal |
| `claim` | POST | Membuka chest (`unit`, `chest` = 4 atau 7) |
| `mushaf_meta`, `mushaf&page=N`, `mushaf_find` | GET | Data dan navigasi mushaf |
| `dev_mark`, `dev_reset` | POST | Alat developer (hanya bila `DEV_MODE` = true) |

ID unit Tahfidz: `0–479` = thumn · `1001–1060` = Ulasan Hizb (1000 + nomor hizb) · `2001–2030` = Ulasan Juz (2000 + nomor juz).

---

## Basis data

SQLite di `storage/hafidz.sqlite`:

| Tabel | Isi |
|---|---|
| `users` | `id, username, hash, xp, streak, last_day, dirham, created_at, streak_peak, dirham_total, course` (kursus aktif) |
| `enrollments` | `user_id, course, xp, enrolled_at, last_active`: kursus yang diikuti dan XP per kursus |
| `progress` | `user_id, course, unit (= ID unit di dalam kursus), node, stars, best`. Kunci `node` (Tahfidz): `V{level}.{lesson}` (lesson selesai), `C4`/`C7` (chest terbuka; jumlah dirham di kolom `stars`), `JUMP` (unit dilewati) |
| `attempts` | Sesi kuis aktif (dengan `course`): soal beserta kunci jawaban, status tiap soal, nyawa, waktu mulai. Dibersihkan setelah 24 jam |
| `feedback` | Masukan pengguna (dengan `course`): jenis, alasan, catatan, soal dan jawaban, status "selesai ditangani" |

---

## Keamanan dan penilaian di server

- Soal dikirim ke browser **tanpa** kunci jawaban, ayat lengkap, dan id pasangan. Tiap jawaban diperiksa lewat `check`; skor dihitung dari catatan server saat `finish`.
- Sesi hanya bisa diselesaikan sekali, tidak bisa dipakai pengguna lain, dan ditolak bila masih ada soal belum selesai atau nyawa habis.
- Kata sandi disimpan dengan `password_hash`. Cookie sesi `HttpOnly` dan `SameSite=Lax`. Halaman admin memakai token CSRF.
- `display_errors` dimatikan di API dan keluaran liar dibuang sebelum JSON dikirim.

Keterbatasan yang diketahui: tidak ada pembatasan laju (rate limiting) pada login; peran admin ditentukan lewat daftar nama pengguna; `DEV_MODE` harus dimatikan di produksi.

---

## Pengujian

Pengujian dilakukan otomatis dengan Chromium headless (Playwright) dan skrip API. Data thumn dan pembagian mushaf adalah data asli pengguna; teks ayat untuk uji berasal dari ayat tiruan atau dari paket npm `quran-json` (CC-BY-4.0, dipakai **hanya untuk pengujian lokal**, tidak termasuk dalam proyek). Yang sudah terbukti: seluruh alur kuis dan penilaian server (termasuk upaya curang), semua tipe soal, aturan buka-kunci, chest, tes lompat, masukan dan halaman admin, streak yang hangus dan nilai tertinggi untuk pencapaian, konverter Excel/CSV (termasuk data rusak), dan untuk Mushaf: seluruh 6.236 ayat muncul tepat sekali dan tersusun kembali persis, 6.234 ayat berhalaman sama dengan data mushaf Hafs (selisih di 2:48 dan 69:8 mengikuti datamu), 132 nilai `Word_Count` valid terhadap jumlah kata ayat, 480 label margin pembagian benar dan sejajar dengan baris ۞ (selisih ≤ 0,16px), serta **seluruh 604 halaman muat tanpa scroll** di 8 ukuran layar (1 halaman) dan 302 spread di 4 ukuran layar (2 halaman).

Multi-kursus diuji dengan database format lama (migrasi tanpa kehilangan data), pendaftaran dan perpindahan kursus (termasuk penolakan 403/404/409), isolasi progres antar kursus, XP per kursus, serta alur belajar lengkap di browser pada layar 1100px (dropdown, katalog, satu lesson sampai layar hasil, profil, Mushaf). Posisi dropdown kursus diuji di lima lebar layar (320–1100px) dan di tema terang maupun gelap.

Belum diuji: data ayat asli lengkap (114 surah) di dalam aplikasi, seret-lepas di layar sentuh asli, Safari dan Firefox, serta hosting sebenarnya.

---

## Batasan dan rencana

- Pemenggalan baris mushaf (15 baris per halaman) dan perombakan aturan pembagian Hizb/Juz menunggu arahan pengguna; sheet topik dan tema di Excel belum dipakai.
- **Tuḥfatulaṭfāl** baru berupa kerangka (status draft, halaman "segera hadir"); materi dan format belajarnya menunggu detail teknis dari pengguna. Rencana kursus lain: syair dan aksara, dengan pembuat soalnya sendiri di atas kerangka kursus yang sama.
- Ide berikutnya: mode review untuk ayat yang sering salah, audio murottal per ayat, tata letak peta di samping untuk PC, toko untuk membelanjakan dirham, papan peringkat.
