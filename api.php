<?php
// Pintu masuk API. Aksi platform (akun, kursus, sesi kuis, profil) ditangani di sini; aksi khusus course diteruskan ke modul course
// lewat manifest-nya (courses/<id>/course.php). Aksi khusus course menerima parameter ?course=<id> (bawaan: kursus aktif pengguna).
ini_set('display_errors', '0');   // jangan pernah mencemari JSON dengan pesan error
ob_start();
require __DIR__ . '/src/bootstrap.php';

$a = $_GET['a'] ?? '';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

function coursesPayload(array $u): array {   // kursus yang diikuti, kursus yang tersedia untuk ditambahkan, dan kursus aktif
    $mine = userCourses((int)$u['id']); $m = []; $av = [];
    foreach (courseRegistry() as $id => $c) { if (isset($mine[$id])) $m[] = courseInfo($c, $mine[$id]); else $av[] = courseInfo($c); }
    return ['current' => activeCourseId($u), 'mine' => $m, 'available' => $av];
}

try { switch ($a) {
case 'register':
    $n = trim($in['username'] ?? ''); $p = $in['password'] ?? '';
    if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $n)) fail('Nama pengguna 3–30 karakter (huruf, angka, _ . -).');
    if (strlen($p) < 6) fail('Kata sandi minimal 6 karakter.');
    $s = db()->prepare('INSERT INTO users(username, hash, created_at) VALUES(?,?,?)');
    try { $s->execute([$n, password_hash($p, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]); } catch (PDOException $e) { fail('Nama pengguna sudah dipakai.'); }
    session_regenerate_id(true); $_SESSION['uid'] = (int)db()->lastInsertId(); enrollUser($_SESSION['uid'], DEFAULT_COURSE);   // pengguna baru otomatis mengikuti kursus bawaan
    out(['user' => pub(me())]);
case 'login':
    $s = db()->prepare('SELECT * FROM users WHERE username=?'); $s->execute([trim($in['username'] ?? '')]); $u = $s->fetch();
    if (!$u || !password_verify($in['password'] ?? '', $u['hash'])) fail('Nama pengguna atau kata sandi salah.', 401);
    session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; out(['user' => pub($u)]);
case 'logout': session_destroy(); out(['ok' => true]);
case 'me': out(['user' => pub(need())]);

case 'courses':
    $u = need(); out(coursesPayload($u));

case 'course_enroll':   // bergabung ke kursus yang belum pernah diikuti; kursus itu langsung menjadi kursus aktif
    $u = need(); $id = (string)($in['course'] ?? ''); courseGet($id) ?? fail('Kursus tidak dikenal.', 404);
    if (isset(userCourses((int)$u['id'])[$id])) fail('Kamu sudah bergabung di kursus ini.', 409);
    enrollUser((int)$u['id'], $id); $u = me(); out(['user' => pub($u)] + coursesPayload($u));

case 'course_switch':   // pindah ke kursus lain yang sudah diikuti
    $u = need(); $id = (string)($in['course'] ?? ''); courseGet($id) ?? fail('Kursus tidak dikenal.', 404);
    if (!isset(userCourses((int)$u['id'])[$id])) fail('Kamu belum bergabung di kursus ini.', 403);
    db()->prepare('UPDATE users SET course=? WHERE id=?')->execute([$id, $u['id']]); $u = me(); out(['user' => pub($u)] + coursesPayload($u));

case 'check':    out(quiz_check(need(), $in));
case 'finish':   out(quiz_finish(need(), $in));
case 'feedback': out(quiz_feedback(need(), $in));

case 'profile':   // statistik global + statistik tiap kursus yang diikuti (dari hook 'profile' course)
    $u = need(); $mine = userCourses((int)$u['id']); $courses = [];
    foreach (courseRegistry() as $id => $c) if (isset($mine[$id])) {
        courseLoad($c); $courses[] = courseInfo($c, $mine[$id]) + ['stats' => (!empty($c['profile']) && function_exists($c['profile'])) ? ($c['profile'])($u) : []];
    }
    $s = db()->prepare('SELECT created_at FROM users WHERE id=?'); $s->execute([$u['id']]);
    out(['user' => pub($u), 'joined' => $s->fetchColumn() ?: null, 'courses' => $courses]);

default:   // aksi khusus course
    $u = need(); $c = courseFor($u, $_GET['course'] ?? null);
    if (!empty($c['api']) && function_exists($c['api'])) ($c['api'])($a, $u, $in, $c);   // memanggil out() bila aksi dikenali
    fail('Aksi tidak dikenal.', 404);
} } catch (Throwable $e) { fail('Kesalahan server: ' . $e->getMessage(), 500); }
