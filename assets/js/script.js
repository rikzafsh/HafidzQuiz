let quizStartTime;      // Waktu mulai kuis keseluruhan
let questionStartTime;  // Waktu mulai tiap soal
let totalCorrect = 0;   // Penghitung jawaban benar
let quizData = [];
let currentIndex = 0;
let isAnswered = false;

document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    
    if (!params.has('surah_id')) {
        window.location.href = 'index.php';
        return;
    }

    try {
        const response = await fetch(`generate.php?${params.toString()}`);
        const textResponse = await response.text(); 
        
        try {
            quizData = JSON.parse(textResponse);
        } catch (err) {
            console.error("Respons Server Asli:", textResponse);
            throw new Error("Respons dari server rusak. Pastikan struktur folder dan file .json sudah benar.");
        }

        if (quizData.error) {
            throw new Error(quizData.error);
        }

        if (quizData.length === 0) {
            throw new Error("Rentang ayat terlalu pendek atau tidak ditemukan.");
        }

        document.getElementById('total-count').innerText = quizData.length;
        quizStartTime = Date.now();
        renderQuestion();
        
    } catch (e) {
        document.getElementById('question-area').innerHTML = `<span style="color:var(--danger); font-size:18px;">Error: ${e.message}</span>`;
        console.error(e);
    }
});

// FUNGSI UTAMA RENDER SOAL
function renderQuestion() {
    const soal = quizData[currentIndex];
    isAnswered = false;
    questionStartTime = Date.now();
    
    document.getElementById('current-index').innerText = currentIndex + 1;
    const questionArea = document.getElementById('question-area');
    const optionArea = document.getElementById('option-area');
    const feedback = document.getElementById('feedback');
    const nextBtn = document.getElementById('next-btn');

    questionArea.innerHTML = '';
    optionArea.innerHTML = '';
    feedback.innerHTML = '';
    feedback.classList.add('hidden');
    nextBtn.style.display = "none";

    if (soal.tipe === 'ikrab') {
        optionArea.style.display = "flex";
        optionArea.style.flexDirection = "row";
        optionArea.style.flexWrap = "wrap";
        optionArea.style.justifyContent = "center";
        optionArea.style.gap = "10px";
    } else {
        // Kembalikan ke layout standar (vertikal) untuk jenis soal lain
        optionArea.style.display = "block"; 
    }

    if (soal.tipe === 'sambung_ayat' || soal.tipe === 'tebak_ayat' || soal.tipe === 'ikrab') {
        questionArea.style.direction = (soal.tipe === 'sambung_ayat') ? "rtl" : "ltr";
        questionArea.innerHTML = soal.teks_soal;

        soal.pilihan.forEach(txt => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.innerHTML = txt;

            // Styling Khusus Pilihan Harakat (Ikrab)
            if (soal.tipe === 'ikrab') {
                btn.style.width = "75px"; 
                btn.style.height = "85px";
                btn.style.fontSize = "55px"; // Sedikit lebih besar
                btn.style.fontFamily = "'LPMQ IsepMisbah', serif";
                
                // PENGATURAN AGAR HARAKAT LEBIH TINGGI
                btn.style.display = "flex";
                btn.style.alignItems = "flex-start"; // Paksa ke atas kotak
                btn.style.justifyContent = "center";
                btn.style.paddingTop = "0px";        // Hapus ruang atas
                btn.style.paddingBottom = "30px";     // Dorong kuat dari bawah ke atas
                
                btn.style.lineHeight = "1";
                btn.style.margin = "0"; 
            } 
            else if (!isNaN(txt)) { // Tebak Nomor
                btn.style.fontSize = "22px";
            }

            btn.onclick = () => checkAnswerSambung(btn, txt, soal.jawaban_benar, soal.ayat_lengkap);
            optionArea.appendChild(btn);
        });
    }
    // 2. RENDER JIKA SOAL LENGKAPI AYAT (DRAG & DROP / CLICK)
    else if (soal.tipe === 'lengkapi_ayat') {
        let html = '<div class="ayah-words-container" dir="rtl">';
        let blankCount = 0;
        
        soal.teks_soal_words.forEach((word) => {
            if (word === '___') {
                html += `<span class="drop-zone" data-expected-index="${blankCount}" ondragover="allowDrop(event)" ondrop="drop(event)"></span>`;
                blankCount++;
            } else {
                html += `<span class="word-static">${word}</span>`;
            }
        });
        html += '</div>';
        questionArea.innerHTML = html;

        let optHtml = '<div id="words-pool" ondragover="allowDrop(event)" ondrop="drop(event)">';
        soal.pilihan.forEach((opt, i) => {
            // Tambahkan event onclick="moveWord(this)" agar bisa diklik
            optHtml += `<span class="draggable-word" draggable="true" ondragstart="drag(event)" id="drag${i}" data-word="${opt}" onclick="moveWord(this)">${opt}</span>`;
        });
        optHtml += '</div>';
        optHtml += `<button id="btn-cek" class="btn-primary" style="margin-top:20px;" onclick="checkLengkapiAyat()">Cek Jawaban</button>`;
        
        optionArea.innerHTML = optHtml;
    }
}

