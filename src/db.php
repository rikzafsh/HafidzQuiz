<?php
// Koneksi SQLite, skema, dan migrasi (aman dijalankan berulang; database versi lama dimigrasi otomatis).
function db(): PDO {
    static $p;
    if (!$p) {
        $dir = ROOT . '/storage';
        if (!is_dir($dir)) { mkdir($dir, 0750, true); file_put_contents("$dir/.htaccess", "Require all denied\n"); }
        $p = new PDO('sqlite:' . $dir . '/hafidz.sqlite');
        $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $p->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        migrateDb($p);
    }
    return $p;
}

function migrateDb(PDO $p): void {
    $p->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY, username TEXT UNIQUE NOT NULL, hash TEXT NOT NULL, xp INTEGER DEFAULT 0, streak INTEGER DEFAULT 0, last_day TEXT, dirham INTEGER DEFAULT 0, created_at TEXT, streak_peak INTEGER DEFAULT 0, dirham_total INTEGER DEFAULT 0, course TEXT DEFAULT 'tahfidz');
              CREATE TABLE IF NOT EXISTS enrollments(user_id INTEGER, course TEXT, xp INTEGER DEFAULT 0, enrolled_at TEXT, last_active TEXT, PRIMARY KEY(user_id, course));
              CREATE TABLE IF NOT EXISTS attempts(id TEXT PRIMARY KEY, user_id INTEGER, course TEXT DEFAULT 'tahfidz', kind TEXT, unit INTEGER, level INTEGER, lesson INTEGER, qjson TEXT, sjson TEXT, hearts INTEGER, started INTEGER, finished INTEGER DEFAULT 0);
              CREATE TABLE IF NOT EXISTS feedback(id INTEGER PRIMARY KEY, user_id INTEGER, course TEXT DEFAULT 'tahfidz', unit INTEGER, level INTEGER, lesson INTEGER, kind TEXT, reason TEXT, note TEXT, qkind TEXT, qjson TEXT, ua TEXT, correct INTEGER, created_at TEXT, resolved INTEGER DEFAULT 0);");
    $cols = fn($t) => array_column($p->query("PRAGMA table_info($t)")->fetchAll(), 'name');

    // --- users: kolom yang ditambahkan seiring versi ---
    $uc = $cols('users');
    foreach (['dirham' => 'INTEGER DEFAULT 0', 'created_at' => 'TEXT'] as $c => $def) if (!in_array($c, $uc, true)) $p->exec("ALTER TABLE users ADD COLUMN $c $def");
    // nilai tertinggi untuk pencapaian: streak terpanjang dan total dirham yang pernah didapat
    foreach (['streak_peak' => 'streak', 'dirham_total' => 'dirham'] as $c => $src)
        if (!in_array($c, $uc, true)) { $p->exec("ALTER TABLE users ADD COLUMN $c INTEGER DEFAULT 0"); $p->exec("UPDATE users SET $c = $src"); }
    // multi-course: semua pengguna lama otomatis mengikuti Tahfidz; XP lama menjadi XP Tahfidz
    if (!in_array('course', $uc, true)) {
        $p->exec("ALTER TABLE users ADD COLUMN course TEXT DEFAULT 'tahfidz'");
        $p->exec("INSERT OR IGNORE INTO enrollments(user_id, course, xp, enrolled_at) SELECT id, 'tahfidz', xp, COALESCE(created_at, date('now')) FROM users");
    }
    // --- attempts & feedback: kolom course ---
    foreach (['attempts', 'feedback'] as $t) if (!in_array('course', $cols($t), true)) $p->exec("ALTER TABLE $t ADD COLUMN course TEXT DEFAULT 'tahfidz'");

    // --- progress: kini per course ("unit" = ID unit di dalam course; skema lama memakai kolom "thumn" tanpa course) ---
    $create = fn() => $p->exec("CREATE TABLE progress(user_id INTEGER, course TEXT NOT NULL DEFAULT 'tahfidz', unit INTEGER, node TEXT, stars INTEGER, best INTEGER, PRIMARY KEY(user_id, course, unit, node))");
    if (!$p->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='progress'")->fetchColumn()) $create();
    elseif (!in_array('course', $cols('progress'), true)) {
        $p->exec('BEGIN');
        $p->exec('ALTER TABLE progress RENAME TO progress_old');
        $create();
        $p->exec("INSERT INTO progress(user_id, course, unit, node, stars, best) SELECT user_id, 'tahfidz', thumn, node, stars, best FROM progress_old");
        $p->exec('DROP TABLE progress_old');
        $p->exec('COMMIT');
    }
}
