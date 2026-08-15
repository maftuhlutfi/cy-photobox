<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CY Photobox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card-bg: rgba(255, 255, 255, 0.04);
            --card-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.1);
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --accent: #ec4899;
            --success: #10b981;
            --text: #f3f4f6;
            --text-dim: #9ca3af;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(139, 92, 246, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 90%, rgba(236, 72, 153, 0.12) 0%, transparent 40%);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 20px 28px;
            border-radius: 20px;
            backdrop-filter: blur(12px);
        }

        .header-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .live-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--success);
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 8px 16px;
            border-radius: 30px;
        }

        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: var(--success);
            border-radius: 50%;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
            padding: 24px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            background: var(--card-hover);
        }

        .stat-info .stat-label {
            font-size: 14px;
            color: var(--text-dim);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .stat-info .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 36px;
            font-weight: 800;
            color: #fff;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .stat-icon.taken {
            background: rgba(139, 92, 246, 0.15);
            color: #a855f7;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .stat-icon.printed {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .stat-icon.action {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-reset {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-reset:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #fff;
        }

        /* Section Title */
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Photos Grid */
        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .photo-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
        }

        .photo-card:hover {
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
        }

        .photo-preview-wrapper {
            width: 100%;
            height: 360px;
            background: #000;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-preview-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .photo-details {
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex-grow: 1;
        }

        .photo-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .session-code {
            font-family: monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
        }

        .photo-time {
            font-size: 12px;
            color: var(--text-dim);
        }

        .status-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .status-badge.ready {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge.printed {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .btn-print-card {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }

        .btn-print-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.5);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border: 1px dashed var(--border);
            border-radius: 20px;
            color: var(--text-dim);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: #111827;
            border: 1px solid var(--border);
            padding: 28px;
            border-radius: 24px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }

        .modal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .modal-desc {
            font-size: 14px;
            color: var(--text-dim);
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-confirm-reset {
            flex: 1;
            padding: 12px;
            background: #ef4444;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-title">
        <span>📸 CY PHOTOBOX ADMIN</span>
    </div>
    <div class="live-status">
        <span class="pulse-dot"></span>
        <span>Real-time Sync Active</span>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Foto Diambil</div>
            <div class="stat-value" id="stat-taken">0</div>
        </div>
        <div class="stat-icon taken">📷</div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Foto Dicetak</div>
            <div class="stat-value" id="stat-printed">0</div>
        </div>
        <div class="stat-icon printed">🖨️</div>
    </div>

    <div class="stat-card" style="flex-direction: column; align-items: stretch; justify-content: center; gap: 10px;">
        <div style="font-size: 13px; color: var(--text-dim); text-align: center;">Manajemen Statistik</div>
        <button class="btn-reset" onclick="openResetModal()">🔄 Reset Stats</button>
    </div>
</div>

<!-- Photos List Section -->
<div class="section-title">
    <span>🖼️ Daftar Foto Siap Cetak & Riwayat</span>
</div>

<div class="photos-grid" id="photos-container">
    <div class="empty-state">
        <p>Memuat data foto...</p>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal-overlay" id="reset-modal">
    <div class="modal-box">
        <div class="modal-title">Reset Statistik?</div>
        <div class="modal-desc">Apakah Anda yakin ingin mengembalikan hitungan Total Foto Diambil dan Total Foto Dicetak ke 0?</div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeResetModal()">Batal</button>
            <button class="btn-confirm-reset" onclick="confirmResetStats()">Ya, Reset</button>
        </div>
    </div>
</div>

<script>
    let currentPhotosJson = '';

    async function fetchAdminData() {
        try {
            const res = await fetch('api.php?action=get_admin_data');
            const data = await res.json();
            if (data.success) {
                // Update stats
                document.getElementById('stat-taken').textContent = data.stats.total_taken;
                document.getElementById('stat-printed').textContent = data.stats.total_printed;

                // Check if photos list changed to avoid flicker
                const jsonStr = JSON.stringify(data.photos);
                if (jsonStr !== currentPhotosJson) {
                    currentPhotosJson = jsonStr;
                    renderPhotos(data.photos);
                }
            }
        } catch (err) {
            console.error('Error fetching admin data:', err);
        }
    }

    function renderPhotos(photos) {
        const container = document.getElementById('photos-container');
        if (!photos || photos.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>📷 Belum ada foto yang diambil oleh user.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = photos.map(p => {
            const isPrinted = p.status === 'printed';
            const badgeClass = isPrinted ? 'printed' : 'ready';
            const badgeText = isPrinted ? '✓ Sudah Dicetak' : '● Siap Cetak';
            
            return `
                <div class="photo-card" id="photo-card-${p.id}">
                    <div class="photo-preview-wrapper">
                        <img src="${p.framed_url}" alt="${p.session_code}" class="photo-preview-img">
                    </div>
                    <div class="photo-details">
                        <div class="photo-meta">
                            <span class="session-code">${p.session_code}</span>
                            <span class="status-badge ${badgeClass}">${badgeText}</span>
                        </div>
                        <div class="photo-time">Waktu: ${p.created_at}</div>
                        <button class="btn-print-card" onclick="printPhoto(${p.id})">
                            🖨️ Cetak 4R
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function printPhoto(id) {
        // Mark as printed via API
        try {
            const res = await fetch('api.php?action=mark_printed', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await res.json();
            if (data.success) {
                // Update stats locally immediately
                document.getElementById('stat-taken').textContent = data.stats.total_taken;
                document.getElementById('stat-printed').textContent = data.stats.total_printed;
                fetchAdminData();
            }
        } catch (e) {
            console.error(e);
        }

        // Open print window
        window.open(`print.php?id=${id}&autoprint=1`, '_blank', 'width=800,height=900');
    }

    function openResetModal() {
        document.getElementById('reset-modal').classList.add('active');
    }

    function closeResetModal() {
        document.getElementById('reset-modal').classList.remove('active');
    }

    async function confirmResetStats() {
        closeResetModal();
        try {
            const res = await fetch('api.php?action=reset_stats', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('stat-taken').textContent = 0;
                document.getElementById('stat-printed').textContent = 0;
                fetchAdminData();
            }
        } catch (e) {
            console.error(e);
        }
    }

    // Initial fetch & Real-time Auto Refresh (polling every 2 seconds)
    fetchAdminData();
    setInterval(fetchAdminData, 2000);
</script>

</body>
</html>
