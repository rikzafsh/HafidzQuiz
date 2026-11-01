<?php
// Inti: sesi, database (SQLite), dan struktur kursus (juz -> thumn -> lesson/test)
date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function db(): PDO {
    static $p;
    if (!$p) {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir)) { mkdir($dir, 0750, true); file_put_contents("$dir/.htaccess", "Require all denied\n"); }
        $p = new PDO('sqlite:' . $dir . '/hafidz.sqlite');
        $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $p->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, hash TEXT NOT NULL, xp INTEGER DEFAULT 0, streak INTEGER DEFAULT 0, last_day TEXT);
                  CREATE TABLE IF NOT EXISTS progress(user_id INTEGER, thumn INTEGER, node TEXT, stars INTEGER, best INTEGER, PRIMARY KEY(user_id, thumn, node));");
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
function thumns(): array { static $t; return $t ??= json_decode(file_get_contents(__DIR__ . '/data/thumn.json'), true); }
function juzOf(array $t): int { return (int)ceil($t['hizb_number'] / 2); }

// Ayat thumn ke-$i: dari titik awalnya sampai sebelum titik awal thumn berikutnya
function thumnAyat(int $i): array {
    $T = thumns(); if (!isset($T[$i])) return [];
    $s = $T[$i]; $e = $T[$i + 1] ?? null; $out = [];
    for ($su = $s['sura_number']; $su <= ($e ? $e['sura_number'] : 114); $su++) {
        foreach (surah($su) as $a) {
            if ($su == $s['sura_number'] && $a['ayah'] < $s['starting_aya']) continue;
            if ($e && $su == $e['sura_number'] && $a['ayah'] >= $e['starting_aya']) break;
            $out[] = $a;
        }
    }
    return $out;
}
// 1 thumn -> 1..5 lesson (sekitar 6 ayat/lesson), lalu 1 test
function lessons(array $ayat): array {
    $n = count($ayat); if (!$n) return [];
    $k = max(1, min(5, (int)ceil($n / 6)));
    return array_chunk($ayat, (int)ceil($n / $k));
}
function nodesOf(array $ayat): array {
    $n = []; foreach (lessons($ayat) as $i => $_) $n[] = 'L' . ($i + 1);
    $n[] = 'T'; return $n;
}
function rangeLabel(array $ayat): string {
    $a = $ayat[0]; $b = end($ayat); $n = $a['surah']['transliteration'];
    return $a['surah_id'] == $b['surah_id'] ? "$n {$a['ayah']}–{$b['ayah']}" : "$n {$a['ayah']} – {$b['surah']['transliteration']} {$b['ayah']}";
}
