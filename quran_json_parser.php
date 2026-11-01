<?php
/**
 * Parser data Al-Qur'an (data/{surah}.json) + pemotong ayat untuk soal "sambung ayat".
 * Kompatibel dengan versi lama: new QuranJsonParser($path), ->data, ->getWord(), ->splitAyahForQuestion().
 */
class QuranJsonParser {
    const LETTER = '\x{0621}-\x{064A}\x{0671}';  // huruf dasar Arab (hamzah..ya + alif wasla)
    const WAQF   = '\x{06D6}-\x{06DC}';          // tanda waqaf ۖ ۗ ۘ ۙ ۚ ۛ ۜ

    public $data = [];
    private static $cache = [];

    public function __construct($json_filename) {
        $this->data = self::read($json_filename);
    }

    // File dibaca & di-decode sekali per request, berapa pun kali dipanggil
    public static function read($file) {
        if (!isset(self::$cache[$file])) {
            if (!is_file($file)) throw new Exception("File {$file} tidak ditemukan.");
            $d = json_decode(file_get_contents($file), true);
            if (!is_array($d)) throw new Exception("File {$file} bukan JSON yang valid.");
            self::$cache[$file] = $d;
        }
        return self::$cache[$file];
    }

    // Daftar kata ayat; jika arabic_words null, pecah berdasarkan spasi
    public static function words(array $ayah) {
        return (isset($ayah['arabic_words']) && is_array($ayah['arabic_words']))
            ? $ayah['arabic_words'] : explode(' ', $ayah['arabic']);
    }

    // "Kata sungguhan": minimal 2 huruf dasar (mengabaikan token waqaf/penanda seperti ەۙ atau ࣖ)
    public static function isRealWord($w) {
        return preg_match_all('/[' . self::LETTER . ']/u', $w) >= 2;
    }

    public function getWord($ayahData, $wordIndex) {
        $words = self::words($ayahData);
        return $words[$wordIndex - 1] ?? null;
    }

    /**
     * Potong daftar kata menjadi [soal, jawaban].
     * Utamakan titik waqaf yang paling dekat dengan tengah ayat (dan tidak terlalu di pinggir);
     * jika tidak ada, potong di kata sungguhan terdekat dari tengah. null jika tidak bisa dipotong.
     * $randomize: pilih acak di antara 2 kandidat terbaik (untuk soal); false = selalu yang terbaik (untuk pengecoh).
     */
    public static function splitWords(array $words, $randomize = false) {
        $n = count($words);
        if ($n < 2) return null;
        $real = array_map([self::class, 'isRealWord'], $words);
        $minSide = max(1, (int)floor($n * 0.2));
        $all = $waqaf = [];
        for ($j = 1; $j < $n; $j++) {
            if (!$real[$j] || !in_array(true, array_slice($real, 0, $j), true)) continue; // tiap sisi harus punya kata
            $all[] = $j;
            if (preg_match('/[' . self::WAQF . ']\s*$/u', $words[$j - 1]) && min($j, $n - $j) >= $minSide) $waqaf[] = $j;
        }
        $cands = $waqaf ?: $all;
        if (!$cands) return null;
        $mid = $n / 2;
        usort($cands, function ($a, $b) use ($mid) { return abs($a - $mid) <=> abs($b - $mid); });
        $j = $randomize ? $cands[array_rand(array_slice($cands, 0, 2))] : $cands[0];
        return [implode(' ', array_slice($words, 0, $j)), implode(' ', array_slice($words, $j))];
    }

    public function splitAyahForQuestion($ayahData, $randomize = false) {
        $words = self::words($ayahData);
        if ($p = self::splitWords($words, $randomize)) return ['soal' => $p[0], 'jawaban' => $p[1]];
        // Fallback (ayat sangat pendek): potong di tengah berdasarkan jumlah kata
        if (!$words) return ['soal' => $ayahData['arabic'], 'jawaban' => ''];
        $m = (int)floor(count($words) / 2);
        return ['soal' => implode(' ', array_slice($words, 0, $m)), 'jawaban' => implode(' ', array_slice($words, $m))];
    }
}
