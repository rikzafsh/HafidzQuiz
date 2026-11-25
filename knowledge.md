# Knowledge HafidzQuiz

Catatan semua informasi, pengetahuan, kebutuhan, dan keputusan yang diberikan pengguna selama pengembangan HafidzQuiz. Dipakai sebagai ingatan proyek: siapa pun (atau sesi berikutnya) yang melanjutkan pengembangan bisa memahami *mengapa* aplikasi dibuat begini.

> **Aturan pemeliharaan:** setiap kali pengguna meminta sesuatu, dokumen ini dan `README.md` diperbarui (permintaan baru dicatat di bagian 11, informasi baru masuk ke bagian yang sesuai, pertanyaan yang terjawab dipindahkan dari bagian 9).
> Terakhir diperbarui: 25 November 2026 (setelah permintaan P19).

**Cara membaca:** bagian 1–7 adalah sumber kebenaran dari pengguna. Bagian 8 berisi asumsi Claude yang **belum dikonfirmasi** dan boleh diubah kapan saja. Rujukan `[P#]` mengarah ke riwayat permintaan di bagian 11.

---

## 1. Tentang proyek

| Hal | Isi |
|---|---|
| Nama | **HafidzQuiz** |
| Tujuan | Aplikasi kuis seperti Duolingo untuk membantu **menghafal Al-Qur'an** [P1] |
| Platform | Web, fleksibel dan responsif: mudah diakses di PC maupun HP [P1] |
| Course (kursus) | Rencana awal: hafalan **matan manzumah**, **syair**, dan belajar **aksara**; tahap awal hanya Al-Qur'an [P1]. Sejak P19 aplikasi **multi-kursus**: course yang ada bernama **"Tahfidz"** (hafalan Al-Qur'an), ditambah **"Tuḥfatulaṭfāl"** (hafalan matan manzumah Tuḥfatulaṭfāl; materi dan detail teknisnya menyusul) [P19] |
| Titik awal | Repo `https://github.com/rikzafsh/HafidzQuiz` berisi kuis dummy (PHP + JS) yang dikembangkan menjadi aplikasi ala Duolingo [P1] |
| Pembagian materi | Per **thumn**, sesuai `thumn.json` [P1] |
| Akun | Wajib login untuk menyimpan data dan capaian/XP [P2] |
| Bahasa | Antarmuka dan komunikasi dalam bahasa Indonesia. Pengguna menyebut berkas sebagai "fail" |
| Cara kerja sama | Pengguna memberi screenshot Duolingo sebagai referensi, data (JSON, CSV, Excel), dan daftar perbaikan. Untuk keputusan teknis ia sering menyerahkan pilihan ("silakan putuskan", "bebas") dan meminta Claude bertanya bila perlu [P1, P9, P15] |

---

## 2. Data yang diberikan pengguna

| Sumber | Isi | Catatan |
|---|---|---|
| Repo GitHub (kuis dummy) | `index.php`, `quiz.php`, `generate.php`, `quran_json_parser.php`, folder `data/` dan `assets/` | Kuis lama punya 6 tipe soal: sambung ayat, lengkapi ayat, ayat sebelum, ayat sesudah, tebak nomor/lafaz, dan ikrab. Kini dipertahankan sebagai "Muroja'ah bebas" |
| `1.json` (contoh Al-Fatihah) [P2] | Format data ayat per surah | Bidang: `id, surah_id, ayah, page, quarter_hizb, juz, manzil, arabic, kitabah, latin, arabic_words, translation, footnotes, updated_at, surah{id, arabic, latin, transliteration, translation, num_ayah, page, location}`. Ayat 1 Al-Fatihah adalah basmalah |
| `thumn.json` (asli) [P2, P8] | 480 titik awal thumn | Bidang: `hizb_number, thumn, sura_number, starting_aya, startingPage`. Berisi **12 salah ketik** (lihat bagian 10). Sudah digantikan CSV di bawah |
| Kode `QuranJsonParser` [P3] | Parser data ayat dan `splitAyahForQuestion` | Pengguna mengizinkan modifikasi agar lebih fleksibel dan efisien. Pola waqaf di kode aslinya: `ۖۗۘۙۚۛۜ`, `ࣖ`, dan karakter `ۮ` yang sebenarnya bukan tanda waqaf |
| Keluaran `cek_thumn.php` [P7] | 9 masalah pada thumn.json | Dipakai untuk melacak data bermasalah |
| **`thumn_fix.csv`** [P9] | **Data thumn hasil perbaikan pengguna: sumber kebenaran terbaru** | Pemisah `;`, UTF-8 BOM, CRLF. Kolom: `No;Juz;Hizb;Nisf;Rub;Thumn;No_Surah;Ayat_Awal`. 480 baris. Dua baris (No 166 dan 291) punya penanda `///` di kolom terakhir yang belum dijelaskan |
| **`Mushaf_Page_Division.xlsx`** [P15] | Pemetaan halaman mushaf yang dibuat pengguna | Kolom: `No, Page, Section, Surah, Ayat_End, Word_Count`. Kolom Page dan Section berupa rumus (`=B4+1`, `=C4`, merujuk 5 baris di atas). Mencakup halaman 1–41. Versi awal (41 halaman); **digantikan oleh versi lengkap di P18** |
| **`Mushaf_Page_Division_1.xlsx`** [P18] | **Versi lengkap pemetaan halaman mushaf**, menggantikan versi 41 halaman | 3 sheet. **Sheet1**: pembagian halaman (kolom sama; kini berisi nilai langsung tanpa rumus), **604 halaman / 3.012 bagian**. **Sheet2** `No, Surah, Ayah, Topic, Subtopic`: topik/subtopik per ayat (baru 2:6–5:110). **Sheet3** `No, Surah, Ayat, Theme`: tema per ayat (baru 2:6–5:119). Sheet2 dan Sheet3 untuk fitur yang akan dijelaskan pengguna nanti. Di proyek disimpan sebagai `data/Mushaf_Page_Division.xlsx` |
| Screenshot | Referensi tampilan | Lihat bagian 5 |

