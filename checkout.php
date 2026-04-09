<?php
require_once 'includes/init.php';

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    redirect('cart.php');
}

// Calculate Total
$total = 0;
$cartItems = [];

// Parse cart keys
$cartParsed = [];
foreach ($_SESSION['cart'] as $key => $quantity) {
    $parts = explode('_', $key);
    $productId = (int)$parts[0];
    $variantId = isset($parts[1]) ? (int)$parts[1] : 0;
    
    if ($productId > 0 && $variantId > 0) {
        $cartParsed[$key] = ['pid' => $productId, 'vid' => $variantId, 'qty' => $quantity];
    } else {
        unset($_SESSION['cart'][$key]); // Strictly remove invalid cart contents
    }
}

if (!empty($cartParsed)) {
    $withVariants = array_filter($cartParsed, function($c) { return $c['vid'] > 0; });

    if (!empty($withVariants)) {
        $variantIds = array_column($withVariants, 'vid');
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        
        $stmt = $pdo->prepare("
            SELECT p.name, v.id AS variant_id, v.size, v.price
            FROM products p
            JOIN product_variants v ON v.product_id = p.id
            WHERE v.id IN ($placeholders)
        ");
        $stmt->execute($variantIds);
        $results = $stmt->fetchAll();
        
        $resultMap = [];
        foreach ($results as $row) {
            $resultMap[$row['variant_id']] = $row;
        }
        
        foreach ($withVariants as $key => $cartData) {
            if (isset($resultMap[$cartData['vid']])) {
                $row = $resultMap[$cartData['vid']];
                $subtotal = $row['price'] * $cartData['qty'];
                $total += $subtotal;
                $cartItems[] = [
                    'name' => $row['name'],
                    'size' => $row['size'],
                    'price' => $row['price'],
                    'qty' => $cartData['qty'],
                    'subtotal' => $subtotal
                ];
            }
        }
    }

}

// Fetch Shop Settings
$settings = [];
$stmtSettings = $pdo->query("SELECT * FROM settings");
while ($row = $stmtSettings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$shippingCost = (float)($settings['shipping_cost'] ?? 50);

if (isset($_SESSION['user_id'])) {
    $shippingCost = 0;
}
$total += $shippingCost;

include 'includes/header.php';
?>

<style>
/* ── Modern Premium Checkout ── */
.checkout-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 80px 5%;
    font-family: 'Kanit', sans-serif;
    color: #fff;
}
.checkout-header {
    margin-bottom: 60px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 20px;
}
.checkout-header h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 3rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: -1px;
    margin: 0;
}
.checkout-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 100px;
}

/* Form Styles */
.checkout-section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #fff;
    margin-bottom: 30px;
    border-bottom: 1px solid #333;
    padding-bottom: 15px;
}
.form-row {
    position: relative;
    margin-bottom: 35px;
}
.form-row label {
    display: block;
    font-size: 0.75rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #888;
    margin-bottom: 10px;
}
.form-row input, .form-row textarea {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 1px solid #444;
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 1rem;
    padding: 8px 0;
    outline: none;
    transition: border-color 0.3s;
}
.form-row input:focus, .form-row textarea:focus {
    border-bottom-color: #fff;
}
.form-row input[readonly] {
    color: #666;
    border-bottom-style: dashed;
}

/* Slip Upload Box */
.slip-upload-modern {
    background: #080808;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 30px;
    margin-top: 30px;
}
.bank-details {
    margin-bottom: 25px;
}
.bank-details p {
    margin: 5px 0;
    color: #aaa;
    font-size: 0.95rem;
}
.bank-details strong {
    color: #fff;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 10px;
}
.acc-number {
    font-family: 'Outfit', monospace;
    color: #fff;
    letter-spacing: 1px;
    font-size: 1.1rem;
}

