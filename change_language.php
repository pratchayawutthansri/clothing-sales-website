<?php
// change_language.php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Redirect back to the referring page, or index.php if HTTP_REFERER is not set
$return_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Security: Validate that the URL belongs to this server (prevent Open Redirect)
$parsed_url = parse_url($return_url);
$serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostOnly = explode(':', $serverHost)[0];

if (isset($parsed_url['host']) && $parsed_url['host'] !== $hostOnly) {
    $return_url = 'index.php';
} else {
    // Sanitize the URL to remove any trailing ?lang= parameters from the old implementation
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query_params);
        if (isset($query_params['lang'])) {
            unset($query_params['lang']);
            $new_query = http_build_query($query_params);
            $return_url = ($parsed_url['scheme'] ?? 'http') . '://' . ($parsed_url['host'] ?? $serverHost) . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '') . ($parsed_url['path'] ?? '/') . ($new_query ? '?' . $new_query : '');
        }
    }
}

header("Location: " . $return_url);
exit;
?>

