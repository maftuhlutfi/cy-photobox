<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CY Photobox - Booth Photo Studio</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- App Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header & 3-Minute Session Timer -->
    <header class="kiosk-header">
        <div class="kiosk-brand">
            <span>📸 CY PHOTOBOX</span>
        </div>
        <div class="session-timer-container">
            <span class="timer-label">Sisa Waktu Sesi:</span>
            <span class="timer-value" id="session-timer">03:00</span>
        </div>
    </header>

    <main class="kiosk-main">
        
        <!-- STEP 1: START SCREEN -->
        <section class="step-screen active" id="screen-start">
            <div class="start-box">
                <h1 class="start-title">Abadikan Momen Serumu!</h1>
                <p class="start-subtitle">
                    Ambil 3 pose foto terbaikmu dalam sesi 3 menit, pilih frame favorit, dan cetak foto langsung!
                </p>
                <button class="btn-start" onclick="startSession()">
                    📸 MULAI FOTO
                </button>
            </div>
        </section>

        <!-- STEP 2: CAMERA CAPTURE SCREEN -->
        <section class="step-screen" id="screen-capture">
            <div class="capture-layout">
                <div class="camera-container">
                    <video id="webcam" autoplay playsinline></video>
                    
                    <div class="photo-counter-badge" id="photo-counter-badge">Foto 1 dari 3</div>

                    <!-- Flash overlay animation -->
                    <div class="flash-overlay" id="flash-overlay"></div>

                    <!-- 5-Second Countdown Overlay -->
                    <div class="countdown-overlay" id="countdown-overlay" style="display: none;">
                        <div class="countdown-number" id="countdown-number">5</div>
                    </div>
                </div>

                <!-- Live Thumbnails -->
                <div class="thumbnails-strip">
                    <div class="thumb-slot" id="thumb-1">Foto 1</div>
                    <div class="thumb-slot" id="thumb-2">Foto 2</div>
                    <div class="thumb-slot" id="thumb-3">Foto 3</div>
                </div>
            </div>
        </section>

        <!-- STEP 3: PREVIEW & FRAME SELECTOR SCREEN -->
        <section class="step-screen" id="screen-editor">
            <div class="editor-layout">
                <!-- Frame Preview Canvas -->
                <div class="frame-preview-box">
                    <canvas id="canvas-preview"></canvas>
                </div>

                <!-- Sidebar Controls -->
                <div class="sidebar-controls">
                    <div class="control-card">
                        <div class="control-title">
                            <span>🎨 Pilih Frame Foto</span>
                        </div>
                        <div class="frames-list" id="frames-list">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button class="btn-confirm" onclick="confirmAndFinish()">
                            ✨ Simpan & Siap Cetak
                        </button>
                        <button class="btn-retake" id="btn-retake" onclick="retakePhotos()">
                            🔄 Foto Ulang
                        </button>
                        <button class="btn-reset-session" onclick="resetToStart()">
                            🗑️ Hapus Foto & Reset Sesi
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- STEP 4: READY TO PRINT & QR CODE SCREEN -->
        <section class="step-screen" id="screen-ready">
            <div class="ready-box">
                <div class="ready-left">
                    <img src="" id="final-preview-img" class="final-preview-img" alt="Final Preview">
                </div>
                <div class="ready-right">
                    <h2 class="ready-title">🎉 Foto Siap Cetak!</h2>
                    <p class="ready-desc">
                        Foto kamu telah dikirim ke antrean cetak admin.<br>
                        Scan QR code berikut untuk download foto langsung ke HP kamu.
                    </p>

                    <div class="qr-card">
                        <div id="qrcode-container"></div>
                    </div>

                    <button class="btn-finish" onclick="resetToStart()">
                        ✨ Selesai / Foto Lagi
                    </button>
                </div>
            </div>
        </section>

    </main>

    <!-- App JavaScript -->
    <script src="app.js"></script>
</body>
</html>