Yang tidak dapat diakses Claude: folder `data/` di GitHub (diblokir untuk akses otomatis). Karena itu data ayat asli milik pengguna belum pernah dipakai untuk pengujian; uji memakai ayat tiruan dan, sejak P18, teks Arab dari paket npm `quran-json` (CC-BY-4.0) hanya untuk pengujian lokal (tidak termasuk dalam proyek).

---

## 3. Pengetahuan domain dari pengguna

### Pembagian Al-Qur'an
- **Thumn** = 1/8 hizb. Satu hizb = 8 thumn, bernomor 1–8. Ada 60 hizb, 30 juz (1 juz = 2 hizb), sehingga **480 thumn** [P1, P6].
- Kolom `Nisf` dan `Rub` pada CSV pengguna: **nisf** = ½ hizb (nisf 1 = thumn 1–4, nisf 2 = thumn 5–8); **rub** = ¼ hizb (rub 1–4; tiap rub = 2 thumn) [P9].
- Pengguna menyebut akhir **rub dan nisf** sebagai titik ulasan. Thumn 2 dan 6 mengakhiri rub, Thumn 4 mengakhiri nisf, dan Thumn 8 mengakhiri hizb [P12, P13, P14].
- Notasi keterangan pembagian di margin mushaf (contoh dari pengguna): `1/8 Hizb 1`, `1/4 Hizb 1`, `3/8 Hizb 1`, `1/2 Hizb 1`, dst. [P16].
- Tanda **۞** adalah tanda rub el hizb. Pada mushaf HafidzQuiz ia dipasang di awal tiap thumn jika belum ada [P15, P16].
- Thumn 1 hizb 1 dimulai di 1:1. Pengguna menyatakan bahwa Al-Baqarah 1–17 "masih termasuk thumn 1" [P7]. Catatan: CSV akhir memulai Thumn 2 di 2:17 (lihat bagian 9, butir 5).

- Pengguna akan **merombak aturan pembagian Hizb dan Juz** [P18]; arahan lebih lanjut menyusul. (Saat ini juz diturunkan dari hizb: `juz = ceil(hizb/2)`.)

### Mushaf (dari data Excel pengguna)
- Satu baris Excel = satu **bagian** halaman. `Surah` + `Ayat_End` = ujung bagian itu; awal bagian = setelah ujung bagian sebelumnya [P15].
- Satu halaman terbagi **5 bagian**, dan pembagiannya boleh jatuh **di tengah ayat** [P15]. Dalam data, halaman 1 dan 2 hanya punya 1 bagian.
- `Word_Count` (opsional) menandai bagian yang berakhir di tengah ayat: bagian berakhir setelah kata ke-N ayat `Ayat_End`. Dalam data pengguna ayat 2:102 terpotong tiga (kata ke-23, kata ke-51, lalu sisanya), jadi hitungannya **kumulatif dari awal ayat**.
- Bagian mengalir di dalam baris dan diberi latar berbeda: **merah, kuning, hijau, biru muda, ungu** [P15].
- Pengguna menulis "Dalam satu halaman mushaf dibagi menjadi 15 halaman" [P15], lalu menjelaskan bahwa yang dimaksud adalah **15 baris setiap halaman** (format Madani) [P18]. Pengguna akan menetapkan sendiri **pemenggalan baris** pada ayat dan indeks kata tertentu; arahannya menyusul.

### Streak, XP, dan pencapaian [P18]
- **Streak** (runtutan hari belajar) **hangus/tereset** bila runtutan terputus. Meski begitu, pencapaian berbasis streak tetap memakai **streak tertinggi** yang pernah diraih.
- **Dirham**: pencapaian dihitung dari **total dirham tertinggi yang pernah didapat**.
- Klarifikasi: pesan pertama menyebut XP sebagai contoh, lalu dikoreksi bahwa yang dimaksud adalah **streak**. **XP tidak hangus**; tidak ada aturan XP tereset.

