<?php
// Inti: sesi, database (SQLite), dan struktur kursus (juz -> thumn -> lesson/test)
date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// ===== MODE DEVELOPER SEMENTARA: ubah ke false (atau hapus) setelah pengetesan selesai =====
const DEV_MODE = true;

function db(): PDO {
    static $p;
    if (!$p) {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir)) { mkdir($dir, 0750, true); file_put_contents("$dir/.htaccess", "Require all denied\n"); }
        $p = new PDO('sqlite:' . $dir . '/hafidz.sqlite');
        $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $p->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, hash TEXT NOT NULL, xp INTEGER DEFAULT 0, streak INTEGER DEFAULT 0, last_day TEXT, dirham INTEGER DEFAULT 0);
                  CREATE TABLE IF NOT EXISTS progress(user_id INTEGER, thumn INTEGER, node TEXT, stars INTEGER, best INTEGER, PRIMARY KEY(user_id, thumn, node));");
        $cols = array_column($p->query('PRAGMA table_info(users)')->fetchAll(), 'name');   // migrasi untuk database lama
        if (!in_array('dirham', $cols, true)) $p->exec('ALTER TABLE users ADD COLUMN dirham INTEGER DEFAULT 0');
    }
    return $p;
}
function out($d, $code = 200) { while (ob_get_level()) ob_end_clean(); http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail($m, $c = 400) { out(['error' => $m], $c); }

// ---- Data Al-Qur'an ----
function surah(int $id): array {
    static $c = [];
    if (!isset($c[$id])) {
        $f = __DIR__ . "/data/$id.json";
        $c[$id] = [];
        if (is_file($f)) foreach (json_decode(file_get_contents($f), true) ?: [] as $a) {
            $c[$id][] = [
                'id' => $a['id'], 'surah_id' => $a['surah_id'], 'ayah' => $a['ayah'], 'arabic' => $a['arabic'],
                'arabic_words' => $a['arabic_words'] ?? explode(' ', $a['arabic']),
                'surah' => ['transliteration' => $a['surah']['transliteration'], 'num_ayah' => $a['surah']['num_ayah']],
            ];
        }
    }
    return $c[$id];
}
// Jumlah ayat tiap surah (Hafs) untuk memeriksa titik awal thumn yang tidak mungkin ada
const AYAT_COUNT = [0, 7, 286, 200, 176, 120, 165, 206, 75, 129, 109, 123, 111, 43, 52, 99, 128, 111, 110, 98, 135, 112, 78, 118, 64, 77, 227, 93, 88, 69, 60, 34, 30, 73, 54, 45, 83, 182, 88, 75, 85, 54, 53, 89, 59, 37, 35, 38, 29, 18, 45, 60, 49, 62, 55, 78, 96, 29, 22, 24, 13, 14, 11, 11, 18, 12, 12, 30, 52, 52, 44, 28, 28, 20, 56, 40, 31, 50, 40, 46, 42, 29, 19, 36, 25, 22, 17, 19, 26, 30, 20, 15, 21, 11, 8, 8, 19, 5, 8, 8, 11, 11, 8, 3, 9, 5, 4, 7, 3, 6, 3, 5, 4, 5, 6];
function thumns(): array {
    static $t;
    if ($t === null) {
        $t = array_values(json_decode(file_get_contents(__DIR__ . '/data/thumn.json'), true) ?: []); // urutan file = urutan hizb/thumn
        foreach ($t as &$x) {
            foreach (['hizb_number', 'thumn', 'sura_number', 'starting_aya', 'startingPage'] as $k) $x[$k] = (int)($x[$k] ?? 0);
            $x['pos'] = $x['sura_number'] * 1000 + $x['starting_aya']; $x['ok'] = false;
            $x['real'] = $x['sura_number'] >= 1 && $x['sura_number'] <= 114 && $x['starting_aya'] >= 1 && $x['starting_aya'] <= AYAT_COUNT[$x['sura_number']]; // ayatnya benar-benar ada?
        }
        unset($x);
        // Titik awal harus selalu naik. Entri yang melanggar (salah ketik di data) dikecualikan lewat
        // rantai naik terpanjang (LIS), sehingga entri lain tidak ikut salah.
        $n = count($t); $len = array_fill(0, $n, 1); $prv = array_fill(0, $n, -1); $best = -1;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $i; $j++) if ($t[$j]['real'] && $t[$i]['real'] && $t[$j]['pos'] < $t[$i]['pos'] && $len[$j] + 1 > $len[$i]) { $len[$i] = $len[$j] + 1; $prv[$i] = $j; }
            if ($t[$i]['real'] && ($best < 0 || $len[$i] > $len[$best])) $best = $i;
        }
        for ($i = $best; $i >= 0; $i = $prv[$i]) $t[$i]['ok'] = true;
    }
    return $t;
}
function juzOf(array $t): int { return (int)ceil($t['hizb_number'] / 2); }

// Ayat thumn ke-$i: dari titik awalnya sampai sebelum titik awal thumn berikutnya
function thumnAyat(int $i): array {
    $T = thumns(); if (!isset($T[$i]) || !$T[$i]['ok']) return [];
    $s = $T[$i]; $e = null; $out = [];
    for ($j = $i + 1; $j < count($T); $j++) if ($T[$j]['ok']) { $e = $T[$j]; break; } // batas = titik awal konsisten berikutnya
    for ($su = $s['sura_number']; $su <= ($e ? $e['sura_number'] : 114); $su++) {
        foreach (surah($su) as $a) {
            if ($su == $s['sura_number'] && $a['ayah'] < $s['starting_aya']) continue;
            if ($e && $su == $e['sura_number'] && $a['ayah'] >= $e['starting_aya']) break;
            $out[] = $a;
        }
    }
    return $out;
}
// 1 thumn -> 1..5 node (sekitar 6 ayat/node), lalu 1 test. Tiap node berisi 3..5 lesson.
function nodeChunks(array $ayat): array {
    $n = count($ayat); if (!$n) return [];
    $k = max(1, min(5, (int)ceil($n / 6)));
    return array_chunk($ayat, (int)ceil($n / $k));
}
function lessonCount(array $chunk): int { $n = count($chunk); return $n <= 3 ? 3 : ($n <= 6 ? 4 : 5); }
function rangeLabel(array $ayat): string {
    $a = $ayat[0]; $b = end($ayat); $n = $a['surah']['transliteration'];
    return $a['surah_id'] == $b['surah_id'] ? "$n {$a['ayah']}–{$b['ayah']}" : "$n {$a['ayah']} – {$b['surah']['transliteration']} {$b['ayah']}";
}