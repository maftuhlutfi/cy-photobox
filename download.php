<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$photo = null;

if ($id > 0) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM photos WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();
}

$framedUrl = $photo ? get_base_url() . '/uploads/framed/' . htmlspecialchars($photo['framed_image']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Foto - CY Photobox</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: rgba(255, 255, 255, 0.05);
            --border: rgba(255, 255, 255, 0.12);
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --accent: #ec4899;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.15) 0%, transparent 40%);
        }

        .container {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .brand {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-dim);
            margin-bottom: 24px;
        }

        .preview-wrapper {
            position: relative;
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: #000;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
        }

        .preview-img {
            width: 100%;
            height: auto;
            display: block;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            border-radius: 16px;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(139, 92, 246, 0.6);
        }

        .btn-download svg {
            width: 20px;
            height: 20px;
        }

        .not-found {
            padding: 40px 20px;
            color: var(--text-dim);
        }

        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="brand">📸 CY PHOTOBOX</div>
    <div class="subtitle">Simpan Kenangan Foto Kamu</div>

    <?php if ($photo && !empty($framedUrl)): ?>
        <div class="preview-wrapper">
            <img src="<?= $framedUrl ?>" alt="Foto Photobox" class="preview-img">
        </div>

        <a href="<?= $framedUrl ?>" download="CY-Photobox-<?= htmlspecialchars($photo['session_code']) ?>.png" class="btn-download">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Download Foto HD
        </a>
    <?php else: ?>
        <div class="not-found">
            <p>⚠️ Foto tidak ditemukan atau telah dihapus.</p>
        </div>
    <?php endif; ?>

    <div class="footer">
        © <?= date('Y') ?> CY Photobox • Powered by PHP
    </div>
</div>

</body>
</html>
