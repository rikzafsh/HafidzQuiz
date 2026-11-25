<?php
// Struktur belajar Tahfidz: unit (thumn / ulasan hizb / ulasan juz), level, lesson, chest, tes lompat, dan penerapan hasil sesi (XP, bintang, progres).
const TAHFIDZ = 'tahfidz';
function tProgress($uid): array { return courseProgress((int)$uid, TAHFIDZ); }

function levelPhase($d) { return $d <= 3 ? 'Dasar' : ($d <= 5 ? 'Menengah' : 'Mahir'); }
// ID unit: 0..479 = thumn | 1001..1060 = ulasan hizb | 2001..2030 = ulasan juz. Setiap level = 4 lesson.
//  thumn: 8 level (+ level 9 "Ulasan Rub/Nisf" pada Thumn 2, 4, 6: 4 lesson ulasan dari awal hizb sampai thumn itu; tidak menahan thumn berikutnya)
//  ulasan hizb: 4 level | ulasan juz: 8 level.  Unit selesai bila lesson ke-4 di level ujian (8 / 4) lulus, atau dilewati lewat "Lompat ke sini".
function unitMeta(int $u): array {
    if ($u >= 2001 && $u <= 2030) return ['kind' => 'juz', 'levels' => 8, 'exam' => 8, 'review' => false];
    if ($u >= 1001 && $u <= 1060) return ['kind' => 'hizb', 'levels' => 4, 'exam' => 4, 'review' => false];
    $T = thumns();   // Thumn 8 = akhir hizb: ulasannya sudah ada sebagai unit "Ulasan Hizb"
    return ['kind' => 'thumn', 'levels' => 8, 'exam' => 8, 'review' => isset($T[$u]) && in_array($T[$u]['thumn'], [2, 4, 6], true)];
}
function maxLevel($m) { return $m['levels'] + ($m['review'] ? 1 : 0); }
function reviewName($thumn) { return $thumn === 4 ? 'Nisf' : 'Rub'; }
function lessonDiff($m, $l, $les) {      // tingkat kesulitan soal untuk level/lesson tertentu
    if ($l > $m['levels']) return [4, 6, 7, 8][$les - 1];                                     // level ulasan: makin sulit per lesson
    return $m['kind'] === 'hizb' ? [1 => 3, 2 => 5, 3 => 7, 4 => 8][$l] : $l;                // ulasan hizb: 4 level memetakan Dasar -> Mahir
}
function unitDone($pr, $u) { $m = unitMeta($u); return isset($pr["$u:V{$m['exam']}.4"]) || isset($pr["$u:JUMP"]); }
function thumnDone($pr, $i) { return unitDone($pr, $i) || !thumnAyat($i); } // thumn tanpa ayat tidak boleh memblokir thumn berikutnya
function unitAyat(int $u): array {
    $T = thumns(); $out = [];
    if ($u >= 2001 && $u <= 2030) { foreach ($T as $i => $t) if (juzOf($t) === $u - 2000) $out = array_merge($out, thumnAyat($i)); return $out; }
    if ($u >= 1001 && $u <= 1060) { foreach ($T as $i => $t) if ($t['hizb_number'] === $u - 1000) $out = array_merge($out, thumnAyat($i)); return $out; }
    return $u >= 0 && $u < 480 ? thumnAyat($u) : [];
}
function cumulAyat(int $i): array {   // dari awal hizb sampai thumn ini
    $T = thumns(); $h = $T[$i]['hizb_number']; $s = $i; while ($s > 0 && $T[$s - 1]['hizb_number'] === $h) $s--;
    $out = []; for ($j = $s; $j <= $i; $j++) $out = array_merge($out, thumnAyat($j)); return $out;
}
function levelNodes($pr, $u, $open) {
    $m = unitMeta($u); $N = 4; $jump = isset($pr["$u:JUMP"]); $nodes = []; $prev = true; $T = thumns();
    for ($l = 1; $l <= maxLevel($m); $l++) {
        $rev = $l > $m['levels'];
        $d = $sum = 0; for ($j = 1; $j <= $N; $j++) if (isset($pr["$u:V$l.$j"])) { $d++; $sum += $pr["$u:V$l.$j"]; }
        $full = $d >= $N; $les = $full ? $N : $d + 1; $exam = !$rev && $l === $m['exam'] && $les === $N; $isDone = $full || $jump;
        $st = !$open ? 'locked' : ($isDone ? 'done' : ($prev ? 'open' : 'locked'));
        $rn = $rev ? reviewName($T[$u]['thumn']) : null;
        $nodes[] = ['id' => "V$l", 'level' => $l, 'levels' => $m['levels'], 'review' => $rev, 'reviewName' => $rn, 'phase' => $rev ? "Ulasan $rn" : levelPhase(lessonDiff($m, $l, 1)),
                    'final' => !$rev && $l === $m['exam'], 'lessons' => $N, 'done' => $d, 'lesson' => $les, 'exam' => $exam, 'skipped' => $jump && !$full, 'state' => $st,
                    'stars' => $d ? (int)round($sum / $d) : 0, 'xp' => $exam ? ($full ? 10 : 20) : ($full ? 5 : 10), 'bonus' => $exam ? 10 : 5];   // XP lesson: 10 (ulang 5), ujian akhir: 20 (ulang 10)
        $prev = $isDone;
    }
    return $nodes;
}
function chests($pr, $u, $nodes, $open) {   // 2 chest di tiap unit thumn: setelah level 4 dan level 7 (hanya bila lesson-nya benar-benar dikerjakan)
    $out = [];
    foreach ([4, 7] as $lv) {
        $n = $nodes[$lv - 1]; $got = $pr["$u:C$lv"] ?? null;
        $out[] = ['id' => "C$lv", 'after' => $lv, 'state' => $got ? 'opened' : ($open && $n['done'] >= $n['lessons'] ? 'ready' : 'locked'), 'amount' => $got ?: null];
    }
    return $out;
}
function skippedThumns($pr, $x): array {   // thumn di juz yang sama, sebelum $x, yang belum selesai -> dilewati oleh tes lompat
    $T = thumns(); $j = juzOf($T[$x]); $out = [];
    foreach ($T as $k => $t) { if ($k >= $x) break; if (juzOf($t) === $j && !thumnDone($pr, $k)) $out[] = $k; }
    return $out;
}
function jumpQuestions($pool): array {   // 25 soal, bertahap dari menengah ke mahir
    $qs = array_merge(genQuestions($pool, $pool, 8, 4), genQuestions($pool, $pool, 9, 6), genQuestions($pool, $pool, 8, 8));
    while (count($qs) < 25 && count($qs) > 0) $qs = array_merge($qs, genQuestions($pool, $pool, 25 - count($qs), 6));
    return array_slice($qs, 0, 25);
}

// ----- hasil sesi kuis -----
function applyLesson($u, $un, $lv, $les, $c, $tot): array {   // $c = jumlah soal benar pada percobaan pertama (dihitung server)
    $m = unitMeta($un); $key = "V$lv.$les"; $exam = $lv <= $m['levels'] && $lv === $m['exam'] && $les === 4;
    $pct = (int)round(100 * $c / $tot); $passed = $exam ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND course=? AND unit=? AND node=?'); $q->execute([$u['id'], TAHFIDZ, $un, $key]); $first = !$q->fetch();
    $base = $exam ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($exam ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,course,unit,node,stars,best) VALUES(?,?,?,?,?,?) ON CONFLICT(user_id,course,unit,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], TAHFIDZ, $un, $key, $stars, $pct]);
    $levelDone = false;
    if ($passed) { $pr = tProgress($u['id']); $levelDone = true; for ($j = 1; $j <= 4; $j++) if (!isset($pr["$un:V$lv.$j"])) $levelDone = false; }
    $aw = award($u, TAHFIDZ, $gain); $streak = $aw['streak'];   // XP global + XP course + streak
    return ['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0, 'xp' => (int)$u['xp'] + $gain,
            'streak' => $streak, 'courseXp' => $aw['courseXp'], 'level' => $lv, 'lesson' => $les, 'lessons' => 4, 'levelDone' => $levelDone, 'unitDone' => $passed && $exam];
}
function applyJump($u, $un, $c, $tot): array {   // tes lompat: 25 soal, semuanya benar pada percobaan pertama. Thumn yang dilewati ditandai JUMP (tanpa chest).
    $skip = skippedThumns(tProgress($u['id']), $un); if (!$skip) fail('Tidak ada thumn yang perlu dilewati.');
    $passed = $tot === 25 && $c === 25; $gain = $passed ? 20 : 0;
    if ($passed) { $s = db()->prepare('INSERT OR IGNORE INTO progress(user_id,course,unit,node,stars,best) VALUES(?,?,?,?,1,100)'); foreach ($skip as $k) $s->execute([$u['id'], TAHFIDZ, $k, 'JUMP']); }
    $aw = award($u, TAHFIDZ, $gain); $streak = $aw['streak'];   // XP global + XP course + streak
    return ['passed' => $passed, 'pct' => $passed ? 100 : 0, 'gain' => $gain, 'bonus' => 0, 'xp' => (int)$u['xp'] + $gain, 'streak' => $streak, 'courseXp' => $aw['courseXp'], 'skipped' => count($skip)];
}