### Kata, bacaan, dan contoh soal dari pengguna
Contoh kata mirip yang sulit dibedakan, untuk soal level akhir [P10]:

| Jenis | Contoh dari pengguna |
|---|---|
| Harakat/i'rab | رَبِّهِمْ vs رَبُّهُمْ |
| Awalan kata kerja | يعملون vs تعلمون |
| Damir | مِنْكُمْ vs مِنْهُمْ |
| Partikel | وَمَا vs فَمَا |
| Kata terakhir ayat | Pengecoh berupa kata terakhir dari ayat lain yang mirip |

---

## 4. Kebutuhan fungsional dari pengguna

### 4.1 Struktur belajar
- Belajar dibagi per thumn; tiap unit thumn punya lesson dan tes seperti Duolingo; soal tes diacak tetapi tetap dalam cakupan thumn itu [P1].
- Pengguna **bebas memilih juz** mana saja, tidak harus urut dari juz 1; juz yang belum tuntas tidak menghalangi memilih juz lain; thumn berikutnya di dalam juz tetap terkunci sampai yang sebelumnya selesai [P2].
- Node berisi 3–5 lesson [P6] → diubah: **1 thumn = 8 level** dan **tiap level = 4 lesson** (total 8×4 per thumn) [P10, P11].
- Level awal: soal mudah (melengkapi, menyambung ayat, lalu menebak ayat sesudahnya). Level tengah: ditambah ayat sebelumnya, nomor ayat, dan harakat. Level akhir: semua tipe sebelumnya ditambah lima tipe baru [P10].
- Lima tipe baru level akhir [P10]: (1) pencocokan beberapa kata awal ayat dengan beberapa kata akhir ayat; (2) pencocokan kata awal ayat dengan nomor ayat; (3) harakat yang lebih sulit terkait i'rab; (4) tebak kata dengan damir/partikel yang mirip; (5) tebak kata terakhir ayat dengan pengecoh kata terakhir ayat lain yang mirip.
- Di akhir tiap **hizb** ada satu unit ulasan yang soalnya mencakup satu hizb; di akhir tiap **juz** ada satu unit ulasan yang mencakup satu juz [P11]. Susunan per juz: 2×8 thumn + 2 ulasan hizb + 1 ulasan juz (pengguna menulis "2 akhir thumn"; dikonfirmasi maksudnya ulasan hizb) [P11, P12].
- **Ulasan Hizb dipersingkat jadi 4 level; Ulasan Juz tetap 8 level** [P12].
- Di akhir rub/nisf ada **satu level tambahan (level 9) berisi 4 lesson ulasan** dari awal hizb sampai titik itu, terpisah dari 8 level utama (bukan lesson tambahan di tiap level) [P12, P13].
- **Thumn 8 tidak perlu diberi ulasan** [P14].
- **Lompat ke sini**: pengguna bisa langsung melompat ke thumn tertentu dengan tes **25 soal yang harus dijawab benar semua** [P13].

### 4.2 Mekanik permainan
- Tiap thumn punya **dua chest** yang bisa dibuka setelah menyelesaikan **level 4 dan level 7**. Isinya soft-currency **dirham**, jumlah **acak 10–20** [P12].
- Di atas progress bar ditampilkan **jumlah jawaban benar beruntun** dalam lesson itu [P13].
- Layar "lesson complete" menampilkan **waktu** yang dipakai menyelesaikan satu lesson [P13].
- Popup sebelum memulai: "lesson ke berapa dari berapa" dan **total XP** yang akan didapat [P6].
- **Penilaian/laporan skor dipindahkan ke server** [P14].
- **Pencapaian memakai nilai tertinggi**: streak terpanjang dan total dirham tertinggi yang pernah didapat; streak boleh hangus, pencapaian tidak turun [P18].

### 4.3 Masukan dan administrator
- Setelah memeriksa jawaban ada tombol **"terlalu mudah"**, **"terlalu sulit"**, dan **"laporkan"**. Masukan dikirim, disimpan, dan dapat ditampilkan sebagai laporan untuk **administrator** [P13].

### 4.4 Alat pengembang
- **Tombol developer sementara** untuk membuka level yang terkunci demi pengetesan [P12].

### 4.5 Ergonomi PC
- Soal lengkapi ayat: pilihan kata bisa **seret-lepas** [P4].
- Pilihan bisa dipilih dengan **keyboard**; `Enter` berarti periksa/lanjut tanpa mengklik tombol [P4].

### 4.6 Navigasi dan layout
- Menu utama aplikasi adalah **Belajar**; tambahkan menu **Profil**; tata letak ikon harus responsif (referensi: sidebar kiri di PC, tab bawah di HP) [P14].
- Menu **Mushaf** ditambahkan di bawah Belajar [P15].

### 4.7 Mushaf
- Menampilkan ayat yang disusun seperti halaman mushaf [P15].
- Navigasi ke **surah, ayat, juz, hizb, atau thumn** tertentu [P15].
- Sumber data: Excel pengguna; Claude boleh memutuskan memakai Excel langsung atau mengonversi ke JSON [P15].
- Bagian halaman bewarna dan mengalir di dalam baris; tanda ۞ disisipkan di awal thumn bila belum ada [P15].
- Opsi tampil **dua halaman sekaligus** [P16].
- Keterangan pembagian di pinggir halaman, **pada baris yang sama dengan tanda rub el hizb** [P16].
- **Satu halaman mushaf harus tampil penuh dari atas ke bawah dalam satu layar tanpa scroll** [P16].
- Data pembagian halaman kini **lengkap: 604 halaman** [P18].

### 4.8 Dokumentasi
- Dibuat `README.md` (penjelasan aplikasi) dan `knowledge.md` (semua informasi dari pengguna). **Keduanya diperbarui setiap kali pengguna meminta sesuatu** [P17].

### 4.9 Rencana fitur (arahan menyusul dari pengguna)
- Perombakan aturan pembagian **Hizb dan Juz** [P18].
- **Pemenggalan baris** mushaf (15 baris per halaman) pada ayat dan indeks kata tertentu [P18].
- Fitur yang memakai **Sheet2 (topik/subtopik)** dan **Sheet3 (tema)** pada Excel [P18].


### 4.10 Multi-kursus [P19]
- Pengguna dapat **meng-enroll dirinya ke course yang belum pernah dipelajari** (referensi: pemilih kursus Duolingo, dropdown "MY COURSES" dengan daftar kursus dan tombol "Add a new course").
- Course yang sudah ada diberi nama **"Tahfidz"**.
- Ditambahkan course baru **"Tuḥfatulaṭfāl"** untuk hafalan matan manzumah Tuḥfatulaṭfāl; **detail teknisnya akan diberikan pengguna nanti** (sementara hanya kerangka).
- Boleh **merestrukturisasi direktori proyek** agar pengembangan ke depan mudah (pengguna meminta daftar berkas yang berubah dan struktur direktorinya).

---

## 5. Preferensi visual dan referensi

| Referensi dari pengguna | Yang diambil |
|---|---|
| Screenshot peta belajar Duolingo [P6] | Susunan node zig-zag, kepala unit berwarna, gelembung "mulai" dengan XP |
| Pesan "agar tidak terlalu mirip Duolingo" [P6] | Tombol lesson berbentuk **oktagon**, bukan lingkaran |
| Palet pelangi [P6] | Warna tiap thumn: merah (thumn 1) sampai ungu (thumn 8), "palet pelangi yang sesuai" |
| Screenshot "Tap the matching pairs" [P10] | Soal pencocokan dua kolom. Setelah dicoba pengguna meminta: **awal ayat di kanan, akhir/nomor ayat di kiri, kotak sama tinggi** [P11] |
| Screenshot chest dan "Jump here?" [P13] | Chest di peta, tombol biru ⏩ "LOMPAT KE SINI?" |
| Screenshot "14 in a row", "Nice job!", TOO EASY/TOO DIFFICULT/REPORT [P13] | Label beruntun di atas bar, tombol masukan setelah memeriksa jawaban |
| Screenshot "Lesson complete" (Total XP, Speedy, Great) [P13] | Tiga kartu: Total XP, Waktu, Akurasi |
| Screenshot sidebar dan tab bawah Duolingo, halaman profil [P14] | Menu responsif dan halaman profil (statistik, pencapaian) |
| "Ubah ikon dari emoji menjadi SVG gaya flat design" [P14] | Semua ikon SVG flat, tanpa emoji |
| Warna bagian mushaf [P15] | Merah, kuning, hijau, biru muda, ungu |
| Screenshot dropdown "MY COURSES" Duolingo (daftar kursus + XP kursus di chip atas, tombol "Add a new course") [P19] | Tombol kursus di bilah atas (ikon kursus + XP kursus + panah), dropdown **"KURSUS SAYA"**, dan tombol **"Tambah kursus baru"** yang membuka katalog |

---

## 6. Keputusan teknis yang diserahkan kepada Claude

