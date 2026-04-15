<?php
require_once 'includes/init.php';

// Security: Check Request Method & CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Security Check Failed: Invalid Token");
}

// 1. Validate Input
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$payment_method = $_POST['payment_method'];

// Whitelist valid payment methods
$valid_methods = ['BANK_TRANSFER', 'COD'];
if (!in_array($payment_method, $valid_methods)) {
    die("Invalid payment method.");
}

if (empty($name) || empty($email) || empty($phone) || empty($address)) {
    die("Please fill in all required fields.");
}

// 2. Calculate Total & Prepare Items
$total_price = 0;
$order_items = [];

if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

try {
    $pdo->beginTransaction();

    foreach ($_SESSION['cart'] as $key => $qty) {
        $parts = explode('_', $key);
        $productId = (int)($parts[0] ?? 0);
        $variantId = (int)($parts[1] ?? 0);
        if ($productId <= 0) continue;
        if ($variantId <= 0) {
            throw new Exception("Invalid product configuration detected in cart.");
        }

        // Product WITH variant — fetch details with row lock
            $stmt = $pdo->prepare("SELECT p.name, v.price, v.size, v.stock FROM products p JOIN product_variants v ON p.id = v.product_id WHERE p.id = ? AND v.id = ? FOR UPDATE");
            $stmt->execute([$productId, $variantId]);
            $item = $stmt->fetch();

            if ($item) {
                // ENHANCED STOCK CHECK - Double verification to prevent race conditions
                if ($item['stock'] < $qty) {
                    throw new Exception("Product {$item['name']} (Size {$item['size']}) is out of stock (Only {$item['stock']} left)");
                }
                
                // Additional safety check: Reserve stock immediately to prevent overselling
                $reserveStmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $reserveResult = $reserveStmt->execute([$qty, $variantId, $qty]);
                
                if ($reserveStmt->rowCount() === 0) {
                    // Stock changed between our read and update - another order got it first
                    throw new Exception("Sorry, product {$item['name']} (Size {$item['size']}) just went out of stock. Please try again.");
                }

                $subtotal = $item['price'] * $qty;
                $total_price += $subtotal;
                
                $order_items[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'product_name' => $item['name'],
                    'size' => $item['size'],
                    'price' => $item['price'],
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];
            }
        }
    // Guard: Prevent empty orders (Critical Bug #2 fix)
    if (empty($order_items)) {
        throw new Exception("No valid items in cart. Products may have been removed.");
    }

    // 3. Enhanced Slip Upload Security
    $slipPath = null;
    if ($payment_method === 'BANK_TRANSFER' && isset($_FILES['slip']) && $_FILES['slip']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Enhanced file validation
        $ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($_FILES['slip']['tmp_name']);
        
        // Multiple security checks
        if (!in_array($ext, $allowed) || !in_array($mimeType, $allowedMimes)) {
            error_log("Upload security breach attempt: Invalid file type - Ext: {$ext}, MIME: {$mimeType}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            throw new Exception("Invalid file type (Allowed: JPG, PNG, GIF, WEBP)");
        }
        if ($_FILES['slip']['size'] > $maxSize) {
            throw new Exception("File is too large (Max 5MB)");
        }
        
        // Additional security: Verify file content is actually an image
        if (!getimagesize($_FILES['slip']['tmp_name'])) {
            error_log("Upload security breach attempt: Fake image file - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            throw new Exception("Invalid image file content");
        }
        
        $uploadDir = 'uploads/slips/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $newName = uniqid() . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['slip']['tmp_name'], $dest)) {
            $slipPath = $dest;
        } else {
            throw new Exception("Failed to upload payment slip");
        }
    }

    // 4. Add Shipping Cost
    $stmtShipping = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'shipping_cost'");
    $shippingCost = (float)($stmtShipping->fetchColumn() ?: 50);
    
    // Free shipping for members
    if (isset($_SESSION['user_id'])) {
        $shippingCost = 0;
    }
    
    $total_price += $shippingCost;

    // 5. Insert Order
    $user_id = $_SESSION['user_id'] ?? null;
    $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, customer_name, email, phone, address, total_price, payment_method, payment_slip, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmtOrder->execute([$user_id, $name, $email, $phone, $address, $total_price, $payment_method, $slipPath]);
    $order_id = $pdo->lastInsertId();

    // 4. Insert Order Items
    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, product_name, size, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($order_items as $item) {
        $stmtItem->execute([
            $order_id,
            $item['product_id'],
            $item['variant_id'],
            $item['product_name'],
            $item['size'],
            $item['price'],
            $item['quantity'],
            $item['subtotal']
        ]);

        // STOCK ALREADY CUT during reservation phase above
        // No need to cut again - this prevents double deduction
        // (Stock was already reserved in the enhanced check phase)
    }

    $pdo->commit();

    // 5. Clear Cart & Redirect with Enhanced Session Security
    $_SESSION['last_order_id'] = (int)$order_id; // For IDOR protection on success page
    $_SESSION['last_order_time'] = time(); // Timestamp for session validation
    unset($_SESSION['cart']);
    
    // Log successful order for security monitoring
    error_log("Order {$order_id} created successfully for user: " . ($_SESSION['user_id'] ?? 'guest') . " at " . date('Y-m-d H:i:s'));
    
    redirect("success.php?order_id=" . $order_id);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order Error: " . $e->getMessage());
    // Show stock-related messages directly; hide technical errors
    $userMessage = (strpos($e->getMessage(), 'Product') !== false || strpos($e->getMessage(), 'File') !== false || strpos($e->getMessage(), 'Invalid') !== false)
        ? $e->getMessage()
        : "An error occurred while processing your order. Please try again.";
    die($userMessage);
}
?>
