<?php
// Prevent multiple inclusions
if (defined('INIT_LOADED')) {
    return;
}
define('INIT_LOADED', true);

// Start Session with Security Settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Load Translation Engine
require_once __DIR__ . '/lang.php';

// Turn on output buffering to prevent header errors
ob_start();

// Load Core Files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Load Models (Phase 2 MVC-Lite Implementation)
require_once __DIR__ . '/models/Product.php';

// Set Timezone (Optional but good practice)
date_default_timezone_set('Asia/Bangkok');
?>
