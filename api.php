<?php
require __DIR__ . '/core.php';
require __DIR__ . '/gen.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

function me() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, username, xp, streak FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function need() { return me() ?? fail('Silakan masuk dahulu.', 401); }
function pub($u) { return ['name' => $u['username'], 'xp' => (int)$u['xp'], 'streak' => (int)$u['streak']]; }
function progress($uid) {
    $s = db()->prepare('SELECT thumn, node, stars FROM progress WHERE user_id=?'); $s->execute([$uid]);
    $m = []; foreach ($s as $r) $m["{$r['thumn']}:{$r['node']}"] = (int)$r['stars']; return $m;
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
    foreach (thumns() as $i => $t) { $j = juzOf($t); $tot[$j] = ($tot[$j] ?? 0) + 1; if (isset($pr["$i:T"])) $done[$j] = ($done[$j] ?? 0) + 1; }
    $r = []; foreach ($tot as $j => $c) $r[] = ['juz' => $j, 'total' => $c, 'done' => $done[$j] ?? 0];
    out(['user' => pub($u), 'juz' => $r]);

case 'units':
    $u = need(); $juz = (int)($_GET['juz'] ?? 1); $pr = progress($u['id']); $T = thumns(); $res = [];
    foreach ($T as $i => $t) {
        if (juzOf($t) !== $juz) continue;
        $ayat = thumnAyat($i); if (!$ayat) continue;
        $open = $i === 0 || juzOf($T[$i - 1]) !== $juz || isset($pr[($i - 1) . ':T']); // thumn pertama tiap juz selalu terbuka
        $nodes = []; $prev = true;
        foreach (nodesOf($ayat) as $id) {
            $has = isset($pr["$i:$id"]);
            $st = !$open ? 'locked' : ($has ? 'done' : ($prev ? 'open' : 'locked'));
            $nodes[] = ['id' => $id, 'state' => $st, 'stars' => $pr["$i:$id"] ?? 0]; $prev = $has;
        }
        $res[] = ['i' => $i, 'hizb' => $t['hizb_number'], 'thumn' => $t['thumn'], 'title' => rangeLabel($ayat), 'nodes' => $nodes];
    }
    out(['user' => pub($u), 'units' => $res]);

case 'quiz':
    need(); $i = (int)($_GET['thumn'] ?? -1); $node = $_GET['node'] ?? '';
    $ayat = thumnAyat($i); if (!$ayat || !preg_match('/^(L\d+|T)$/', $node)) fail('Pelajaran tidak ditemukan.', 404);
    $ls = lessons($ayat);
    if ($node === 'T') { $src = $ayat; $n = 15; $easy = false; }
    else { $li = (int)substr($node, 1) - 1; if (!isset($ls[$li])) fail('Pelajaran tidak ditemukan.', 404); $src = $ls[$li]; $n = 8; $easy = $li === 0 && count($ls) > 1; }
    out(['questions' => genQuestions($src, $ayat, $n, $easy)]);

case 'complete':
    $u = need(); $i = (int)($in['thumn'] ?? -1); $node = $in['node'] ?? '';
    $c = max(0, (int)($in['correct'] ?? 0)); $tot = max(1, (int)($in['total'] ?? 1)); $c = min($c, $tot);
    if (!thumnAyat($i) || !preg_match('/^(L\d+|T)$/', $node)) fail('Data tidak valid.');
    $pct = (int)round(100 * $c / $tot); $passed = $node === 'T' ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1); $gain = $c * 2 + ($passed ? 10 : 0);
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $i, $node, $stars, $pct]);
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, $today, $u['id']]);
    out(['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'xp' => (int)$u['xp'] + $gain, 'streak' => $streak]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }
