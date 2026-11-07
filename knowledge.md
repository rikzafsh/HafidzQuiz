# Knowledge HafidzQuiz

Catatan semua informasi, pengetahuan, kebutuhan, dan keputusan yang diberikan pengguna selama pengembangan HafidzQuiz. Dipakai sebagai ingatan proyek: siapa pun (atau sesi berikutnya) yang melanjutkan pengembangan bisa memahami *mengapa* aplikasi dibuat begini.

> **Aturan pemeliharaan:** setiap kali pengguna meminta sesuatu, dokumen ini dan `README.md` diperbarui (permintaan baru dicatat di bagian 11, informasi baru masuk ke bagian yang sesuai, pertanyaan yang terjawab dipindahkan dari bagian 9).
> Terakhir diperbarui: 7 November 2026 (setelah permintaan P17).

**Cara membaca:** bagian 1–7 adalah sumber kebenaran dari pengguna. Bagian 8 berisi asumsi Claude yang **belum dikonfirmasi** dan boleh diubah kapan saja. Rujukan `[P#]` mengarah ke riwayat permintaan di bagian 11.

---

## 1. Tentang proyek

| Hal | Isi |
|---|---|
| Nama | **HafidzQuiz** |
| Tujuan | Aplikasi kuis seperti Duolingo untuk membantu **menghafal Al-Qur'an** [P1] |
| Platform | Web, fleksibel dan responsif: mudah diakses di PC maupun HP [P1] |
| Rencana course lain | Hafalan **matan manzumah**, **syair**, dan belajar **aksara**. Tahap ini hanya Al-Qur'an [P1] |
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
| **`Mushaf_Page_Division.xlsx`** [P15] | Pemetaan halaman mushaf yang dibuat pengguna | Kolom: `No, Page, Section, Surah, Ayat_End, Word_Count`. Kolom Page dan Section berupa rumus (`=B4+1`, `=C4`, merujuk 5 baris di atas). Mencakup halaman 1–41. **Belum lengkap; akan ditambah pengguna** |
| Screenshot | Referensi tampilan | Lihat bagian 5 |

Yang tidak dapat diakses Claude: folder `data/` di GitHub (diblokir untuk akses otomatis). Karena itu data ayat asli lengkap belum pernah dipakai untuk pengujian; semua uji memakai data tiruan.

---

## 3. Pengetahuan domain dari pengguna

### Pembagian Al-Qur'an
- **Thumn** = 1/8 hizb. Satu hizb = 8 thumn, bernomor 1–8. Ada 60 hizb, 30 juz (1 juz = 2 hizb), sehingga **480 thumn** [P1, P6].
- Kolom `Nisf` dan `Rub` pada CSV pengguna: **nisf** = ½ hizb (nisf 1 = thumn 1–4, nisf 2 = thumn 5–8); **rub** = ¼ hizb (rub 1–4; tiap rub = 2 thumn) [P9].
- Pengguna menyebut akhir **rub dan nisf** sebagai titik ulasan. Thumn 2 dan 6 mengakhiri rub, Thumn 4 mengakhiri nisf, dan Thumn 8 mengakhiri hizb [P12, P13, P14].
- Notasi keterangan pembagian di margin mushaf (contoh dari pengguna): `1/8 Hizb 1`, `1/4 Hizb 1`, `3/8 Hizb 1`, `1/2 Hizb 1`, dst. [P16].
- Tanda **۞** adalah tanda rub el hizb. Pada mushaf HafidzQuiz ia dipasang di awal tiap thumn jika belum ada [P15, P16].
- Thumn 1 hizb 1 dimulai di 1:1. Pengguna menyatakan bahwa Al-Baqarah 1–17 "masih termasuk thumn 1" [P7]. Catatan: CSV akhir memulai Thumn 2 di 2:17 (lihat bagian 9, butir 5).

