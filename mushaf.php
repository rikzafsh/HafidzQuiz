<?php
// Mushaf: halaman disusun dari data/mushaf.json (hasil xlsx_ke_mushaf.php). Tiap halaman = beberapa bagian (5 warna) yang mengalir di dalam baris.
require_once __DIR__ . '/quran_json_parser.php';

// Daftar bagian berurutan, lengkap dengan posisi awal [surah, ayat, kata-mulai] dan akhir [surah, ayat, kata-akhir|null].
function mushafSections(): array {
    static $S; if ($S !== null) return $S;
    $S = []; $f = __DIR__ . '/data/mushaf.json'; if (!is_file($f)) return $S;
    $d = json_decode(file_get_contents($f), true); $prev = null;
    foreach ($d['pages'] ?? [] as $pg) foreach ($pg['sections'] as $i => $sc) {
        if (!$prev) $start = [1, 1, 1];
        elseif ($prev[2] !== null) $start = [$prev[0], $prev[1], $prev[2] + 1];                       // lanjutan ayat yang terpotong
        else { [$s, $a] = [$prev[0], $prev[1]]; if ($a >= AYAT_COUNT[$s]) { $s++; $a = 1; } else $a++; $start = [$s, $a, 1]; }
        $end = [$sc['surah'], $sc['ayat_end'], $sc['word_count']];
        $S[] = ['page' => $pg['page'], 'n' => $i + 1, 'start' => $start, 'end' => $end]; $prev = $end;
    }
    return $S;
}
function mushafMaxPage(): int { $S = mushafSections(); return $S ? end($S)['page'] : 0; }
function mushafLastPos(): ?array { $S = mushafSections(); return $S ? end($S)['end'] : null; }

// Potongan kata ke-$from .. $to (1-based; $to null = sampai akhir). Tanda waqaf bukan kata: ikut kata sebelumnya.
function wordPart(array $tokens, int $from, ?int $to): string {
    $rc = 0; $out = [];
    foreach ($tokens as $t) {
        if (QuranJsonParser::isRealWord($t)) $rc++;
        if ($rc === 0 ? $from === 1 : ($rc >= $from && ($to === null || $rc <= $to))) $out[] = $t;
    }
    return implode(' ', $out);
}
function thumnStartMap(): array {   // posisi awal thumn (hanya entri yang konsisten) -> info thumn
    static $M; if ($M !== null) return $M; $M = [];
    foreach (thumns() as $i => $t) if ($t['ok']) $M[$t['pos']] = ['hizb' => $t['hizb_number'], 'thumn' => $t['thumn'], 'i' => $i];
    return $M;
}
function thumnIndexAt(int $s, int $a): ?int {   // thumn yang memuat ayat s:a
    $pos = $s * 1000 + $a; $best = null; foreach (thumns() as $i => $t) if ($t['ok'] && $t['pos'] <= $pos) $best = $i; return $best;
}
function surahNames(): array {   // 114 nama surah; di-cache setelah semua file surah tersedia
    static $N; if ($N) return $N;
    $f = __DIR__ . '/storage/surah_names.json';
    if (is_file($f) && is_array($c = json_decode(file_get_contents($f), true)) && count($c) === 114) return $N = $c;
    $N = []; $all = true;
    for ($s = 1; $s <= 114; $s++) {
        $m = surah($s)[0]['surah'] ?? null; if (!$m) $all = false;
        $N[] = ['n' => $s, 'latin' => $m['transliteration'] ?? "Surah $s", 'arabic' => $m['arabic'] ?? '', 'ayat' => AYAT_COUNT[$s]];
    }
    if ($all) { if (!is_dir(dirname($f))) @mkdir(dirname($f), 0750, true); @file_put_contents($f, json_encode($N, JSON_UNESCAPED_UNICODE)); }
    return $N;
}

function sectionItems(array $sc): array {
    [$s0, $a0, $w0] = $sc['start']; [$s1, $a1, $w1] = $sc['end']; $items = []; $tmap = thumnStartMap(); $names = surahNames();
    for ($s = $s0; $s <= $s1; $s++) {
        foreach (surah($s) as $a) {
            $n = $a['ayah'];
            if ($s == $s0 && $n < $a0) continue;
            if ($s == $s1 && $n > $a1) break;
            $from = ($s == $s0 && $n == $a0) ? $w0 : 1; $to = ($s == $s1 && $n == $a1) ? $w1 : null;   // null = sampai akhir ayat
            if ($n == 1 && $from == 1) $items[] = ['t' => 'surah', 'surah' => $s, 'name' => $names[$s - 1]['latin'], 'arabic' => $names[$s - 1]['arabic'], 'basmalah' => $s != 1 && $s != 9];
            $text = ($from == 1 && $to === null) ? $a['arabic'] : wordPart($a['arabic_words'], $from, $to);
            $tm = $from == 1 ? ($tmap[$s * 1000 + $n] ?? null) : null;       // awal thumn: sisipkan ۞ bila teks belum memilikinya
            $items[] = ['t' => 'ayat', 'surah' => $s, 'ayah' => $n, 'text' => $text, 'end' => $to === null,
                        'tm' => $tm ? ['hizb' => $tm['hizb'], 'thumn' => $tm['thumn']] : null, 'mark' => $tm && mb_strpos($text, '۞') === false];
        }
    }
    return $items;
}
function mushafPage(int $n): ?array {
    $secs = array_values(array_filter(mushafSections(), fn($x) => $x['page'] === $n)); if (!$secs) return null;
    $T = thumns(); [$fs, $fa] = $secs[0]['start']; $ti = thumnIndexAt($fs, $fa); $names = surahNames();
    return ['page' => $n, 'maxPage' => mushafMaxPage(), 'juz' => $ti !== null ? juzOf($T[$ti]) : null, 'hizb' => $ti !== null ? $T[$ti]['hizb_number'] : null,
            'thumn' => $ti !== null ? $T[$ti]['thumn'] : null, 'surah' => ['n' => $fs, 'latin' => $names[$fs - 1]['latin'], 'arabic' => $names[$fs - 1]['arabic']],
            'sections' => array_map(fn($sc) => ['n' => $sc['n'], 'items' => sectionItems($sc)], $secs)];
}
function mushafPageOf(int $s, int $a): ?int {   // halaman tempat ayat s:a mulai
    foreach (mushafSections() as $sc) if ([$sc['start'][0], $sc['start'][1]] <= [$s, $a] && [$s, $a] <= [$sc['end'][0], $sc['end'][1]]) return $sc['page'];
    return null;
}
function mushafFind(array $q): array {
    $type = $q['type'] ?? ''; $T = thumns(); $label = '';
    if ($type === 'page') {
        $p = (int)($q['page'] ?? 0); if ($p < 1 || $p > 604) fail('Nomor halaman harus 1–604.');
        if (!mushafPage($p)) fail("Halaman $p belum tersedia (data mushaf baru sampai halaman " . mushafMaxPage() . ').', 404);
        return ['page' => $p, 'target' => null, 'label' => "Halaman $p"];
    }
    if ($type === 'surah') {
        $s = (int)($q['surah'] ?? 0); $a = (int)($q['ayah'] ?? 1);
        if ($s < 1 || $s > 114) fail('Nomor surah harus 1–114.'); if ($a < 1 || $a > AYAT_COUNT[$s]) fail("Surah $s hanya punya " . AYAT_COUNT[$s] . ' ayat.');
        $label = "Surah $s ayat $a";
    } else {
        $n = (int)($q['n'] ?? 0); $e = null;
        if ($type === 'juz') { if ($n < 1 || $n > 30) fail('Nomor juz harus 1–30.'); foreach ($T as $t) if ($t['ok'] && juzOf($t) === $n) { $e = $t; break; } $label = "Juz $n"; }
        elseif ($type === 'hizb') { if ($n < 1 || $n > 60) fail('Nomor hizb harus 1–60.'); foreach ($T as $t) if ($t['ok'] && $t['hizb_number'] === $n) { $e = $t; break; } $label = "Hizb $n"; }
        elseif ($type === 'thumn') {
            $h = (int)($q['hizb'] ?? 0); $th = (int)($q['thumn'] ?? 0); if ($h < 1 || $h > 60 || $th < 1 || $th > 8) fail('Hizb harus 1–60 dan thumn 1–8.');
            foreach ($T as $t) if ($t['ok'] && $t['hizb_number'] === $h && $t['thumn'] === $th) { $e = $t; break; } $label = "Hizb $h · Thumn $th";
        } else fail('Jenis navigasi tidak dikenal.');
        if (!$e) fail("$label tidak ada di data thumn.", 404);
        [$s, $a] = [$e['sura_number'], $e['starting_aya']]; $label .= " (mulai di $s:$a)";
    }
    $p = mushafPageOf($s, $a); $lp = mushafLastPos();
    if (!$p) fail("$label belum tersedia di data mushaf (baru sampai halaman " . mushafMaxPage() . ($lp ? ", ayat {$lp[0]}:{$lp[1]}" : '') . ').', 404);
    return ['page' => $p, 'target' => ['surah' => $s, 'ayah' => $a], 'label' => $label];
}