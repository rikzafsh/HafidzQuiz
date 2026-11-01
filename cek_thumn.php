<?php
// Diagnostik data/thumn.json.  Jalankan:  php cek_thumn.php        (ringkas: hanya yang bermasalah)
//                                          php cek_thumn.php all    (semua baris)
// Atau buka di browser (?all=1). HAPUS file ini setelah selesai.
header('Content-Type: text/plain; charset=utf-8');
require __DIR__ . '/core.php';
$all = isset($_GET['all']) || (($argv[1] ?? '') === 'all');
$T = thumns(); $seen = []; $total = 0; $bad = 0;
printf("%-4s %-5s %-5s %-9s %-5s %s\n", '#', 'Hizb', 'Thumn', 'Mulai', 'Ayat', 'Rentang / Masalah');
foreach ($T as $i => $t) {
    $a = thumnAyat($i); $n = count($a); $total += $n; $f = [];
    $k = "{$t['hizb_number']}.{$t['thumn']}";
    if (isset($seen[$k])) $f[] = 'DUPLIKAT hizb+thumn'; $seen[$k] = 1;
    if (!$n) $f[] = 'KOSONG (titik awal sama dengan thumn berikutnya, atau file surah hilang)';
    if ($t['thumn'] < 1 || $t['thumn'] > 8) $f[] = 'nomor thumn di luar 1..8';
    if ($i && $t['hizb_number'] - $T[$i - 1]['hizb_number'] > 1) $f[] = 'LOMPAT hizb';
    if ($i && $t['hizb_number'] < $T[$i - 1]['hizb_number']) $f[] = 'hizb MUNDUR (urutan file tidak sesuai posisi mushaf)';
    if ($f) $bad++;
    if ($all || $f) printf("%-4d %-5d %-5d %-9s %-5d %s\n", $i, $t['hizb_number'], $t['thumn'], "{$t['sura_number']}:{$t['starting_aya']}", $n, $f ? '!! ' . implode('; ', $f) : rangeLabel($a));
}
for ($h = 1; $h <= 60; $h++) for ($x = 1; $x <= 8; $x++) if (!isset($seen["$h.$x"])) { echo "HILANG: Hizb $h Thumn $x tidak ada di thumn.json\n"; $bad++; }
echo "\nEntri thumn: " . count($T) . " (seharusnya 480) | total ayat tercakup: $total (seharusnya 6236) | masalah: $bad\n";