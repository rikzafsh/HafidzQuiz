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
// Lesson selesai dalam satu node: baris "L1.1", "L1.2", ... (baris lama "L1" = seluruh node selesai)
function nodeState($pr, $i, $id, $m) {
    if (isset($pr["$i:$id"])) return [$m, $pr["$i:$id"]];
    $d = $sum = 0;
    for ($j = 1; $j <= $m; $j++) if (isset($pr["$i:$id.$j"])) { $d++; $sum += $pr["$i:$id.$j"]; }
    return [$d, $d ? (int)round($sum / $d) : 0];
}
function thumnDone($pr, $i) { return isset($pr["$i:T"]) || !thumnAyat($i); } // thumn kosong tidak boleh memblokir thumn berikutnya

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
    foreach (thumns() as $i => $t) { $j = juzOf($t); $tot[$j] = ($tot[$j] ?? 0) + 1; if (isset($pr["$i:T"])) $done[$j] = ($done[$j] ?? 0) + 1; }
    $r = []; foreach ($tot as $j => $c) $r[] = ['juz' => $j, 'total' => $c, 'done' => $done[$j] ?? 0];
    out(['user' => pub($u), 'juz' => $r]);

case 'units':
    $u = need(); $juz = (int)($_GET['juz'] ?? 1); $pr = progress($u['id']); $T = thumns(); $res = [];
    foreach ($T as $i => $t) {
        if (juzOf($t) !== $juz) continue;
        $ayat = thumnAyat($i);
        $unit = ['i' => $i, 'hizb' => $t['hizb_number'], 'thumn' => $t['thumn'], 'empty' => !$ayat, 'bad' => !$t['ok'], 'nodes' => [],
                 'title' => $ayat ? rangeLabel($ayat) : ($t['ok'] ? 'Tidak ada ayat' : 'Titik awal tidak konsisten')];
        $open = $i === 0 || juzOf($T[$i - 1]) !== $juz || thumnDone($pr, $i - 1); // thumn pertama tiap juz selalu terbuka
        $prev = true;
        foreach (nodeChunks($ayat) as $ni => $ch) {
            $id = 'L' . ($ni + 1); $m = lessonCount($ch); [$d, $stars] = nodeState($pr, $i, $id, $m); $full = $d >= $m;
            $st = !$open ? 'locked' : ($full ? 'done' : ($prev ? 'open' : 'locked'));
            $unit['nodes'][] = ['id' => $id, 'label' => rangeLabel($ch), 'lessons' => $m, 'done' => $d, 'lesson' => min($d + 1, $m),
                                'state' => $st, 'stars' => $stars, 'xp' => $full ? 5 : 10, 'bonus' => 5];
            $prev = $full;
        }
        if ($ayat) {
            $td = isset($pr["$i:T"]); $st = !$open ? 'locked' : ($td ? 'done' : ($prev ? 'open' : 'locked'));
            $unit['nodes'][] = ['id' => 'T', 'label' => 'Test', 'lessons' => 1, 'done' => $td ? 1 : 0, 'lesson' => 1,
                                'state' => $st, 'stars' => $pr["$i:T"] ?? 0, 'xp' => $td ? 10 : 20, 'bonus' => 10];
        }
        $res[] = $unit;
    }
    out(['user' => pub($u), 'units' => $res]);

case 'quiz':
    need(); $i = (int)($_GET['thumn'] ?? -1); $node = $_GET['node'] ?? ''; $les = (int)($_GET['lesson'] ?? 1);
    $ayat = thumnAyat($i); if (!$ayat || !preg_match('/^(L\d+|T)$/', $node)) fail('Pelajaran tidak ditemukan.', 404);
    if ($node === 'T') { $src = $ayat; $n = 15; $stage = 'mid'; }
    else {
        $ch = nodeChunks($ayat)[(int)substr($node, 1) - 1] ?? null; if (!$ch) fail('Pelajaran tidak ditemukan.', 404);
        $m = lessonCount($ch); if ($les < 1 || $les > $m) fail('Lesson tidak ditemukan.', 404);
        $src = $ch; $n = 8; $stage = $les === 1 ? 'intro' : ($les === $m ? 'master' : 'mid');
    }
    $qs = genQuestions($src, $ayat, $n, $stage); if (!$qs) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
    out(['questions' => $qs]);

case 'complete':
    $u = need(); $i = (int)($in['thumn'] ?? -1); $node = $in['node'] ?? ''; $les = (int)($in['lesson'] ?? 1);
    $ayat = thumnAyat($i); if (!$ayat || !preg_match('/^(L\d+|T)$/', $node)) fail('Data tidak valid.');
    $test = $node === 'T'; $m = 1;
    if (!$test) {
        $ch = nodeChunks($ayat)[(int)substr($node, 1) - 1] ?? null; if (!$ch) fail('Data tidak valid.');
        $m = lessonCount($ch); if ($les < 1 || $les > $m) fail('Data tidak valid.');
    }
    $key = $test ? 'T' : "$node.$les";
    $c = max(0, (int)($in['correct'] ?? 0)); $tot = max(1, (int)($in['total'] ?? 1)); $c = min($c, $tot);
    $pct = (int)round(100 * $c / $tot); $passed = $test ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND thumn=? AND node IN (?,?)'); $q->execute([$u['id'], $i, $key, $test ? 'T' : $node]);
    $first = !$q->fetch();
    // XP tetap & bisa ditampilkan sebelum mulai: lesson 10 (ulang 5) + bonus 5 bila sempurna; test 20 (ulang 10) + bonus 10
    $base = $test ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($test ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $i, $key, $stars, $pct]);
    $nodeDone = false;
    if ($passed && !$test) { [$d] = nodeState(progress($u['id']), $i, $node, $m); $nodeDone = $d >= $m; }
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, $today, $u['id']]);
    out(['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0,
         'xp' => (int)$u['xp'] + $gain, 'streak' => $streak, 'lesson' => $les, 'lessons' => $m, 'nodeDone' => $nodeDone]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }