# Panduan membuat course HafidzQuiz (mandiri)

Dokumen ini **cukup berdiri sendiri**: dengan dokumen ini dan materi course, sebuah percakapan baru dapat menulis seluruh kode course tanpa melihat proyek lengkap. Untuk *menjalankan/menguji* course, proyek tetap diperlukan (lihat bagian 11).

## 0. Cara memakai di percakapan baru

Unggah **dokumen ini** + materi course (teks, CSV/Excel, dsb.), lalu tempel perintah berikut (ganti isian):

```
Ikuti courses/README.md terlampir. Buat course dengan id "<id>" bernama "<Nama>".
Aturan belajar: <jelaskan: struktur unit/level, jenis soal, XP, syarat lulus, dsb.>
Materi: <berkas terlampir / penjelasan>.
Serahkan HANYA berkas baru di: courses/<id>/*, assets/js/courses/<id>.js, data/<id>/*.
Jangan ubah berkas platform (src/*, api.php, app.php, assets/js/{core,shell,quiz,icons}.js, assets/css/app.css).
Bila butuh sesuatu dari platform (tipe soal baru, ikon baru, gaya CSS), daftarkan terpisah di bagian "Kebutuhan platform".
Perbarui juga README.md dan knowledge.md bagian course ini.
```

Hasil percakapan itu ditaruh ke folder proyek dengan nama yang sama (tidak ada berkas yang bertabrakan, selama `id` course unik). Platform otomatis mendaftarkannya.

---

## 1. Arsitektur singkat

- **Platform** (`src/`, `api.php`, `assets/js/{core,shell,quiz,icons}.js`): akun, XP, streak, dirham, pendaftaran course (enroll), mesin sesi kuis, pemeriksaan jawaban di server.
- **Course** (`courses/<id>/`): materi, pembuat soal, struktur belajar, penerapan hasil sesi, dan layar klien.
- Setiap folder `courses/<id>/` yang berisi `course.php` otomatis terdaftar; setiap `assets/js/courses/*.js` otomatis dimuat (diurutkan nama).
- Pengguna yang membuka course mendapat `me.course = <id>`; **setiap panggilan `api()` otomatis membawa `&course=<id>`**; platform memastikan pengguna sudah bergabung (403 bila belum).

```
courses/<id>/course.php         manifest (wajib)
courses/<id>/boot.php           require_once semua berkas PHP course (dimuat malas)
courses/<id>/*.php              data, pembuat soal, penangan API, hook
assets/js/courses/<id>.js       modul klien: Courses.<id> = {...}
data/<id>/                      data materi (JSON/CSV), jangan taruh di data/ langsung
```

## 2. Manifest `courses/<id>/course.php`

Berkas mengembalikan array. `status` = `'draft'` berarti bisa diikuti tetapi belum ada materi (cukup `id,name,subtitle,icon,color,order,status,description`; klien menampilkan "Segera hadir" lewat modul JS-nya).

| kunci | wajib | arti |
|---|---|---|
| `id` | ya | huruf kecil/angka, unik; sama dengan nama folder. Juga awalan nama fungsi PHP |
| `name`, `subtitle`, `description` | ya | tampil di katalog "Tambah kursus baru" |
| `icon` | ya | salah satu kunci `ICONS` (daftar di bagian 9) |
| `color` | ya | warna hex |
| `order` | ya | urutan di katalog (Tahfidz = 1, Tuḥfatulaṭfāl = 2) |
| `status` | ya | `'ready'` atau `'draft'` |
| `boot` | ready | nama berkas (relatif ke folder course) yang memuat semua kode PHP |
| `api` | ready | nama fungsi penangan aksi API |
| `finish` | ready | nama fungsi hook hasil sesi |
| `profile` | ready | nama fungsi hook statistik profil |

## 3. Kontrak PHP (sisi server)

### Penangan API
```php
function <id>_api(string $a, array $u, array $in, array $c): bool
```
- `$a` nama aksi, `$u` baris pengguna (kolom: `id, username, xp, streak, last_day, dirham, ...`), `$in` isi JSON body (POST), `$c` manifest course. Parameter query string dibaca dari `$_GET`.
- Aksi yang dikenal: `switch`, lalu akhiri dengan **`out($array)`** (mencetak JSON dan menghentikan eksekusi). Aksi tidak dikenal: `return false;`.
- `fail(string $pesan, int $kode = 400)` menghentikan dengan galat JSON `{error: pesan}`.
- Aksi **platform** (jangan dipakai ulang namanya): `register, login, logout, me, courses, course_enroll, course_switch, check, finish, feedback, profile`. Semua nama lain diteruskan ke penangan course.
- Aksi yang **dipanggil mesin kuis klien** dan karenanya wajib ada bila memakai `session()`: **`quiz`** (lihat bagian 4).

### Membuat sesi kuis
```php
newAttempt(int $uid, string $courseId, string $kind, int $unit, int $level, int $lesson, array $soal): array
```
Menyimpan soal + kunci di server, mengembalikan array siap `out()`:
`{attempt, hearts, total, questions:[soal tanpa kunci + i]}`. `$kind` bebas (`'lesson'`, `'jump'` → nyawa 1, selain itu nyawa 5). `$unit/$level/$lesson` bilangan bulat bebas yang nanti kembali di `$attempt` pada hook `finish` (course yang menentukan artinya; untuk course sederhana pakai `1,1,n`).

Aksi `quiz` **harus** begini (klien memanggil `GET api.php?a=quiz&course=<id>&unit=U&level=L&lesson=N`):
```php
case 'quiz': out(newAttempt($u['id'], '<id>', 'lesson', (int)$_GET['unit'], (int)$_GET['level'], (int)$_GET['lesson'], $soal));
```
Bila `?jump=1` ada, klien menunggu tes lompat 1 nyawa (opsional, abaikan bila tidak dipakai).

### Format soal (satu-satunya tipe yang dikenal mesin kuis)
Semua soal punya `t`, `title` (kalimat perintah, teks biasa), dan `full` (teks penjelasan jawaban benar yang ditampilkan setelah menjawab; rtl/Arab boleh). Kunci `ans` dan `full` **tidak dikirim** ke klien sebelum dijawab.

1. **`choice`** — pilihan ganda
```php
['t'=>'choice','mode'=>'ar',          // 'ar' = opsi Arab (rtl, satu kolom) | 'num' = pendek 2 kolom | 'har' = awalan huruf ◌ | apa pun lain = teks 2 kolom
 'title'=>'Pilih lanjutan ayat:', 'show'=>'teks Arab/konteks (boleh string kosong)',
 'opts'=>['A','B','C','D'], 'ans'=>'B', 'full'=>'teks lengkap']
```
`ans` harus **string yang sama persis** dengan salah satu `opts`. Opsional: `tokens`(array kata) dan `mark`(indeks kata yang disorot) menggantikan `show`.

2. **`fill`** — isi kotak kosong dengan menyeret/memilih kata
```php
['t'=>'fill','title'=>'Lengkapi:','tokens'=>['kata1','kata2','kata3','kata4'],
 'blanks'=>[1,3],                       // indeks pada tokens yang dikosongkan
 'ans'=>['kata2','kata4'],              // urutan sama dengan blanks
 'opts'=>['kata4','kata2','pengecoh'],  // ubin yang tersedia (acak sendiri)
 'full'=>'teks lengkap']
```
3. **`match`** — mencocokkan pasangan (maks 5)
```php
['t'=>'match','rmode'=>'num',           // 'ar' = kolom kanan Arab rtl; 'num' = pendek
 'title'=>'Cocokkan:',
 'left' =>[['id'=>1,'text'=>'A1'],['id'=>2,'text'=>'A2']],     // 'id' = kunci pasangan (dibuang sebelum dikirim)
 'right'=>[['id'=>2,'text'=>'B2'],['id'=>1,'text'=>'B1']],     // acak sendiri urutan kedua kolom
 'full'=>'penjelasan']
```
Pasangan benar bila `left[l].id === right[r].id` (id harus tipe sama; gunakan int). Tipe soal baru memerlukan perubahan platform (`quiz.js` dan `quiz_check()`), jadi catat di "Kebutuhan platform".

### Hook `finish`
```php
function <id>_finish(array $u, array $attempt, int $benarPercobaanPertama, int $totalSoal): array
```
Dipanggil platform saat sesi selesai (semua soal terjawab, nyawa > 0). `$attempt` baris sesi: `unit, level, lesson, kind, course, started`. `$benarPercobaanPertama` = jumlah soal yang tak pernah salah. Tugas hook: hitung bintang/XP, simpan progres, panggil `award()`, kembalikan array hasil. Platform menambahkan `secs` (durasi detik).

```php
award(array $u, string $courseId, int $xp): array   // → ['streak'=>int,'xp'=>int(total global baru),'courseXp'=>int]
```
`award()` menambah XP global dan XP course, memperbarui streak dan puncaknya. **Panggil tepat sekali per sesi.** Dirham (opsional): `UPDATE users SET dirham=dirham+?, dirham_total=dirham_total+? WHERE id=?`.

**Kontrak array hasil** yang dibaca layar hasil klien (kunci wajib ditebalkan):

| kunci | arti |
|---|---|
| **`passed`** (bool) | lulus? `false` → "Belum lulus" + tombol Ulangi |
| **`gain`** (int) | XP yang didapat sesi ini (tampil "TOTAL XP") |
| **`xp`**, **`streak`**, **`courseXp`** | dari `award()` (klien memperbarui `me`) |
| **`pct`** (int) | akurasi 0–100 |
| `stars` (0–3) | bintang pada layar hasil |
| `bonus` (int) | tampil "Termasuk bonus N XP" bila > 0 |
| `lesson`, `lessons`, `level`, `levelDone`, `unitDone` | menentukan judul: `unitDone` → "<jenis> selesai!"; `levelDone` → "Level N selesai!"; selain itu "Lesson X dari Y selesai!" |
| `skipped` | hanya untuk tes lompat |

### Hook `profile`
```php
function <id>_profile(array $u): array   // array asosiatif bebas; dikirim klien sebagai `stats` course
```

### Progres & basis data (SQLite via `db()` → PDO, `PDO::FETCH_ASSOC`)
```sql
progress(user_id, course, unit INTEGER, node TEXT, stars INTEGER, best INTEGER, PRIMARY KEY(user_id,course,unit,node))
enrollments(user_id, course, xp, enrolled_at, last_active)      -- diurus platform
attempts / feedback                                              -- diurus platform (sudah ber-kolom course)
```
`courseProgress(int $uid, string $courseId): array` → `["unit:node" => stars]`. Selalu isi kolom `course` saat INSERT/UPDATE/DELETE. Catatan SQLite/PDO: parameter terikat sebagai teks; gunakan `CAST(? AS INTEGER)` di dalam `MAX(...)`.
Semua nama fungsi PHP bersifat global: **awali dengan id course**. Jangan kirim kunci jawaban ke klien.

## 4. Kontrak klien (JS, tanpa modul/bundler; semua global)

```js
Courses.<id> = {
  nav: [[kode, 'Label', 'ikon', () => ...]],      // menu course; platform menambahkan "Profil"
  home() {},                                       // layar utama course (dipanggil saat course dibuka)
  statCards: (s, card) => [card('check', s.x, 'Label')],   // opsional; s = hasil hook profile; card(ikon, nilai, label, kelas?, sub?)
  achievements: s => [{icon:'book', title:'', steps:[1,5,10], text:n=>`...${n}`, val:()=>s.x}],   // opsional
  summary: s => 'teks',                            // opsional: ringkasan di "Kursus saya"
  profileActions: () => [],                        // opsional: tombol tambahan di profil
};
```
Fungsi global yang boleh dipakai:

| fungsi | keterangan |
|---|---|
| `el(tag, attrs, ...anak)` | pembuat DOM; `attrs.class`, `onclick`, `dir`, `style`, `aria-*`; anak bisa string/Node/array |
| `await api(aksi, body?, tambahanQuery?)` | `body` ada → POST JSON, kalau tidak GET. Contoh `api('quiz', null, '&unit=1&level=1&lesson=2')`. Lempar `Error(message)` bila galat |
| `ic(kunciIkon, ukuran=20, kelas='')`, `stars(n, ukuran)` | ikon SVG, bintang |
| `toast(teks)` | pesan singkat |
| `mount(node)` | ganti seluruh isi layar |
| `shell(kodeMenuAktif, topbar(), el('div',{class:'wrap'}, ...))` | kerangka dengan menu; `topbar(kembaliFn?)` menampilkan tombol kembali bila diberi fungsi |
| `session(unit, level, lesson, kembaliFn, 'Jenis', {jump?})` | **mesin kuis**: memanggil aksi `quiz`, menjalankan soal, memeriksa lewat server, memanggil `finish`, lalu `kembaliFn()` |
| `redraw = () => ...` | isi dengan fungsi penggambar ulang layar saat ini (dipakai tombol DEV) |
| `me` | pengguna saat ini: `{id,name,xp,streak,streakPeak,dirham,dirhamTotal,course,courses[],dev,admin}` |
| `store.get(k)/store.set(k,v)` | penyimpanan lokal aman |
| `fmtTime(detik)`, `onEnter(fn)` | pemformat waktu; pasang Enter |

Setelah `finish`, platform memperbarui `me.xp`, `me.streak`, XP course. Untuk menyegarkan data, `me = (await api('me')).user`.

Kelas CSS yang tersedia: `.wrap` (kolom utama), `.btn`, `.btn.alt`, `.btn.red`, `.mut` (teks redup), `.err`, `.ayat` (teks Arab besar; pakai `dir:'rtl'`; `.ayat.sm` kecil), `.big` (layar tengah), `.chip`, `.sc2` (kartu statistik), `h2`, `h3`.

## 5. Ikon (kunci `ICONS`)
`flame bolt coin lock unlock check trophy repeat ff star starO book logout cog chart arrowLeft close target timer home user party medal sad rocket zzz mountain flag mushaf page1 page2 search scroll plus caret shield avatar` (ikon `heart`/`heartBroken` dipakai mesin kuis). Ikon baru → catat di "Kebutuhan platform" (jangan ubah `icons.js`).

## 6. Aturan penting
- **Isolasi**: progres, sesi, masukan menyimpan `course`. XP dihitung global dan per course. Streak, dirham, dan pencapaian lintas course bersifat global.
- **Keamanan**: soal beserta kunci hanya di server; pemeriksaan di `check` (platform). Jangan beri klien kunci.
- **Nama fungsi PHP global**: awali dengan id course. Alat CLI di `courses/<id>/tools/` diawali:
  `require dirname(__DIR__, 3) . '/src/bootstrap.php'; courseLoad(courseGet('<id>'));`
- **Zona waktu** aplikasi `Asia/Jakarta`; PHP ≥ 8.0, tanpa Composer.
- Teks antarmuka berbahasa Indonesia.

## 7. Contoh lengkap minimal (course "contoh")

Setiap blok diawali komentar `FILE:`; seluruhnya sudah diuji berjalan. Salin apa adanya, lalu ganti isinya.

**`courses/contoh/course.php`**
```php
<?php
// FILE: courses/contoh/course.php
return [
    'id' => 'contoh', 'name' => 'Contoh', 'subtitle' => 'Course contoh', 'icon' => 'book', 'color' => '#1cb0f6', 'order' => 9,
    'status' => 'ready', 'description' => 'Course contoh minimal untuk menguji kontrak platform.',
    'boot' => 'boot.php', 'api' => 'contoh_api', 'finish' => 'contoh_finish', 'profile' => 'contoh_profile',
];
```

**`courses/contoh/boot.php`**
```php
<?php
// FILE: courses/contoh/boot.php
require_once __DIR__ . '/soal.php';
require_once __DIR__ . '/handlers.php';
```

**`courses/contoh/soal.php`** (bank soal + pembuat soal)
```php
<?php
// FILE: courses/contoh/soal.php
const CONTOH_BANK = [   // Arab => arti
    'الحمد' => 'pujian', 'الله' => 'Allah', 'رب' => 'tuhan', 'العالمين' => 'seluruh alam',
];
function contoh_soal(int $n): array {   // 1 pilihan ganda + 1 isi kotak + 1 pencocokan
    $b = CONTOH_BANK; $k = array_keys($b); shuffle($k); $a = $k[0];
    $opts = array_values($b); shuffle($opts);
    $choice = ['t' => 'choice', 'mode' => 'num', 'title' => "Arti “{$a}” adalah…", 'show' => $a, 'opts' => $opts, 'ans' => $b[$a], 'full' => "{$a} = {$b[$a]}"];
    $tok = ['الحمد', 'لله', 'رب', 'العالمين'];
    $fill = ['t' => 'fill', 'title' => 'Lengkapi ayat berikut:', 'tokens' => $tok, 'blanks' => [1, 3], 'ans' => ['لله', 'العالمين'],
             'opts' => ['العالمين', 'لله', 'الرحيم'], 'full' => implode(' ', $tok)];
    $L = $R = []; $i = 0;
    foreach (array_slice($b, 0, 3, true) as $ar => $id) { $i++; $L[] = ['id' => $i, 'text' => $ar]; $R[] = ['id' => $i, 'text' => $id]; }
    shuffle($R);
    $match = ['t' => 'match', 'rmode' => 'num', 'title' => 'Cocokkan kata dengan artinya:', 'left' => $L, 'right' => $R, 'full' => 'Pasangan kata dan arti'];
    return [$choice, $fill, $match];
}
```

**`courses/contoh/handlers.php`**
```php
<?php
// FILE: courses/contoh/handlers.php
function contoh_api(string $a, array $u, array $in, array $c): bool {
    switch ($a) {
        case 'quiz':   // dipanggil session(): ?unit=&level=&lesson=
            out(newAttempt($u['id'], 'contoh', 'lesson', (int)($_GET['unit'] ?? 1), (int)($_GET['level'] ?? 1), (int)($_GET['lesson'] ?? 1), contoh_soal(3)));
        case 'path':   // layar utama: progres saya
            out(['progress' => courseProgress($u['id'], 'contoh')]);
    }
    return false;
}
function contoh_finish(array $u, array $at, int $benar, int $total): array {
    $pct = (int)round(100 * $benar / max(1, $total)); $stars = $pct >= 100 ? 3 : ($pct >= 70 ? 2 : 1);
    $bonus = $benar === $total ? 5 : 0; $gain = 10 + $bonus;
    db()->prepare('INSERT INTO progress(user_id,course,unit,node,stars,best) VALUES(?,?,?,?,?,?)
                   ON CONFLICT(user_id,course,unit,node) DO UPDATE SET stars=MAX(stars, excluded.stars), best=MAX(best, excluded.best)')
        ->execute([$u['id'], 'contoh', (int)$at['unit'], 'L' . $at['lesson'], $stars, $pct]);
    $r = award($u, 'contoh', $gain);
    return ['passed' => true, 'gain' => $gain, 'bonus' => $bonus, 'pct' => $pct, 'stars' => $stars, 'lesson' => (int)$at['lesson'], 'lessons' => 3,
            'level' => 1, 'levelDone' => false, 'unitDone' => false] + $r;
}
function contoh_profile(array $u): array {
    $p = courseProgress($u['id'], 'contoh'); return ['selesai' => count($p), 'bintang' => array_sum($p)];
}
```

**`assets/js/courses/contoh.js`**
```js
// FILE: assets/js/courses/contoh.js
'use strict';
Courses.contoh = {
  nav: [['learn', 'Belajar', 'home', () => Courses.contoh.home()]],
  async home() {
    redraw = () => Courses.contoh.home();
    const d = await api('path');
    const rows = [1, 2, 3].map(n => el('button', { class: 'btn' + (d.progress['1:L' + n] ? ' alt' : ''), onclick: () => session(1, 1, n, () => Courses.contoh.home(), 'Unit') },
      `Lesson ${n} `, stars(d.progress['1:L' + n] || 0, 16)));
    shell('learn', topbar(), el('div', { class: 'wrap' }, el('h2', {}, 'Contoh'), el('p', { class: 'mut' }, 'Selesaikan tiga lesson.'), ...rows.flatMap(b => [b, el('p')])));
  },
  statCards: (s, card) => [card('check', s.selesai, 'Lesson selesai')],
  achievements: s => [{ icon: 'book', title: 'Murid Contoh', steps: [1, 3, 10], text: n => `Selesaikan ${n} lesson`, val: () => s.selesai }],
  summary: s => `${s.selesai} lesson selesai`,
};
```

## 8. Pemeriksaan sebelum menyerahkan
1. `php -l` untuk setiap berkas PHP.
2. Setiap soal: `choice` → `ans` ∈ `opts`; `fill` → `ans` sepanjang `blanks` dan setiap isi ∈ `opts`; `match` → id kiri dan kanan sama-sama unik dan sepasang.
3. Fungsi PHP diawali id course; semua query progres memuat `course`.
4. Tidak ada berkas platform yang diubah.

## 9. Kebutuhan platform (format laporan)
Bila course memerlukan tipe soal baru, ikon, gaya CSS, atau kolom basis data baru, tulis di akhir jawaban satu bagian "Kebutuhan platform" yang menjelaskan apa dan mengapa; **jangan** mengubah berkas platform di percakapan course.

## 10. Hal yang tidak ada di platform (jangan berasumsi)
Tidak ada pustaka UI, bundler, atau modul ES; tidak ada tipe soal selain `choice/fill/match`; tidak ada unggah berkas oleh pengguna; tidak ada pemberitahuan push.

## 11. Menguji
Tanpa proyek, kode course hanya bisa diperiksa secara statis (bagian 8). Untuk menjalankannya, unggah **zip proyek** (±95 KB) ke percakapan itu: ekstrak, letakkan berkas course, lalu `php -S localhost:8000` dan buka `app.php`. Pengujian API dapat memakai `curl` (`register` → `course_enroll` → `quiz` → `check` → `finish`).
