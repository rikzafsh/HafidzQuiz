<?php
// Generator soal. Soal diambil dari ayat $src; pengecoh dari seluruh thumn ($pool).
// Struktur: 1 thumn = 8 level. Dasar (1-3) -> Menengah (4-5) -> Mahir (6-8). Level 8 = ujian akhir thumn.
require_once __DIR__ . '/quran_json_parser.php';
const ALM = "\u{061C}";
const LET = '\x{0621}-\x{064A}\x{0671}';
const HAR = '\x{064B}-\x{0652}';
const WQF = '[^' . LET . HAR . ']*';
const FATHA = "\u{064E}", DAMMA = "\u{064F}", KASRA = "\u{0650}", SUKUN = "\u{0652}", SHADDA = "\u{0651}", DAGGER = "\u{0670}";

// Bobot tipe soal per level (makin besar = makin sering)
const LEVEL_TYPES = [
    1 => ['lengkapi' => 3, 'sambung' => 3],
    2 => ['lengkapi' => 3, 'sambung' => 3, 'sesudah' => 2],
    3 => ['lengkapi' => 2, 'sambung' => 3, 'sesudah' => 3],
    4 => ['lengkapi' => 2, 'sambung' => 2, 'sesudah' => 2, 'sebelum' => 3, 'nomor' => 3, 'ikrab' => 2],
    5 => ['lengkapi' => 2, 'sambung' => 2, 'sesudah' => 2, 'sebelum' => 2, 'nomor' => 2, 'ikrab' => 3],
    6 => ['lengkapi' => 1, 'sambung' => 2, 'sesudah' => 1, 'sebelum' => 1, 'nomor' => 1, 'ikrab' => 1,
          'match_ends' => 3, 'match_nomor' => 2, 'terakhir' => 2, 'irab' => 2, 'mirip' => 2],
    7 => ['lengkapi' => 1, 'sambung' => 1, 'sesudah' => 1, 'sebelum' => 1, 'nomor' => 1, 'ikrab' => 1,
          'match_ends' => 2, 'match_nomor' => 2, 'terakhir' => 3, 'irab' => 3, 'mirip' => 3],
    8 => ['lengkapi' => 1, 'sambung' => 1, 'sesudah' => 1, 'sebelum' => 1, 'nomor' => 1, 'ikrab' => 1,
          'match_ends' => 3, 'match_nomor' => 3, 'terakhir' => 3, 'irab' => 3, 'mirip' => 3],
];

