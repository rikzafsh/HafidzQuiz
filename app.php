<?php
// Halaman utama (shell HTML). Skrip klien dimuat berurutan; skrip course (assets/js/courses/*.js) ditemukan otomatis.
// Nomor versi = waktu ubah berkas, sehingga browser tidak memakai berkas lama dari cache.
$v = fn($f) => $f . '?v=' . filemtime(__DIR__ . '/' . $f);
$scripts = array_merge(['assets/js/core.js', 'assets/js/icons.js', 'assets/js/quiz.js', 'assets/js/shell.js'],
    array_map(fn($f) => 'assets/js/courses/' . basename($f), glob(__DIR__ . '/assets/js/courses/*.js') ?: []), ['assets/js/boot.js']);
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>HafidzQuiz</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Scheherazade+New:wght@400;700&display=swap">
<link rel="stylesheet" href="<?= $v('assets/css/app.css') ?>">
</head>
<body>
<div id="app"></div>
<?php foreach ($scripts as $s): ?><script src="<?= $v($s) ?>"></script>
<?php endforeach; ?></body>
</html>
