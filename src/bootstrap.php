<?php
// Bootstrap platform HafidzQuiz: zona waktu, sesi, konfigurasi, dan pembantu JSON.
// Dimuat oleh api.php, admin.php, dan semua alat (courses/*/tools). Kode khusus course dimuat lewat registry (src/courses.php).
date_default_timezone_set('Asia/Jakarta');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
define('ROOT', dirname(__DIR__));

// ===== MODE DEVELOPER SEMENTARA: ubah ke false (atau hapus) setelah pengetesan selesai =====
const DEV_MODE = true;
// Nama pengguna yang boleh membuka admin.php (laporan masukan dari pengguna). Daftar dulu akunnya di aplikasi.
const ADMIN_USERS = ['admin'];
function isAdmin($u): bool { return in_array($u['username'] ?? '', ADMIN_USERS, true); }
const DEFAULT_COURSE = 'tahfidz';   // kursus yang otomatis diikuti pengguna baru

function out($d, $code = 200) { while (ob_get_level()) ob_end_clean(); http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
function fail($m, $c = 400) { out(['error' => $m], $c); }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/courses.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/quiz.php';
