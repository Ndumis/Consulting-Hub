<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$uid = (int)$_SESSION['user_id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $action ?: ($body['action'] ?? '');

    if ($action === 'mark_all_read') {
        $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
        echo json_encode(['ok' => true]);
        exit();
    }

    if ($action === 'mark_read' && isset($body['id'])) {
        $db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([(int)$body['id'], $uid]);
        echo json_encode(['ok' => true]);
        exit();
    }
}

// GET: return count + recent list
$count = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$count->execute([$uid]);
$unread = (int)$count->fetchColumn();

$stmt = $db->prepare("SELECT id, type, title, message, link, is_read, created_at
    FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 12");
$stmt->execute([$uid]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format timestamps
foreach ($items as &$item) {
    $item['time_ago'] = time_ago_str($item['created_at']);
    $item['is_read']  = (bool)$item['is_read'];
}
unset($item);

echo json_encode(['count' => $unread, 'items' => $items]);

function time_ago_str($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return round($diff/60).'m ago';
    if ($diff < 86400)  return round($diff/3600).'h ago';
    if ($diff < 604800) return round($diff/86400).'d ago';
    return date('d M Y', strtotime($dt));
}
