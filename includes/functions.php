<?php
// Function to generate CSRF Token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF Token
function verifyCsrfToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

// Function to format price safely
function formatPrice($price) {
    return '฿' . number_format((float)$price, 0);
}

// Function to redirect safely
function redirect($url) {
    // Sanitize: only allow relative URLs or valid http(s) URLs
    if (!preg_match('#^(https?://|/)#i', $url) && !preg_match('#^[a-zA-Z0-9_./-]+(\?.*)?$#', $url)) {
        $url = 'index.php';
    }
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo "<script>window.location.href='" . $safeUrl . "';</script>";
        exit;
    }
}
?>
