# HafidzQuiz

Aplikasi web berbentuk kuis ala Duolingo untuk **menghafal Al-Qur'an**. Responsif (PC dan HP), memakai akun pengguna untuk menyimpan XP dan capaian, dan dirancang agar course lain (hafalan matan manzumah, syair, belajar aksara) bisa ditambahkan nanti. Tahap ini baru berisi course **Hafalan Al-Qur'an**.

> Dokumen ini dan `knowledge.md` diperbarui setiap ada permintaan baru.
> Terakhir diperbarui: 7 November 2026.

---

## Daftar isi

1. [Fitur](#fitur)
2. [Struktur belajar](#struktur-belajar)
3. [Jenis soal](#jenis-soal)
4. [Hadiah, nyawa, dan aturan skor](#hadiah-nyawa-dan-aturan-skor)
5. [Mushaf](#mushaf)
6. [Profil dan administrator](#profil-dan-administrator)
7. [Teknologi dan struktur proyek](#teknologi-dan-struktur-proyek)
8. [Instalasi dan menjalankan](#instalasi-dan-menjalankan)
9. [Konfigurasi](#konfigurasi)
10. [Data](#data)
11. [API](#api)
12. [Basis data](#basis-data)
13. [Keamanan dan penilaian di server](#keamanan-dan-penilaian-di-server)
14. [Pengujian](#pengujian)
15. [Batasan dan rencana](#batasan-dan-rencana)

---

## Fitur

- **Belajar bertingkat**: Juz → Hizb → Thumn (480 thumn). Setiap thumn punya 8 level × 4 lesson, bukan sekadar daftar soal.
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

## Struktur belajar

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

Semua soal dibuat acak di `gen.php`. Bobot kemunculan per level ada di konstanta `LEVEL_TYPES`.

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
| Streak | Naik bila belajar di hari berurutan; reset ke 1 bila terlewat sehari |
| Beruntun | Label "N BERTURUT-TURUT" muncul mulai 2 benar beruntun; bar menjadi oranye mulai 3 |
| Chest | Dua per unit thumn: setelah level 4 dan level 7 selesai. Isi acak **10–20 dirham**, sekali per chest. Hanya terbuka bila lesson level itu benar-benar dikerjakan (bukan lewat lompat) |
| Waktu | Dihitung server dari sesi dimulai sampai selesai, ditampilkan di layar "Lesson selesai" |

Layar "Lesson selesai" menampilkan tiga kartu: Total XP, Waktu, Akurasi, ditambah bintang dan streak.

---

## Mushaf

Menu **Mushaf** (di bawah Belajar) menampilkan ayat seperti halaman mushaf:

- Data pembagian halaman dari `data/mushaf.json` (hasil konversi Excel). Saat ini tersedia **halaman 1–41** (Juz 1 dan 2); data akan ditambah.
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

Dua menu di sidebar kiri (PC, berlabel), sidebar ikon (tablet ≤ 1000px), atau tab bawah (HP ≤ 700px): **Belajar**, **Mushaf**, **Profil**. Layar kuis dan layar hasil tampil penuh tanpa menu.

### Profil

Banner dan avatar, nama, tanggal bergabung, kartu statistik (streak, XP, dirham, thumn selesai), dan **pencapaian bertingkat**:

| Pencapaian | Ambang level |
|---|---|
| Api Semangat (streak) | 3, 7, 14, 30, 100, 365 hari |
| Penuntut Ilmu (XP) | 100, 500, 1.000, 5.000, 10.000, 30.000 |
| Penghafal (thumn selesai) | 1, 4, 8, 16, 48, 160, 480 |
| Pengumpul Dirham | 20, 100, 300, 1.000 |

Tombol di profil: Muroja'ah bebas (kuis lama), Laporan masukan (khusus admin), Keluar.

### Halaman admin: `admin.php`

Hanya untuk akun yang namanya tercantum di `ADMIN_USERS` (`core.php`). Isi: ringkasan jumlah masukan, rekap per tipe soal, 10 lesson paling bermasalah, daftar masukan dengan filter (jenis, tipe soal, status, ID unit), tombol "Selesai" untuk menandai sudah ditangani, dan ekspor CSV. Soal dan jawaban pengguna diambil server dari catatan sesi, bukan dari kiriman browser.

---

## Teknologi dan struktur proyek

**PHP** (tanpa framework) · **SQLite** via PDO · **JavaScript** vanilla · **CSS** biasa. Tidak ada langkah build.

```
app.php                    Halaman utama (shell HTML; nomor versi aset otomatis)
api.php                    API JSON (semua aksi)
core.php                   Sesi, database, pemuatan data Al-Qur'an dan thumn, konfigurasi
gen.php                    Pembuat soal
mushaf.php                 Logika halaman mushaf dan pencarian posisi
quran_json_parser.php      Parser data ayat dan pemotong ayat (kompatibel dengan versi lama)
admin.php                  Laporan masukan pengguna
xlsx_ke_mushaf.php         Konverter Excel/CSV → data/mushaf.json (+ validasi)
csv_ke_thumn.php           Konverter CSV → data/thumn.json (+ validasi)
cek_thumn.php              Diagnostik thumn.json (hapus setelah selesai dipakai)
assets/js/app.js           Seluruh antarmuka
assets/css/app.css         Gaya
data/{1..114}.json         Teks ayat per surah (dari repo asli)
data/thumn.json            Titik awal 480 thumn (dibaca aplikasi)
data/thumn.csv             Sumber yang diedit untuk thumn.json
data/mushaf.json           Pembagian halaman mushaf (dibaca aplikasi)
data/Mushaf_Page_Division.xlsx   Sumber yang diedit untuk mushaf.json
storage/                   Dibuat otomatis: hafidz.sqlite, surah_names.json, .htaccess
```

File dari repo asli yang tetap dipakai: `index.php`, `quiz.php`, `generate.php` (kuis lama, kini "Muroja'ah bebas"), dan `assets/js/script.js` serta `assets/css/style.css` milik kuis lama. `thumn_cek.csv` adalah sisa keluaran lama dan boleh dihapus.

---

## Instalasi dan menjalankan

**Kebutuhan**: PHP 8.1 atau lebih baru (diuji dengan 8.3) dengan ekstensi `pdo_sqlite`, `mbstring`, `json`, dan sesi. Ekstensi `zip` hanya diperlukan untuk `xlsx_ke_mushaf.php` (tidak diperlukan bila memakai CSV).

```bash
# dari folder proyek
php -S localhost:8080
# lalu buka http://localhost:8080/app.php
```

Di hosting Apache/Nginx: letakkan proyek di folder web, pastikan folder `storage/` dapat ditulis oleh PHP. `storage/.htaccess` menolak akses langsung (Apache); untuk Nginx tambahkan aturan yang menolak `/storage/`.

Langkah pertama:

1. Buka `app.php`, pilih **Daftar**, buat akun (nama pengguna 3–30 karakter: huruf, angka, `_ . -`; kata sandi minimal 6 karakter).
2. Untuk membuka halaman admin, daftar dengan nama pengguna `admin` atau ubah `ADMIN_USERS` di `core.php`.
3. Database SQLite dan tabelnya dibuat otomatis saat pertama kali dipakai. Database lama dimigrasi otomatis (kolom baru ditambahkan).

Setelah mengubah `app.js` atau `app.css`, `app.php` otomatis menambahkan nomor versi aset sehingga browser mengambil berkas terbaru.

---

## Konfigurasi

Di `core.php`:

| Konstanta | Fungsi |
|---|---|
| `DEV_MODE` (`true`/`false`) | **Mode developer sementara.** Saat `true`, muncul tombol DEV di bilah atas: membuka level terkunci untuk pengetesan, serta tombol "Tandai selesai s.d. level ini" dan "Reset unit" di popup. **Ubah ke `false` sebelum dipakai pengguna sebenarnya**: tombol hilang dan endpoint `dev_*` menolak dengan 403 |
| `ADMIN_USERS` | Daftar nama pengguna yang boleh membuka `admin.php` |
| `date_default_timezone_set('Asia/Jakarta')` | Zona waktu untuk streak harian |
| `AYAT_COUNT` | Jumlah ayat 114 surah (untuk validasi) |

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
php csv_ke_thumn.php        # validasi dulu; tidak menimpa thumn.json bila ada kesalahan
php cek_thumn.php           # diagnostik tambahan (opsional)
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

```bash
php xlsx_ke_mushaf.php                 # membaca data/Mushaf_Page_Division.xlsx
php xlsx_ke_mushaf.php data/file.csv   # atau CSV
```

Skrip memvalidasi: halaman berurutan tanpa lubang, bagian berurutan, ayat ada, posisi tidak mundur, `Word_Count` kumulatif dan kurang dari jumlah kata ayat. Hasilnya mencetak ringkasan dan daftar bagian yang terpotong di tengah ayat untuk dicek ulang.

---

## API

Semua lewat `api.php?a=<aksi>`; kecuali `register`/`login`, semuanya butuh login. Respons JSON; kesalahan berbentuk `{"error": "..."}` dengan kode HTTP yang sesuai.

| Aksi | Metode | Fungsi |
|---|---|---|
| `register`, `login`, `logout`, `me` | POST/GET | Akun dan sesi |
| `juz` | GET | Daftar 30 juz dan progresnya |
| `units&juz=N` | GET | Unit, level, chest, tombol lompat untuk satu juz |
| `quiz&unit=U&level=L&lesson=S` | GET | Membuat sesi kuis; mengirim soal **tanpa kunci** dan token `attempt` |
| `quiz&unit=U&jump=1` | GET | Sesi tes lompat 25 soal |
| `check` | POST | Memeriksa satu jawaban (atau satu pasangan) di server |
| `finish` | POST | Menutup sesi; skor, XP, dan waktu dihitung server |
| `claim` | POST | Membuka chest (`unit`, `chest` = 4 atau 7) |
| `feedback` | POST | Menyimpan masukan (soal diambil dari sesi di server) |
| `profile` | GET | Statistik profil |
| `mushaf_meta`, `mushaf&page=N`, `mushaf_find` | GET | Data dan navigasi mushaf |
| `dev_mark`, `dev_reset` | POST | Alat developer (hanya bila `DEV_MODE` = true) |

ID unit: `0–479` = thumn · `1001–1060` = Ulasan Hizb (1000 + nomor hizb) · `2001–2030` = Ulasan Juz (2000 + nomor juz).

---

## Basis data

SQLite di `storage/hafidz.sqlite`:

| Tabel | Isi |
|---|---|
| `users` | `id, username, hash, xp, streak, last_day, dirham, created_at` |
| `progress` | `user_id, thumn (= ID unit), node, stars, best`. Kunci `node`: `V{level}.{lesson}` (lesson selesai), `C4`/`C7` (chest terbuka; jumlah dirham di kolom `stars`), `JUMP` (unit dilewati) |
| `attempts` | Sesi kuis aktif: soal beserta kunci jawaban, status tiap soal, nyawa, waktu mulai. Dibersihkan setelah 24 jam |
| `feedback` | Masukan pengguna: jenis, alasan, catatan, soal dan jawaban, status "selesai ditangani" |

---

## Keamanan dan penilaian di server

- Soal dikirim ke browser **tanpa** kunci jawaban, ayat lengkap, dan id pasangan. Tiap jawaban diperiksa lewat `check`; skor dihitung dari catatan server saat `finish`.
- Sesi hanya bisa diselesaikan sekali, tidak bisa dipakai pengguna lain, dan ditolak bila masih ada soal belum selesai atau nyawa habis.
- Kata sandi disimpan dengan `password_hash`. Cookie sesi `HttpOnly` dan `SameSite=Lax`. Halaman admin memakai token CSRF.
- `display_errors` dimatikan di API dan keluaran liar dibuang sebelum JSON dikirim.

Keterbatasan yang diketahui: tidak ada pembatasan laju (rate limiting) pada login; peran admin ditentukan lewat daftar nama pengguna; `DEV_MODE` harus dimatikan di produksi.

---

## Pengujian

Pengujian dilakukan otomatis dengan Chromium headless (Playwright) dan skrip API, memakai **data tiruan**: ayat Al-Baqarah yang diketik manual dengan panjang realistis, dan data thumn serta mushaf asli. Yang sudah terbukti: seluruh alur kuis dan penilaian server (termasuk upaya curang), semua tipe soal, aturan buka-kunci, chest, tes lompat, masukan dan halaman admin, konverter Excel/CSV (termasuk data rusak), 259 ayat terpetakan ke halaman yang cocok dengan data mushaf Hafs, label margin pembagian, mode 1 dan 2 halaman, serta 41 halaman muat tanpa scroll di 11 ukuran layar.

Belum diuji: data ayat asli lengkap (114 surah) di dalam aplikasi, seret-lepas di layar sentuh asli, Safari dan Firefox, serta hosting sebenarnya.

---

## Batasan dan rencana

- Data mushaf baru sampai halaman 41 (Juz 1–2); halaman lain menampilkan pesan "belum tersedia".
- Hanya course Al-Qur'an. Rencana: course matan manzumah, syair, dan aksara dengan generator soalnya sendiri di atas kerangka unit/level/lesson yang sama.
- Ide berikutnya: mode review untuk ayat yang sering salah, audio murottal per ayat, tata letak peta di samping untuk PC, toko untuk membelanjakan dirham, papan peringkat.