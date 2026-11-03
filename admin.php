<?php
// Laporan masukan pengguna ("terlalu mudah", "terlalu sulit", "laporkan"). Hanya untuk akun di ADMIN_USERS (core.php).
require __DIR__ . '/core.php';
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function unitLabel($id) {
    if ($id >= 2001) return 'Ulasan Juz ' . ($id - 2000);
    if ($id >= 1001) return 'Ulasan Hizb ' . ($id - 1000);
    $T = thumns(); return isset($T[$id]) ? "Hizb {$T[$id]['hizb_number']} · Thumn {$T[$id]['thumn']}" : "Unit $id";
}
const KIND_LBL = ['easy' => 'Terlalu mudah', 'hard' => 'Terlalu sulit', 'report' => 'Laporan'];

$me = null;
if (!empty($_SESSION['uid'])) { $s = db()->prepare('SELECT id, username FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]); $me = $s->fetch(); }
if (!$me || !isAdmin($me)) {
    http_response_code(403); header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><meta charset="utf-8"><body style="font:16px system-ui;padding:24px"><p>Akses ditolak. Masuk dulu di <a href="app.php">aplikasi</a> dengan akun yang terdaftar di <code>ADMIN_USERS</code> (core.php), lalu buka halaman ini lagi.</p>');
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$f = ['kind' => $_GET['kind'] ?? '', 'qkind' => $_GET['qkind'] ?? '', 'res' => $_GET['res'] ?? '0', 'unit' => $_GET['unit'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {   // tandai sudah/belum ditangani
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { http_response_code(400); exit('Token tidak valid.'); }
    db()->prepare('UPDATE feedback SET resolved = 1 - resolved WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
    $keep = array_intersect_key($_POST, array_flip(['kind', 'qkind', 'res', 'unit'])); header('Location: admin.php' . ($keep ? '?' . http_build_query($keep) : '')); exit;
}

$w = []; $p = [];
if (in_array($f['kind'], ['easy', 'hard', 'report'], true)) { $w[] = 'f.kind=?'; $p[] = $f['kind']; }
if ($f['qkind'] !== '') { $w[] = 'f.qkind=?'; $p[] = $f['qkind']; }
if ($f['unit'] !== '') { $w[] = 'f.unit=?'; $p[] = (int)$f['unit']; }
if ($f['res'] === '0' || $f['res'] === '1') { $w[] = 'f.resolved=?'; $p[] = (int)$f['res']; }
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$q = db()->prepare("SELECT f.*, u.username FROM feedback f LEFT JOIN users u ON u.id=f.user_id $where ORDER BY f.id DESC LIMIT 300"); $q->execute($p); $rows = $q->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {   // ekspor CSV (mengikuti filter)
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="masukan-hafidzquiz.csv"'); echo "\xEF\xBB\xBF";
    $o = fopen('php://output', 'w'); fputcsv($o, ['id', 'waktu', 'pengguna', 'jenis', 'unit', 'level', 'lesson', 'tipe_soal', 'alasan', 'catatan', 'dijawab_benar', 'jawaban_pengguna', 'judul_soal', 'ayat', 'selesai'], ',', '"', '');
    foreach ($rows as $r) { $j = json_decode($r['qjson'], true) ?: []; fputcsv($o, [$r['id'], $r['created_at'], $r['username'], KIND_LBL[$r['kind']] ?? $r['kind'], unitLabel((int)$r['unit']), $r['level'], $r['lesson'], $r['qkind'], $r['reason'], $r['note'], $r['correct'] ? 'ya' : 'tidak', $r['ua'], $j['title'] ?? '', $j['full'] ?? '', $r['resolved'] ? 'ya' : 'tidak'], ',', '"', ''); }
    exit;
}
$tot = db()->query("SELECT kind, COUNT(*) c FROM feedback GROUP BY kind")->fetchAll(PDO::FETCH_KEY_PAIR);
$byQ = db()->query("SELECT qkind, SUM(kind='easy') e, SUM(kind='hard') h, SUM(kind='report') r, COUNT(*) n FROM feedback GROUP BY qkind ORDER BY (h+r) DESC, n DESC")->fetchAll();
$hot = db()->query("SELECT unit, level, lesson, SUM(kind='easy') e, SUM(kind='hard') h, SUM(kind='report') r FROM feedback GROUP BY unit, level, lesson HAVING h+r>0 ORDER BY h+r DESC LIMIT 10")->fetchAll();
$qs = http_build_query(array_filter($f, fn($v) => $v !== ''));
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Laporan Masukan · HafidzQuiz</title>
<style>
:root{--bg:#fff;--fg:#3c3c3c;--mut:#777;--line:#e5e5e5;--soft:#f5f5f5;--g:#58cc02;--r:#ff4b4b;--y:#e8a800;--b:#1cb0f6}
@media(prefers-color-scheme:dark){:root{--bg:#131f24;--fg:#e8eef0;--mut:#9aa7ad;--line:#2f3d44;--soft:#202f36}}
body{margin:0;background:var(--bg);color:var(--fg);font:15px/1.5 system-ui,sans-serif}.w{max-width:1100px;margin:0 auto;padding:20px}
h1{margin:0 0 4px}h2{margin:28px 0 8px;font-size:18px}.mut{color:var(--mut)}a{color:var(--b)}
.cards{display:flex;gap:12px;flex-wrap:wrap}.card{flex:1;min-width:140px;border:2px solid var(--line);border-radius:14px;padding:12px}.card b{display:block;font-size:26px}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:8px;border-bottom:1px solid var(--line);vertical-align:top}th{color:var(--mut);font-size:13px}
.k{display:inline-block;padding:1px 8px;border-radius:8px;font-size:12px;font-weight:700;color:#fff}.k.easy{background:var(--b)}.k.hard{background:var(--y)}.k.report{background:var(--r)}
.ar{font-family:"LPMQ IsepMisbah","Scheherazade New",serif;font-size:22px;direction:rtl;line-height:2}
form.f{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}select,input,button{font:inherit;padding:6px 10px;border:2px solid var(--line);border-radius:10px;background:var(--bg);color:var(--fg)}
button{cursor:pointer}.ai{width:16px;height:16px;vertical-align:-3px}tr.res{opacity:.5}details pre{white-space:pre-wrap;background:var(--soft);padding:8px;border-radius:8px;font-size:12px;max-height:220px;overflow:auto}
</style></head><body><div class="w">
<h1>Laporan Masukan</h1><p class="mut">Masuk sebagai <?= h($me['username']) ?> · <a href="app.php">kembali ke aplikasi</a></p>
<div class="cards">
 <div class="card"><small class="mut">Total masukan</small><b><?= array_sum($tot) ?></b></div>
 <div class="card"><small class="mut"><svg class="ai" viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="#1cb0f6" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h6l-6 7h6"/><path d="M13 12h5l-5 6h5"/></g></svg> Terlalu mudah</small><b><?= (int)($tot['easy'] ?? 0) ?></b></div>
 <div class="card"><small class="mut"><svg class="ai" viewBox="0 0 24 24" aria-hidden="true"><path d="M1.5 20.5L9 7l4.4 7.4 2.6-3.6 6.5 9.7z" fill="#e8a800"/></svg> Terlalu sulit</small><b><?= (int)($tot['hard'] ?? 0) ?></b></div>
 <div class="card"><small class="mut"><svg class="ai" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 21V3.5" stroke="#8b99a1" stroke-width="2.4" stroke-linecap="round"/><path d="M6.5 4.5h11.5l-2.6 4.2 2.6 4.2H6.5z" fill="#ff4b4b"/></svg> Laporan</small><b><?= (int)($tot['report'] ?? 0) ?></b></div>
</div>
<h2>Per tipe soal</h2>
<table><tr><th>Tipe soal</th><th>Mudah</th><th>Sulit</th><th>Laporan</th><th>Total</th></tr>
<?php foreach ($byQ as $r): ?><tr><td><?= h($r['qkind'] ?: '(tidak diketahui)') ?></td><td><?= $r['e'] ?></td><td><?= $r['h'] ?></td><td><?= $r['r'] ?></td><td><?= $r['n'] ?></td></tr><?php endforeach; if (!$byQ) echo '<tr><td colspan="5" class="mut">Belum ada masukan.</td></tr>'; ?></table>
<h2>Lesson paling bermasalah</h2>
<table><tr><th>Unit</th><th>Level · Lesson</th><th>Mudah</th><th>Sulit</th><th>Laporan</th></tr>
<?php foreach ($hot as $r): ?><tr><td><a href="?unit=<?= (int)$r['unit'] ?>&res=all"><?= h(unitLabel((int)$r['unit'])) ?></a></td><td><?= $r['level'] ? "Level {$r['level']} · Lesson {$r['lesson']}" : 'Tes lompat' ?></td><td><?= $r['e'] ?></td><td><?= $r['h'] ?></td><td><?= $r['r'] ?></td></tr><?php endforeach; if (!$hot) echo '<tr><td colspan="5" class="mut">—</td></tr>'; ?></table>
<h2>Daftar masukan <span class="mut">(<?= count($rows) ?> ditampilkan)</span></h2>
<form class="f" method="get">
 <select name="kind"><option value="">Semua jenis</option><?php foreach (KIND_LBL as $k => $l) echo '<option value="' . $k . '"' . ($f['kind'] === $k ? ' selected' : '') . '>' . h($l) . '</option>'; ?></select>
 <select name="qkind"><option value="">Semua tipe soal</option><?php foreach ($byQ as $r) if ($r['qkind'] !== '') echo '<option' . ($f['qkind'] === $r['qkind'] ? ' selected' : '') . '>' . h($r['qkind']) . '</option>'; ?></select>
 <select name="res"><option value="0"<?= $f['res'] === '0' ? ' selected' : '' ?>>Belum ditangani</option><option value="1"<?= $f['res'] === '1' ? ' selected' : '' ?>>Sudah ditangani</option><option value="all"<?= $f['res'] === 'all' ? ' selected' : '' ?>>Semua</option></select>
 <input name="unit" placeholder="ID unit" value="<?= h($f['unit']) ?>" size="8"><button>Terapkan</button> <a href="?<?= h($qs . ($qs ? '&' : '') . 'export=csv') ?>">Ekspor CSV</a>
</form>
<table><tr><th>Waktu / pengguna</th><th>Jenis</th><th>Lokasi</th><th>Soal</th><th></th></tr>
<?php foreach ($rows as $r): $j = json_decode($r['qjson'], true) ?: []; ?>
<tr class="<?= $r['resolved'] ? 'res' : '' ?>"><td><?= h($r['created_at']) ?><br><span class="mut"><?= h($r['username']) ?></span></td>
<td><span class="k <?= h($r['kind']) ?>"><?= h(KIND_LBL[$r['kind']] ?? $r['kind']) ?></span><?php if ($r['reason'] || $r['note']): ?><br><b><?= h($r['reason']) ?></b><br><?= h($r['note']) ?><?php endif; ?></td>
<td><?= h(unitLabel((int)$r['unit'])) ?><br><span class="mut"><?= $r['level'] ? "Level {$r['level']} · Lesson {$r['lesson']}" : 'Tes lompat' ?></span></td>
<td><b><?= h($j['title'] ?? '') ?></b> <span class="mut">[<?= h($r['qkind']) ?>] · pengguna menjawab <?= $r['correct'] ? 'benar' : 'salah' ?></span>
 <?php if (!empty($j['full'])): ?><div class="ar"><?= nl2br(h($j['full'])) ?></div><?php endif; ?>
 <details><summary class="mut">Detail soal & jawaban</summary><pre><?= h(json_encode(['jawaban_pengguna' => json_decode($r['ua'], true), 'soal' => $j], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre></details></td>
<td><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
 <?php foreach (['kind', 'qkind', 'res', 'unit'] as $k) if ($f[$k] !== '') echo '<input type="hidden" name="' . $k . '" value="' . h($f[$k]) . '">'; ?>
 <button><?= $r['resolved'] ? 'Buka lagi' : 'Selesai' ?></button></form></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="5" class="mut">Tidak ada masukan yang cocok.</td></tr>'; ?></table>
</div></body></html>