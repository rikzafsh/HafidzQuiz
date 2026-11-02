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
// Thumn selesai bila level 8 (ujian akhir) lulus. Thumn tanpa ayat tidak boleh memblokir thumn berikutnya.
function thumnDone($pr, $i) { return isset($pr["$i:V8"]) || !thumnAyat($i); }

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
    foreach (thumns() as $i => $t) { $j = juzOf($t); $tot[$j] = ($tot[$j] ?? 0) + 1; if (isset($pr["$i:V8"])) $done[$j] = ($done[$j] ?? 0) + 1; }
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
        for ($l = 1; $ayat && $l <= 8; $l++) {
            $done = isset($pr["$i:V$l"]);
            $st = !$open ? 'locked' : ($done ? 'done' : ($prev ? 'open' : 'locked'));
            $unit['nodes'][] = ['id' => "V$l", 'level' => $l, 'phase' => levelPhase($l), 'final' => $l === 8, 'state' => $st, 'stars' => $pr["$i:V$l"] ?? 0,
                                'xp' => $l === 8 ? ($done ? 10 : 20) : ($done ? 5 : 10), 'bonus' => $l === 8 ? 10 : 5];
            $prev = $done;
        }
        $res[] = $unit;
    }
    out(['user' => pub($u), 'units' => $res]);

case 'quiz':
    need(); $i = (int)($_GET['thumn'] ?? -1); $lv = (int)($_GET['level'] ?? 0);
    $ayat = thumnAyat($i); if (!$ayat || $lv < 1 || $lv > 8) fail('Level tidak ditemukan.', 404);
    $qs = genQuestions($ayat, $ayat, levelCount(count($ayat), $lv), $lv);
    if (count($qs) < 3) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
    out(['questions' => $qs]);

case 'complete':
    $u = need(); $i = (int)($in['thumn'] ?? -1); $lv = (int)($in['level'] ?? 0);
    if (!thumnAyat($i) || $lv < 1 || $lv > 8) fail('Data tidak valid.');
    $key = "V$lv"; $final = $lv === 8;
    $c = max(0, (int)($in['correct'] ?? 0)); $tot = max(1, (int)($in['total'] ?? 1)); $c = min($c, $tot);
    $pct = (int)round(100 * $c / $tot); $passed = $final ? $pct >= 80 : true;      // level 8 = ujian akhir thumn
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND thumn=? AND node=?'); $q->execute([$u['id'], $i, $key]); $first = !$q->fetch();
    // XP tetap & bisa ditampilkan sebelum mulai: level 10 (ulang 5) + bonus 5 bila sempurna; level 8: 20 (ulang 10) + bonus 10
    $base = $final ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($final ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $i, $key, $stars, $pct]);
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, $today, $u['id']]);
    out(['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0,
         'xp' => (int)$u['xp'] + $gain, 'streak' => $streak, 'level' => $lv, 'thumnDone' => $passed && $final]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }