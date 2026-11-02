<?php
// Mengubah data/thumn.csv (sumber yang kamu edit) menjadi data/thumn.json (yang dibaca aplikasi), sambil memvalidasi.
//   php csv_ke_thumn.php [path/ke/file.csv]
// Kolom wajib: Hizb, Thumn, No_Surah, Ayat_Awal (pemisah ; atau , dikenali otomatis; Juz/Nisf/Rub opsional).
// Halaman (startingPage) diisi dari kolom "page" di data/{surah}.json bila ada; aplikasi tidak memakainya untuk menentukan ayat.
require __DIR__ . '/core.php';   // untuk AYAT_COUNT
$src = $argv[1] ?? __DIR__ . '/data/thumn.csv'; $dst = __DIR__ . '/data/thumn.json';
if (!is_file($src)) exit("File tidak ditemukan: $src\n");
$lines = array_values(array_filter(preg_split('/\R/', preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($src))), fn($l) => trim($l, " ;,\t") !== ''));
$sep = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';
$csv = fn($l) => str_getcsv($l, $sep, '"', '');
$head = array_map('trim', $csv(array_shift($lines))); $col = array_flip($head);
foreach (['Hizb', 'Thumn', 'No_Surah', 'Ayat_Awal'] as $k) if (!isset($col[$k])) exit("Kolom '$k' tidak ada. Kolom yang ditemukan: " . implode(' | ', $head) . "\n");
function pageOf($s, $a) { static $c = []; if (!isset($c[$s])) { $c[$s] = []; $f = __DIR__ . "/data/$s.json";
    if (is_file($f)) foreach (json_decode(file_get_contents($f), true) ?: [] as $x) $c[$s][(int)$x['ayah']] = (int)($x['page'] ?? 0); } return $c[$s][$a] ?? 0; }

$out = []; $err = []; $prev = 0;
foreach ($lines as $n => $l) {
    $r = $csv($l); $g = fn($k) => (int)trim($r[$col[$k]] ?? '0'); $row = $n + 2;
    [$h, $t, $s, $a] = [$g('Hizb'), $g('Thumn'), $g('No_Surah'), $g('Ayat_Awal')];
    if ($h != intdiv($n, 8) + 1 || $t != $n % 8 + 1) $err[] = "baris $row: hizb/thumn ($h/$t) tidak sesuai urutan, seharusnya " . (intdiv($n, 8) + 1) . '/' . ($n % 8 + 1);
    if ($s < 1 || $s > 114 || $a < 1 || $a > AYAT_COUNT[$s]) $err[] = "baris $row: ayat $s:$a tidak ada";
    elseif ($s * 1000 + $a <= $prev) $err[] = "baris $row: $s:$a tidak lebih besar dari titik awal sebelumnya";
    $prev = $s * 1000 + $a;
    $o = ['hizb_number' => $h, 'thumn' => $t, 'sura_number' => $s, 'starting_aya' => $a, 'startingPage' => pageOf($s, $a)];
    foreach (['Juz' => 'juz', 'Nisf' => 'nisf', 'Rub' => 'rub'] as $k => $key) if (isset($col[$k])) $o[$key] = $g($k);
    $out[] = $o;
}
if (count($out) !== 480) $err[] = 'jumlah baris data ' . count($out) . ' (seharusnya 480)';
if ($err) { echo "GAGAL, tidak ditulis:\n - " . implode("\n - ", $err) . "\n"; exit(1); }
file_put_contents($dst, json_encode($out, JSON_PRETTY_PRINT));
echo "OK: " . count($out) . " thumn ditulis ke data/thumn.json\n";