| Pertanyaan pengguna | Keputusan | Alasan |
|---|---|---|
| Thumn: JSON atau aplikasi membaca CSV? [P9] | JSON dibaca aplikasi; CSV tetap sumber yang diedit; skrip `csv_ke_thumn.php` mengonversi dan memvalidasi | `json_decode` bawaan dan cepat; CSV menambah risiko (pemisah, BOM, format Excel regional) |
| Mushaf: Excel langsung atau JSON? [P15] | JSON dibaca aplikasi; skrip `xlsx_ke_mushaf.php` membaca `.xlsx` langsung, memvalidasi, lalu menulis `data/mushaf.json` | PHP tidak punya pembaca xlsx bawaan; nilai tersimpan rumus Excel dipakai, dan rumus sederhana dihitung ulang bila perlu |
| Bentuk teknis aplikasi | PHP + SQLite + JS vanilla, tanpa langkah build | Selaras dengan repo asal (PHP) dan mudah di-hosting |
| Struktur direktori multi-kursus [P19] | Dibagi **platform** (`src/`, `api.php`, `assets/js/{core,shell,quiz,icons,boot}.js`) dan **kursus** (`courses/<id>/` + `assets/js/courses/<id>.js`). Kursus terdaftar otomatis lewat manifest `course.php`; `api.php` menjadi router tipis | Kursus baru tidak perlu mengubah kode platform; kode kursus hanya dimuat saat dipakai. Panduan: `courses/README.md` |

---

## 7. Klarifikasi dan koreksi dari pengguna

1. **Thumn 7 hizb 1 ada**: "Tapi thumn 7 ada loh" → entri itu ada di datanya tetapi salah ketik (1:60, seharusnya 2:60) [P8].
2. **Penomoran thumn di aplikasi salah** pada versi saat itu (Al-Baqarah 1–17 tampil sebagai thumn 7 sendiri) → penyebab: pengurutan ulang berdasarkan posisi (kesalahan Claude), ditambah data bermasalah [P7].
3. **"2 akhir thumn"** pada permintaan P11 dikonfirmasi berarti **2 ulasan akhir hizb** ("Ok. Benar.") [P12].
4. **Ulasan rub/nisf**: bukan lesson tambahan di tiap level, melainkan satu level tersendiri di akhir [P13].
5. **Thumn 8 tidak perlu ulasan** [P14].
6. **Data thumn yang berlaku** adalah `thumn_fix.csv` buatan pengguna, bukan perbaikan otomatis Claude [P9].
7. **Data mushaf belum lengkap** dan akan ditambah pengguna [P15] → **sudah dilengkapi** (604 halaman) di P18.
8. **Dasar pencapaian** [P18]: streak tertinggi dan total dirham tertinggi yang pernah didapat; yang hangus saat runtutan terputus adalah **streak**, bukan XP (koreksi atas pesan pertama).
9. **Label thumn pertama** di margin mushaf: `Awal Hizb N` [P18].
10. **2:48** [P18]: jawaban "2:48 akhirnya" ditafsirkan Claude sebagai **Excel sudah final**: 2:48 mulai di halaman 8 (halaman 7 berakhir di 2:47). Aplikasi mengikuti Excel.
11. **"15 halaman"** berarti **15 baris per halaman**; pemenggalan baris akan diatur pengguna kemudian [P18].
12. **Batas Thumn 1/2**: ikuti data yang sudah fiks (`thumn_fix.csv`, Thumn 2 mulai di 2:17) [P18].
13. **Tanda `///`** di CSV thumn hanya penanda kesalahan pada catatan pengguna sebelumnya: **diabaikan** [P18].
14. **Perombakan aturan pembagian Hizb dan Juz** akan dijelaskan pengguna kemudian [P18].

---

## 8. Asumsi dan keputusan Claude (belum dikonfirmasi pengguna)

Semua ini dapat diubah. Daftar ini membantu agar angka-angka "ajaib" di kode tidak dianggap berasal dari pengguna.

