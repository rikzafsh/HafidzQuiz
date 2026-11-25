<?php
// Mesin sesi kuis generik (dipakai semua course). Soal disimpan di server lengkap dengan kunci jawaban; browser hanya menerima soal tanpa kunci.
// Hasil akhir sesi diterapkan oleh course pemilik sesi lewat hook 'finish' di manifest course.
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
function newAttempt($uid, string $course, $kind, $un, $lv, $les, array $Q): array {
    $id = bin2hex(random_bytes(12)); $hearts = $kind === 'jump' ? 1 : 5;
    $S = array_map(fn($q) => ['tries' => 0, 'done' => false, 'miss' => false, 'last' => null, 'ua' => null, 'matched' => []], $Q);
    $pdo = db(); $pdo->prepare('DELETE FROM attempts WHERE started < ?')->execute([time() - 86400]);
    $pdo->prepare('INSERT INTO attempts(id,user_id,course,kind,unit,level,lesson,qjson,sjson,hearts,started) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$id, $uid, $course, $kind, $un, $lv, $les, json_encode($Q, JSON_UNESCAPED_UNICODE), json_encode($S, JSON_UNESCAPED_UNICODE), $hearts, time()]);
    return ['attempt' => $id, 'hearts' => $hearts, 'total' => count($Q), 'questions' => array_map('publicQuestion', $Q, array_keys($Q))];
}
function loadAttempt($uid, array $in): array {
    $s = db()->prepare('SELECT * FROM attempts WHERE id=? AND user_id=?'); $s->execute([(string)($in['attempt'] ?? ''), $uid]);
    return $s->fetch() ?: fail('Sesi kuis tidak ditemukan atau sudah kedaluwarsa.', 404);
}

function quiz_check(array $u, array $in): array {   // periksa satu jawaban (atau satu pasangan pada soal pencocokan) di server
    $at = loadAttempt($u['id'], $in); if ($at['finished']) fail('Sesi sudah selesai.', 409);
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
    return ['ok' => $ok, 'done' => $done, 'hearts' => $hearts, 'mistakes' => $tries, 'full' => ($done || $q['t'] !== 'match') ? $q['full'] : null];
}

function quiz_finish(array $u, array $in): array {   // skor dihitung dari catatan server, bukan dari laporan browser
    $at = loadAttempt($u['id'], $in); if ($at['finished']) fail('Sesi sudah selesai.', 409);
    $Q = json_decode($at['qjson'], true); $S = json_decode($at['sjson'], true);
    if ((int)$at['hearts'] <= 0) fail('Nyawa habis.', 409);
    foreach ($S as $s) if (!$s['done']) fail('Masih ada soal yang belum diselesaikan.', 409);
    $upd = db()->prepare('UPDATE attempts SET finished=1 WHERE id=? AND finished=0'); $upd->execute([$at['id']]); if ($upd->rowCount() === 0) fail('Sesi sudah selesai.', 409);
    $tot = count($Q); $cnt = count(array_filter($S, fn($s) => !$s['miss'])); $secs = max(1, time() - (int)$at['started']);
    $c = courseGet((string)$at['course']) ?? fail('Kursus tidak dikenal.', 404); courseLoad($c);   // hasil sesi diterapkan oleh course pemilik sesi
    if (empty($c['finish']) || !function_exists($c['finish'])) fail('Kursus ini belum mendukung kuis.');
    return ($c['finish'])($u, $at, $cnt, $tot) + ['secs' => $secs];
}

function quiz_feedback(array $u, array $in): array {   // "terlalu mudah" / "terlalu sulit" / "laporkan": soal & jawaban diambil dari catatan server
    $kind = $in['kind'] ?? '';
    if (!in_array($kind, ['easy', 'hard', 'report'], true)) fail('Jenis masukan tidak valid.');
    $at = loadAttempt($u['id'], $in); $Q = json_decode($at['qjson'], true); $S = json_decode($at['sjson'], true); $qi = (int)($in['qi'] ?? -1);
    if (!isset($Q[$qi]) || (!$S[$qi]['done'] && !$S[$qi]['tries'])) fail('Jawab soal ini dulu sebelum memberi masukan.');
    db()->prepare('INSERT INTO feedback(user_id,course,unit,level,lesson,kind,reason,note,qkind,qjson,ua,correct,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
        $u['id'], (string)$at['course'], (int)$at['unit'], (int)$at['level'], (int)$at['lesson'], $kind, mb_substr((string)($in['reason'] ?? ''), 0, 80), mb_substr((string)($in['note'] ?? ''), 0, 500),
        mb_substr((string)($Q[$qi]['kind'] ?? ''), 0, 30), json_encode($Q[$qi], JSON_UNESCAPED_UNICODE), mb_substr(json_encode($S[$qi]['ua'], JSON_UNESCAPED_UNICODE), 0, 2000), !empty($S[$qi]['last']) ? 1 : 0, date('Y-m-d H:i:s')]);
    return ['ok' => true];
}
