<?php
// Pengguna: sesi, streak, dan pemberian XP (global dan per course).
function me() {
    if (empty($_SESSION['uid'])) return null;
    $s = db()->prepare('SELECT id, username, xp, streak, last_day, dirham, streak_peak, dirham_total, course FROM users WHERE id=?'); $s->execute([$_SESSION['uid']]);
    return $s->fetch() ?: null;
}
function need() { return me() ?? fail('Silakan masuk dahulu.', 401); }
function effStreak($u): int {   // streak yang tampil: hangus (0) bila kemarin dan hari ini belum belajar
    $d = $u['last_day'] ?? null; return ($d === date('Y-m-d') || $d === date('Y-m-d', strtotime('-1 day'))) ? (int)$u['streak'] : 0;
}
function pub($u) {
    $es = effStreak($u); $dr = (int)($u['dirham'] ?? 0); $mine = userCourses((int)$u['id']); $courses = [];
    foreach (courseRegistry() as $id => $c) if (isset($mine[$id])) $courses[] = courseInfo($c, $mine[$id]);
    return ['name' => $u['username'], 'xp' => (int)$u['xp'], 'streak' => $es, 'dirham' => $dr, 'dev' => DEV_MODE, 'admin' => isAdmin($u),
            'streakPeak' => max((int)($u['streak_peak'] ?? 0), $es), 'dirhamTotal' => max((int)($u['dirham_total'] ?? 0), $dr),   // dasar pencapaian
            'course' => activeCourseId($u), 'courses' => $courses];
}
function touchStreak($u): int {
    $today = date('Y-m-d'); $streak = (int)$u['streak'];
    if ($u['last_day'] !== $today) $streak = $u['last_day'] === date('Y-m-d', strtotime('-1 day')) ? $streak + 1 : 1;
    return $streak;
}

// Memberi XP: menambah XP global dan XP course, memperbarui streak (global) beserta rekor tertingginya.
function award(array $u, string $course, int $gain): array {
    $streak = touchStreak($u); $today = date('Y-m-d');
    db()->prepare('UPDATE users SET xp=xp+?, streak=?, last_day=?, streak_peak=MAX(streak_peak, CAST(? AS INTEGER)) WHERE id=?')->execute([$gain, $streak, $today, $streak, $u['id']]);   // CAST: PDO mengikat parameter sebagai teks
    db()->prepare('UPDATE enrollments SET xp=xp+?, last_active=? WHERE user_id=? AND course=?')->execute([$gain, $today, $u['id'], $course]);
    $s = db()->prepare('SELECT xp FROM enrollments WHERE user_id=? AND course=?'); $s->execute([$u['id'], $course]);
    return ['streak' => $streak, 'xp' => (int)$u['xp'] + $gain, 'courseXp' => (int)$s->fetchColumn()];
}
