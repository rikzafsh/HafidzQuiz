<?php
// Diagnostik & usulan perbaikan data/thumn.json.
//   php cek_thumn.php        -> hanya yang bermasalah (+ menulis data/thumn.fixed.json bila ada perbaikan pasti)
//   php cek_thumn.php all    -> tampilkan semua thumn
// Atau buka di browser (?all=1). Periksa data/thumn.fixed.json, lalu ganti nama menjadi thumn.json. HAPUS file ini setelah selesai.
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/core.php';
$all = isset($_GET['all']) || (($argv[1] ?? '') === 'all');
$T = thumns(); $n = count($T);

function raw($s) { // ayah => halaman, dari data mentah
    static $c = [];
    if (!isset($c[$s])) { $f = __DIR__ . "/data/$s.json"; $c[$s] = [];
        if (is_file($f)) foreach (json_decode(file_get_contents($f), true) ?: [] as $a) $c[$s][(int)$a['ayah']] = (int)($a['page'] ?? 0); }
    return $c[$s];
}
function variants($n) { // kemungkinan salah ketik 1 digit: ganti, tukar, hapus
    $s = (string)$n; $o = []; $L = strlen($s);
    for ($i = 0; $i < $L; $i++) { for ($d = 0; $d <= 9; $d++) { $v = $s; $v[$i] = (string)$d; $o[] = (int)$v; } if ($L > 1) $o[] = (int)(substr($s, 0, $i) . substr($s, $i + 1)); }
    for ($i = 0; $i + 1 < $L; $i++) { $v = $s; [$v[$i], $v[$i + 1]] = [$v[$i + 1], $v[$i]]; $o[] = (int)$v; }
    return array_values(array_unique(array_filter($o)));
}
function lbl($t) { return "Hizb {$t['hizb_number']} Thumn {$t['thumn']}"; }

$m = $c = 0;
foreach ($T as $t) if ($t['ok'] && ($p = raw($t['sura_number'])[$t['starting_aya']] ?? null) !== null) { $c++; $m += ($p === $t['startingPage']); }
$pageOK = $c && $m / $c >= 0.8;
echo "Entri: $n (seharusnya 480) | halaman cocok pada entri konsisten: $m/$c" . ($pageOK ? "\n" : " -> kolom halaman tidak sebanding dengan data ayat; usulan berbasis halaman dimatikan\n");

$seen = []; $fix = []; $manual = 0; $bad = 0; $total = 0;
foreach ($T as $i => $t) {
    $k = "{$t['hizb_number']}.{$t['thumn']}";
    if (isset($seen[$k])) echo "\n!! #$i " . lbl($t) . ": label hizb+thumn DUPLIKAT\n"; $seen[$k] = 1;
    $ay = thumnAyat($i); $total += count($ay);
    if ($t['ok']) {
        if (!$ay) { echo "\n!! #$i " . lbl($t) . " @ {$t['sura_number']}:{$t['starting_aya']} - tidak ada ayat (file data surah hilang?)\n"; $bad++; }
        elseif ($all) printf("#%-3d %-16s %-9s %3d ayat  %s\n", $i, lbl($t), "{$t['sura_number']}:{$t['starting_aya']}", count($ay), rangeLabel($ay));
        continue;
    }
    $bad++;
    for ($p = $i - 1; $p >= 0 && !$T[$p]['ok']; $p--); for ($q = $i + 1; $q < $n && !$T[$q]['ok']; $q++);
    $lo = $p >= 0 ? $T[$p]['pos'] : 0; $hi = $q < $n ? $T[$q]['pos'] : PHP_INT_MAX;
    [$s0, $a0, $pg] = [$t['sura_number'], $t['starting_aya'], $t['startingPage']];
    $ref = fn($x) => "{$x['sura_number']}:{$x['starting_aya']} (" . lbl($x) . ")";
    printf("\n!! #%d %s tercatat %d:%d (hal. %d) - tidak konsisten\n   seharusnya di antara %s dan %s\n", $i, lbl($t), $s0, $a0, $pg, $p >= 0 ? $ref($T[$p]) : 'awal', $q < $n ? $ref($T[$q]) : 'akhir');
    if (!isset(raw($s0)[$a0])) echo "   catatan: ayat $s0:$a0 sebenarnya tidak ada.\n";
    if (!$pageOK) { $manual++; echo "   -> perbaiki manual.\n"; continue; }
    $in = fn($s, $a) => $s * 1000 + $a > $lo && $s * 1000 + $a < $hi;
    $h1 = $h2 = [];                                                        // H1: nomor surah keliru | H2: nomor ayat keliru
    for ($s = 1; $s <= 114; $s++) if ((raw($s)[$a0] ?? null) === $pg && $in($s, $a0)) $h1[] = "$s:$a0";
    foreach (raw($s0) as $a => $pp) if ($pp === $pg && $in($s0, $a)) $h2[] = "$s0:$a";
    $pick = count($h1) === 1 ? $h1[0] : (!$h1 && count($h2) === 1 ? $h2[0] : null); $how = 'PERBAIKAN PASTI';
    if (!$pick) { // kandidat di halaman yang sama yang hanya beda 1 digit dari yang tercatat
        $vv = variants($a0); $h3 = array_values(array_filter($h2, fn($x) => in_array((int)explode(':', $x)[1], $vv, true)));
        if (count($h3) === 1) { $pick = $h3[0]; $how = 'KEMUNGKINAN BESAR (beda 1 digit dari yang tercatat)'; }
    }
    if ($pick) { $fix[$i] = array_map('intval', explode(':', $pick)); echo "   -> $how: $pick (halaman cocok, urutan terpenuhi)\n"; continue; }
    $cand = array_merge($h1, $h2);
    if (!$cand) for ($s = intdiv($lo, 1000); $s <= min(114, intdiv(min($hi, 114999), 1000)); $s++)
        foreach (raw($s) as $a => $pp) if ($pp === $pg && $in($s, $a)) $cand[] = "$s:$a";
    $manual++; echo "   -> pilih manual, kandidat di halaman $pg: " . ($cand ? implode(', ', array_slice($cand, 0, 15)) : '(tidak ada)') . "\n";
}
for ($h = 1; $h <= 60; $h++) for ($x = 1; $x <= 8; $x++) if (!isset($seen["$h.$x"])) { echo "HILANG: Hizb $h Thumn $x tidak ada di thumn.json\n"; $bad++; }

if ($fix) {
    $out = []; $prev = 0; $viol = 0;
    foreach ($T as $i => $t) {
        $o = array_intersect_key($t, array_flip(['hizb_number', 'thumn', 'sura_number', 'starting_aya', 'startingPage']));
        if (isset($fix[$i])) [$o['sura_number'], $o['starting_aya']] = $fix[$i];
        $pos = $o['sura_number'] * 1000 + $o['starting_aya']; if ($pos <= $prev) $viol++; $prev = $pos; $out[] = $o;
    }
    file_put_contents(__DIR__ . '/data/thumn.fixed.json', json_encode($out, JSON_PRETTY_PRINT));
    echo "\nDitulis data/thumn.fixed.json (" . count($fix) . " perbaikan pasti). Pelanggaran urutan yang tersisa di file itu: $viol" . ($manual ? " (sisa $manual entri perlu dipilih manual)" : '') . "\n";
}
echo "\nTotal ayat tercakup: $total (seharusnya 6236) | masalah: $bad | perlu manual: $manual\n";