| Topik | Keputusan sekarang |
|---|---|
| Nyawa | 5 per sesi; tes lompat 1 |
| Ujian akhir | Lesson ke-4 pada level terakhir unit; 15 soal; lulus ≥ 80% |
| Jumlah soal lesson | `8 + floor(ayat/10)`, maksimal 12 |
| XP | Lesson 10 (ulang 5) +5 bonus sempurna; ujian 20 (ulang 10) +10 bonus; tes lompat +20; ujian gagal 0 |
| Bintang | 100% = 3, ≥ 85% = 2, selainnya 1 |
| Pemetaan kesulitan | Level 1–3 Dasar, 4–5 Menengah, 6–8 Mahir. Ulasan Hizb: tingkat 3, 5, 7, 8. Level 9: tingkat 4, 6, 7, 8 |
| Level 9 | Hanya Thumn 2, 4, 6; "Ulasan Rub" (2, 6) dan "Ulasan Nisf" (4); tidak menahan thumn berikutnya |
| Ulasan tidak menahan | Ulasan Hizb/Juz tidak menghalangi thumn berikutnya |
| Tes lompat | Soal dari thumn yang dilewati di juz yang sama; thumn dilewati tidak membuka chest |
| Chest | Hanya terbuka bila lesson level itu benar-benar dikerjakan |
| Streak | Per hari kalender, zona waktu Asia/Jakarta. Streak yang tampil 0 bila kemarin dan hari ini belum belajar; belajar lagi mulai dari 1; rekor tertinggi disimpan di `streak_peak` |
| Label margin mushaf | `Awal Hizb N` untuk thumn pertama (**dikonfirmasi pengguna**, P18) |
| Mode dua halaman | Hanya di layar ≥ 760px; halaman ganjil di kanan, genap di kiri; catatan margin di sisi luar |
| Penyesuaian layar | Ukuran huruf dicari otomatis agar satu halaman muat; huruf minimal 11px lalu boleh sampai 7px bila terpaksa |
| Pencapaian profil | Ambang: streak 3/7/14/30/100/365; XP 100/500/1000/5000/10000/30000; thumn 1/4/8/16/48/160/480; dirham 20/100/300/1000. Nilai yang dipakai: streak **tertinggi**, XP, thumn selesai, dan **total dirham yang pernah didapat** (`dirham_total`; tidak berkurang bila dirham dibelanjakan, dan sebelum ada toko sama dengan saldo) |
| Migrasi pencapaian | Pada database lama, `streak_peak` dan `dirham_total` diisi dari nilai saat migrasi (riwayat sebelumnya tidak diketahui) |
| Admin | Ditentukan lewat daftar nama pengguna `ADMIN_USERS` (awal: `admin`) |
| `DEV_MODE` | Awalnya `true` selama pengetesan; harus `false` di produksi |
| Hitung kata (`Word_Count`) | Hanya token yang memuat ≥ 2 huruf dasar; tanda waqaf tidak dihitung |
| Spanduk surah | Dengan basmalah kecuali Al-Fatihah dan At-Taubah |
| Entri thumn tidak konsisten | Dikecualikan lewat rantai naik terpanjang, ayatnya digabung ke thumn sebelumnya, dan ditandai di peta |
| Retensi sesi kuis | Sesi di server dihapus setelah 24 jam |
| Pengguna baru dan lama | Pengguna baru otomatis mengikuti Tahfidz (`DEFAULT_COURSE`). Pengguna lama otomatis didaftarkan ke Tahfidz, dan XP lamanya menjadi XP Tahfidz [P19] |
| Cakupan data multi-kursus | **Global**: XP total, streak, dirham, rekor streak, total dirham, pencapaian lintas kursus. **Per kursus**: progres, sesi kuis, masukan, XP kursus. Streak dihitung dari belajar di kursus mana pun [P19] |
| Pendaftaran kursus | Mendaftar langsung menjadikan kursus itu aktif; kursus berstatus `draft` boleh diikuti dan menampilkan "segera hadir" [P19] |
| Penamaan | Nama tampilan **"Tuḥfatulaṭfāl"** (dengan tanda diakritik), id teknis `tuhfatulatfal`; ikon gulungan (`scroll`), warna ungu; urutan: Tahfidz lalu Tuḥfatulaṭfāl [P19] |
| Menu per kursus | Menu mengikuti kursus aktif (Tahfidz: Belajar, Mushaf, Profil; Tuḥfatulaṭfāl: Belajar, Profil). Pencapaian "Penghafal" dan tombol "Muroja'ah bebas" milik Tahfidz [P19] |
| Persyaratan | PHP 8.1+ (diuji 8.3), `pdo_sqlite`, `mbstring`; `zip` hanya untuk konverter xlsx |

---

## 9. Pertanyaan terbuka dan arahan yang menyusul

Tujuh pertanyaan dari versi sebelumnya sudah dijawab pengguna di P18 dan dipindahkan ke bagian 7. Yang masih terbuka atau perlu dicatat:

### Catatan data (tidak mendesak)
1. **Mushaf 69:8**: Excel menaruhnya di halaman 567, data mushaf Hafs di halaman 566. Aplikasi mengikuti Excel (kemungkinan pagination mushaf pengguna memang berbeda). Informasi saja.
2. **Sheet3 (tema)**: urutan ayat melanggar sekali pada baris 266–267 (5:90 diikuti 5:84), kemungkinan salah ketik. Sel M178 berisi catatan pengguna "Source misspell" (pada baris tema 4:14).
3. **Sheet2 (topik)**: 9 baris terakhir (baris 156–164) hanya berisi rumus `=A+1` tanpa ayat; kemungkinan baris yang belum diisi.
4. **Sheet3**: 36 baris (dari baris 238) tidak punya nomor `No`, dan 33 baris punya sel Surah kosong yang diartikan "sama dengan baris di atas".

### Arahan yang akan diberikan pengguna
- **A.** Perombakan aturan pembagian Hizb dan Juz.
- **B.** Pemenggalan baris mushaf (15 baris per halaman) pada ayat dan indeks kata tertentu.
- **C.** Fitur yang memakai Sheet2 (topik/subtopik) dan Sheet3 (tema).
- **D.** Detail teknis course **Tuḥfatulaṭfāl** (materi matan, format belajar, tipe soal, struktur unit/level) [P19].