// ==========================================
// FITUR BARU: PINDAH KATA DENGAN KLIK
// ==========================================
function moveWord(element) {
    if (isAnswered) return; // Nonaktifkan klik jika sudah dijawab

    const currentParent = element.parentElement;

    // Jika kata ada di penampungan bawah, pindahkan ke tempat kosong pertama
    if (currentParent.id === 'words-pool') {
        const dropZones = document.querySelectorAll('.drop-zone');
        for (let zone of dropZones) {
            if (!zone.hasChildNodes()) {
                zone.appendChild(element);
                break; // Hentikan pencarian setelah menemukan tempat kosong
            }
        }
    } 
    // Jika kata sudah ada di area rumpang, kembalikan ke penampungan bawah
    else if (currentParent.classList.contains('drop-zone')) {
        document.getElementById('words-pool').appendChild(element);
    }
}

// PENGECEKAN UNTUK SAMBUNG AYAT
function checkAnswerSambung(btn, selected, correct, ayatLengkap) {
    if (isAnswered) return;
    isAnswered = true;

    const feedback = document.getElementById('feedback');
    feedback.classList.remove('hidden');
    const ayatHtml = `<div class="ayat-box">${ayatLengkap}</div>`;

    if (selected === correct) {
        totalCorrect++;
        btn.classList.add('correct');
        feedback.innerHTML = `<span style="color: var(--success); font-weight: bold; font-size: 18px;">Masyaallah, jawaban Anda benar!</span> ${ayatHtml}`;
    } else {
        btn.classList.add('wrong');
        feedback.innerHTML = `<span style="color: var(--danger); font-weight: bold; font-size: 18px;">Jawaban salah. Berikut ayat lengkapnya:</span> ${ayatHtml}`;
        
        document.querySelectorAll('.option-btn').forEach(b => {
            if (b.innerText === correct) b.classList.add('correct');
        });
    }
    showNextButton();
}

// PENGECEKAN UNTUK LENGKAPI AYAT 
// PENGECEKAN UNTUK LENGKAPI AYAT 
function checkLengkapiAyat() {
    if (isAnswered) return;
    
    const soal = quizData[currentIndex];
    const dropZones = document.querySelectorAll('.drop-zone');
    let isAllCorrect = true;
    let isAllFilled = true;

    dropZones.forEach((zone, index) => {
        if (!zone.hasChildNodes()) {
            isAllFilled = false;
        } else {
            let answerWord = zone.firstChild.getAttribute('data-word');
            let expectedWord = soal.jawaban_benar_urutan[index];
            if (answerWord !== expectedWord) isAllCorrect = false;
        }
    });

    // Peringatan jika belum penuh
    if (!isAllFilled) {
        alert("Harap isi semua bagian yang kosong terlebih dahulu!");
        return; // Return DI SINI agar isAnswered tetap false dan user bisa melanjutkan
    }

    // Kunci soal HANYA JIKA semua kotak sudah terisi
    isAnswered = true; 
    document.getElementById('btn-cek').style.display = 'none'; 

    const feedback = document.getElementById('feedback');
    feedback.classList.remove('hidden'); // PERBAIKAN: Munculkan elemen feedback yang disembunyikan
    
    const ayatHtml = `<div class="ayat-box">${soal.ayat_lengkap}</div>`;

    if (isAllCorrect) {
        totalCorrect++; // PERBAIKAN: Tambahkan nilai skor ke hasil akhir
        feedback.innerHTML = `<span style="color: var(--success); font-weight: bold; font-size: 18px;">Masyaallah, jawaban Anda benar!</span> ${ayatHtml}`;
    } else {
        feedback.innerHTML = `<span style="color: var(--danger); font-weight: bold; font-size: 18px;">Ada urutan kata yang keliru. Berikut ayat yang benar:</span> ${ayatHtml}`;
    }
    
    showNextButton();
}

function showNextButton() {
    const nextBtn = document.getElementById('next-btn');
    const isLastQuestion = currentIndex === quizData.length - 1;

    if (!isLastQuestion) {
        nextBtn.style.display = "block";
        nextBtn.innerText = "Soal Berikutnya";
        nextBtn.onclick = () => {
            currentIndex++;
            renderQuestion();
        };
    } else {
        // Tampilkan tombol Lihat Hasil di soal terakhir
        nextBtn.style.display = "block";
        nextBtn.innerText = "Lihat Hasil Akhir";
        nextBtn.onclick = () => showFinalResult();
    }
}

function showFinalResult() {
    const totalTimeMs = Date.now() - quizStartTime;
    const totalSeconds = Math.floor(totalTimeMs / 1000);
    const avgSeconds = (totalSeconds / quizData.length).toFixed(1);
    const scorePercent = Math.round((totalCorrect / quizData.length) * 100);

    // Sembunyikan elemen utama
    document.getElementById('quiz-header').classList.add('hidden');
    document.getElementById('question-area').classList.add('hidden');
    document.getElementById('option-area').classList.add('hidden');
    document.getElementById('feedback').classList.add('hidden');
    document.getElementById('next-btn').style.display = "none";

    // Masukkan data
    document.getElementById('res-correct').innerText = totalCorrect;
    document.getElementById('res-total').innerText = quizData.length;
    document.getElementById('res-score').innerText = scorePercent;
    document.getElementById('res-avg').innerText = avgSeconds;
    
    // Format waktu
    const min = Math.floor(totalSeconds / 60);
    const sec = totalSeconds % 60;
    document.getElementById('res-time').innerText = min > 0 ? `${min}m ${sec}s` : `${sec}s`;

    // Tampilkan layar hasil
    const resultScreen = document.getElementById('result-screen');
    resultScreen.classList.remove('hidden');
    resultScreen.style.animation = "fadeIn 0.5s ease-out";
}

// KUMPULAN FUNGSI DRAG AND DROP HTML5
function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.id);
}

function drop(ev) {
    ev.preventDefault();
    if (isAnswered) return; // Blokir drag jika soal sudah dicek

    var data = ev.dataTransfer.getData("text");
    var draggedEl = document.getElementById(data);
    var target = ev.target;

    if (target.classList.contains('draggable-word') && target.parentElement.classList.contains('drop-zone')) {
        document.getElementById('words-pool').appendChild(target);
        target.parentElement.appendChild(draggedEl);
        return;
    }

    if (target.classList.contains('drop-zone')) {
        if (target.hasChildNodes()) {
            document.getElementById('words-pool').appendChild(target.firstChild);
        }
        target.appendChild(draggedEl);
    } 
    else if (target.id === 'words-pool' || target.closest('#words-pool')) {
        document.getElementById('words-pool').appendChild(draggedEl);
    }
}