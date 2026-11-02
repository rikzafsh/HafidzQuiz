<?php
ini_set('display_errors', '0');   // jangan pernah mencemari JSON dengan pesan error
ob_start();
require __DIR__ . '/core.php';
require __DIR__ . '/gen.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

function me() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, username, xp, streak, last_day FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function need() { return me() ?? fail('Silakan masuk dahulu.', 401); }
function pub($u) { return ['name' => $u['username'], 'xp' => (int)$u['xp'], 'streak' => (int)$u['streak']]; }
function progress($uid) {
    $s = db()->prepare('SELECT thumn, node, stars FROM progress WHERE user_id=?'); $s->execute([$uid]);
    $m = []; foreach ($s as $r) $m["{$r['thumn']}:{$r['node']}"] = (int)$r['stars']; return $m;
}
function levelPhase($l) { return $l <= 3 ? 'Dasar' : ($l <= 5 ? 'Menengah' : 'Mahir'); }
// ID unit: 0..479 = thumn | 1001..1060 = ulasan hizb | 2001..2030 = ulasan juz. Setiap unit: 8 level x 4 lesson.
// Unit selesai bila lesson ke-4 di level 8 (ujian akhir unit) lulus.
function unitDone($pr, $u) { return isset($pr["$u:V8.4"]); }
function thumnDone($pr, $i) { return unitDone($pr, $i) || !thumnAyat($i); } // thumn tanpa ayat tidak boleh memblokir thumn berikutnya
function unitAyat(int $u): array {
    $T = thumns(); $out = [];
    if ($u >= 2001 && $u <= 2030) { foreach ($T as $i => $t) if (juzOf($t) === $u - 2000) $out = array_merge($out, thumnAyat($i)); return $out; }
    if ($u >= 1001 && $u <= 1060) { foreach ($T as $i => $t) if ($t['hizb_number'] === $u - 1000) $out = array_merge($out, thumnAyat($i)); return $out; }
    return $u >= 0 && $u < 480 ? thumnAyat($u) : [];
}
function levelNodes($pr, $u, $open) {     // 8 level; tiap level = 4 lesson
    $nodes = []; $prev = true;
    for ($l = 1; $l <= 8; $l++) {
        $d = $sum = 0; for ($j = 1; $j <= 4; $j++) if (isset($pr["$u:V$l.$j"])) { $d++; $sum += $pr["$u:V$l.$j"]; }
        $full = $d >= 4; $les = $full ? 4 : $d + 1; $exam = $l === 8 && $les === 4;
        $st = !$open ? 'locked' : ($full ? 'done' : ($prev ? 'open' : 'locked'));
        $nodes[] = ['id' => "V$l", 'level' => $l, 'phase' => levelPhase($l), 'final' => $l === 8, 'lessons' => 4, 'done' => $d, 'lesson' => $les, 'exam' => $exam,
                    'state' => $st, 'stars' => $d ? (int)round($sum / $d) : 0,
                    'xp' => $exam ? ($full ? 10 : 20) : ($full ? 5 : 10), 'bonus' => $exam ? 10 : 5];   // XP lesson: 10 (ulang 5), ujian akhir: 20 (ulang 10)
        $prev = $full;
    }
    return $nodes;
}

