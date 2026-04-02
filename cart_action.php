<?php
require_once 'includes/init.php';

// Ensure Cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';

// Check CSRF Token for ANY action (Add, Update, Remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("Security Check Failed: Invalid CSRF Token"); // Stop execution if token is invalid
    }
}

if ($action === 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Strict Input Validation
    if ($quantity > 100) $quantity = 100; // Cap max quantity
    if ($quantity < 1) $quantity = 1;

    if ($product_id > 0) {
        if ($variant_id > 0) {
            // Product WITH variant — verify variant belongs to product
            $stmtCheck = $pdo->prepare("SELECT id FROM product_variants WHERE id = ? AND product_id = ?");
            $stmtCheck->execute([$variant_id, $product_id]);
            if (!$stmtCheck->fetch()) {
                redirect('shop.php'); // Invalid variant-product pair
            }
        } else {
            // GUARD: Prevent "Variant Bypass" (Business Logic Vulnerability)
            // If the product has variants in DB, but variant_id=0 was passed, it's an exploit attempt.
            $stmtRequireVariant = $pdo->prepare("SELECT id FROM product_variants WHERE product_id = ? LIMIT 1");
            $stmtRequireVariant->execute([$product_id]);
            if ($stmtRequireVariant->fetch()) {
                // Security Check Failed: Product REQUIRES a variant selection. Deny request.
                redirect('shop.php'); 
            }

            // Product WITHOUT variant (variant_id=0) — verify product exists
            $stmtCheck = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_visible = 1");
            $stmtCheck->execute([$product_id]);
            if (!$stmtCheck->fetch()) {
                redirect('shop.php'); // Invalid product
            }
        }

        $cartKey = $product_id . '_' . $variant_id; // Key: ProductID_VariantID (0 = no variant)

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = $quantity;
        }
    }
    
    redirect('cart.php');

} elseif ($action === 'update') {
    $cartKey = $_POST['key'] ?? '';
    $quantity = (int)$_POST['quantity'];
    
    // Validate key format (must be productId_variantId)
    if (!preg_match('/^\d+_\d+$/', $cartKey)) {
        redirect('cart.php');
    }
    
    // Validate quantity
    if ($quantity > 100) $quantity = 100;
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$cartKey]);
    } else {
        $_SESSION['cart'][$cartKey] = $quantity;
    }
    
    redirect('cart.php');

} elseif ($action === 'remove') {
    $cartKey = $_POST['key'] ?? '';
    
    // Validate key format
    if (!preg_match('/^\d+_\d+$/', $cartKey)) {
        redirect('cart.php');
    }
    
    unset($_SESSION['cart'][$cartKey]);
    
    redirect('cart.php');
}

redirect('index.php');
?>
