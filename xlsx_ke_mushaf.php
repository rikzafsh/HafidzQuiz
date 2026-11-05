<?php
// Mengubah data pembagian halaman mushaf (Excel .xlsx, atau .csv) menjadi data/mushaf.json yang dibaca aplikasi, sambil memvalidasi.
//   php xlsx_ke_mushaf.php [file.xlsx|file.csv]        (bawaan: data/Mushaf_Page_Division.xlsx)
// Kolom: No | Page | Section | Surah | Ayat_End | Word_Count
//   Satu baris = satu bagian; Surah+Ayat_End = ujung bagian itu. Word_Count (opsional) = bagian berakhir SETELAH kata ke-N dari ayat Ayat_End
//   (dihitung kumulatif dari awal ayat; tanda waqaf bukan kata). Kosong = bagian berakhir di akhir ayat.
// Kolom Page/Section boleh berupa rumus; nilai tersimpan Excel dipakai, dan rumus sederhana (=B4+1, =C4) dihitung ulang bila tidak ada nilainya.
require __DIR__ . '/core.php';   // AYAT_COUNT, surah()
require_once __DIR__ . '/quran_json_parser.php';
$src = $argv[1] ?? __DIR__ . '/data/Mushaf_Page_Division.xlsx'; $dst = __DIR__ . '/data/mushaf.json';
if (!is_file($src)) exit("File tidak ditemukan: $src\n");

function xmlText($s) { return html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'); }
function xmlAttrs($tag) { preg_match_all('/([\w:]+)="([^"]*)"/', $tag, $m, PREG_SET_ORDER); $a = []; foreach ($m as $x) $a[$x[1]] = xmlText($x[2]); return $a; }
function readXlsx($file) {   // -> [barisKe => [kolomHuruf => ['v' => nilai, 'f' => rumus]]]. Hanya butuh ekstensi 'zip' (tanpa SimpleXML).
    if (!class_exists('ZipArchive')) exit("Ekstensi PHP 'zip' tidak aktif. Aktifkan, atau simpan Excel sebagai CSV lalu jalankan ulang dengan file .csv.\n");
    $z = new ZipArchive; if ($z->open($file) !== true) exit("Tidak bisa membuka $file (bukan file .xlsx yang valid).\n");
    $ss = [];
    if (($x = $z->getFromName('xl/sharedStrings.xml')) !== false && preg_match_all('#<si\b[^>]*>(.*?)</si>#s', $x, $si))
        foreach ($si[1] as $body) { preg_match_all('#<t\b[^>]*>(.*?)</t>#s', $body, $t); $ss[] = xmlText(implode('', $t[1])); }
    $path = 'xl/worksheets/sheet1.xml'; $wb = (string)$z->getFromName('xl/workbook.xml');
    if (preg_match('#<sheet\b[^>]*>#', $wb, $m) && ($rid = xmlAttrs($m[0])['r:id'] ?? null) && ($rels = $z->getFromName('xl/_rels/workbook.xml.rels')) !== false && preg_match_all('#<Relationship\b[^>]*>#', $rels, $rm))
        foreach ($rm[0] as $tag) { $at = xmlAttrs($tag); if (($at['Id'] ?? '') === $rid) $path = 'xl/' . ltrim(preg_replace('#^/?xl/#', '', $at['Target'] ?? ''), '/'); }
    $xml = $z->getFromName($path); if ($xml === false) exit("Lembar kerja pertama tidak ditemukan di $file.\n");
    $cells = [];
    preg_match_all('#<c\b([^>]*?)(?:/>|>(.*?)</c>)#s', $xml, $cm, PREG_SET_ORDER);
    foreach ($cm as $c) {
        $at = xmlAttrs($c[1]); if (!preg_match('/^([A-Z]+)(\d+)$/', $at['r'] ?? '', $m)) continue; $body = $c[2] ?? ''; $t = $at['t'] ?? '';
        $v = preg_match('#<v\b[^>]*>(.*?)</v>#s', $body, $vm) ? xmlText($vm[1]) : null;
        if ($t === 's' && $v !== null) $v = $ss[(int)$v] ?? null; elseif ($t === 'inlineStr') { preg_match_all('#<t\b[^>]*>(.*?)</t>#s', $body, $tm); $v = xmlText(implode('', $tm[1])); }
        $f = preg_match('#<f\b[^>]*>(.*?)</f>#s', $body, $fm) ? xmlText($fm[1]) : (preg_match('#<f\b[^>]*/>#', $body) ? '' : null);
        $cells[(int)$m[2]][$m[1]] = ['v' => $v, 'f' => $f];
    }
    return $cells;
}
function readCsv($file) {
    $lines = array_values(array_filter(preg_split('/\R/', preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($file))), fn($l) => trim($l, " ;,\t") !== ''));
    $sep = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ','; $cells = [];
    foreach ($lines as $i => $l) foreach (str_getcsv($l, $sep, '"', '') as $j => $v) $cells[$i + 1][chr(65 + $j)] = ['v' => $v === '' ? null : $v, 'f' => null];
    return $cells;
}
$cells = preg_match('/\.csv$/i', $src) ? readCsv($src) : readXlsx($src);
function cellVal($cells, $col, $row, $depth = 0) {   // nilai sel; rumus sederhana dihitung bila nilai tersimpan tidak ada
    $c = $cells[$row][$col] ?? null; if (!$c) return null; if ($c['v'] !== null && $c['v'] !== '') return $c['v'];
    if ($c['f'] !== null && $depth < 500 && preg_match('/^=?\$?([A-Z]+)\$?(\d+)([+-]\d+)?$/', trim($c['f']), $m)) {
        $v = cellVal($cells, $m[1], (int)$m[2], $depth + 1); return $v === null ? null : (isset($m[3]) ? (string)((float)$v + (int)$m[3]) : $v);
    }
    if ($c['f'] !== null) exit("Rumus tidak didukung di {$col}{$row}: {$c['f']} (tidak ada nilai tersimpan). Buka file di Excel, simpan ulang, lalu coba lagi.\n");
    return null;
}
// ---- peta kolom dari baris header ----
$alias = ['page' => ['page', 'halaman'], 'section' => ['section', 'bagian'], 'surah' => ['surah'], 'ayat' => ['ayat_end', 'ayat', 'ayatakhir'], 'wc' => ['word_count', 'wordcount', 'kata']];
$hdr = []; foreach (($cells[1] ?? []) as $col => $c) { $name = strtolower(trim((string)$c['v'])); foreach ($alias as $k => $al) if (in_array($name, $al, true)) $hdr[$k] = $col; }
foreach (['page', 'section', 'surah', 'ayat'] as $k) if (!isset($hdr[$k])) exit("Kolom '$k' tidak ditemukan di baris 1. Header yang diharapkan: No | Page | Section | Surah | Ayat_End | Word_Count\n");
$err = []; $warn = []; $pages = []; $prev = null; $splits = []; $rowsN = 0; $maxRow = max(array_keys($cells));
$nWords = function ($s, $a) { static $c = []; if (!isset($c[$s])) { $c[$s] = []; foreach (surah($s) as $x) $c[$s][$x['ayah']] = count(array_filter($x['arabic_words'], 'isRealWordX')); } return $c[$s][$a] ?? null; };
function isRealWordX($w) { return QuranJsonParser::isRealWord($w); }
for ($r = 2; $r <= $maxRow; $r++) {
    $g = fn($k) => isset($hdr[$k]) ? cellVal($cells, $hdr[$k], $r) : null;
    [$pg, $sec, $su, $ae, $wc] = [$g('page'), $g('section'), $g('surah'), $g('ayat'), $g('wc')];
    if ($pg === null && $sec === null && $su === null && $ae === null) continue;                    // baris kosong
    $rowsN++; $pg = (int)$pg; $sec = (int)$sec; $su = (int)$su; $ae = (int)$ae; $wc = ($wc === null || $wc === '') ? null : (int)$wc;
    if ($su < 1 || $su > 114 || $ae < 1 || $ae > AYAT_COUNT[$su]) { $err[] = "baris $r: ayat $su:$ae tidak ada"; continue; }
    $lastPg = $pages ? array_key_last($pages) : 0;
    if ($pg !== $lastPg && $pg !== $lastPg + 1) $err[] = "baris $r: halaman $pg melompat dari halaman $lastPg (halaman harus berurutan tanpa lubang, mulai dari 1)";
    $expSec = $pg === $lastPg ? count($pages[$pg]) + 1 : 1; if ($sec !== $expSec) $err[] = "baris $r: bagian $sec di halaman $pg, seharusnya $expSec";
    if ($prev) {
        [$ps, $pa, $pw] = $prev;
        if ([$su, $ae] < [$ps, $pa]) $err[] = "baris $r: $su:$ae mundur dari $ps:$pa";
        elseif ([$su, $ae] == [$ps, $pa]) {
            if ($pw === null) $err[] = "baris $r: ayat $su:$ae sudah berakhir di bagian sebelumnya, tetapi muncul lagi";
            elseif ($wc !== null && $wc <= $pw) $err[] = "baris $r: Word_Count $wc harus lebih besar dari $pw (dihitung kumulatif dari awal ayat)";
        }
    }
    if ($wc !== null) {
        $tot = $nWords($su, $ae); $splits[] = "hal.$pg bagian $sec: $su:$ae setelah kata ke-$wc" . ($tot ? " dari $tot" : '');
        if ($wc < 1) $err[] = "baris $r: Word_Count harus >= 1"; elseif ($tot !== null && $tot > 0 && $wc >= $tot) $err[] = "baris $r: Word_Count $wc >= jumlah kata ayat $su:$ae ($tot); kosongkan bila bagian berakhir di akhir ayat";
    }
    $pages[$pg][] = ['section' => $sec, 'surah' => $su, 'ayat_end' => $ae, 'word_count' => $wc]; $prev = [$su, $ae, $wc];
}
if (!$pages) $err[] = 'tidak ada baris data.';
if ($err) { echo "GAGAL, data/mushaf.json tidak ditulis:\n - " . implode("\n - ", array_slice($err, 0, 40)) . (count($err) > 40 ? "\n   ... dan " . (count($err) - 40) . " lagi" : '') . "\n"; exit(1); }
$out = ['source' => basename($src), 'pages' => []]; foreach ($pages as $p => $secs) $out['pages'][] = ['page' => $p, 'sections' => $secs];
file_put_contents($dst, json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$cnt = array_count_values(array_map('count', $pages)); ksort($cnt);
echo "OK: " . count($pages) . " halaman (1-" . array_key_last($pages) . "), $rowsN bagian -> data/mushaf.json\n";
echo "Bagian per halaman: " . implode(', ', array_map(fn($k, $v) => "$k bagian: $v halaman", array_keys($cnt), $cnt)) . "\n";
echo "Berakhir di: {$prev[0]}:{$prev[1]}" . ($prev[2] ? " (setelah kata ke-{$prev[2]}, di tengah ayat)" : '') . "\n";
echo "Bagian yang terpotong di tengah ayat (" . count($splits) . "):\n  " . implode("\n  ", $splits) . "\n";