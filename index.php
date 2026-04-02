<?php
require_once 'includes/init.php';
include 'includes/header.php';

// Fetch featured products (newest 4 visible products)
$stmt = $pdo->query("SELECT * FROM products WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 4");
$featured_products = $stmt->fetchAll();
?>

<!-- Custom Luxury Styles -->
<style>
    /* Typography Upgrade */
    h1, h2, h3 { font-family: 'Outfit', sans-serif; }
    
    /* Hero Refinement - Hybrid Cartoon Luxury */
    .hero {
        background: #f4f4f4; /* Revert to solid/light background for cartoon contrast */
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 5%;
    }
    .hero::before { display: none; } /* Remove dark overlay */
    
    .hero-image { 
        display: block; 
        max-width: 50%;
        height: auto;
        object-fit: contain;
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
        100% { transform: translateY(0px); }
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        color: #1a1a1a; /* Dark text for light background */
        text-align: left;
        max-width: 600px;
    }
    .hero h1 {
        font-size: 5rem;
        line-height: 1;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .hero p {
        color: #666;
        font-size: 1.2rem;
        font-weight: 300;
        letter-spacing: 1px;
    }
    .hero .btn {
        background: #1a1a1a;
        color: white;
        border: none;
        padding: 15px 40px;
        font-weight: 600;
        letter-spacing: 2px;
    }
    .hero .btn:hover {
        background: #333;
        transform: translateY(-5px);
    }
    
    @media (max-width: 768px) {
        .hero { flex-direction: column-reverse; text-align: center; padding: 50px 20px; }
        .hero-image { max-width: 80%; margin-bottom: 30px; }
        .hero h1 { font-size: 3rem; }
    }

    /* Featured Section Polish */
    .section-title {
        font-size: 3rem;
        text-transform: uppercase;
        letter-spacing: 3px;
        position: relative;
        display: inline-block;
    }
    .section-title::after {
        content: '';
        display: block;
        width: 50%;
        height: 2px;
        background: black;
        margin-top: 10px;
    }

    /* Product Card Hover (From Shop) */
    .product-card { position: relative; overflow: hidden; }
    .quick-add-btn {
        position: absolute;
        bottom: 0; left: 0; width: 100%;
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
        z-index: 10;
    }
    .product-image-wrapper { position: relative; }
    .product-card:hover .quick-add-btn { transform: translateY(0); opacity: 1; }

    /* Promo Banner Polish */
    .promo-banner {
        background-color: #0d0d0d;
        padding: 120px 0;
    }
    .promo-content h2 {
        font-size: 4rem;
        font-style: italic;
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 style="font-family: 'Outfit', sans-serif; font-weight: 800; letter-spacing: -2px; text-transform: uppercase; font-size: 5rem; line-height: 0.9;">
            <?= __('hero_title_1') ?><br><span style="color: #ff4444;"><?= __('hero_title_2') ?></span>
        </h1>
        <p style="font-family: 'Kanit', sans-serif; font-weight: 300; font-size: 1.2rem; margin-top: 20px; color: #666;">
            <?= __('hero_subtitle') ?>
        </p>
        <a href="shop.php" class="btn" style="background: #000; color: #fff; border-radius: 50px; padding: 15px 40px; font-family: 'Outfit', sans-serif; font-weight: 600; margin-top: 30px;"><?= mb_strtoupper(__('shop_the_vibe')) ?></a>
    </div>
    <img src="images/hero_cartoon_new.png" alt="XIVEX Cartoon Mascot" class="hero-image">
</section>

<!-- Featured Section -->
<section class="featured" style="padding: 100px 0;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
            <h2 class="section-title" style="font-family: 'Outfit', sans-serif; font-size: 3rem; font-weight: 700; margin-bottom: 10px;"><?= __('new_arrivals') ?></h2>
            <div style="margin-top: 10px;">
                <a href="shop.php" style="font-family: 'Outfit', sans-serif; font-weight: 400; font-size: 1rem; color: #666; text-decoration: none; border-bottom: 1px solid #ccc;"><?= __('view_all_products') ?></a>
            </div>
        </div>

        <div class="product-grid">
            <?php foreach ($featured_products as $product): ?>
            <div class="product-card">
                <a href="product.php?id=<?= $product['id'] ?>">
                    <div class="product-image-wrapper">
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
    </div>
</section>

<!-- Promotional Banner -->
<section class="promo-banner">
    <div class="promo-content">
        <h2><?= __('promo_title') ?? 'The Monochrome Series' ?></h2>
        <p style="margin-bottom:30px; color:#999; font-family: 'Kanit', sans-serif; letter-spacing: 1px;"><?= __('promo_subtitle') ?? 'Elevated Simplicity' ?></p>
        <a href="shop.php?cat=Monochrome" class="btn" style="background: white; color: black; border: none;"><?= mb_strtoupper(__('shop_series') ?? 'Shop Series') ?></a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