### Mushaf (dari data Excel pengguna)
- Satu baris Excel = satu **bagian** halaman. `Surah` + `Ayat_End` = ujung bagian itu; awal bagian = setelah ujung bagian sebelumnya [P15].
- Satu halaman terbagi **5 bagian**, dan pembagiannya boleh jatuh **di tengah ayat** [P15]. Dalam data, halaman 1 dan 2 hanya punya 1 bagian.
- `Word_Count` (opsional) menandai bagian yang berakhir di tengah ayat: bagian berakhir setelah kata ke-N ayat `Ayat_End`. Dalam data pengguna ayat 2:102 terpotong tiga (kata ke-23, kata ke-51, lalu sisanya), jadi hitungannya **kumulatif dari awal ayat**.
- Bagian mengalir di dalam baris dan diberi latar berbeda: **merah, kuning, hijau, biru muda, ungu** [P15].
- Pengguna menulis "Dalam satu halaman mushaf dibagi menjadi 15 halaman" [P15]. Kalimat itu belum dijelaskan (lihat bagian 9, butir 4).

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

### 4.8 Dokumentasi
- Dibuat `README.md` (penjelasan aplikasi) dan `knowledge.md` (semua informasi dari pengguna). **Keduanya diperbarui setiap kali pengguna meminta sesuatu** [P17].

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

---

## 6. Keputusan teknis yang diserahkan kepada Claude

| Pertanyaan pengguna | Keputusan | Alasan |
|---|---|---|
| Thumn: JSON atau aplikasi membaca CSV? [P9] | JSON dibaca aplikasi; CSV tetap sumber yang diedit; skrip `csv_ke_thumn.php` mengonversi dan memvalidasi | `json_decode` bawaan dan cepat; CSV menambah risiko (pemisah, BOM, format Excel regional) |
| Mushaf: Excel langsung atau JSON? [P15] | JSON dibaca aplikasi; skrip `xlsx_ke_mushaf.php` membaca `.xlsx` langsung, memvalidasi, lalu menulis `data/mushaf.json` | PHP tidak punya pembaca xlsx bawaan; nilai tersimpan rumus Excel dipakai, dan rumus sederhana dihitung ulang bila perlu |
| Bentuk teknis aplikasi | PHP + SQLite + JS vanilla, tanpa langkah build | Selaras dengan repo asal (PHP) dan mudah di-hosting |

---

## 7. Klarifikasi dan koreksi dari pengguna

1. **Thumn 7 hizb 1 ada**: "Tapi thumn 7 ada loh" → entri itu ada di datanya tetapi salah ketik (1:60, seharusnya 2:60) [P8].
2. **Penomoran thumn di aplikasi salah** pada versi saat itu (Al-Baqarah 1–17 tampil sebagai thumn 7 sendiri) → penyebab: pengurutan ulang berdasarkan posisi (kesalahan Claude), ditambah data bermasalah [P7].
3. **"2 akhir thumn"** pada permintaan P11 dikonfirmasi berarti **2 ulasan akhir hizb** ("Ok. Benar.") [P12].
4. **Ulasan rub/nisf**: bukan lesson tambahan di tiap level, melainkan satu level tersendiri di akhir [P13].
5. **Thumn 8 tidak perlu ulasan** [P14].
6. **Data thumn yang berlaku** adalah `thumn_fix.csv` buatan pengguna, bukan perbaikan otomatis Claude [P9].
7. **Data mushaf belum lengkap** dan akan ditambah pengguna [P15].

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
| Streak | Per hari kalender, zona waktu Asia/Jakarta |
| Label margin mushaf | Thumn pertama hizb diberi label `Awal Hizb N` |
| Mode dua halaman | Hanya di layar ≥ 760px; halaman ganjil di kanan, genap di kiri; catatan margin di sisi luar |
| Penyesuaian layar | Ukuran huruf dicari otomatis agar satu halaman muat; huruf minimal 11px lalu boleh sampai 7px bila terpaksa |
| Pencapaian profil | Ambang: streak 3/7/14/30/100/365; XP 100/500/1000/5000/10000/30000; thumn 1/4/8/16/48/160/480; dirham 20/100/300/1000 |
| Admin | Ditentukan lewat daftar nama pengguna `ADMIN_USERS` (awal: `admin`) |
| `DEV_MODE` | Awalnya `true` selama pengetesan; harus `false` di produksi |
| Hitung kata (`Word_Count`) | Hanya token yang memuat ≥ 2 huruf dasar; tanda waqaf tidak dihitung |
| Spanduk surah | Dengan basmalah kecuali Al-Fatihah dan At-Taubah |
| Entri thumn tidak konsisten | Dikecualikan lewat rantai naik terpanjang, ayatnya digabung ke thumn sebelumnya, dan ditandai di peta |
| Retensi sesi kuis | Sesi di server dihapus setelah 24 jam |
| Persyaratan | PHP 8.1+ (diuji 8.3), `pdo_sqlite`, `mbstring`; `zip` hanya untuk konverter xlsx |

