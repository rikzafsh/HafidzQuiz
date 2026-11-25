<?php
// Manifest course Tahfidz (hafalan Al-Qur'an). Lihat src/courses.php untuk arti tiap kunci.
return [
    'id' => 'tahfidz', 'name' => 'Tahfidz', 'subtitle' => "Hafalan Al-Qur'an", 'icon' => 'mushaf', 'color' => '#2f9e6b', 'order' => 1, 'status' => 'ready',
    'description' => "Hafalkan Al-Qur'an per thumn: level bertahap, ulasan hizb dan juz, serta mushaf.",
    'boot' => 'boot.php', 'api' => 'tahfidz_api', 'finish' => 'tahfidz_finish', 'profile' => 'tahfidz_profile',
];
