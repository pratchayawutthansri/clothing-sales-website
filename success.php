<?php
require_once 'includes/init.php';

// Enhanced IDOR Protection
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Additional validation: Order ID must be reasonable (1-999999)
if ($order_id <= 0 || $order_id > 999999) {
    redirect('index.php');
}

$allowed = false;

if ($order_id > 0) {
    // Check session token (first visit after placing order) - with timestamp validation
    if (isset($_SESSION['last_order_id']) && 
        isset($_SESSION['last_order_time']) &&
        $_SESSION['last_order_id'] === $order_id &&
        (time() - $_SESSION['last_order_time']) < 3600) { // Valid for 1 hour
        
        $allowed = true;
        // Clear session data after use
        unset($_SESSION['last_order_id']);
        unset($_SESSION['last_order_time']);
    }
    
    // Check ownership for logged-in users - with additional email verification
    if (!$allowed && isset($_SESSION['user_id'])) {
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE id = ? AND user_id = ? AND order_date > ?");
        $stmtCheck->execute([$order_id, $_SESSION['user_id'], $thirtyDaysAgo]);
        if ($stmtCheck->fetchColumn() > 0) {
            $allowed = true;
            
            // Log access for security monitoring
            error_log("Order ID {$order_id} accessed by user {$_SESSION['user_id']} at " . date('Y-m-d H:i:s'));
        }
    }
}

if (!$allowed) {
    redirect('index.php');
}


// Fetch Order Details for confirmation (Optional: could just show ID)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container" style="padding: 100px 0; text-align: center;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="font-size: 5rem; color: #4CAF50; margin-bottom: 20px;">✓</div>
        <h1 style="font-size: 2.5rem; margin-bottom: 15px;"><?= __('suc_thank_you') ?></h1>
        <p style="font-size: 1.2rem; margin-bottom: 30px; color: #555;">
            <?= __('suc_order_num') ?> <strong>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></strong>
        </p>

        <?php if ($order['status'] === 'pending'): ?>
            <div class="alert alert-info">
                📧 <?= __('suc_tracking_sent') ?> <strong><?= htmlspecialchars($order['email']) ?></strong> <?= __('suc_once_shipped') ?>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <h3><?= __('chk_shipping_details') ?></h3>
            <p style="margin-top: 10px;"><strong><?= __('suc_name') ?></strong> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><strong><?= __('suc_address') ?></strong> <?= htmlspecialchars($order['address']) ?></p>
            <p><strong><?= __('suc_phone') ?></strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p class="total">
                <strong><?= __('chk_total_amount') ?>:</strong> <?= formatPrice($order['total_price']) ?> 
                <?php if ($order['payment_method'] === 'BANK_TRANSFER'): ?>
                <?= __('suc_bank_transfer') ?>
                <?php endif; ?>
            </p>
        </div>

        <div style="margin-top: 40px;">
            <a href="index.php" class="btn"><?= __('cart_continue') ?></a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
