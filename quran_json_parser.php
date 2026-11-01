<?php
class QuranJsonParser {
    public $data = [];

    public function __construct($json_filename) {
        $json_string = file_get_contents($json_filename);
        $this->data = json_decode($json_string, true);
    }

    public function getWord($ayahData, $wordIndex) {
        // Cek apakah arabic_words berupa array atau null
        if (isset($ayahData['arabic_words']) && is_array($ayahData['arabic_words'])) {
            $words = $ayahData['arabic_words'];
        } else {
            // Jika null, pecah manual berdasarkan spasi
            $words = explode(' ', $ayahData['arabic']);
        }
        return isset($words[$wordIndex - 1]) ? $words[$wordIndex - 1] : null;
    }

    public function splitAyahForQuestion($ayahData) {
        $fullText = $ayahData['arabic']; // Teks utuh
        
        // Amankan variabel $words jika nilainya null dari JSON
        if (isset($ayahData['arabic_words']) && is_array($ayahData['arabic_words'])) {
            $words = $ayahData['arabic_words'];
        } else {
            // Fallback: pecah teks ayat berdasarkan spasi
            $words = explode(' ', $fullText);
        }
        
        // Pola regex untuk tanda waqaf
        $pattern = '/([ۖۗۘۙۚۛۜۮࣖ])/u';

        // Coba potong berdasarkan tanda waqaf
        $parts = preg_split($pattern, $fullText, 2, PREG_SPLIT_DELIM_CAPTURE);
        
        if (count($parts) > 2 && trim($parts[2]) !== '') {
            $soal = trim($parts[0] . $parts[1]); 
            $jawaban = trim($parts[2]);          
        } else {
            // Logika Fallback: Potong di tengah berdasarkan jumlah kata
            $totalWords = count($words);
            
            // Jaga-jaga jika ayat kosong
            if ($totalWords === 0) {
                return ['soal' => $fullText, 'jawaban' => ''];
            }

            $middle = floor($totalWords / 2);
            
            $soal = implode(' ', array_slice($words, 0, $middle));
            $jawaban = implode(' ', array_slice($words, $middle));
        }

        return ['soal' => $soal, 'jawaban' => $jawaban];
    }
}
?>