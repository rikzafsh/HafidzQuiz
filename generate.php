<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function toArabicNumber($number) {
    $arabic_digits = array('٠','١','٢','٣','٤','٥','٦','٧','٨','٩');
    $english_digits = array('0','1','2','3','4','5','6','7','8','9');
    return str_replace($english_digits, $arabic_digits, (string)$number);
}

$alm = "\u{061C}";
// Daftar harakat untuk pilihan jawaban Ikrab
$daftar_harakat = ['َ', 'ُ', 'ِ', 'ً', 'ٌ', 'ٍ', 'ْ', 'ّ']; 
// Unicode: Fathah, Dhammah, Kasrah, Fathatain, Dhammatain, Kasratain, Sukun, Tasydid

$surah_id = isset($_GET['surah_id']) ? (int)$_GET['surah_id'] : 1;
$ayah_start = isset($_GET['ayah_start']) ? (int)$_GET['ayah_start'] : 1;
$ayah_end = isset($_GET['ayah_end']) ? (int)$_GET['ayah_end'] : 7;
$jumlah_soal_diminta = isset($_GET['jumlah']) ? (int)$_GET['jumlah'] : 5;

// Menangkap jenis soal dari form index.php (secara default pakai keduanya jika tidak diset)
$jenis_soal_req = isset($_GET['type']) ? $_GET['type'] : ['sambung', 'lengkapi'];

$json_path = "data/{$surah_id}.json";
if (!file_exists($json_path)) {
    echo json_encode(['error' => "Fail {$json_path} tidak ditemukan di dalam folder 'data'."]);
    exit;
}

if (!file_exists('quran_json_parser.php')) {
    echo json_encode(['error' => "Fail quran_json_parser.php tidak ditemukan."]);
    exit;
}

require_once 'quran_json_parser.php';