try { switch ($a) {
case 'register':
    $n = trim($in['username'] ?? ''); $p = $in['password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $n)) fail('Nama pengguna 3–30 karakter (huruf, angka, _ . -).');
    if (strlen($p) < 6) fail('Kata sandi minimal 6 karakter.');
    $s = db()->prepare('INSERT INTO users(username, hash) VALUES(?,?)');
    try { $s->execute([$n, password_hash($p, PASSWORD_DEFAULT)]); } catch (PDOException $e) { fail('Nama pengguna sudah dipakai.'); }
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
    ksort($byH); $juzAyat = []; $reviewsDone = true;
    foreach ($byH as $h => $idxs) {
        $hAyat = []; $hDone = true;
        foreach ($idxs as $i) {
            $t = $T[$i]; $ayat = thumnAyat($i);
            $open = $i === 0 || juzOf($T[$i - 1]) !== $juz || thumnDone($pr, $i - 1); // thumn pertama tiap juz selalu terbuka
            $res[] = ['i' => $i, 'kind' => 'thumn', 'hizb' => $h, 'thumn' => $t['thumn'], 'label' => "Hizb $h · Thumn {$t['thumn']}", 'empty' => !$ayat, 'bad' => !$t['ok'],
                      'title' => $ayat ? rangeLabel($ayat) : ($t['ok'] ? 'Tidak ada ayat' : 'Titik awal tidak konsisten'), 'nodes' => $ayat ? levelNodes($pr, $i, $open) : []];
            $hAyat = array_merge($hAyat, $ayat); $hDone = $hDone && thumnDone($pr, $i);
        }
        $res[] = ['i' => 1000 + $h, 'kind' => 'hizb', 'hizb' => $h, 'thumn' => 0, 'label' => "Ulasan Hizb $h", 'empty' => !$hAyat, 'bad' => false,
                  'title' => $hAyat ? rangeLabel($hAyat) : '-', 'nodes' => $hAyat ? levelNodes($pr, 1000 + $h, $hDone) : []];
        $juzAyat = array_merge($juzAyat, $hAyat); $reviewsDone = $reviewsDone && unitDone($pr, 1000 + $h);
    }
    $res[] = ['i' => 2000 + $juz, 'kind' => 'juz', 'hizb' => 0, 'thumn' => 0, 'label' => "Ulasan Juz $juz", 'empty' => !$juzAyat, 'bad' => false,
              'title' => $juzAyat ? rangeLabel($juzAyat) : '-', 'nodes' => $juzAyat ? levelNodes($pr, 2000 + $juz, $reviewsDone) : []];
    out(['user' => pub($u), 'units' => $res]);

case 'quiz':
    need(); $un = (int)($_GET['unit'] ?? -1); $lv = (int)($_GET['level'] ?? 0); $les = (int)($_GET['lesson'] ?? 1);
    $ayat = unitAyat($un); if (!$ayat || $lv < 1 || $lv > 8 || $les < 1 || $les > 4) fail('Lesson tidak ditemukan.', 404);
    $n = ($lv === 8 && $les === 4) ? 15 : levelCount(count($ayat), $lv);        // ujian akhir unit: 15 soal
    $qs = genQuestions($ayat, $ayat, $n, $lv);
    if (count($qs) < 3) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
    out(['questions' => $qs]);

case 'complete':
    $u = need(); $un = (int)($in['unit'] ?? -1); $lv = (int)($in['level'] ?? 0); $les = (int)($in['lesson'] ?? 1);
    if (!unitAyat($un) || $lv < 1 || $lv > 8 || $les < 1 || $les > 4) fail('Data tidak valid.');
    $key = "V$lv.$les"; $exam = $lv === 8 && $les === 4;
    $c = max(0, (int)($in['correct'] ?? 0)); $tot = max(1, (int)($in['total'] ?? 1)); $c = min($c, $tot);
    $pct = (int)round(100 * $c / $tot); $passed = $exam ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND thumn=? AND node=?'); $q->execute([$u['id'], $un, $key]); $first = !$q->fetch();
    $base = $exam ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($exam ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $un, $key, $stars, $pct]);
    $levelDone = false;
    if ($passed) { $pr = progress($u['id']); $levelDone = true; for ($j = 1; $j <= 4; $j++) if (!isset($pr["$un:V$lv.$j"])) $levelDone = false; }
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, $today, $u['id']]);
    out(['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0, 'xp' => (int)$u['xp'] + $gain,
         'streak' => $streak, 'level' => $lv, 'lesson' => $les, 'levelDone' => $levelDone, 'unitDone' => $passed && $exam]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }