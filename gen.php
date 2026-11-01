<?php
// Generator soal. Soal diambil dari ayat $src (lesson/test), pengecoh dari seluruh thumn ($pool).
require_once __DIR__ . '/quran_json_parser.php';
const ALM = "\u{061C}";
const LET = '\x{0621}-\x{064A}\x{0671}';
const HAR = '\x{064B}-\x{0652}';
const WQF = '[^' . LET . HAR . ']*';

function arNum($n) { return strtr((string)$n, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩']); }
function isReal($w) { return QuranJsonParser::isRealWord($w); }
function fullText($a) { return $a['arabic'] . ' ' . arNum($a['ayah']) . ALM; }

function splitAyah($a, $rand = true) { return QuranJsonParser::splitWords($a['arabic_words'], $rand); }
function others($pool, $ex, $max) {
    $ids = array_column($ex, 'id'); $txt = array_column($ex, 'arabic'); shuffle($pool); $o = [];
    foreach ($pool as $p) {
        if (in_array($p['id'], $ids) || in_array($p['arabic'], $txt, true)) continue;
        $o[] = $p; $txt[] = $p['arabic']; if (count($o) >= $max) break;
    }
    return $o;
}
function choice($mode, $title, $show, $opts, $ans, $a, $extra = []) {
    shuffle($opts);
    return array_merge(['t' => 'choice', 'mode' => $mode, 'title' => $title, 'show' => $show, 'opts' => array_values($opts), 'ans' => (string)$ans, 'full' => fullText($a)], $extra);
}

function genQ($type, $a, $pool) {
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
        $q = choice('ar', $type === 'sebelum' ? 'Manakah ayat sebelumnya?' : 'Manakah ayat sesudahnya?', $a['arabic'], array_merge([$c['arabic']], array_column($d, 'arabic')), $c['arabic'], $c);
        return $q;
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
        foreach ($w as $k => $x) if (isReal($x) && preg_match($re, $x, $m) && $m[1] !== "\u{0651}") $c[] = $k;
        if (!$c) return null;
        $k = (mt_rand(1, 3) > 1) ? end($c) : $c[array_rand($c)];
        preg_match($re, $w[$k], $m); $ans = $m[1];
        $w[$k] = preg_replace('/[' . HAR . '](' . WQF . ')$/u', '◌${1}', $w[$k]);
        $all = ["\u{064E}", "\u{064F}", "\u{0650}", "\u{064B}", "\u{064C}", "\u{064D}", "\u{0652}"];
        $o = array_diff($all, [$ans]); shuffle($o);
        return choice('har', 'Pilih harakat akhir yang tepat untuk kata berwarna:', '', array_merge([$ans], array_slice($o, 0, 3)), $ans, $a, ['tokens' => $w, 'mark' => $k]);
    case 'lengkapi':
        $w = $a['arabic_words']; $real = [];
        foreach ($w as $k => $x) if (isReal($x)) $real[] = $k;
        if (count($real) < 2) return null;
        $keys = (array)array_rand($real, mt_rand(1, min(3, count($real) - 1)));
        $bl = []; foreach ($keys as $kk) $bl[] = $real[$kk]; sort($bl);
        $ans = array_map(fn($k) => $w[$k], $bl);
        $dist = [];
        foreach ($pool as $p) foreach ($p['arabic_words'] as $x) if (isReal($x) && !in_array($x, $ans, true)) $dist[$x] = 1;
        $dist = array_keys($dist); shuffle($dist);
        $tiles = array_merge($ans, array_slice($dist, 0, 3)); shuffle($tiles);
        foreach ($bl as $k) $w[$k] = '';
        return ['t' => 'fill', 'title' => 'Lengkapi ayat berikut:', 'tokens' => $w, 'blanks' => $bl, 'ans' => $ans, 'opts' => $tiles, 'full' => fullText($a)];
    }
    return null;
}

function genQuestions($src, $pool, $n, $easy) {
    $types = $easy ? ['nomor', 'sambung', 'lengkapi'] : ['nomor', 'sambung', 'sambung', 'lengkapi', 'lengkapi', 'sebelum', 'sesudah', 'ikrab'];
    $order = [];
    while (count($order) < $n) { $s = $src; shuffle($s); $order = array_merge($order, $s); }
    $qs = [];
    foreach (array_slice($order, 0, $n) as $a) {
        $t = $types; shuffle($t); $t = array_merge($t, ['nomor', 'sambung']);
        foreach ($t as $ty) if ($q = genQ($ty, $a, $pool)) { $qs[] = $q; break; }
    }
    return $qs;
}
