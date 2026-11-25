<?php
// Registry course dan pendaftaran (enroll). Setiap folder courses/<id>/ yang berisi course.php (manifest) otomatis terdaftar.
//
// Manifest (array yang dikembalikan course.php):
//   id, name, subtitle, icon (kunci ikon SVG di klien), color, description, order,
//   status   : 'ready' (siap dipelajari) | 'draft' (terdaftar tetapi materinya belum ada)
//   boot     : berkas (relatif terhadap folder course) yang memuat kode course; dimuat hanya saat course dipakai
//   api      : fungsi ($aksi, $user, $in, $course) yang menangani aksi khusus course; memanggil out() bila aksi dikenali
//   finish   : fungsi ($user, $attempt, $benarPercobaanPertama, $totalSoal): array hasil sesi kuis (XP, bintang, dst.)
//   profile  : fungsi ($user): array statistik untuk halaman profil
function courseRegistry(): array {
    static $R;
    if ($R === null) {
        $R = [];
        foreach (glob(ROOT . '/courses/*/course.php') ?: [] as $f) {
            $c = require $f;
            if (is_array($c) && !empty($c['id'])) $R[$c['id']] = $c + ['dir' => dirname($f), 'order' => 100, 'status' => 'ready'];
        }
        uasort($R, fn($a, $b) => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);
    }
    return $R;
}
function courseGet(string $id): ?array { return courseRegistry()[$id] ?? null; }
function courseLoad(?array $c): void { if ($c && !empty($c['boot'])) require_once $c['dir'] . '/' . $c['boot']; }
function courseInfo(array $c, ?int $xp = null): array {   // data publik untuk klien
    return ['id' => $c['id'], 'name' => $c['name'], 'subtitle' => $c['subtitle'] ?? '', 'icon' => $c['icon'] ?? 'book', 'color' => $c['color'] ?? '#58cc02',
            'description' => $c['description'] ?? '', 'status' => $c['status'], 'xp' => $xp];
}
function userCourses(int $uid): array {   // course yang diikuti pengguna: [id => xp course]
    $s = db()->prepare('SELECT course, xp FROM enrollments WHERE user_id=?'); $s->execute([$uid]); $o = [];
    foreach ($s as $r) $o[$r['course']] = (int)$r['xp']; return $o;
}
function enrollUser(int $uid, string $course, bool $activate = true): void {
    db()->prepare('INSERT OR IGNORE INTO enrollments(user_id, course, xp, enrolled_at) VALUES(?,?,0,?)')->execute([$uid, $course, date('Y-m-d H:i:s')]);
    if ($activate) db()->prepare('UPDATE users SET course=? WHERE id=?')->execute([$course, $uid]);
}
function activeCourseId(array $u): string {
    $id = $u['course'] ?? DEFAULT_COURSE; return courseGet($id) ? $id : (array_key_first(courseRegistry()) ?? DEFAULT_COURSE);
}
function courseFor(array $u, ?string $requested = null): array {   // course tujuan sebuah aksi; pengguna harus sudah bergabung
    $id = $requested ?: activeCourseId($u); $c = courseGet($id) ?? fail('Kursus tidak dikenal.', 404);
    if (!isset(userCourses((int)$u['id'])[$id])) fail('Kamu belum bergabung di kursus ini.', 403);
    courseLoad($c); return $c;
}
function courseProgress(int $uid, string $course): array {   // ["unit:node" => stars]
    $s = db()->prepare('SELECT unit, node, stars FROM progress WHERE user_id=? AND course=?'); $s->execute([$uid, $course]); $m = [];
    foreach ($s as $r) $m["{$r['unit']}:{$r['node']}"] = (int)$r['stars']; return $m;
}
