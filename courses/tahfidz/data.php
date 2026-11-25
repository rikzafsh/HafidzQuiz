<?php
// Data Al-Qur'an untuk course Tahfidz: teks ayat per surah (data/{n}.json), titik awal thumn (data/thumn.json), dan pembantu rentang ayat.
// ---- Data Al-Qur'an ----
function surah(int $id): array {
    static $c = [];
    if (!isset($c[$id])) {
        $f = ROOT . "/data/$id.json";
        $c[$id] = [];
        if (is_file($f)) foreach (json_decode(file_get_contents($f), true) ?: [] as $a) {
            $c[$id][] = [
                'id' => $a['id'], 'surah_id' => $a['surah_id'], 'ayah' => $a['ayah'], 'arabic' => $a['arabic'],
                'arabic_words' => $a['arabic_words'] ?? explode(' ', $a['arabic']),
                'surah' => ['transliteration' => $a['surah']['transliteration'], 'num_ayah' => $a['surah']['num_ayah'], 'arabic' => trim($a['surah']['arabic'] ?? '')],
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
        $t = array_values(json_decode(file_get_contents(ROOT . '/data/thumn.json'), true) ?: []); // urutan file = urutan hizb/thumn
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
function rangeLabel(array $ayat): string {
    $a = $ayat[0]; $b = end($ayat); $n = $a['surah']['transliteration'];
    return $a['surah_id'] == $b['surah_id'] ? "$n {$a['ayah']}–{$b['ayah']}" : "$n {$a['ayah']} – {$b['surah']['transliteration']} {$b['ayah']}";
}
