<?php
require_once 'includes/init.php';
include 'includes/header.php';

// Input Validation
$category = trim($_GET['cat'] ?? '');
$isNew = isset($_GET['new']);
// Categories for Filter (Matched with DB)
$categories = [
    'Tops' => 'Tops', 
    'Bottoms' => 'Bottoms', 
    'Outerwear' => 'Outerwear',
    'Accessories' => 'Accessories'
];

$products = Product::getAll($category);
?>

<style>
    .new-drop-banner {
        background: linear-gradient(180deg, #0f0f0f 0%, #000000 100%);
        color: #fff;
        text-align: center;
        padding: 120px 20px 100px;
        margin-bottom: -40px; /* Setup for filter bar overlap if needed */
        position: relative;
        overflow: hidden;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .new-drop-banner::before {
        content: '';
        position: absolute;
        inset: 0;
        /* Subtle red luxury glow for 'Fresh Drop' feeling */
        background: radial-gradient(circle at top, rgba(255, 30, 30, 0.15) 0%, transparent 60%),
                    radial-gradient(circle at bottom, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
        z-index: 1;
    }
    .banner-content {
        position: relative;
        z-index: 2;
    }
    .banner-badge {
        display: inline-block;
        background: #000;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        padding: 6px 20px;
        font-size: 0.8rem;
        letter-spacing: 2.5px;
        margin-bottom: 25px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(255,0,0,0.1);
    }
    .banner-title {
        font-family: 'Outfit', sans-serif;
        font-size: clamp(3rem, 6vw, 4.5rem);
        font-weight: 900;
        text-transform: uppercase;
        margin-bottom: 15px;
        line-height: 0.95;
        letter-spacing: -1.5px;
    }
    .banner-subtitle {
        font-family: 'Kanit', sans-serif;
        font-size: 1.1rem;
        font-weight: 300;
        color: #888;
        max-width: 600px;
        margin: 0 auto;
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
    <div class="premium-shop-header">
        <div class="container">
            <h1 class="page-title">
                <?= $category ? htmlspecialchars($category) : __('shop_all_products_title') ?>
            </h1>
            <p><?= __('shop_subtitle') ?></p>
        </div>
    </div>
    
    <div class="container">
        <div class="filter-bar premium-filters">
            <a href="shop.php" class="filter-link <?= !$category ? 'active' : '' ?>"><?= __('shop_all_filter') ?></a>
            <?php foreach ($categories as $dbValue => $displayName): ?>
                <a href="shop.php?cat=<?= urlencode($dbValue) ?>" class="filter-link <?= $category === $dbValue ? 'active' : '' ?>">
                    <?= htmlspecialchars($dbValue) ?>
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
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <span class="product-cat"><?= htmlspecialchars($product['category']) ?></span>
                        <span class="product-price">฿<?= number_format($product['base_price'], 0) ?></span>
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