// ---------- Utilitas teks Arab ----------
function arNum($n) { return strtr((string)$n, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩']); }
function isReal($w) { return QuranJsonParser::isRealWord($w); }
function fullText($a) { return $a['arabic'] . ' ' . arNum($a['ayah']) . ALM; }
function splitAyah($a, $rand = true) { return QuranJsonParser::splitWords($a['arabic_words'], $rand); }
function stripTail($w) { return preg_replace('/[^' . LET . HAR . '\x{0670}]+$/u', '', $w); }   // buang tanda waqaf di ujung kata
function realWords($a) { return array_values(array_map('stripTail', array_filter($a['arabic_words'], 'isReal'))); }
function skel($w) { return preg_replace('/[^' . LET . ']+/u', '', $w); }                          // kerangka huruf tanpa harakat
function lastReal($a) { $r = realWords($a); return $r ? end($r) : null; }
function sim($x, $y) {   // kemiripan: akhiran sama (rima fasilah) paling berbobot, lalu awalan, lalu panjang
    $a = preg_split('//u', $x, -1, PREG_SPLIT_NO_EMPTY); $b = preg_split('//u', $y, -1, PREG_SPLIT_NO_EMPTY);
    $c = 0; $i = count($a) - 1; $j = count($b) - 1;
    while ($i >= 0 && $j >= 0 && $a[$i] === $b[$j]) { $c++; $i--; $j--; }
    $p = 0; while ($p < min(count($a), count($b)) && $a[$p] === $b[$p]) $p++;
    return 2 * $c + 0.5 * $p - 0.7 * abs(count($a) - count($b));
}
function others($pool, $ex, $max) {
    $ids = array_column($ex, 'id'); $txt = array_column($ex, 'arabic'); shuffle($pool); $o = [];
    foreach ($pool as $p) {
        if (in_array($p['id'], $ids) || in_array($p['arabic'], $txt, true)) continue;
        $o[] = $p; $txt[] = $p['arabic']; if (count($o) >= $max) break;
    }
    return $o;
}
function choice($mode, $title, $show, $opts, $ans, $a, $extra = []) {
    $opts = array_values(array_unique($opts)); shuffle($opts);
    return array_merge(['t' => 'choice', 'mode' => $mode, 'title' => $title, 'show' => $show, 'opts' => $opts, 'ans' => (string)$ans, 'full' => fullText($a)], $extra);
}
function blankAt($a, $k) { $t = $a['arabic_words']; $t[$k] = '＿＿＿'; return $t; }

// ---------- Analisis huruf & harakat (untuk soal i'rab dan kata mirip) ----------
function clusters($w) {   // [[huruf, [tanda...]], ...]; null bila ada karakter yang tidak didukung
    $w = stripTail($w);
    if (!preg_match_all('/([' . LET . '])([' . HAR . '\x{0670}]*)/u', $w, $m, PREG_SET_ORDER)) return null;
    $out = []; $re = '';
    foreach ($m as $x) { $out[] = [$x[1], preg_split('//u', $x[2], -1, PREG_SPLIT_NO_EMPTY)]; $re .= $x[0]; }
    return $re === $w ? $out : null;
}
function markRank($m) { return $m === SHADDA ? 0 : ($m === DAGGER ? 2 : 1); }
function build($cl) {     // bentuk kanonik (syaddah dulu, lalu harakat)
    $s = '';
    foreach ($cl as [$b, $mk]) { usort($mk, fn($x, $y) => markRank($x) <=> markRank($y)); $s .= $b . implode('', $mk); }
    return $s;
}
function caseVowel($mk) { foreach ($mk as $m) if (in_array($m, [FATHA, DAMMA, KASRA], true)) return $m; return null; }
function withVowel($mk, $v) { $o = array_values(array_filter($mk, fn($m) => !in_array($m, [FATHA, DAMMA, KASRA, SUKUN], true))); $o[] = $v; return $o; }
// Damir/akhiran yang menempel di akhir kata -> [jenis, jumlah cluster batang] atau null
function pronSuffix($cl) {
    $n = count($cl); if ($n < 3) return null;
    [$lb, $lm] = $cl[$n - 1]; [$pb, $pm] = $cl[$n - 2]; $hv = [[KASRA], [DAMMA]];
    if ($n >= 4 && $lb === 'م' && $lm === [SUKUN]) {
        if ($pb === 'ه' && in_array($pm, $hv, true)) return ['hm', $n - 2];
        if ($pb === 'ك' && $pm === [DAMMA]) return ['km', $n - 2];
    }
    if ($n >= 4 && $lb === 'ا' && !$lm && $pb === 'ه' && $pm === [FATHA]) return ['ha', $n - 2];
    if ($n >= 4 && $lb === 'ن' && count($lm) === 2 && in_array(SHADDA, $lm, true) && in_array(FATHA, $lm, true) && $pb === 'ه' && in_array($pm, $hv, true)) return ['hn', $n - 2];
    if ($lb === 'ه' && in_array($lm, $hv, true) && skel(build($cl)) !== 'الله') return ['h', $n - 1];
    return null;
}
// I'rab pada kata bersambung damir, mis. رَبِّهِمْ / رَبُّهُمْ / رَبَّهُمْ (harakat huruf terakhir batang + harakat ه yang harus menyesuaikan)
function irabVariants($w) {
    $cl = clusters($w); if (!$cl || !($sf = pronSuffix($cl))) return null;
    [$type, $sn] = $sf; $si = $sn - 1; [$sb, $sm] = $cl[$si];
    if (in_array($sb, ['ا', 'و', 'ي', 'ى'], true) || !caseVowel($sm) || in_array(SUKUN, $sm, true)) return null;
    $mk = function ($v, $hv) use ($cl, $si, $sn, $type) {
        $c = $cl; $c[$si][1] = withVowel($cl[$si][1], $v);
        if (in_array($type, ['hm', 'hn', 'h'], true)) $c[$sn][1] = withVowel($cl[$sn][1], $hv);
        return build($c);
    };
    $ans = build($cl); $cur = caseVowel($sm); $wrong = [];
    foreach ([FATHA, DAMMA, KASRA] as $v) if ($v !== $cur) $wrong[] = $mk($v, $v === KASRA ? KASRA : DAMMA);       // i'rab lain, damir menyesuaikan
    if (in_array($type, ['hm', 'hn', 'h'], true)) $wrong[] = $mk($cur, $cur === KASRA ? DAMMA : KASRA);             // i'rab benar, harakat damir keliru
    return ['ans' => $ans, 'wrong' => array_values(array_diff(array_unique($wrong), [$ans]))];
}
// Kata yang mirip: awalan و/ف, awalan kata kerja ي/ت, dan damir (هم/كم/هن/ها)
function lookalikes($w) {
    $cl = clusters($w); if (!$cl) return []; $n = count($cl); $out = [];
    if ($n >= 3 && in_array($cl[0][0], ['و', 'ف'], true) && $cl[0][1] === [FATHA]) { $c = $cl; $c[0][0] = $cl[0][0] === 'و' ? 'ف' : 'و'; $out['awalan'][] = build($c); }
    if ($n >= 4 && in_array($cl[0][0], ['ي', 'ت'], true) && $cl[0][1] === [FATHA] && $cl[$n - 1][0] === 'ن' && $cl[$n - 2][0] === 'و' && $cl[$n - 2][1] === [SUKUN]) {
        $c = $cl; $c[0][0] = $cl[0][0] === 'ي' ? 'ت' : 'ي'; $out['kerja'][] = build($c);
    }
    if ($sf = pronSuffix($cl)) {
        [$type, $sn] = $sf; $stem = array_slice($cl, 0, $sn); $prev = $stem[$sn - 1];
        $hv = (caseVowel($prev[1]) === KASRA || ($prev[0] === 'ي' && in_array(SUKUN, $prev[1], true))) ? KASRA : DAMMA;
        $defs = ['hm' => [['ه', [$hv]], ['م', [SUKUN]]], 'km' => [['ك', [DAMMA]], ['م', [SUKUN]]], 'hn' => [['ه', [$hv]], ['ن', [SHADDA, FATHA]]], 'ha' => [['ه', [FATHA]], ['ا', []]]];
        foreach ($defs as $t => $suf) if ($t !== $type) $out['damir'][] = build(array_merge($stem, $suf));
    }
    $orig = build($cl);
    foreach ($out as $k => $v) { $out[$k] = array_values(array_diff(array_unique($v), [$orig])); if (!$out[$k]) unset($out[$k]); }
    return $out;
}

// ---------- Soal pasangan (match) ----------
function matchSet($a, $pool, $kind) {
    $need = $kind === 'ends' ? 4 : 3; $list = [];
    foreach ($pool as $p) { if ($p['id'] == $a['id'] || ($kind === 'nomor' && $p['surah_id'] != $a['surah_id'])) continue; $list[] = $p; }
    shuffle($list); array_unshift($list, $a);
    foreach (($kind === 'ends' ? [[3, 6], [2, 4]] : [[3, 3], [4, 4]]) as [$k, $minw]) {     // [jumlah kata, minimal kata sungguhan]
        $sel = []; $sL = $sR = [];
        foreach ($list as $p) {
            $r = realWords($p); if (count($r) < $minw) continue;
            $L = implode(' ', array_slice($r, 0, $k)); $R = $kind === 'ends' ? implode(' ', array_slice($r, -$k)) : 'Ayat ' . $p['ayah'];
            if (isset($sL[$L]) || isset($sR[$R])) continue;
            $sL[$L] = $sR[$R] = 1; $sel[] = [$p, $L, $R]; if (count($sel) >= 5) break;
        }
        if (count($sel) >= $need && $sel[0][0]['id'] == $a['id']) {
            $left = $right = [];
            foreach ($sel as [$p, $L, $R]) { $left[] = ['id' => $p['id'], 'text' => $L]; $right[] = ['id' => $p['id'], 'text' => $R]; }
            shuffle($left); shuffle($right);
            return ['t' => 'match', 'rmode' => $kind === 'ends' ? 'ar' : 'num', 'left' => $left, 'right' => $right,
                    'title' => $kind === 'ends' ? 'Cocokkan awal ayat dengan akhir ayatnya:' : 'Surah ' . $a['surah']['transliteration'] . ' — cocokkan awal ayat dengan nomornya:',
                    'full' => implode("\n", array_map(fn($s) => fullText($s[0]), $sel))];
        }
    }
    return null;
}

function genQ($type, $a, $pool, $lv = 4) {
    $sn = $a['surah']['transliteration'];
    switch ($type) {
    case 'nomor':
        if (mt_rand(0, 1)) {
            $nums = array_column(array_filter($pool, fn($p) => $p['surah_id'] == $a['surah_id'] && $p['ayah'] != $a['ayah']), 'ayah');
            shuffle($nums); $o = array_slice($nums, 0, 3);
            for ($d = 1; $d <= 8 && count($o) < 3; $d++) foreach ([$a['ayah'] - $d, $a['ayah'] + $d] as $x)
                if ($x >= 1 && $x <= $a['surah']['num_ayah'] && !in_array($x, $o) && count($o) < 3) $o[] = $x;
            if (!$o) return null;
            return choice('num', "Surah $sn — ayat nomor berapakah ini?", $a['arabic'], array_map('strval', array_merge([$a['ayah']], $o)), $a['ayah'], $a);
        }
        $d = others($pool, [$a], 3); if (!$d) return null;
        return choice('ar', "Surah $sn — manakah bunyi ayat ke-{$a['ayah']}?", '', array_merge([$a['arabic']], array_column($d, 'arabic')), $a['arabic'], $a);
    case 'sebelum': case 'sesudah':
        $k = array_search($a['id'], array_column($pool, 'id')); $t = $k + ($type === 'sebelum' ? -1 : 1);
        if ($k === false || !isset($pool[$t]) || $pool[$t]['surah_id'] != $a['surah_id']) return null;
        $c = $pool[$t]; $d = others($pool, [$a, $c], 3); if (!$d) return null;
        return choice('ar', $type === 'sebelum' ? 'Manakah ayat sebelumnya?' : 'Manakah ayat sesudahnya?', $a['arabic'], array_merge([$c['arabic']], array_column($d, 'arabic')), $c['arabic'], $c);
    case 'sambung':
        $sp = splitAyah($a); if (!$sp) return null;
        [$pre, $ans] = $sp; $opts = [$ans]; $L = mb_strlen($ans); $cs = $pool; shuffle($cs);
        foreach ([true, false] as $strict) foreach ($cs as $c) {
            if (count($opts) >= 4) break;
            if ($c['id'] == $a['id'] || !($s = splitAyah($c, false))) continue;
            $l = mb_strlen($s[1]);
            if (in_array($s[1], $opts, true) || ($strict && ($l < $L * .4 || $l > $L * 1.8))) continue;
            $opts[] = $s[1];
        }
        if (count($opts) < 2) return null;
        return choice('ar', 'Lanjutkan ayat berikut:', $pre, $opts, $ans, $a);
    case 'ikrab':
        $w = $a['arabic_words']; $re = '/([' . HAR . '])(' . WQF . ')$/u'; $c = [];
        foreach ($w as $k => $x) if (isReal($x) && preg_match($re, $x, $m) && $m[1] !== SHADDA) $c[] = $k;
        if (!$c) return null;
        $k = (mt_rand(1, 3) > 1) ? end($c) : $c[array_rand($c)];
        preg_match($re, $w[$k], $m); $ans = $m[1];
        $w[$k] = preg_replace('/[' . HAR . '](' . WQF . ')$/u', '◌${1}', $w[$k]);
        $o = array_diff([FATHA, DAMMA, KASRA, "\u{064B}", "\u{064C}", "\u{064D}", SUKUN], [$ans]); shuffle($o);
        return choice('har', 'Pilih harakat akhir yang tepat untuk kata berwarna:', '', array_merge([$ans], array_slice($o, 0, 3)), $ans, $a, ['tokens' => $w, 'mark' => $k]);
    case 'lengkapi':
        $w = $a['arabic_words']; $real = [];
        foreach ($w as $k => $x) if (isReal($x)) $real[] = $k;
        if (count($real) < 2) return null;
        $cap = $lv <= 1 ? 1 : ($lv == 2 ? 2 : 3);      // level awal: sedikit rumpang
        $keys = (array)array_rand($real, mt_rand(1, min($cap, count($real) - 1)));
        $bl = []; foreach ($keys as $kk) $bl[] = $real[$kk]; sort($bl);
        $ans = array_map(fn($k) => $w[$k], $bl);
        $dist = [];
        foreach ($pool as $p) foreach ($p['arabic_words'] as $x) if (isReal($x) && !in_array($x, $ans, true)) $dist[$x] = 1;
        $dist = array_keys($dist); shuffle($dist);
        $tiles = array_merge($ans, array_slice($dist, 0, 3)); shuffle($tiles);
        foreach ($bl as $k) $w[$k] = '';
        return ['t' => 'fill', 'title' => 'Lengkapi ayat berikut:', 'tokens' => $w, 'blanks' => $bl, 'ans' => $ans, 'opts' => $tiles, 'full' => fullText($a)];

    // ---- tipe baru (level mahir) ----
    case 'match_ends': return matchSet($a, $pool, 'ends');
    case 'match_nomor': return matchSet($a, $pool, 'nomor');
    case 'irab':
        $c = []; foreach ($a['arabic_words'] as $k => $x) if (isReal($x) && ($v = irabVariants($x)) && $v['wrong']) $c[$k] = $v;
        if (!$c) return null;
        $k = array_rand($c); $v = $c[$k]; $wr = $v['wrong']; shuffle($wr);
        return choice('ar', "Pilih bacaan yang tepat sesuai i’rab:", '', array_merge([$v['ans']], array_slice($wr, 0, 3)), $v['ans'], $a, ['tokens' => blankAt($a, $k), 'mark' => $k]);
    case 'mirip':
        $c = []; foreach ($a['arabic_words'] as $k => $x) if (isReal($x) && ($v = lookalikes($x))) $c[$k] = $v;
        if (!$c) return null;
        $k = array_rand($c); $groups = $c[$k]; $pick = [];
        shuffle($groups); foreach ($groups as $g) { shuffle($g); $pick[] = array_shift($g); if ($g) $groups[] = $g; if (count($pick) >= 3) break; }
        $ans = build(clusters($a['arabic_words'][$k]));
        return choice('ar', 'Manakah kata yang tepat? (hati-hati, mirip)', '', array_merge([$ans], $pick), $ans, $a, ['tokens' => blankAt($a, $k), 'mark' => $k]);
    case 'terakhir':
        $w = $a['arabic_words']; $ri = []; foreach ($w as $k => $x) if (isReal($x)) $ri[] = $k;
        if (count($ri) < 2) return null;
        $k = end($ri); $ans = stripTail($w[$k]); $sk = skel($ans); $cand = [];
        $consider = function ($list, $bonus) use (&$cand, $ans, $sk, $a) {
            foreach ($list as $p) {
                if ($p['id'] == $a['id'] || ($lw = lastReal($p)) === null) continue;
                $x = stripTail($lw); if ($x === $ans || skel($x) === $sk) continue;
                $s = sim($sk, skel($x)) + $bonus; if (!isset($cand[$x]) || $cand[$x] < $s) $cand[$x] = $s;
            }
        };
        $consider($pool, 1.5);
        if (count($cand) < 6 && function_exists('surah')) $consider(surah($a['surah_id']), 0);   // lengkapi pengecoh dari surah yang sama bila thumn terlalu pendek
        if (!$cand) return null;
        arsort($cand); $top = array_slice(array_map('strval', array_keys($cand)), 0, 3);
        return choice('ar', 'Manakah kata terakhir ayat ini?', '', array_merge([$ans], $top), $ans, $a, ['tokens' => blankAt($a, $k), 'mark' => $k]);
    }
    return null;
}

function levelCount($nAyat, $lv) { return min(12, 8 + intdiv($nAyat, 10)); }   // ujian akhir unit (15 soal) ditentukan di api.php   // thumn panjang -> soal sedikit lebih banyak

function genQuestions($src, $pool, $n, $lv = 4) {
    $w = LEVEL_TYPES[max(1, min(8, (int)$lv))];
    $order = [];
    while (count($order) < $n) { $s = $src; shuffle($s); $order = array_merge($order, $s); }
    $qs = []; $prev = null;
    foreach (array_slice($order, 0, $n) as $a) {
        $r = []; foreach ($w as $t => $wt) $r[$t] = -log(mt_rand(1, 1000000) / 1000000) / $wt;   // undian berbobot tanpa pengembalian
        asort($r); $types = array_keys($r);
        if ($prev !== null && count($types) > 1 && $types[0] === $prev) $types[] = array_shift($types);   // hindari tipe sama beruntun
        foreach (array_merge($types, ['lengkapi', 'sambung', 'nomor']) as $ty) if ($q = genQ($ty, $a, $pool, $lv)) { $qs[] = $q; $prev = $ty; break; }
    }
    return $qs;
}