---

## 10. Temuan hasil analisis data pengguna

### `thumn.json` asli (12 salah ketik; sudah digantikan CSV)

| Entri | Tercatat | Dugaan Claude | Keyakinan |
|---|---|---|---|
| Hizb 1 Thumn 7 | 1:60 | 2:60 | Pasti |
| Hizb 2 Thumn 2 | 2:38 | 2:88 | Besar |
| Hizb 6 Thumn 2 | 2:23 | 3:23 | Pasti |
| Hizb 10 Thumn 6 | 4:164 | 4:124 | Besar |
| Hizb 13 Thumn 3 | 5:76 | 5:96 | Besar |
| Hizb 26 Thumn 2 | 13:53 | 13:27 | Perkiraan (CSV pengguna: 13:30) |
| Hizb 26 Thumn 3 | 13:19 | 13:35 | Besar |
| Hizb 34 Thumn 2 | 22:33 | 22:13 | Besar |
| Hizb 42 Thumn 3 | 31:11 | 32:11 | Pasti |
| Hizb 53 Thumn 2 | 50:51 | 51:57 | Perkiraan (CSV pengguna: 52:1) |
| Hizb 57 Thumn 6 | 69:1 | 70:1 | Pasti |
| Hizb 57 Thumn 7 | 69:40 | 70:40 | Pasti |

Data asli tidak mengikuti batas rub standar secara persis (hanya 176 dari 240 thumn ganjil sama dengan awal rub standar), sehingga hanya kesalahan yang terbukti mustahil yang diperbaiki.

### `thumn_fix.csv`
- 480 baris valid: hizb/thumn berurutan, semua ayat ada, titik awal naik terus, total 6.236 ayat tercakup.
- 209 dari 240 thumn ganjil sama dengan awal rub standar; berbeda di 151 entri dari perbaikan otomatis Claude sebelumnya.

### `Mushaf_Page_Division.xlsx` (versi awal, 41 halaman; digantikan di P18)
- 197 baris, halaman 1–41 (Juz 1 dan 2 lengkap), tanpa lubang; halaman 1–2 satu bagian, sisanya lima bagian.
- Akhir tiap halaman cocok dengan data mushaf Hafs (Madani, 604 halaman). 258 dari 259 ayat terpetakan ke halaman yang sama dengan data Hafs; satu selisih di 2:48 (bagian 9, butir 3).
- 19 baris `Word_Count` (18 ayat berbeda; 2:102 terpotong tiga), semuanya kumulatif.

### Rujukan luar yang dipakai Claude untuk verifikasi
Paket npm `quran-meta` (daftar rub dan halaman mushaf Hafs) dan data hizb dari QUL/Tarteel (Hizb 1 mencakup 1:1–2:74). Keduanya bukan masukan dari pengguna dan hanya dipakai sebagai pembanding.

### `Mushaf_Page_Division_1.xlsx` (P18)
- **Sheet1**: 604 halaman dan 3.012 bagian (halaman 1–2: 1 bagian; 602 halaman lain: 5 bagian), nilai langsung tanpa rumus, berakhir tepat di 114:6. 132 baris `Word_Count` (bagian terpotong di tengah ayat), semuanya kumulatif.
- **Validasi terhadap data mushaf Hafs**: dari 6.236 ayat, 6.234 berhalaman sama. Selisih: **2:48** (Excel hal. 8, Hafs hal. 7; dikonfirmasi pengguna, bagian 7 butir 10) dan **69:8** (Excel hal. 567, Hafs hal. 566). Tidak ada ayat yang terbelah antar halaman.
- **Validasi terhadap teks ayat nyata** (paket `quran-json`, hanya untuk uji lokal): semua 132 `Word_Count` lebih kecil dari jumlah kata ayatnya; titik potong berada di 17%–87% panjang ayat.
- **Navigasi**: juz 1 → hal. 1, juz 2 → 22, juz 3 → 42, juz 30 → 582, 114:6 → 604; seluruh 480 awal thumn memiliki halaman.
- **Sheet2** `No, Surah, Ayah, Topic, Subtopic`: 163 baris, 154 berisi ayat (2:6 sampai 5:110; per surah: 2:60, 3:38, 4:33, 5:23). Tiap baris berisi **tepat satu** dari Topic (33 baris) atau Subtopic (121 baris). Contoh: 2:8 subtopik "Golongan Munafik".
- **Sheet3** `No, Surah, Ayat, Theme`: 272 baris, semuanya bertema (2:6 sampai 5:119; per surah: 2:116, 3:55, 4:64, 5:37). Contoh: 2:7 "Penjelasan mengenai beberapa sifat dan keadaan orang-orang munafik …".
- Sheet2 dan Sheet3 belum dipakai aplikasi; menunggu arahan pengguna (bagian 9, arahan C).

---

## 11. Riwayat permintaan

| # | Permintaan pengguna | Hasil |
|---|---|---|
| P1 | Kembangkan kuis dummy HafidzQuiz menjadi aplikasi ala Duolingo; belajar per thumn dengan lesson dan tes acak | Rancangan awal diajukan, lalu dibangun |
| P2 | Memberi `1.json`, `thumn.json`, dan rancangan: bebas pilih juz, wajib login | Peta per juz, akun, XP, SQLite |
| P3 | Memberi kode parser; boleh dimodifikasi | `quran_json_parser.php` baru (pemotongan di waqaf, cache, kompatibel) |
| P4 | Perbaiki hasil "undefined"; seret-lepas; keyboard + Enter | Bug `last_day` dan respons rusak diperbaiki; seret-lepas dan keyboard |
| P5 | Seret-lepas dan keyboard belum jalan di PC | Penyebab: cache browser; ditambahkan nomor versi aset otomatis |
| P6 | Peta ala Duolingo: node 3–5 lesson, warna pelangi per thumn, popup lesson+XP, tombol oktagon; "Hizb 2 Thumn 1 kok tidak ada?" | Diterapkan; kasus Hizb 2 diselidiki lewat diagnostik |
| P7 | Penomoran thumn masih salah; memberi keluaran `cek_thumn.php` | Pengurutan ulang dihentikan; entri janggal dikecualikan dan ditandai |
| P8 | "Thumn 7 ada"; mengunggah `thumn.json` lengkap | Ditemukan 12 salah ketik; file perbaikan dibuat |
| P9 | Buat CSV untuk dicek manual; lalu mengunggah `thumn_fix.csv` | `thumn.csv` + `csv_ke_thumn.php`; JSON dibangun dari CSV |
| P10 | 1 thumn = 8 level dengan tingkat kesulitan bertahap + 5 tipe soal baru | Generator soal ditulis ulang (11 tipe) |
| P11 | Pencocokan kolom dibalik dan sama tinggi; 4 lesson per level; unit ulasan hizb dan juz | Diterapkan; "2 akhir thumn" ditafsirkan sebagai ulasan hizb |
| P12 | Ulasan Hizb 4 level; ulasan rub/nisf; chest dirham di level 4 dan 7; tombol developer sementara | Diterapkan (ulasan rub/nisf awalnya salah tafsir) |
| P13 | Ulasan rub/nisf sebagai level 9; lompat ke sini; beruntun; tombol masukan + laporan admin; waktu | Diterapkan; `admin.php` dibuat |
| P14 | Thumn 8 tanpa ulasan; penilaian ke server; ikon SVG; menu Belajar dan Profil | Diterapkan; endpoint skor lama dihapus |
| P15 | Menu Mushaf dari data Excel (5 bagian berwarna, potongan di tengah ayat, ۞, navigasi) | `mushaf.json`, `xlsx_ke_mushaf.php`, `mushaf.php`, tampilan Mushaf |
| P16 | Opsi dua halaman; keterangan pembagian di margin; satu halaman penuh dalam satu layar | Diterapkan dan diuji di 11 ukuran layar |
| P17 | Buat `README.md` dan `knowledge.md`; perbarui setiap ada permintaan | Dokumen ini dan `README.md` dibuat |
| P18 | Menjawab 7 pertanyaan terbuka; mengunggah Excel mushaf lengkap (3 sheet); klarifikasi bahwa yang hangus adalah streak (bukan XP) | `mushaf.json` 604 halaman; pencapaian memakai streak tertinggi dan total dirham tertinggi; streak yang tampil hangus saat runtutan terputus; sheet topik/tema dianalisis; dokumen diperbarui |
| P19 | Fitur multi-kursus (pengguna bisa meng-enroll ke kursus baru, pemilih kursus ala Duolingo); course lama bernama "Tahfidz"; tambah course "Tuḥfatulaṭfāl" (detail menyusul); restrukturisasi direktori; kirim daftar berkas yang berubah dan struktur direktori | Arsitektur platform + course (`src/`, `courses/`), migrasi database otomatis (`progress` per kursus, tabel `enrollments`), dropdown kursus + katalog, kerangka Tuḥfatulaṭfāl (draft), `courses/README.md`; diuji dengan database format lama dan di browser |

---

## 12. Cara memperbarui dokumen ini

Setiap kali pengguna meminta sesuatu:

1. Tambahkan baris di bagian 11 (nomor berikutnya, ringkasan, hasil).
2. Catat informasi baru dari pengguna (data, aturan, preferensi, klarifikasi) di bagian 2–7.
3. Catat asumsi baru Claude di bagian 8 dan pertanyaan baru di bagian 9; pindahkan yang sudah dijawab ke bagian 7.
4. Perbarui `README.md` bila fitur, struktur, aturan, atau cara pakai berubah.
5. Ubah tanggal "Terakhir diperbarui" di kedua file.
