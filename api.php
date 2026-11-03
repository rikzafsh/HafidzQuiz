<?php
ini_set('display_errors', '0');   // jangan pernah mencemari JSON dengan pesan error
ob_start();
require __DIR__ . '/core.php';
require __DIR__ . '/gen.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

function me() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, username, xp, streak, last_day, dirham FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function need() { return me() ?? fail('Silakan masuk dahulu.', 401); }
function pub($u) { return ['name' => $u['username'], 'xp' => (int)$u['xp'], 'streak' => (int)$u['streak'], 'dirham' => (int)($u['dirham'] ?? 0), 'dev' => DEV_MODE]; }
function progress($uid) {
    $s = db()->prepare('SELECT thumn, node, stars FROM progress WHERE user_id=?'); $s->execute([$uid]);
    $m = []; foreach ($s as $r) $m["{$r['thumn']}:{$r['node']}"] = (int)$r['stars']; return $m;
}
function levelPhase($d) { return $d <= 3 ? 'Dasar' : ($d <= 5 ? 'Menengah' : 'Mahir'); }
// ID unit: 0..479 = thumn | 1001..1060 = ulasan hizb | 2001..2030 = ulasan juz.
//  thumn: 8 level x 4 lesson (thumn genap = akhir rub/nisf: +1 lesson ulasan dari awal hizb, jadi 5 lesson per level)
//  ulasan hizb: 4 level x 4 lesson | ulasan juz: 8 level x 4 lesson.  Unit selesai bila lesson terakhir di level terakhir (ujian akhir) lulus.
function unitMeta(int $u): array {
    if ($u >= 2001 && $u <= 2030) return ['kind' => 'juz', 'levels' => 8, 'lessons' => 4];
    if ($u >= 1001 && $u <= 1060) return ['kind' => 'hizb', 'levels' => 4, 'lessons' => 4];
    $T = thumns(); return ['kind' => 'thumn', 'levels' => 8, 'lessons' => (isset($T[$u]) && $T[$u]['thumn'] % 2 === 0) ? 5 : 4];
}
function diffLevel($kind, $l) { return $kind === 'hizb' ? [1 => 3, 2 => 5, 3 => 7, 4 => 8][$l] : $l; }   // ulasan hizb: 4 level memetakan Dasar -> Mahir
function unitDone($pr, $u) { $m = unitMeta($u); return isset($pr["$u:V{$m['levels']}.{$m['lessons']}"]); }
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
    $m = unitMeta($u); [$L, $N] = [$m['levels'], $m['lessons']]; $nodes = []; $prev = true;
    for ($l = 1; $l <= $L; $l++) {
        $d = $sum = 0; for ($j = 1; $j <= $N; $j++) if (isset($pr["$u:V$l.$j"])) { $d++; $sum += $pr["$u:V$l.$j"]; }
        $full = $d >= $N; $les = $full ? $N : $d + 1; $exam = $l === $L && $les === $N; $cumul = $m['kind'] === 'thumn' && $N === 5 && $les === 5;
        $st = !$open ? 'locked' : ($full ? 'done' : ($prev ? 'open' : 'locked'));
        $nodes[] = ['id' => "V$l", 'level' => $l, 'levels' => $L, 'phase' => levelPhase(diffLevel($m['kind'], $l)), 'final' => $l === $L, 'lessons' => $N, 'done' => $d,
                    'lesson' => $les, 'exam' => $exam, 'cumul' => $cumul, 'state' => $st, 'stars' => $d ? (int)round($sum / $d) : 0,
                    'xp' => $exam ? ($full ? 10 : 20) : ($full ? 5 : 10), 'bonus' => $exam ? 10 : 5];   // XP lesson: 10 (ulang 5), ujian akhir: 20 (ulang 10)
        $prev = $full;
    }
    return $nodes;
}
function chests($pr, $u, $nodes, $open) {   // 2 chest di tiap unit thumn: setelah level 4 dan level 7
    $out = [];
    foreach ([4, 7] as $lv) {
        $n = $nodes[$lv - 1]; $got = $pr["$u:C$lv"] ?? null;
        $out[] = ['id' => "C$lv", 'after' => $lv, 'state' => $got ? 'opened' : ($open && $n['done'] >= $n['lessons'] ? 'ready' : 'locked'), 'amount' => $got ?: null];
    }
    return $out;
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
            $nodes = $ayat ? levelNodes($pr, $i, $open) : [];
            $res[] = ['i' => $i, 'kind' => 'thumn', 'hizb' => $h, 'thumn' => $t['thumn'], 'label' => "Hizb $h · Thumn {$t['thumn']}", 'empty' => !$ayat, 'bad' => !$t['ok'],
                      'title' => $ayat ? rangeLabel($ayat) : ($t['ok'] ? 'Tidak ada ayat' : 'Titik awal tidak konsisten'), 'nodes' => $nodes, 'chests' => $ayat ? chests($pr, $i, $nodes, $open) : []];
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
    $ayat = unitAyat($un); $m = unitMeta($un);
    if (!$ayat || $lv < 1 || $lv > $m['levels'] || $les < 1 || $les > $m['lessons']) fail('Lesson tidak ditemukan.', 404);
    $exam = $lv === $m['levels'] && $les === $m['lessons'];
    $src = ($m['kind'] === 'thumn' && $m['lessons'] === 5 && $les === 5) ? cumulAyat($un) : $ayat;   // lesson ke-5 = ulasan dari awal hizb
    $df = diffLevel($m['kind'], $lv); $n = $exam ? 15 : levelCount(count($src), $df);
    $qs = genQuestions($src, $src, $n, $df);
    if (count($qs) < 3) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
    out(['questions' => $qs]);

case 'complete':
    $u = need(); $un = (int)($in['unit'] ?? -1); $lv = (int)($in['level'] ?? 0); $les = (int)($in['lesson'] ?? 1); $m = unitMeta($un);
    if (!unitAyat($un) || $lv < 1 || $lv > $m['levels'] || $les < 1 || $les > $m['lessons']) fail('Data tidak valid.');
    $key = "V$lv.$les"; $exam = $lv === $m['levels'] && $les === $m['lessons'];
    $c = max(0, (int)($in['correct'] ?? 0)); $tot = max(1, (int)($in['total'] ?? 1)); $c = min($c, $tot);
    $pct = (int)round(100 * $c / $tot); $passed = $exam ? $pct >= 80 : true;
    $stars = $pct >= 100 ? 3 : ($pct >= 85 ? 2 : 1);
    $q = db()->prepare('SELECT 1 FROM progress WHERE user_id=? AND thumn=? AND node=?'); $q->execute([$u['id'], $un, $key]); $first = !$q->fetch();
    $base = $exam ? ($first ? 20 : 10) : ($first ? 10 : 5); $bonus = $c === $tot ? ($exam ? 10 : 5) : 0;
    $gain = $passed ? $base + $bonus : 0;
    if ($passed) db()->prepare('INSERT INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,?) ON CONFLICT(user_id,thumn,node) DO UPDATE SET stars=MAX(stars,excluded.stars), best=MAX(best,excluded.best)')->execute([$u['id'], $un, $key, $stars, $pct]);
    $levelDone = false;
    if ($passed) { $pr = progress($u['id']); $levelDone = true; for ($j = 1; $j <= $m['lessons']; $j++) if (!isset($pr["$un:V$lv.$j"])) $levelDone = false; }
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=? WHERE id=?')->execute([$gain, $streak, $today, $u['id']]);
    out(['passed' => $passed, 'pct' => $pct, 'stars' => $passed ? $stars : 0, 'gain' => $gain, 'bonus' => $passed ? $bonus : 0, 'xp' => (int)$u['xp'] + $gain,
         'streak' => $streak, 'level' => $lv, 'lesson' => $les, 'lessons' => $m['lessons'], 'levelDone' => $levelDone, 'unitDone' => $passed && $exam]);

case 'claim':   // buka chest (unit thumn, setelah level 4 / 7): 10-20 dirham acak, sekali per chest
    $u = need(); $un = (int)($in['unit'] ?? -1); $ch = (int)($in['chest'] ?? 0);
    if ($un < 0 || $un >= 480 || !in_array($ch, [4, 7], true) || !thumnAyat($un)) fail('Chest tidak valid.');
    $pr = progress($u['id']); $m = unitMeta($un);
    for ($j = 1; $j <= $m['lessons']; $j++) if (!isset($pr["$un:V$ch.$j"])) fail("Selesaikan Level $ch dulu untuk membuka chest ini.");
    $amt = random_int(10, 20); $pdo = db(); $pdo->beginTransaction();
    $s = $pdo->prepare('INSERT OR IGNORE INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,?,0)');   // jumlah dirham disimpan di kolom stars
    $s->execute([$u['id'], $un, "C$ch", $amt]);
    if ($s->rowCount() === 0) { $pdo->rollBack(); fail('Chest ini sudah dibuka.'); }
    $pdo->prepare('UPDATE users SET dirham=dirham+? WHERE id=?')->execute([$amt, $u['id']]); $pdo->commit();
    out(['amount' => $amt, 'dirham' => (int)$u['dirham'] + $amt]);

case 'dev_mark':   // SEMENTARA: tandai level (dan semua level sebelumnya) selesai
    if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
    $u = need(); $un = (int)($in['unit'] ?? -1); $lv = (int)($in['level'] ?? 0); $m = unitMeta($un);
    if (!unitAyat($un) || $lv < 1 || $lv > $m['levels']) fail('Data tidak valid.');
    $s = db()->prepare('INSERT OR IGNORE INTO progress(user_id,thumn,node,stars,best) VALUES(?,?,?,3,100)');
    for ($l = 1; $l <= $lv; $l++) for ($j = 1; $j <= $m['lessons']; $j++) $s->execute([$u['id'], $un, "V$l.$j"]);
    out(['ok' => true]);

case 'dev_reset':  // SEMENTARA: hapus seluruh progres satu unit (termasuk chest)
    if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
    $u = need(); db()->prepare('DELETE FROM progress WHERE user_id=? AND thumn=?')->execute([$u['id'], (int)($in['unit'] ?? -1)]);
    out(['ok' => true]);
default: fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }