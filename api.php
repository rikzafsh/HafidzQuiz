<?php
ini_set('display_errors', '0');   // jangan pernah mencemari JSON dengan pesan error
ob_start();
require __DIR__ . '/core.php';
require __DIR__ . '/gen.php';
require __DIR__ . '/mushaf.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

function me() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, username, xp, streak, last_day, dirham FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function need() { return me() ?? fail('Silakan masuk dahulu.', 401); }
function pub($u) { return ['name' => $u['username'], 'xp' => (int)$u['xp'], 'streak' => (int)$u['streak'], 'dirham' => (int)($u['dirham'] ?? 0), 'dev' => DEV_MODE, 'admin' => isAdmin($u)]; }
function progress($uid) {
    $s = db()->prepare('SELECT thumn, node, stars FROM progress WHERE user_id=?'); $s->execute([$uid]);
    $m = []; foreach ($s as $r) $m["{$r['thumn']}:{$r['node']}"] = (int)$r['stars']; return $m;
}
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

// ===== Penilaian di SERVER =====
// Soal disimpan di tabel attempts (lengkap dengan kunci jawaban). Browser hanya menerima soal tanpa kunci, mengirim jawaban satu per satu
// ke 'check', lalu 'finish'. Skor, nyawa, dan waktu dihitung server; browser tidak bisa lagi melaporkan skornya sendiri.
function publicQuestion(array $q, int $i): array {
    unset($q['ans'], $q['full']);
    if ($q['t'] === 'match') {   // id pasangan = kunci jawaban -> dibuang
        $q['left'] = array_map(fn($x) => ['text' => $x['text']], $q['left']);
        $q['right'] = array_map(fn($x) => ['text' => $x['text']], $q['right']);
    }
    return ['i' => $i] + $q;
}
function newAttempt($uid, $kind, $un, $lv, $les, array $Q): array {
    $id = bin2hex(random_bytes(12)); $hearts = $kind === 'jump' ? 1 : 5;
    $S = array_map(fn($q) => ['tries' => 0, 'done' => false, 'miss' => false, 'last' => null, 'ua' => null, 'matched' => []], $Q);
    $pdo = db(); $pdo->prepare('DELETE FROM attempts WHERE started < ?')->execute([time() - 86400]);
    $pdo->prepare('INSERT INTO attempts(id,user_id,kind,unit,level,lesson,qjson,sjson,hearts,started) VALUES(?,?,?,?,?,?,?,?,?,?)')
        ->execute([$id, $uid, $kind, $un, $lv, $les, json_encode($Q, JSON_UNESCAPED_UNICODE), json_encode($S, JSON_UNESCAPED_UNICODE), $hearts, time()]);
    return ['attempt' => $id, 'hearts' => $hearts, 'total' => count($Q), 'questions' => array_map('publicQuestion', $Q, array_keys($Q))];
}
function loadAttempt($uid, array $in): array {
    $s = db()->prepare('SELECT * FROM attempts WHERE id=? AND user_id=?'); $s->execute([(string)($in['attempt'] ?? ''), $uid]);
    return $s->fetch() ?: fail('Sesi kuis tidak ditemukan atau sudah kedaluwarsa.', 404);
}
function touchStreak($u): int {
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    return $streak;
}
function applyLesson($u, $un, $lv, $les, $c, $tot): array {   // $c = jumlah soal benar pada percobaan pertama (dihitung server)
    $m = unitMeta($un); $key = "V$lv.$les"; $exam = $lv <= $m['levels'] && $lv === $m['exam'] && $les === 4;
    $pct = (int)round(100 * $c / $tot); $passed = $exam ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND thumn=? AND node=?'); $q->execute([$u['id'], $un, $key]); $first = !$q->fetch();
    $base = $exam ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($exam ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $un, $key, $stars, $pct]);
    $levelDone = false;
    if ($passed) { $pr = progress($u['id']); $levelDone = true; for ($j = 1; $j <= 4; $j++) if (!isset($pr["$un:V$lv.$j"])) $levelDone = false; }
    $streak = touchStreak($u);
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, date('Y-m-d'), $u['id']]);
    return ['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0, 'xp' => (int)$u['xp'] + $gain,
            'streak' => $streak, 'level' => $lv, 'lesson' => $les, 'lessons' => 4, 'levelDone' => $levelDone, 'unitDone' => $passed && $exam];
}
function applyJump($u, $un, $c, $tot): array {   // tes lompat: 25 soal, semuanya benar pada percobaan pertama. Thumn yang dilewati ditandai JUMP (tanpa chest).
    $skip = skippedThumns(progress($u['id']), $un); if (!$skip) fail('Tidak ada thumn yang perlu dilewati.');
    $passed = $tot === 25 && $c === 25; $gain = $passed ? 20 : 0;
    if ($passed) { $s = db()->prepare('INSERT OR IGNORE INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,1,100)'); foreach ($skip as $k) $s->execute([$u['id'], $k, 'JUMP']); }
    $streak = touchStreak($u);
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, date('Y-m-d'), $u['id']]);
    return ['passed' => $passed, 'pct' => $passed ? 100 : 0, 'gain' => $gain, 'bonus' => 0, 'xp' => (int)$u['xp'] + $gain, 'streak' => $streak, 'skipped' => count($skip)];
}

try { switch ($a) {
case 'register':
    $n = trim($in['username'] ?? ''); $p = $in['password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $n)) fail('Nama pengguna 3–30 karakter (huruf, angka, _ . -).');
    if (strlen($p) < 6) fail('Kata sandi minimal 6 karakter.');
    $s = db()->prepare('INSERT INTO users(username, hash, created_at) VALUES(?,?,?)');
    try { $s->execute([$n, password_hash($p, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]); } catch (PDOException $e) { fail('Nama pengguna sudah dipakai.'); }
    session_regenerate_id(true); $_SESSION['uid'] = (int)db()->lastInsertId();
    out(['user' => pub(me())]);
case 'login':
    $s = db()->prepare('SELECT * FROM users WHERE username=?'); $s->execute([trim($in['username'] ?? '')]); $u = $s->fetch();
    if (!$u || !password_verify($in['password'] ?? '', $u['hash'])) fail('Nama pengguna atau kata sandi salah.', 401);
    session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; out(['user' => pub($u)]);
case 'logout': session_destroy(); out(['ok' => true]);
case 'me': out(['user' => pub(need())]);

case 'juz':
    $u = need(); $pr = progress($u['id']); $tot = $done = [];
    foreach (thumns() as $i => $t) { $j = juzOf($t); $tot[$j] = ($tot[$j] ?? 0) + 1; if (unitDone($pr, $i)) $done[$j] = ($done[$j] ?? 0) + 1; }
    $r = []; foreach ($tot as $j => $c) $r[] = ['juz' => $j, 'total' => $c, 'done' => $done[$j] ?? 0];
    out(['user' => pub($u), 'juz' => $r]);

case 'units':   // per juz: [8 thumn + ulasan hizb] x 2 hizb, lalu ulasan juz
    $u = need(); $juz = (int)($_GET['juz'] ?? 1); $pr = progress($u['id']); $T = thumns(); $res = []; $byH = [];
    foreach ($T as $i => $t) if (juzOf($t) === $juz) $byH[$t['hizb_number']][] = $i;
    ksort($byH); $juzAyat = []; $reviewsDone = true; $dn = [];
    foreach ($byH as $idxs) foreach ($idxs as $i) $dn[$i] = thumnDone($pr, $i);
    foreach ($byH as $h => $idxs) {
        $hAyat = []; $hDone = true;
        foreach ($idxs as $i) {
            $t = $T[$i]; $ayat = thumnAyat($i);
            $open = $i === 0 || !isset($dn[$i - 1]) || $dn[$i - 1];  // thumn pertama tiap juz selalu terbuka
            $skip = 0; if (!$open && $ayat) foreach ($dn as $k => $dd) if ($k < $i && !$dd) $skip++;
            $nodes = $ayat ? levelNodes($pr, $i, $open) : [];
            $res[] = ['i' => $i, 'kind' => 'thumn', 'hizb' => $h, 'thumn' => $t['thumn'], 'label' => "Hizb $h · Thumn {$t['thumn']}", 'empty' => !$ayat, 'bad' => !$t['ok'],
                      'title' => $ayat ? rangeLabel($ayat) : ($t['ok'] ? 'Tidak ada ayat' : 'Titik awal tidak konsisten'), 'nodes' => $nodes, 'chests' => $ayat ? chests($pr, $i, $nodes, $open) : [],
                      'jump' => $skip > 0, 'skip' => $skip];
            $hAyat = array_merge($hAyat, $ayat); $hDone = $hDone && $dn[$i];
        }
        $res[] = ['i' => 1000 + $h, 'kind' => 'hizb', 'hizb' => $h, 'thumn' => 0, 'label' => "Ulasan Hizb $h", 'empty' => !$hAyat, 'bad' => false,
                  'title' => $hAyat ? rangeLabel($hAyat) : '-', 'nodes' => $hAyat ? levelNodes($pr, 1000 + $h, $hDone) : []];
        $juzAyat = array_merge($juzAyat, $hAyat); $reviewsDone = $reviewsDone && unitDone($pr, 1000 + $h);
    }
    $res[] = ['i' => 2000 + $juz, 'kind' => 'juz', 'hizb' => 0, 'thumn' => 0, 'label' => "Ulasan Juz $juz", 'empty' => !$juzAyat, 'bad' => false,
              'title' => $juzAyat ? rangeLabel($juzAyat) : '-', 'nodes' => $juzAyat ? levelNodes($pr, 2000 + $juz, $reviewsDone) : []];
    out(['user' => pub($u), 'units' => $res]);

case 'quiz':
    $u = need(); $un = (int)($_GET['unit'] ?? -1);
    if (isset($_GET['jump'])) {   // tes "Lompat ke sini": 25 soal dari thumn yang akan dilewati
        if ($un < 0 || $un >= 480 || !thumnAyat($un)) fail('Thumn tidak valid.', 404);
        $pool = []; foreach (skippedThumns(progress($u['id']), $un) as $k) $pool = array_merge($pool, thumnAyat($k));
        if (!$pool) fail('Tidak ada thumn yang perlu dilewati.', 404);
        $qs = jumpQuestions($pool); if (count($qs) < 25) fail('Soal belum bisa dibuat.', 500);
        out(newAttempt($u['id'], 'jump', $un, 0, 0, $qs));
    }
    $lv = (int)($_GET['level'] ?? 0); $les = (int)($_GET['lesson'] ?? 1); $ayat = unitAyat($un); $m = unitMeta($un);
    if (!$ayat || $lv < 1 || $lv > maxLevel($m) || $les < 1 || $les > 4) fail('Lesson tidak ditemukan.', 404);
    $rev = $lv > $m['levels']; $exam = !$rev && $lv === $m['exam'] && $les === 4;
    $src = $rev ? cumulAyat($un) : $ayat;                                       // level ulasan: dari awal hizb sampai thumn ini
    $n = ($exam || ($rev && $les === 4)) ? 15 : levelCount(count($src), lessonDiff($m, $lv, $les));
    $qs = genQuestions($src, $src, $n, lessonDiff($m, $lv, $les));
    if (count($qs) < 3) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
    out(newAttempt($u['id'], 'lesson', $un, $lv, $les, $qs));

case 'check':   // periksa satu jawaban (atau satu pasangan pada soal pencocokan) di server
    $u = need(); $at = loadAttempt($u['id'], $in); if ($at['finished']) fail('Sesi sudah selesai.', 409);
    $Q = json_decode($at['qjson'], true); $S = json_decode($at['sjson'], true); $qi = (int)($in['qi'] ?? -1);
    if (!isset($Q[$qi])) fail('Soal tidak valid.'); $hearts = (int)$at['hearts']; if ($hearts <= 0) fail('Nyawa habis.', 409);
    $q = $Q[$qi]; $s = &$S[$qi]; $ok = false;
    if ($s['done']) $ok = true;                                                  // sudah benar: tidak mengubah apa pun
    elseif ($q['t'] === 'match') {
        $l = (int)($in['l'] ?? -1); $r = (int)($in['r'] ?? -1);
        if (!isset($q['left'][$l], $q['right'][$r]) || in_array($l, $s['matched'], true)) fail('Pasangan tidak valid.');
        $ok = $q['left'][$l]['id'] === $q['right'][$r]['id'];
        if ($ok) { $s['matched'][] = $l; if (count($s['matched']) === count($q['left'])) $s['done'] = true; }
        else { $s['tries']++; $s['miss'] = true; $hearts--; }
        $s['ua'] = ['kesalahan' => $s['tries']]; $s['last'] = $s['tries'] === 0;
    } else {
        $ans = $in['answer'] ?? null; $ok = $q['t'] === 'fill' ? ($ans === $q['ans']) : ((string)$ans === $q['ans']);
        $s['tries']++; $s['ua'] = $ans; $s['last'] = $ok;
        if ($ok) $s['done'] = true; else { $s['miss'] = true; $hearts--; }
    }
    $done = $s['done']; $tries = $s['tries']; unset($s);
    db()->prepare('UPDATE attempts SET sjson=?, hearts=? WHERE id=?')->execute([json_encode($S, JSON_UNESCAPED_UNICODE), $hearts, $at['id']]);
    out(['ok' => $ok, 'done' => $done, 'hearts' => $hearts, 'mistakes' => $tries, 'full' => ($done || $q['t'] !== 'match') ? $q['full'] : null]);

case 'finish':   // skor dihitung dari catatan server, bukan dari laporan browser
    $u = need(); $at = loadAttempt($u['id'], $in); if ($at['finished']) fail('Sesi sudah selesai.', 409);
    $Q = json_decode($at['qjson'], true); $S = json_decode($at['sjson'], true);
    if ((int)$at['hearts'] <= 0) fail('Nyawa habis.', 409);
    foreach ($S as $s) if (!$s['done']) fail('Masih ada soal yang belum diselesaikan.', 409);
    $upd = db()->prepare('UPDATE attempts SET finished=1 WHERE id=? AND finished=0'); $upd->execute([$at['id']]); if ($upd->rowCount() === 0) fail('Sesi sudah selesai.', 409);
    $tot = count($Q); $c = count(array_filter($S, fn($s) => !$s['miss'])); $secs = max(1, time() - (int)$at['started']);
    $res = $at['kind'] === 'jump' ? applyJump($u, (int)$at['unit'], $c, $tot) : applyLesson($u, (int)$at['unit'], (int)$at['level'], (int)$at['lesson'], $c, $tot);
    out($res + ['secs' => $secs]);

case 'mushaf_meta':
    need(); out(['surahs' => surahNames(), 'maxPage' => mushafMaxPage(), 'lastPos' => mushafLastPos()]);
case 'mushaf':
    need(); $p = (int)($_GET['page'] ?? 1); $r = mushafPage($p);
    if (!$r) fail("Halaman $p belum tersedia (data mushaf baru sampai halaman " . mushafMaxPage() . ').', 404);
    out($r);
case 'mushaf_find':
    need(); out(mushafFind($_GET));

case 'profile':
    $u = need(); $pr = progress($u['id']); $done = $skipped = $lessons = 0;
    foreach (thumns() as $i => $t) { if (isset($pr["$i:V8.4"])) $done++; elseif (isset($pr["$i:JUMP"])) $skipped++; }
    foreach ($pr as $k => $_) if (preg_match('/:V\d+\.\d+$/', $k)) $lessons++;
    $s = db()->prepare('SELECT created_at FROM users WHERE id=?'); $s->execute([$u['id']]);
    out(['user' => pub($u), 'joined' => $s->fetchColumn() ?: null, 'stats' => ['thumnDone' => $done + $skipped, 'thumnSkipped' => $skipped, 'thumnTotal' => count(thumns()), 'lessons' => $lessons]]);

case 'feedback':   // "terlalu mudah" / "terlalu sulit" / "laporkan": soal & jawaban diambil dari catatan server (bukan dari browser)
    $u = need(); $kind = $in['kind'] ?? '';
    if (!in_array($kind, ['easy', 'hard', 'report'], true)) fail('Jenis masukan tidak valid.');
    $at = loadAttempt($u['id'], $in); $Q = json_decode($at['qjson'], true); $S = json_decode($at['sjson'], true); $qi = (int)($in['qi'] ?? -1);
    if (!isset($Q[$qi]) || (!$S[$qi]['done'] && !$S[$qi]['tries'])) fail('Jawab soal ini dulu sebelum memberi masukan.');
    db()->prepare('INSERT INTO feedback(user_id,unit,level,lesson,kind,reason,note,qkind,qjson,ua,correct,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
        $u['id'], (int)$at['unit'], (int)$at['level'], (int)$at['lesson'], $kind, mb_substr((string)($in['reason'] ?? ''), 0, 80), mb_substr((string)($in['note'] ?? ''), 0, 500),
        mb_substr((string)($Q[$qi]['kind'] ?? ''), 0, 30), json_encode($Q[$qi], JSON_UNESCAPED_UNICODE), mb_substr(json_encode($S[$qi]['ua'], JSON_UNESCAPED_UNICODE), 0, 2000), !empty($S[$qi]['last']) ? 1 : 0, date('Y-m-d H:i:s')]);
    out(['ok' => true]);

case 'claim':   // buka chest (unit thumn, setelah level 4 / 7): 10-20 dirham acak, sekali per chest
    $u = need(); $un = (int)($in['unit'] ?? -1); $ch = (int)($in['chest'] ?? 0);
    if ($un < 0 || $un >= 480 || !in_array($ch, [4, 7], true) || !thumnAyat($un)) fail('Chest tidak valid.');
    $pr = progress($u['id']);
    for ($j = 1; $j <= 4; $j++) if (!isset($pr["$un:V$ch.$j"])) fail("Selesaikan Level $ch dulu untuk membuka chest ini.");
    $amt = random_int(10, 20); $pdo = db(); $pdo->beginTransaction();
    $s = $pdo->prepare('INSERT OR IGNORE INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,0)');   // jumlah dirham disimpan di kolom stars
    $s->execute([$u['id'], $un, "C$ch", $amt]);
    if ($s->rowCount() === 0) { $pdo->rollBack(); fail('Chest ini sudah dibuka.'); }
    $pdo->prepare('UPDATE users SET dirham=dirham+? WHERE id=?')->execute([$amt, $u['id']]); $pdo->commit();
    out(['amount' => $amt, 'dirham' => (int)$u['dirham'] + $amt]);

case 'dev_mark':   // SEMENTARA: tandai level (dan semua level sebelumnya) selesai
    if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
    $u = need(); $un = (int)($in['unit'] ?? -1); $lv = (int)($in['level'] ?? 0); $m = unitMeta($un);
    if (!unitAyat($un) || $lv < 1 || $lv > maxLevel($m)) fail('Data tidak valid.');
    $s = db()->prepare('INSERT OR IGNORE INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,3,100)');
    for ($l = 1; $l <= $lv; $l++) for ($j = 1; $j <= 4; $j++) $s->execute([$u['id'], $un, "V$l.$j"]);
    out(['ok' => true]);

case 'dev_reset':  // SEMENTARA: hapus seluruh progres satu unit (termasuk chest & lompat)
    if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
    $u = need(); db()->prepare('DELETE FROM progress WHERE user_id=? AND thumn=?')->execute([$u['id'], (int)($in['unit'] ?? -1)]);
    out(['ok' => true]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }