<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = get_db();

    if ($action === 'upload_session') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $session_code = 'PB' . date('YmdHis') . rand(100, 999);
        $framed_b64 = $input['framed_image'] ?? '';
        $photo1_b64 = $input['photo1'] ?? '';
        $photo2_b64 = $input['photo2'] ?? '';
        $photo3_b64 = $input['photo3'] ?? '';
        $frame_id   = $input['frame_id'] ?? 'frame 1.svg';

        if (empty($framed_b64)) {
            echo json_encode(['success' => false, 'message' => 'Framed image is required']);
            exit;
        }

        // Helper to save base64 image
        $save_b64 = function($b64, $filepath) {
            if (empty($b64)) return '';
            $data = preg_replace('#^data:image/\w+;base64,#i', '', $b64);
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                file_put_contents($filepath, $decoded);
                return basename($filepath);
            }
            return '';
        };

        $framed_filename = 'framed_' . $session_code . '.png';
        $save_b64($framed_b64, UPLOAD_FRAMED_DIR . $framed_filename);

        $p1_file = $save_b64($photo1_b64, UPLOAD_RAW_DIR . 'photo1_' . $session_code . '.png');
        $p2_file = $save_b64($photo2_b64, UPLOAD_RAW_DIR . 'photo2_' . $session_code . '.png');
        $p3_file = $save_b64($photo3_b64, UPLOAD_RAW_DIR . 'photo3_' . $session_code . '.png');

        // Insert database record
        $stmt = $db->prepare("INSERT INTO photos (session_code, photo1, photo2, photo3, framed_image, frame_id, status) VALUES (?, ?, ?, ?, ?, ?, 'ready')");
        $stmt->execute([$session_code, $p1_file, $p2_file, $p3_file, $framed_filename, $frame_id]);
        $photo_id = $db->lastInsertId();

        // Increment stats total_taken
        $db->exec("UPDATE stats SET total_taken = total_taken + 1 WHERE id = 1");

        $baseUrl = get_base_url();
        $downloadUrl = $baseUrl . '/download.php?id=' . $photo_id;
        $framedImageUrl = $baseUrl . '/uploads/framed/' . $framed_filename;

        echo json_encode([
            'success' => true,
            'id' => $photo_id,
            'session_code' => $session_code,
            'framed_image_url' => $framedImageUrl,
            'download_url' => $downloadUrl
        ]);
        exit;
    }

    if ($action === 'get_admin_data') {
        // Fetch stats
        $stats = $db->query("SELECT total_taken, total_printed FROM stats WHERE id = 1")->fetch();

        // Fetch all photo sessions
        $stmt = $db->query("SELECT id, session_code, framed_image, frame_id, status, created_at FROM photos ORDER BY id DESC LIMIT 100");
        $photos = $stmt->fetchAll();

        $baseUrl = get_base_url();
        foreach ($photos as &$p) {
            $p['framed_url'] = $baseUrl . '/uploads/framed/' . $p['framed_image'];
            $p['download_url'] = $baseUrl . '/download.php?id=' . $p['id'];
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_taken' => (int)($stats['total_taken'] ?? 0),
                'total_printed' => (int)($stats['total_printed'] ?? 0)
            ],
            'photos' => $photos
        ]);
        exit;
    }

    if ($action === 'mark_printed') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE photos SET status = 'printed' WHERE id = ?");
            $stmt->execute([$id]);

            $db->exec("UPDATE stats SET total_printed = total_printed + 1 WHERE id = 1");
        }

        $stats = $db->query("SELECT total_taken, total_printed FROM stats WHERE id = 1")->fetch();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_taken' => (int)($stats['total_taken'] ?? 0),
                'total_printed' => (int)($stats['total_printed'] ?? 0)
            ]
        ]);
        exit;
    }

    if ($action === 'reset_stats') {
        $db->exec("UPDATE stats SET total_taken = 0, total_printed = 0 WHERE id = 1");

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_taken' => 0,
                'total_printed' => 0
            ]
        ]);
        exit;
    }

    if ($action === 'delete_all_photos') {
        // Delete photo records from database
        $db->exec("DELETE FROM photos");
        try {
            $db->exec("DELETE FROM sqlite_sequence WHERE name = 'photos'");
        } catch (Exception $e) {}

        // Reset stats
        $db->exec("UPDATE stats SET total_taken = 0, total_printed = 0 WHERE id = 1");

        // Helper function to remove files in directory
        $emptyDir = function($dir) {
            if (!is_dir($dir)) return;
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === '.gitignore') continue;
                $filePath = $dir . '/' . $file;
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        };

        $emptyDir(UPLOAD_RAW_DIR);
        $emptyDir(UPLOAD_FRAMED_DIR);

        echo json_encode([
            'success' => true,
            'message' => 'Semua foto berhasil dihapus dan statistik di-reset.',
            'stats' => [
                'total_taken' => 0,
                'total_printed' => 0
            ]
        ]);
        exit;
    }

    if ($action === 'delete_photo') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("SELECT photo1, photo2, photo3, framed_image FROM photos WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch();

            if ($photo) {
                if (!empty($photo['framed_image'])) @unlink(UPLOAD_FRAMED_DIR . $photo['framed_image']);
                if (!empty($photo['photo1'])) @unlink(UPLOAD_RAW_DIR . $photo['photo1']);
                if (!empty($photo['photo2'])) @unlink(UPLOAD_RAW_DIR . $photo['photo2']);
                if (!empty($photo['photo3'])) @unlink(UPLOAD_RAW_DIR . $photo['photo3']);

                $delStmt = $db->prepare("DELETE FROM photos WHERE id = ?");
                $delStmt->execute([$id]);
            }
        }

        $stats = $db->query("SELECT total_taken, total_printed FROM stats WHERE id = 1")->fetch();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_taken' => (int)($stats['total_taken'] ?? 0),
                'total_printed' => (int)($stats['total_printed'] ?? 0)
            ]
        ]);
        exit;
    }

    if ($action === 'list_frames') {
        $frameDir = __DIR__ . '/frame/';
        $frames = [];
        if (is_dir($frameDir)) {
            $files = scandir($frameDir);
            foreach ($files as $f) {
                if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'svg') {
                    $frames[] = [
                        'id' => $f,
                        'name' => pathinfo($f, PATHINFO_FILENAME),
                        'url' => 'frame/' . rawurlencode($f),
                        'content' => file_get_contents($frameDir . $f)
                    ];
                }
            }
        }
        echo json_encode(['success' => true, 'frames' => $frames]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
