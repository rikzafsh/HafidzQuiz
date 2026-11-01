<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HafidzQuiz</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body>
    <div class="container">
        <h2>HafidzQuiz</h2>
        <form action="quiz.php" method="GET">
            
            <div class="form-group">
                <label>Pilih Surah:</label>
                <select name="surah_id" id="surah-select" required>
                    <option value="" disabled selected>Memuat Surah...</option>
                    </select>
            </div>
            
            <div class="form-group" style="display: flex; gap: 15px;">
                <div style="flex: 1;">
                    <label>Ayat Awal:</label>
                    <select name="ayah_start" id="ayah-start" required>
                        <option value="">-</option>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Ayat Akhir:</label>
                    <select name="ayah_end" id="ayah-end" required>
                        <option value="">-</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Jumlah Soal:</label>
                <input type="number" name="jumlah" value="5" min="1" max="50" required>
            </div>

            <div style="margin-bottom: 15px;">
            <label style="font-weight: bold; color: var(--primary);">Jenis Soal:</label><br>
            <input type="checkbox" name="type[]" value="sambung" checked> Sambung Ayat<br>
            <input type="checkbox" name="type[]" value="lengkapi"> Lengkapi Ayat<br>
            <input type="checkbox" name="type[]" value="sebelum"> Tebak Ayat Sebelumnya<br>
            <input type="checkbox" name="type[]" value="sesudah"> Tebak Ayat Sesudahnya<br>
            <input type="checkbox" name="type[]" value="nomor"> Tebak Nomor Ayat / Lafaz<br>
            <input type="checkbox" name="type[]" value="ikrab"> Ikrab (Harakat Akhir)<br>
        </div>

            <div class="form-group">
                <label>Mode Soal:</label>
                <input type="radio" name="mode" value="direct" checked> Tampilkan Benar/Salah Langsung
            </div>

            <button type="submit" class="btn-primary">Mulai Kuis</button>
        </form>
    </div>

    <script>
        const surahList = [
            "Al-Fatihah", "Al-Baqarah", "Ali 'Imran", "An-Nisa'", "Al-Ma'idah", "Al-An'am", "Al-A'raf", "Al-Anfal", "At-Taubah", "Yunus", "Hud", "Yusuf", "Ar-Ra'd", "Ibrahim", "Al-Hijr", "An-Nahl", "Al-Isra'", "Al-Kahf", "Maryam", "Taha", "Al-Anbiya'", "Al-Hajj", "Al-Mu'minun", "An-Nur", "Al-Furqan", "Asy-Syu'ara'", "An-Naml", "Al-Qasas", "Al-'Ankabut", "Ar-Rum", "Luqman", "As-Sajdah", "Al-Ahzab", "Saba'", "Fatir", "Yasin", "As-Saffat", "Sad", "Az-Zumar", "Ghafir", "Fussilat", "Asy-Syura", "Az-Zukhruf", "Ad-Dukhan", "Al-Jasiyah", "Al-Ahqaf", "Muhammad", "Al-Fath", "Al-Hujurat", "Qaf", "Az-Zariyat", "At-Tur", "An-Najm", "Al-Qamar", "Ar-Rahman", "Al-Waqi'ah", "Al-Hadid", "Al-Mujadalah", "Al-Hasyr", "Al-Mumtahanah", "As-Saff", "Al-Jumu'ah", "Al-Munafiqun", "At-Tagabun", "At-Talaq", "At-Tahrim", "Al-Mulk", "Al-Qalam", "Al-Haqqah", "Al-Ma'arij", "Nuh", "Al-Jinn", "Al-Muzzammil", "Al-Muddassir", "Al-Qiyamah", "Al-Insan", "Al-Mursalat", "An-Naba'", "An-Nazi'at", "'Abasa", "At-Takwir", "Al-Infitar", "Al-Mutaffifin", "Al-Insyiqaq", "Al-Buruj", "At-Tariq", "Al-A'la", "Al-Gasyiyah", "Al-Fajr", "Al-Balad", "Asy-Syams", "Al-Lail", "Ad-Duha", "Asy-Syarh", "At-Tin", "Al-'Alaq", "Al-Qadr", "Al-Bayyinah", "Az-Zalzalah", "Al-'Adiyat", "Al-Qari'ah", "At-Takasur", "Al-'Asr", "Al-Humazah", "Al-Fil", "Quraisy", "Al-Ma'un", "Al-Kausar", "Al-Kafirun", "An-Nasr", "Al-Lahab", "Al-Ikhlas", "Al-Falaq", "An-Nas"
        ];

        const surahSelect = document.getElementById('surah-select');
        const ayahStartSelect = document.getElementById('ayah-start');
        const ayahEndSelect = document.getElementById('ayah-end');
        const jumlahInput = document.querySelector('input[name="jumlah"]');
        const formElement = document.querySelector('form');

        surahSelect.innerHTML = '<option value="" disabled selected>Pilih Surah</option>';
        surahList.forEach((surah, index) => {
            let option = document.createElement('option');
            option.value = index + 1;
            option.text = `${index + 1}. ${surah}`;
            surahSelect.appendChild(option);
        });

        async function loadAyahDropdowns(surahId) {
            ayahStartSelect.innerHTML = '<option value="">Memuat...</option>';
            ayahEndSelect.innerHTML = '<option value="">Memuat...</option>';

            try {
                const response = await fetch(`data/${surahId}.json`);
                if (!response.ok) throw new Error('File tidak ditemukan');
                const data = await response.json();
                
                const totalAyah = data.length;
                populateAyahDropdowns(totalAyah);
                return totalAyah;
            } catch (error) {
                console.error(`Gagal memuat data Surah ${surahId}.`);
                return 0;
            }
        }

        function populateAyahDropdowns(totalAyah) {
            ayahStartSelect.innerHTML = '';
            ayahEndSelect.innerHTML = '';
            for (let i = 1; i <= totalAyah; i++) {
                ayahStartSelect.innerHTML += `<option value="${i}">${i}</option>`;
                ayahEndSelect.innerHTML += `<option value="${i}">${i}</option>`;
            }
            ayahEndSelect.value = totalAyah;
        }

        surahSelect.addEventListener('change', function() {
            loadAyahDropdowns(this.value);
        });

        ayahStartSelect.addEventListener('change', function() {
            if (parseInt(this.value) > parseInt(ayahEndSelect.value)) ayahEndSelect.value = this.value;
        });
        ayahEndSelect.addEventListener('change', function() {
            if (parseInt(this.value) < parseInt(ayahStartSelect.value)) ayahStartSelect.value = this.value;
        });

        // 1. Simpan ke LocalStorage saat form dikirim
        formElement.addEventListener('submit', function() {
            localStorage.setItem('tahfiz_surah_id', surahSelect.value);
            localStorage.setItem('tahfiz_ayah_start', ayahStartSelect.value);
            localStorage.setItem('tahfiz_ayah_end', ayahEndSelect.value);
            localStorage.setItem('tahfiz_jumlah', jumlahInput.value);
            
            // Simpan status checkbox
            const selectedTypes = Array.from(document.querySelectorAll('input[name="type[]"]:checked')).map(cb => cb.value);
            localStorage.setItem('tahfiz_types', JSON.stringify(selectedTypes));
        });

        // 2. Pulihkan data saat halaman dimuat
        async function restoreFormData() {
            const savedSurah = localStorage.getItem('tahfiz_surah_id');
            const savedJumlah = localStorage.getItem('tahfiz_jumlah');
            const savedTypesStr = localStorage.getItem('tahfiz_types');

            if (savedJumlah) jumlahInput.value = savedJumlah;

            // Pulihkan status checkbox
            if (savedTypesStr) {
                const savedTypes = JSON.parse(savedTypesStr);
                document.querySelectorAll('input[name="type[]"]').forEach(cb => {
                    cb.checked = savedTypes.includes(cb.value);
                });
            }

            if (savedSurah) {
                surahSelect.value = savedSurah;
                await loadAyahDropdowns(savedSurah);
                
                const savedAyahStart = localStorage.getItem('tahfiz_ayah_start');
                const savedAyahEnd = localStorage.getItem('tahfiz_ayah_end');
                if (savedAyahStart) ayahStartSelect.value = savedAyahStart;
                if (savedAyahEnd) ayahEndSelect.value = savedAyahEnd;
            }
        }

        restoreFormData();
    </script>
</body>
</html>