<?php
// admin/chat_api.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
require_once 'includes/config.php';
require_once '../includes/db.php';

// Ensure Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_sessions') {
    // Fetch all active sessions, sorting unanswered first
    $stmt = $pdo->query("
        SELECT 
            c1.session_id, 
            MAX(c1.created_at) as last_msg, 
            COUNT(*) as msg_count,
            (SELECT is_admin FROM chat_messages c2 WHERE c2.session_id = c1.session_id ORDER BY created_at DESC LIMIT 1) as last_is_admin
        FROM chat_messages c1 
        GROUP BY c1.session_id 
        ORDER BY last_is_admin ASC, last_msg DESC
    ");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'sessions' => $sessions]);
    exit;
}

$sessionId = $_GET['session'] ?? '';

if ($sessionId) {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$sessionId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output to prevent XSS
    foreach ($messages as &$msg) {
        $msg['message'] = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
    }
    unset($msg);

    echo json_encode(['status' => 'success', 'messages' => $messages]);
} else {
    echo json_encode(['status' => 'success', 'messages' => []]);
}
?>