---

## 9. Pertanyaan dan ambiguitas yang belum terjawab

1. **Pengumpul Dirham**: apakah pencapaiannya dihitung dari **total dirham yang pernah didapat** (bukan saldo)? Bila nanti ada toko, saldo bisa turun dan pencapaian ikut mundur. *(Ditanyakan di P14, belum dijawab.)*
2. **Label thumn pertama** di margin mushaf: `Awal Hizb N` atau tulisan lain (misalnya `Hizb N`)? *(Ditanyakan di P16.)*
3. **Mushaf 2:48**: Excel menaruh halaman 7 berakhir di 2:47 dan halaman 8 sampai 2:50, sedangkan data mushaf Hafs menempatkan 2:48 mulai di halaman 7. Mungkin 2:48 dimulai di dasar halaman 7 dan berlanjut ke halaman 8 (perlu baris `Word_Count`). Menunggu pengecekan pengguna terhadap mushafnya.
4. **"15 halaman"**: pengguna menulis satu halaman mushaf dibagi menjadi 15 halaman. Dugaan Claude: yang dimaksud **15 baris per halaman** (format Madani). Angka ini tidak dipakai dalam tampilan karena tiap bagian "mengalir saja dalam baris".
5. **Batas Thumn 1/2**: pernyataan awal "Al-Baqarah 1–17 masih termasuk thumn 1" dibuat saat data lama memulai Thumn 2 di 2:18, sedangkan `thumn_fix.csv` memulai Thumn 2 di 2:17. Aplikasi memakai CSV.
6. **Tanda `///`** di kolom terakhir `thumn_fix.csv` baris No 166 dan 291: diabaikan; belum diketahui apakah itu penanda yang perlu ditindaklanjuti.
7. **Cakupan data mushaf**: halaman 42 dan seterusnya (Juz 3 dst.) menunggu data tambahan dari pengguna.

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

### `Mushaf_Page_Division.xlsx`
- 197 baris, halaman 1–41 (Juz 1 dan 2 lengkap), tanpa lubang; halaman 1–2 satu bagian, sisanya lima bagian.
- Akhir tiap halaman cocok dengan data mushaf Hafs (Madani, 604 halaman). 258 dari 259 ayat terpetakan ke halaman yang sama dengan data Hafs; satu selisih di 2:48 (bagian 9, butir 3).
- 19 baris `Word_Count` (18 ayat berbeda; 2:102 terpotong tiga), semuanya kumulatif.

### Rujukan luar yang dipakai Claude untuk verifikasi
Paket npm `quran-meta` (daftar rub dan halaman mushaf Hafs) dan data hizb dari QUL/Tarteel (Hizb 1 mencakup 1:1–2:74). Keduanya bukan masukan dari pengguna dan hanya dipakai sebagai pembanding.

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

---

## 12. Cara memperbarui dokumen ini

Setiap kali pengguna meminta sesuatu:

1. Tambahkan baris di bagian 11 (nomor berikutnya, ringkasan, hasil).
2. Catat informasi baru dari pengguna (data, aturan, preferensi, klarifikasi) di bagian 2–7.
3. Catat asumsi baru Claude di bagian 8 dan pertanyaan baru di bagian 9; pindahkan yang sudah dijawab ke bagian 7.
4. Perbarui `README.md` bila fitur, struktur, aturan, atau cara pakai berubah.
5. Ubah tanggal "Terakhir diperbarui" di kedua file.