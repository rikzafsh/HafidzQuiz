<?php
// Aksi API khusus course Tahfidz (peta juz/unit, pembuatan soal, chest, mushaf, alat developer) dan hook hasil sesi/profil.
// Dipanggil oleh api.php lewat manifest (course.php). Setiap aksi yang dikenal berakhir dengan out() (keluar); mengembalikan false bila aksi tidak dikenal.
function tahfidz_api(string $a, array $u, array $in, array $c): bool {
    switch ($a) {
    case 'juz':
        $pr = tProgress($u['id']); $tot = $done = [];
        foreach (thumns() as $i => $t) { $j = juzOf($t); $tot[$j] = ($tot[$j] ?? 0) + 1; if (unitDone($pr, $i)) $done[$j] = ($done[$j] ?? 0) + 1; }
        $r = []; foreach ($tot as $j => $c) $r[] = ['juz' => $j, 'total' => $c, 'done' => $done[$j] ?? 0];
        out(['user' => pub($u), 'juz' => $r]);

    case 'units':   // per juz: [8 thumn + ulasan hizb] x 2 hizb, lalu ulasan juz
        $juz = (int)($_GET['juz'] ?? 1); $pr = tProgress($u['id']); $T = thumns(); $res = []; $byH = [];
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
        $un = (int)($_GET['unit'] ?? -1);
        if (isset($_GET['jump'])) {   // tes "Lompat ke sini": 25 soal dari thumn yang akan dilewati
            if ($un < 0 || $un >= 480 || !thumnAyat($un)) fail('Thumn tidak valid.', 404);
            $pool = []; foreach (skippedThumns(tProgress($u['id']), $un) as $k) $pool = array_merge($pool, thumnAyat($k));
            if (!$pool) fail('Tidak ada thumn yang perlu dilewati.', 404);
            $qs = jumpQuestions($pool); if (count($qs) < 25) fail('Soal belum bisa dibuat.', 500);
            out(newAttempt($u['id'], TAHFIDZ, 'jump', $un, 0, 0, $qs));
        }
        $lv = (int)($_GET['level'] ?? 0); $les = (int)($_GET['lesson'] ?? 1); $ayat = unitAyat($un); $m = unitMeta($un);
        if (!$ayat || $lv < 1 || $lv > maxLevel($m) || $les < 1 || $les > 4) fail('Lesson tidak ditemukan.', 404);
        $rev = $lv > $m['levels']; $exam = !$rev && $lv === $m['exam'] && $les === 4;
        $src = $rev ? cumulAyat($un) : $ayat;                                       // level ulasan: dari awal hizb sampai thumn ini
        $n = ($exam || ($rev && $les === 4)) ? 15 : levelCount(count($src), lessonDiff($m, $lv, $les));
        $qs = genQuestions($src, $src, $n, lessonDiff($m, $lv, $les));
        if (count($qs) < 3) fail('Soal belum bisa dibuat untuk bagian ini.', 500);
        out(newAttempt($u['id'], TAHFIDZ, 'lesson', $un, $lv, $les, $qs));

    case 'mushaf_meta':
        out(['surahs' => surahNames(), 'maxPage' => mushafMaxPage(), 'lastPos' => mushafLastPos()]);

    case 'mushaf':
        $p = (int)($_GET['page'] ?? 1); $r = mushafPage($p);
        if (!$r) fail("Halaman $p belum tersedia (data mushaf baru sampai halaman " . mushafMaxPage() . ').', 404);
        out($r);

    case 'mushaf_find':
        out(mushafFind($_GET));

    case 'claim':   // buka chest (unit thumn, setelah level 4 / 7): 10-20 dirham acak, sekali per chest
        $un = (int)($in['unit'] ?? -1); $ch = (int)($in['chest'] ?? 0);
        if ($un < 0 || $un >= 480 || !in_array($ch, [4, 7], true) || !thumnAyat($un)) fail('Chest tidak valid.');
        $pr = tProgress($u['id']);
        for ($j = 1; $j <= 4; $j++) if (!isset($pr["$un:V$ch.$j"])) fail("Selesaikan Level $ch dulu untuk membuka chest ini.");
        $amt = random_int(10, 20); $pdo = db(); $pdo->beginTransaction();
        $s = $pdo->prepare('INSERT OR IGNORE INTO progress(user_id,course,unit,node,stars,best) VALUES(?,?,?,?,?,0)');   // jumlah dirham disimpan di kolom stars
        $s->execute([$u['id'], TAHFIDZ, $un, "C$ch", $amt]);
        if ($s->rowCount() === 0) { $pdo->rollBack(); fail('Chest ini sudah dibuka.'); }
        $pdo->prepare('UPDATE users SET dirham=dirham+?, dirham_total=dirham_total+? WHERE id=?')->execute([$amt, $amt, $u['id']]); $pdo->commit();
        out(['amount' => $amt, 'dirham' => (int)$u['dirham'] + $amt]);

    case 'dev_mark':   // SEMENTARA: tandai level (dan semua level sebelumnya) selesai
        if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
        $un = (int)($in['unit'] ?? -1); $lv = (int)($in['level'] ?? 0); $m = unitMeta($un);
        if (!unitAyat($un) || $lv < 1 || $lv > maxLevel($m)) fail('Data tidak valid.');
        $s = db()->prepare('INSERT OR IGNORE INTO progress(user_id,course,unit,node,stars,best) VALUES(?,?,?,?,3,100)');
        for ($l = 1; $l <= $lv; $l++) for ($j = 1; $j <= 4; $j++) $s->execute([$u['id'], TAHFIDZ, $un, "V$l.$j"]);
        out(['ok' => true]);

    case 'dev_reset':  // SEMENTARA: hapus seluruh progres satu unit (termasuk chest & lompat)
        if (!DEV_MODE) fail('Mode developer nonaktif.', 403);
        db()->prepare('DELETE FROM progress WHERE user_id=? AND course=? AND unit=?')->execute([$u['id'], TAHFIDZ, (int)($in['unit'] ?? -1)]);
        out(['ok' => true]);
    }
    return false;
}

// Hook 'finish': menerapkan hasil sesi kuis Tahfidz (XP, bintang, progres, streak).
function tahfidz_finish(array $u, array $at, int $correct, int $total): array {
    return $at['kind'] === 'jump' ? applyJump($u, (int)$at['unit'], $correct, $total) : applyLesson($u, (int)$at['unit'], (int)$at['level'], (int)$at['lesson'], $correct, $total);
}

// Hook 'profile': statistik Tahfidz untuk halaman profil.
function tahfidz_profile(array $u): array {
    $pr = tProgress($u['id']); $done = $skipped = $lessons = 0;
    foreach (thumns() as $i => $t) { if (isset($pr["$i:V8.4"])) $done++; elseif (isset($pr["$i:JUMP"])) $skipped++; }
    foreach ($pr as $k => $_) if (preg_match('/:V\d+\.\d+$/', $k)) $lessons++;
    return ['thumnDone' => $done + $skipped, 'thumnSkipped' => $skipped, 'thumnTotal' => count(thumns()), 'lessons' => $lessons];
}
