<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HafidzQuiz</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" id="quiz-app">
        <div id="quiz-header">
            <h2 id="quiz-title">HafidzQuiz</h2>
            <p style="text-align:center">Soal <span id="current-index">1</span> dari <span id="total-count">0</span></p>
        </div>

        <div id="question-area" class="question-box">Memuat soal...</div>
        
        <div id="option-area" class="option-container">
            </div>

        <div id="feedback" class="hidden"></div>

        <div id="result-screen" class="hidden">
            <h2 style="color:var(--purple); margin-bottom: 30px;">Hasil Akhir</h2>
            
            <div class="result-grid">
                <div class="result-card">
                    <div class="card-label">Jawaban Benar</div>
                    <div class="card-value"><span id="res-correct">0</span> / <span id="res-total">0</span></div>
                </div>
                <div class="result-card primary">
                    <div class="card-label">Skor Akhir</div>
                    <div class="card-value"><span id="res-score">0</span>%</div>
                </div>
                <div class="result-card">
                    <div class="card-label">Total Waktu</div>
                    <div class="card-value" id="res-time">0</div>
                </div>
                <div class="result-card">
                    <div class="card-label">Rata-rata / Soal</div>
                    <div class="card-value"><span id="res-avg">0</span>s</div>
                </div>
            </div>

            <button onclick="window.location.href='index.php'" class="btn-primary" style="margin-top: 30px; width: 100%;">Muroja'ah Lagi</button>
        </div>

        <button id="next-btn" class="btn-primary" style="margin-top:20px; display:none">Soal Berikutnya</button>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>