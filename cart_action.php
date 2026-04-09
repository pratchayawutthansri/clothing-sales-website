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

    if ($product_id > 0 && $variant_id > 0) {
        // Product AND Variant must exist — verify variant belongs to product AND check stock
        $stmtCheck = $pdo->prepare("SELECT id, stock FROM product_variants WHERE id = ? AND product_id = ?");
        $stmtCheck->execute([$variant_id, $product_id]);
        $vData = $stmtCheck->fetch();
        if (!$vData) {
            redirect('shop.php'); // Invalid variant-product pair
        }
        $maxStock = (int)$vData['stock'];

        $cartKey = $product_id . '_' . $variant_id; // Key: ProductID_VariantID

        // Calculate requested total quantity
        $currentQty = isset($_SESSION['cart'][$cartKey]) ? $_SESSION['cart'][$cartKey] : 0;
        $requestedQty = $currentQty + $quantity;

        // Apply Stock Limits (New UX Update)
        if ($requestedQty > $maxStock) {
            $requestedQty = $maxStock;
            if ($requestedQty <= 0) $requestedQty = 0; // Out of stock
            
            // Set simple alert message for the user if they tried to add more than exists
            if ($maxStock > 0 && ($currentQty + $quantity) > $maxStock) {
                $_SESSION['cart_error'] = "You can only add up to {$maxStock} items. Stock adjusted.";
            } elseif ($maxStock == 0) {
                 $_SESSION['cart_error'] = "This item is currently out of stock.";
            }
        }

        if ($requestedQty > 0) {
            $_SESSION['cart'][$cartKey] = $requestedQty;
        } else {
            // Remove if 0 somehow
            unset($_SESSION['cart'][$cartKey]);
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
    
    $parts = explode('_', $cartKey);
    $productId = (int)$parts[0];
    $variantId = isset($parts[1]) ? (int)$parts[1] : 0;
    
    // Strict variant check
    if ($variantId <= 0) {
        unset($_SESSION['cart'][$cartKey]);
        redirect('cart.php');
    }
    
    $maxStock = 0;
    $stmtCheck = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ?");
    $stmtCheck->execute([$variantId]);
    $vStock = $stmtCheck->fetchColumn();
    if ($vStock !== false) $maxStock = (int)$vStock;
    if ($quantity > $maxStock) {
        $quantity = $maxStock;
        $_SESSION['cart_error'] = "You can only update up to {$maxStock} items.";
    }
    
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
