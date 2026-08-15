<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

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
    <title>Cetak Foto 4R - CY Photobox</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: #111;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            font-size: 15px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-print {
            background: #8b5cf6;
            color: white;
        }

        .btn-close {
            background: #374151;
            color: white;
        }

        .print-container {
            width: 4in;
            height: 6in;
            background: #000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            position: relative;
            overflow: hidden;
        }

        .print-image {
            width: 100%;
            height: 100%;
            object-fit: fill;
            display: block;
        }

        /* Standard 4R Photo Paper Print CSS */
        @page {
            size: 4in 6in;
            margin: 0;
        }

        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                width: 100vw;
                height: 100vh;
                box-shadow: none;
            }
            .print-image {
                width: 100%;
                height: 100%;
                object-fit: fill;
            }
        }
    </style>
</head>
<body>

<?php if ($photo && !empty($framedUrl)): ?>
    <div class="controls no-print">
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak Sekarang (4R)</button>
        <button onclick="window.close()" class="btn btn-close">Tutup</button>
    </div>

    <div class="print-container">
        <img src="<?= $framedUrl ?>" alt="Cetak 4R" class="print-image">
    </div>

    <?php if ($autoprint): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        </script>
    <?php endif; ?>
<?php else: ?>
    <div style="padding: 40px; text-align: center;">
        <h2>⚠️ Foto tidak ditemukan</h2>
        <p style="margin-top: 10px; color: #9ca3af;">Silakan pilih foto lain di halaman Admin.</p>
    </div>
<?php endif; ?>

</body>
</html>
