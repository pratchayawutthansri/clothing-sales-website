<?php
// api/chat.php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
require_once '../includes/config.php';
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate or retrieve persistent guest chat session ID (store in cookie for consistency across visits)
if (!isset($_COOKIE['chat_session_id'])) {
    $guest_session_id = session_id() . '_' . uniqid();
    setcookie('chat_session_id', $guest_session_id, time() + (86400 * 30), "/"); // 30 days expiry
} else {
    $guest_session_id = $_COOKIE['chat_session_id'];
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle Request
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection: Require custom header (browsers block this cross-origin without CORS preflight)
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid request']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['message']) || empty(trim($input['message']))) {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }

    $message = trim($input['message']);
    
    // Message length limit
    if (mb_strlen($message) > 500) {
        echo json_encode(['status' => 'error', 'message' => 'Message too long (max 500 chars)']);
        exit;
    }

    // Basic rate limiting (session-based)
    if (!isset($_SESSION['chat_rate'])) $_SESSION['chat_rate'] = [];
    $_SESSION['chat_rate'][] = time();
    // Keep only messages from last 60 seconds
    $_SESSION['chat_rate'] = array_filter($_SESSION['chat_rate'], function($t) { return $t > time() - 60; });
    if (count($_SESSION['chat_rate']) > 20) {
        echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Please wait.']);
        exit;
    }

    // For admin, we need user_session_id from post data to reply to specific user
    $targetSession = $guest_session_id; // Default for guests
    if ($isAdmin && isset($input['target_session'])) {
        $targetSession = $input['target_session'];
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, message, is_admin) VALUES (?, ?, ?)");
    $stmt->execute([$targetSession, $message, $isAdmin ? 1 : 0]);

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'fetch') {
    // If guest is fetching, use their persistent session ID
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$guest_session_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output to prevent XSS
    foreach ($messages as &$msg) {
        $msg['message'] = htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8');
    }
    unset($msg);

    echo json_encode(['status' => 'success', 'messages' => $messages]);
    exit;
}
?>