try {
    $quran = new QuranJsonParser($json_path);
    $semua_ayat = $quran->data;

    $data_rentang = array_filter($semua_ayat, function($ayat) use ($ayah_start, $ayah_end) {
        return $ayat['ayah'] >= $ayah_start && $ayat['ayah'] <= $ayah_end;
    });
    $data_rentang = array_values($data_rentang);

    $max_soal = count($data_rentang);
    if ($max_soal === 0) {
        echo json_encode(['error' => "Tidak ada ayat dalam rentang yang Anda pilih."]);
        exit;
    }
    
    $jumlah_soal = min($jumlah_soal_diminta, $max_soal); 
    $soal_generated = [];
    $ayat_untuk_soal = $data_rentang;

    for ($i = 0; $i < $jumlah_soal; $i++) {
        $randomIndex = array_rand($ayat_untuk_soal);
        $ayatTerpilih = $ayat_untuk_soal[$randomIndex];
        unset($ayat_untuk_soal[$randomIndex]); 
        $ayat_untuk_soal = array_values($ayat_untuk_soal); 

        // Acak jenis soal berdasarkan pilihan di form
        $tipe_soal = $jenis_soal_req[array_rand($jenis_soal_req)];
        $ayat_lengkap_dengan_nomor = $ayatTerpilih['arabic'] . " " . toArabicNumber($ayatTerpilih['ayah']) . $alm;

        // ==========================================
        // LOGIKA 1: LENGKAPI AYAT (DRAG & DROP / CLICK)
        // ==========================================
        $words = [];
        if (isset($ayatTerpilih['arabic_words']) && is_array($ayatTerpilih['arabic_words'])) {
            $words = $ayatTerpilih['arabic_words'];
        } else {
            $words = explode(' ', $ayatTerpilih['arabic']);
        }

        // FILTER: Hanya ambil kata yang memiliki minimal 2 huruf dasar Arab (Abaikan waqaf/harakat)
        $valid_indices = [];
        foreach ($words as $idx => $word) {
            // Regex ini menghitung huruf Arab dari Hamzah (\x{0621}) sampai Ya (\x{064A}) + Alif Wasla (\x{0671})
            $base_letters = preg_match_all('/[\x{0621}-\x{064A}\x{0671}]/u', $word);
            if ($base_letters >= 2) {
                $valid_indices[] = $idx;
            }
        }

        // Pastikan ada minimal 2 kata yang valid untuk dijadikan soal
        if ($tipe_soal === 'lengkapi' && count($valid_indices) >= 2) {
            
            // Hilangkan 1 sampai 3 kata secara acak HANYA dari indeks yang valid
            $num_blanks = rand(1, min(3, count($valid_indices) - 1));
            if ($num_blanks < 1) $num_blanks = 1;

            $blank_keys = array_rand($valid_indices, $num_blanks);
            if (!is_array($blank_keys)) {
                $blank_keys = [$blank_keys];
            }
            
            $blank_indices = [];
            foreach ($blank_keys as $k) {
                $blank_indices[] = $valid_indices[$k];
            }
            sort($blank_indices); 

            $teks_soal_words = $words;
            $jawaban_benar_urutan = [];
            
            // Ganti kata terpilih dengan garis bawah (rumpang)
            foreach ($blank_indices as $idx) {
                $jawaban_benar_urutan[] = $words[$idx];
                $teks_soal_words[$idx] = '___';
            }

            // Cari Pengecoh dari ayat lain di Surah ini
            $distractors = [];
            $limit = 0;
            while(count($distractors) < 3 && $limit < 50) {
                $limit++;
                $randAyat = $semua_ayat[array_rand($semua_ayat)];
                
                $randWordsArray = [];
                if (isset($randAyat['arabic_words']) && is_array($randAyat['arabic_words'])) {
                    $randWordsArray = $randAyat['arabic_words'];
                } else {
                    $randWordsArray = explode(' ', $randAyat['arabic']);
                }

                if(!empty($randWordsArray)) {
                    $randWord = $randWordsArray[array_rand($randWordsArray)];
                    
                    // Pastikan kata pengecoh juga bukan sekadar tanda waqaf
                    $base_letters_distractor = preg_match_all('/[\x{0621}-\x{064A}\x{0671}]/u', $randWord);
                    
                    if($base_letters_distractor >= 2 && !in_array($randWord, $jawaban_benar_urutan) && !in_array($randWord, $distractors)) {
                        $distractors[] = $randWord;
                    }
                }
            }

            $pilihan = array_merge($jawaban_benar_urutan, $distractors);
            shuffle($pilihan);

            $soal_generated[] = [
                'tipe' => 'lengkapi_ayat',
                'surah' => $ayatTerpilih['surah']['transliteration'],
                'teks_soal_words' => $teks_soal_words, 
                'jawaban_benar_urutan' => $jawaban_benar_urutan, 
                'pilihan' => $pilihan,
                'ayat_lengkap' => $ayat_lengkap_dengan_nomor
            ];
            continue; 
        }
        // ==========================================
        // LOGIKA: MENEBAK AYAT SEBELUMNYA
        // ==========================================
        if ($tipe_soal === 'sebelum') {
            $ayat_index = -1;
            foreach ($semua_ayat as $idx => $ayat) {
                if ($ayat['ayah'] == $ayatTerpilih['ayah']) {
                    $ayat_index = $idx; break;
                }
            }

            // Pastikan ini BUKAN ayat pertama di surah (karena tidak ada ayat sebelumnya)
            if ($ayat_index > 0) { 
                $correct_ayah = $semua_ayat[$ayat_index - 1];
                
                $teks_soal = "<div style='text-align:center; margin-bottom:10px; font-size: 14px; color: #555;'>Perhatikan ayat berikut:</div>";
                $teks_soal .= "<div class='ayat-box' dir='rtl' style='font-size:24px;'>" . $ayatTerpilih['arabic'] . "</div>";
                $teks_soal .= "<div style='text-align:center; margin-top:15px; font-weight:bold; color:var(--primary); font-size: 16px;'>Manakah ayat sebelumnya?</div>";
                
                $jawaban_benar = $correct_ayah['arabic'];

                // Cari Pengecoh
                $distractors = [];
                $limit = 0;
                while(count($distractors) < 3 && $limit < 50) {
                    $limit++;
                    $rand = $semua_ayat[array_rand($semua_ayat)];
                    $rand_text = $rand['arabic'];
                    // Syarat pengecoh: BUKAN jawaban benar, BUKAN ayat pertanyaan, dan belum masuk list
                    if ($rand['ayah'] != $correct_ayah['ayah'] && $rand['ayah'] != $ayatTerpilih['ayah'] && !in_array($rand_text, $distractors)) {
                        $distractors[] = $rand_text;
                    }
                }

                $pilihan = array_merge([$jawaban_benar], $distractors);
                shuffle($pilihan);

                $soal_generated[] = [
                    'tipe' => 'tebak_ayat', 
                    'teks_soal' => $teks_soal,
                    'jawaban_benar' => $jawaban_benar,
                    'pilihan' => $pilihan,
                    // Menggunakan fungsi toArabicNumber dan menghilangkan tanda kurung
                    'ayat_lengkap' => $jawaban_benar . " " . toArabicNumber($correct_ayah['ayah']) 
                ];
                continue;
            } else {
                // Jika jatuh di ayat 1, ganti jadi soal sambung ayat saja
                $tipe_soal = 'sambung';
            }
        }

        // ==========================================
        // LOGIKA: MENEBAK AYAT SESUDAHNYA
        // ==========================================
        if ($tipe_soal === 'sesudah') {
            $ayat_index = -1;
            foreach ($semua_ayat as $idx => $ayat) {
                if ($ayat['ayah'] == $ayatTerpilih['ayah']) {
                    $ayat_index = $idx; break;
                }
            }

            // Pastikan ini BUKAN ayat terakhir di surah
            if ($ayat_index < count($semua_ayat) - 1) { 
                $correct_ayah = $semua_ayat[$ayat_index + 1];
                
                $teks_soal = "<div style='text-align:center; margin-bottom:10px; font-size: 14px; color: #555;'>Perhatikan ayat berikut:</div>";
                $teks_soal .= "<div class='ayat-box' dir='rtl' style='font-size:24px;'>" . $ayatTerpilih['arabic'] . "</div>";
                $teks_soal .= "<div style='text-align:center; margin-top:15px; font-weight:bold; color:var(--primary); font-size: 16px;'>Manakah ayat sesudahnya?</div>";
                
                $jawaban_benar = $correct_ayah['arabic'];

                $distractors = [];
                $limit = 0;
                while(count($distractors) < 3 && $limit < 50) {
                    $limit++;
                    $rand = $semua_ayat[array_rand($semua_ayat)];
                    $rand_text = $rand['arabic'];
                    if ($rand['ayah'] != $correct_ayah['ayah'] && $rand['ayah'] != $ayatTerpilih['ayah'] && !in_array($rand_text, $distractors)) {
                        $distractors[] = $rand_text;
                    }
                }

                $pilihan = array_merge([$jawaban_benar], $distractors);
                shuffle($pilihan);

                $soal_generated[] = [
                    'tipe' => 'tebak_ayat', 
                    'teks_soal' => $teks_soal,
                    'jawaban_benar' => $jawaban_benar,
                    'pilihan' => $pilihan,
                    // Menggunakan fungsi toArabicNumber dan menghilangkan tanda kurung
                    'ayat_lengkap' => $jawaban_benar . " " . toArabicNumber($correct_ayah['ayah']) 
                ];
                continue;
            } else {
                $tipe_soal = 'sambung';
            }
        }
        
        // ==========================================
        // LOGIKA 5: TEBAK NOMOR AYAT / LAFAZ
        // ==========================================
        if ($tipe_soal === 'nomor') {
            $sub_tipe = rand(0, 1); 

            if ($sub_tipe === 0) {
                // --- SUB 1: Ada Ayat, Tebak Nomor ---
                $teks_soal = "<div style='text-align:center; margin-bottom:10px; font-size: 14px; color: #555;'>Perhatikan ayat berikut:</div>";
                $teks_soal .= "<div class='ayat-box' dir='rtl' style='font-size:24px;'>" . $ayatTerpilih['arabic'] . "</div>";
                $teks_soal .= "<div style='text-align:center; margin-top:15px; font-weight:bold; color:var(--primary); font-size: 16px;'>Ayat nomor berapakah ini?</div>";

                $jawaban_benar = (string)$ayatTerpilih['ayah'];
                $pilihan = [$jawaban_benar];

                // Ambil pengecoh HANYA dari $data_rentang
                $temp_rentang = $data_rentang;
                shuffle($temp_rentang);
                foreach ($temp_rentang as $p) {
                    if (count($pilihan) >= 4) break;
                    $num = (string)$p['ayah'];
                    if (!in_array($num, $pilihan)) $pilihan[] = $num;
                }
            } else {
                // --- SUB 2: Ada Nomor, Tebak Lafaz ---
                $teks_soal = "<div style='text-align:center; margin-bottom:10px; font-size: 14px; color: #555;'>Surah " . $ayatTerpilih['surah']['transliteration'] . "</div>";
                $teks_soal .= "<div style='text-align:center; margin-top:10px; font-weight:bold; color:var(--primary); font-size: 20px;'>Manakah bunyi ayat ke-" . $ayatTerpilih['ayah'] . "?</div>";

                $jawaban_benar = $ayatTerpilih['arabic'];
                $pilihan = [$jawaban_benar];

                // Ambil pengecoh HANYA dari $data_rentang
                $temp_rentang = $data_rentang;
                shuffle($temp_rentang);
                foreach ($temp_rentang as $p) {
                    if (count($pilihan) >= 4) break;
                    if (!in_array($p['arabic'], $pilihan)) $pilihan[] = $p['arabic'];
                }
            }

            shuffle($pilihan);
            $soal_generated[] = [
                'tipe' => 'tebak_ayat',
                'teks_soal' => $teks_soal,
                'jawaban_benar' => $jawaban_benar,
                'pilihan' => $pilihan,
                'ayat_lengkap' => $ayatTerpilih['arabic'] . " " . toArabicNumber($ayatTerpilih['ayah'])
            ];
            continue;
        }
        // ==========================================
        // LOGIKA 6: IKRAB (TEBAK HARAKAT AKHIR) - VERSI PERBAIKAN
        // ==========================================
        if ($tipe_soal === 'ikrab') {
            $fullText = $ayatTerpilih['arabic'];
            // Pisahkan berdasarkan spasi, tapi filter agar tidak ada elemen kosong
            $words = array_values(array_filter(explode(' ', $fullText), 'trim'));
            
            $waqaf_pattern = '/[ۖۗۘۙۚۛۜۮࣖ]/u';
            $posisi_valid = [];
            
            // Cari index kata yang benar-benar memiliki harakat di bagian akhirnya
            foreach ($words as $index => $w) {
                // Kita cari harakat terakhir sebelum karakter non-huruf (seperti waqaf) atau di akhir kata
                if (preg_match('/([ًٌٍَُِّْٰٖٓٗ])[ۖۗۘۙۚۛۜۮࣖ]*$/u', $w)) {
                    $posisi_valid[] = $index;
                }
            }

            if (empty($posisi_valid)) {
                // Jika tidak ketemu (sangat jarang), ambil kata terakhir saja
                $selectedIndex = count($words) - 1;
            } else {
                // Terapkan peluang: 2/3 ambil dari akhir ayat/sebelum waqaf
                $posisi_akhir = [];
                $posisi_tengah = [];
                foreach ($posisi_valid as $idx) {
                    if (preg_match($waqaf_pattern, $words[$idx]) || $idx === count($words) - 1) {
                        $posisi_akhir[] = $idx;
                    } else {
                        $posisi_tengah[] = $idx;
                    }
                }

                $roll = rand(1, 3);
                if ($roll === 1 && !empty($posisi_tengah)) {
                    $selectedIndex = $posisi_tengah[array_rand($posisi_tengah)];
                } else {
                    $selectedIndex = !empty($posisi_akhir) ? $posisi_akhir[array_rand($posisi_akhir)] : $posisi_valid[array_rand($posisi_valid)];
                }
            }

            $wordToProcess = $words[$selectedIndex];

            // Ambil harakat terakhir (mengabaikan tanda waqaf di belakangnya)
            if (preg_match('/([ًٌٍَُِّْٰٖٓٗ])([ۖۗۘۙۚۛۜۮࣖ]*)$/u', $wordToProcess, $matches)) {
                $jawaban_benar = $matches[1];
                $waqaf_after = $matches[2];
                // Hilangkan harakat tersebut, ganti dengan placeholder bulat kecil agar posisi tidak geser
                $wordDisplay = preg_replace('/[ًٌٍَُِّْٰٖٓٗ]([ۖۗۘۙۚۛۜۮࣖ]*)$/u', '◌' . $waqaf_after, $wordToProcess);
            } else {
                $jawaban_benar = 'َ';
                $wordDisplay = $wordToProcess;
            }

            $displayWords = $words;
            // Menggunakan warna (Royal Blue) dan cetak tebal, tanpa garis bawah
            $displayWords[$selectedIndex] = "<span style='color:#3d46e0; font-family:\"LPMQ IsepMisbah\", serif;'>$wordDisplay</span>";
            $teks_soal_ikrab = implode(' ', $displayWords);

            $pilihan = [$jawaban_benar];
            $temp_harakat = ['َ', 'ُ', 'ِ', 'ً', 'ٌ', 'ٍ', 'ْ', 'ّ']; 
            shuffle($temp_harakat);
            foreach ($temp_harakat as $h) {
                if (count($pilihan) >= 4) break;
                if ($h !== $jawaban_benar) $pilihan[] = $h;
            }
            shuffle($pilihan);

            $soal_generated[] = [
                'tipe' => 'ikrab', // Beri tipe spesifik agar script.js bisa membedakan layout
                'teks_soal' => "<div style='text-align:center; font-size:14px; color:#666; margin-bottom:15px;'>Pilih harakat akhir yang tepat untuk kata yang berwarna biru:</div>
                                <div class='ayat-box' dir='rtl' style='font-size:28px; line-height:2.5;'>$teks_soal_ikrab</div>",
                'jawaban_benar' => $jawaban_benar,
                'pilihan' => $pilihan,
                'ayat_lengkap' => $fullText . " " . toArabicNumber($ayatTerpilih['ayah'])
            ];
            continue;
        }

        // ==========================================
        // LOGIKA 2: SAMBUNG AYAT (PILIHAN GANDA)
        // ==========================================
        $potongan = $quran->splitAyahForQuestion($ayatTerpilih);
        $jawaban_benar = $potongan['jawaban'];
        $pilihan = [$jawaban_benar];
        
        $teks_soal_fix = $potongan['soal'] . $alm;

        $target_length = mb_strlen($jawaban_benar);
        $min_len = $target_length * 0.4; 
        $max_len = $target_length * 1.8; 

        // FASE 1: Ambil dari rentang ayat & panjang yang mirip
        $limit_loop = 0;
        while (count($pilihan) < 4 && $limit_loop < 150) {
            $limit_loop++;
            $randomSalahIndex = array_rand($data_rentang);
            
            if ($data_rentang[$randomSalahIndex]['id'] !== $ayatTerpilih['id']) {
                $ayatSalah = $data_rentang[$randomSalahIndex];
                $potonganSalah = $quran->splitAyahForQuestion($ayatSalah);
                $jawabanSalah = $potonganSalah['jawaban'];
                $salah_length = mb_strlen($jawabanSalah);
                
                if (!in_array($jawabanSalah, $pilihan) && trim($jawabanSalah) !== "" && $salah_length >= $min_len && $salah_length <= $max_len) {
                    $pilihan[] = $jawabanSalah;
                }
            }
        }

        // FASE 2: Fallback
        if (count($pilihan) < 4) {
            foreach ($data_rentang as $ayatSalah) {
                if (count($pilihan) >= 4) break;
                if ($ayatSalah['id'] == $ayatTerpilih['id']) continue;
                
                $potonganSalah = $quran->splitAyahForQuestion($ayatSalah);
                $jawabanSalah = $potonganSalah['jawaban'];
                if (!in_array($jawabanSalah, $pilihan) && trim($jawabanSalah) !== "") {
                    $pilihan[] = $jawabanSalah;
                }
            }
        }

        // FASE 3: Fallback Darurat
        if (count($pilihan) < 4) {
            foreach ($data_rentang as $ayatSalah) {
                if (count($pilihan) >= 4) break;
                if ($ayatSalah['id'] == $ayatTerpilih['id']) continue;
                
                $potonganSalah = $quran->splitAyahForQuestion($ayatSalah);
                $jawabanSalah = $potonganSalah['jawaban'];
                if (!in_array($jawabanSalah, $pilihan) && trim($jawabanSalah) !== "") {
                    $pilihan[] = $jawabanSalah;
                }
            }
        }
        
        shuffle($pilihan);
        
        $soal_generated[] = [
            'tipe' => 'sambung_ayat',
            'surah' => $ayatTerpilih['surah']['transliteration'],
            'teks_soal' => $teks_soal_fix,
            'pilihan' => $pilihan,
            'jawaban_benar' => $jawaban_benar,
            'ayat_lengkap' => $ayat_lengkap_dengan_nomor
        ];
    }

    echo json_encode($soal_generated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => "Terjadi kesalahan sistem: " . $e->getMessage()]);
}
?>