.file-input-wrapper {
    position: relative;
    border: 1px dashed #444;
    padding: 20px;
    text-align: center;
    transition: border-color 0.3s;
}
.file-input-wrapper:hover {
    border-color: #888;
}
.file-input-wrapper input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}
.file-input-label {
    color: #888;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Order Summary */
.checkout-summary-modern {
    background: #0a0a0a;
    padding: 40px;
    border: 1px solid rgba(255,255,255,0.05);
    position: sticky;
    top: 120px;
    height: fit-content;
}
.summary-items-list {
    margin-bottom: 30px;
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.summary-item-line {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    color: #ccc;
}
.summary-item-name {
    color: #fff;
}
.summary-divider {
    height: 1px;
    background: #222;
    margin: 20px 0;
}
.summary-total-line {
    display: flex;
    justify-content: space-between;
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #333;
}
.submit-order-btn {
    display: block;
    width: 100%;
    background: #fff;
    color: #000;
    border: none;
    padding: 20px;
    margin-top: 40px;
    font-family: 'Outfit', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
}
.submit-order-btn:hover {
    background: #ccc;
}

@media (max-width: 1024px) {
    .checkout-grid {
        grid-template-columns: 1fr;
        gap: 60px;
    }
    .checkout-summary-modern {
        position: relative;
        top: 0;
        order: -1; /* Show summary first on mobile */
    }
}

/* ── Light Mode Overrides ── */
body.light-mode .checkout-wrapper { color: var(--text-color); }
body.light-mode .checkout-header { border-bottom: 1px solid var(--border-medium); }
body.light-mode .checkout-header h1 { color: var(--text-color); }
body.light-mode .checkout-section-title { border-bottom: 1px solid var(--border-medium); color: var(--text-color); }
body.light-mode .form-row input, body.light-mode .form-row textarea { 
    border-bottom: 1px solid var(--border-medium); color: var(--text-color); 
}
body.light-mode .form-row input:focus, body.light-mode .form-row textarea:focus { border-bottom-color: var(--text-color); }
body.light-mode .slip-upload-modern { background: var(--bg-secondary); border: 1px solid var(--border-medium); }
body.light-mode .bank-details strong, body.light-mode .acc-number { color: var(--text-color); }
body.light-mode .bank-details p { color: var(--text-secondary); }
body.light-mode .file-input-wrapper { border-color: var(--border-medium); }
body.light-mode .checkout-summary-modern { background: var(--bg-secondary); border: 1px solid var(--border-medium); }
body.light-mode .summary-item-name, body.light-mode .summary-total-line { color: var(--text-color); }
body.light-mode .summary-item-line { color: var(--text-secondary); }
body.light-mode .summary-divider { background: var(--border-medium); }
body.light-mode .submit-order-btn { background: var(--text-color); color: var(--bg-primary); }
body.light-mode .submit-order-btn:hover { background: var(--text-muted); }
</style>

<div class="checkout-wrapper">
    <div class="checkout-header">
        <h1><?= __('chk_title') ?></h1>
    </div>

    <div class="checkout-grid">
        
        <!-- Left: Form -->
        <div class="checkout-form-area">
            <h3 class="checkout-section-title"><?= __('chk_shipping_details') ?></h3>
            
            <form action="process_order.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <div class="form-row">
                    <label><?= __('chk_full_name') ?></label>
                    <input type="text" name="name" required value="<?= isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : '' ?>">
                </div>
                
                <div class="form-row">
                    <label><?= __('chk_email') ?></label>
                    <input type="email" name="email" required <?= isset($_SESSION['user_id']) ? 'readonly' : '' ?> value="<?= isset($_SESSION['user_id']) ? htmlspecialchars(isset($_SESSION['email']) ? $_SESSION['email'] : '') : '' ?>">
                </div>
                
                <div class="form-row">
                    <label><?= __('chk_phone') ?></label>
                    <input type="tel" name="phone" required>
                </div>
                
                <div class="form-row">
                    <label><?= __('chk_address') ?></label>
                    <textarea name="address" rows="3" required></textarea>
                </div>
                
                <h3 class="checkout-section-title" style="margin-top:50px;"><?= __('chk_payment_method') ?></h3>
                
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="radio" name="payment_method" value="BANK_TRANSFER" checked style="accent-color: #fff;">
                    <span style="font-family: 'Outfit', sans-serif; text-transform: uppercase; letter-spacing: 1px;"><?= __('chk_bank_transfer') ?></span>
                </label>

                <div class="slip-upload-modern">
                    <div class="bank-details">
                        <span style="display:block; margin-bottom:15px; font-size:0.8rem; letter-spacing:1px; color:#888; text-transform:uppercase;"><?= __('chk_transfer_to') ?></span>
                        <strong><?= htmlspecialchars($settings['bank_name'] ?? __('chk_not_set')) ?></strong>
                        <p><?= __('chk_acc_number') ?>: <span class="acc-number"><?= htmlspecialchars($settings['bank_account'] ?? '-') ?></span></p>
                        <p><?= __('chk_acc_name') ?>: <?= htmlspecialchars($settings['bank_owner'] ?? '-') ?></p>
                    </div>
                    
                    <div class="file-input-wrapper">
                        <span class="file-input-label"><?= __('chk_attach_slip') ?> * (Click to Browse)</span>
                        <input type="file" name="slip" accept="image/*" required>
                    </div>
                </div>

                <button type="submit" class="submit-order-btn"><?= __('chk_confirm_order') ?></button>
            </form>
        </div>
        
        <!-- Right: Summary -->
        <div class="checkout-summary-modern">
            <h3 class="checkout-section-title" style="border-bottom:none; margin-bottom: 20px;"><?= __('chk_order_summary') ?></h3>
            
            <div class="summary-items-list">
                <?php foreach ($cartItems as $item): ?>
                <div class="summary-item-line">
                    <div>
                        <span class="summary-item-name"><?= htmlspecialchars($item['name']) ?></span> 
                        <span style="color:#888;">(<?= htmlspecialchars($item['size']) ?>) &times; <?= $item['qty'] ?></span>
                    </div>
                    <span><?= formatPrice($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-divider"></div>
            
            <div class="summary-item-line">
                <span><?= __('chk_shipping') ?></span>
                <span><?= isset($_SESSION['user_id']) ? '<span style="color:#fff;">' . __('chk_free_member') . '</span>' : formatPrice($shippingCost) ?></span>
            </div>
            
            <div class="summary-total-line">
                <span><?= __('chk_total_amount') ?></span>
                <span><?= formatPrice($total) ?></span>
            </div>
            <p style="text-align:right; font-size:0.8rem; color:#666; margin-top:10px; font-family:'Outfit', sans-serif;"><?= __('chk_vat_included') ?? 'VAT INCLUDED' ?></p>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
