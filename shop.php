<?php
require_once 'includes/init.php';
include 'includes/header.php';

// Input Validation - Prevent SQL Injection
$category = trim($_GET['cat'] ?? '');
$isNew = isset($_GET['new']);
$whereClause = "";
$params = [];

// Whitelist valid categories to prevent SQL injection
$validCategories = ['Tops', 'Bottoms', 'Outerwear', 'Accessories'];

if ($category && in_array($category, $validCategories)) {
    $whereClause = "WHERE category = ? AND is_visible = 1";
    $params[] = $category;
} elseif ($category) {
    // Invalid category - redirect to safe page
    redirect('shop.php');
} else {
    $whereClause = "WHERE is_visible = 1";
}

// Categories for Filter (Matched with DB)
$categories = [
    'Tops' => 'Tops', 
    'Bottoms' => 'Bottoms', 
    'Outerwear' => 'Outerwear',
    'Accessories' => 'Accessories'
];

// Logic: If 'new' is requested, we might want to limit to recent items or specific categories.
// For now, we keep the ORDER BY id DESC which naturally shows new items first.
$stmt = $pdo->prepare("SELECT * FROM products $whereClause ORDER BY id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<style>
    .new-drop-banner {
        background-color: #000;
        color: white;
        text-align: center;
        padding: 80px 20px;
        margin-bottom: 50px;
        background-image: url('https://images.unsplash.com/photo-1523398002811-6c9baa02d769?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        position: relative;
    }
    .new-drop-banner::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.6);
    }
    .banner-content {
        position: relative;
        z-index: 2;
    }
    .banner-badge {
        display: inline-block;
        background: #ff4444;
        color: white;
        padding: 5px 15px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.8rem;
        margin-bottom: 20px;
    }
    .banner-title {
        font-family: 'Outfit', sans-serif;
        font-size: 4rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 10px;
        line-height: 1;
    }
    .banner-subtitle {
        font-family: 'Kanit', sans-serif;
        font-size: 1.2rem;
        font-weight: 300;
        opacity: 0.9;
    }
    
    .badge-new {
        position: absolute;
        top: 10px;
        left: 10px;
        background: #000;
        color: #fff;
        padding: 5px 10px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    /* Filter Bar */
    .filter-bar {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 50px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }
    .filter-link {
        color: #888;
        font-family: 'Outfit', sans-serif;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
        position: relative;
        transition: 0.3s;
    }
    .filter-link:hover, .filter-link.active {
        color: #1a1a1a;
        font-weight: 600;
    }
    .filter-link.active::after {
        content: '';
        position: absolute;
        bottom: -21px; /* Aligns with border-bottom */
        left: 0;
        width: 100%;
        height: 2px;
        background: #1a1a1a;
    }

    /* Product Card Hover */
    .product-card {
        position: relative;
    }
    .quick-add-btn {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #1a1a1a;
        color: white;
        text-align: center;
        padding: 12px;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
        transform: translateY(100%);
        opacity: 0;
        transition: all 0.3s ease;
    }
    .product-image-wrapper {
        overflow: hidden; /* Ensure button stays hidden */
        position: relative;
    }
    .product-card:hover .quick-add-btn {
        transform: translateY(0);
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .banner-title { font-size: 2.5rem; }
    }
</style>

<?php if ($isNew): ?>
    <div class="new-drop-banner">
        <div class="banner-content">
            <div class="banner-badge"><?= mb_strtoupper(__('shop_fresh_drop')) ?></div>
            <h1 class="banner-title"><?= __('shop_hyped_essentials') ?></h1>
            <p class="banner-subtitle"><?= __('shop_sub_banner') ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="page-header" style="text-align: center; padding: 60px 0;">
        <div class="container">
            <h1 class="page-title" style="font-family: 'Outfit', sans-serif; font-size: 3rem; font-weight: 700; margin-bottom: 10px;">
                <?= $category ? htmlspecialchars($category) : __('shop_all_products_title') ?>
            </h1>
            <p style="color: #666; font-family: 'Kanit', sans-serif; font-weight: 300;"><?= __('shop_subtitle') ?></p>
        </div>
    </div>
    
    <div class="container">
        <div class="filter-bar">
            <a href="shop.php" class="filter-link <?= !$category ? 'active' : '' ?>"><?= __('shop_all_filter') ?></a>
            <?php foreach ($categories as $dbValue => $displayName): ?>
                <a href="shop.php?cat=<?= urlencode($dbValue) ?>" class="filter-link <?= $category === $dbValue ? 'active' : '' ?>">
                    <?= htmlspecialchars($dbValue) /* Use English Key for cleaner URL/Display or displayName if preferred */ ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="container" style="margin-bottom: 100px;">
    <?php if (count($products) > 0): ?>
        <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
            <div class="product-card">
                <a href="product.php?id=<?= $product['id'] ?>">
                    <div class="product-image-wrapper" style="position: relative;">
                        <!-- Show NEW badge for the first 3 items or if specifically looking at New Arrivals -->
                        <?php if ($index < 3 || $isNew): ?>
                            <span class="badge-new"><?= __('badge_new') ?></span>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        
                        <div class="quick-add-btn"><?= __('view_details') ?></div>
                    </div>
                    <div class="product-info">
                        <div>
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <span class="product-cat"><?= htmlspecialchars($product['category']) ?></span>
                        </div>
                        <span class="product-price"><?= __('from_price') ?><?= number_format($product['base_price'], 0) ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center; padding: 50px;"><?= __('shop_no_products') ?></p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
