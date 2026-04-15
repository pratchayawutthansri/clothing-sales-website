<?php
require_once 'config.php';

try {
    // MySQL connection
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Better security against SQL Injection
} catch (PDOException $e) {
    // Log error instead of showing it to user
    error_log("Database Connection Error: " . $e->getMessage());
    die("ขออภัย ระบบขัดข้องชั่วคราว โปรดลองใหม่ภายหลัง");
}
?>
