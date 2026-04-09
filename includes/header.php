<?php

// Sanitize Cart (Remove legacy items without variant ID)
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $qty) {
        if (strpos((string)$key, '_') === false) {
            unset($_SESSION['cart'][$key]);
        }
    }
}

// Calculate cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += $quantity;
    }
}

// Calculate unread notifications count + latest notifications
$unread_notifications_count = 0;
$latest_notifications = [];
if (isset($_SESSION['user_id'])) {
    $stmtUnread = $pdo->prepare("SELECT COUNT(id) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$_SESSION['user_id']]);
    $unread_notifications_count = $stmtUnread->fetchColumn();

    // Fetch latest 5 notifications for the dropdown
    $stmtLatest = $pdo->prepare("
        SELECT n.id, n.title, n.type, n.created_at, un.is_read 
        FROM notifications n 
        JOIN user_notifications un ON n.id = un.notification_id 
        WHERE un.user_id = ? 
        ORDER BY n.created_at DESC LIMIT 5
    ");
    $stmtLatest->execute([$_SESSION['user_id']]);
    $latest_notifications = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="XIVEX - สตรีทแวร์พรีเมียมสไตล์ไทย เสื้อผ้าแฟชั่นคุณภาพ ออกแบบด้วยความใส่ใจในทุกรายละเอียด">
    <link rel="icon" href="<?= defined('SITE_URL') ? SITE_URL : '' ?>favicon.ico" type="image/x-icon">
    <title>XIVEX | สตรีทแวร์พรีเมียม</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS (Cache Busting) -->
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        .nav-link, .cart-icon, .lang-switch {
            font-family: 'Kanit', sans-serif !important;
            font-weight: 500 !important;
            letter-spacing: 0.5px;
            font-size: 1rem !important;
        }
        .logo {
            font-family: 'Outfit', sans-serif !important;
            font-weight: 900 !important;
        }
        .lang-switch {
            margin-left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .lang-switch a {
            color: #999;
            text-decoration: none;
            transition: color 0.3s;
        }
        .lang-switch a.active {
            color: #000;
            font-weight: 700 !important;
        }

        /* ─── Notification Bell ─── */
        .notif-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            margin-right: 18px;
        }
        .notif-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            transition: background 0.25s, transform 0.25s;
            text-decoration: none;
        }
        .notif-btn:hover {
            background: #e8e8e8;
            transform: scale(1.08);
        }
        .notif-btn svg {
            width: 20px;
            height: 20px;
            color: #333;
            transition: color 0.25s;
        }
        .notif-btn:hover svg { color: #000; }

        .notif-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            min-width: 18px;
            height: 18px;
            background: #ef4444;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            line-height: 1;
            font-family: 'Outfit', sans-serif;
        }
        .notif-badge.has-notif {
            animation: notifPulse 2s ease-in-out infinite;
        }

        @keyframes notifPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            50% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        }

        /* Dropdown */
        .notif-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: -20px;
            width: 340px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.04);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: opacity 0.25s, visibility 0.25s, transform 0.25s;
            z-index: 9999;
            overflow: hidden;
        }
        .notif-wrapper:hover .notif-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .notif-dropdown::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 30px;
            width: 12px;
            height: 12px;
            background: #fff;
            transform: rotate(45deg);
            box-shadow: -2px -2px 4px rgba(0,0,0,0.04);
        }
        .notif-dd-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .notif-dd-header h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            font-family: 'Kanit', sans-serif;
        }
        .notif-dd-header span {
            font-size: 0.75rem;
            background: #ef4444;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .notif-dd-list {
            max-height: 320px;
            overflow-y: auto;
        }
        .notif-dd-item {
            display: flex;
            gap: 12px;
            padding: 14px 20px;
            text-decoration: none;
            transition: background 0.2s;
            border-bottom: 1px solid #f8f8f8;
        }
        .notif-dd-item:hover { background: #fafafa; }
        .notif-dd-item.unread { background: #f0fdf4; }
        .notif-dd-item.unread:hover { background: #dcfce7; }
        .notif-dd-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .notif-dd-icon.promo { background: #fef3c7; }
        .notif-dd-icon.alert { background: #fee2e2; }
        .notif-dd-icon.info  { background: #dbeafe; }
        .notif-dd-body { flex: 1; min-width: 0; }
        .notif-dd-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
            font-family: 'Kanit', sans-serif;
        }
        .notif-dd-time {
            font-size: 0.72rem;
            color: #94a3b8;
            font-family: 'Outfit', sans-serif;
        }
        .notif-dd-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 6px;
        }
        .notif-dd-footer {
            text-align: center;
            padding: 12px;
            border-top: 1px solid #f0f0f0;
        }
        .notif-dd-footer a {
            font-size: 0.85rem;
            color: #000;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Kanit', sans-serif;
            transition: color 0.2s;
        }
        .notif-dd-footer a:hover { color: #555; }
        .notif-dd-empty {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
            font-size: 0.85rem;
            font-family: 'Kanit', sans-serif;
        }
        .theme-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            margin-left: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
            padding: 5px;
        }
        .theme-toggle-btn:hover { color: #fff; }
        body.light-mode .theme-toggle-btn:hover { color: #000; }
        body.light-mode .theme-toggle-btn { color: #666; }
    </style>
</head>
<body>
<!-- Theme Initializer: Prevents FOUC (Flash of Unstyled Content) -->
<script>if(localStorage.getItem('xivex_theme') === 'light') { document.body.classList.add('light-mode'); }</script>

<header>
    <div class="container">
        <a href="index.php" class="logo">XIVEX</a>

        <div class="mobile-toggle">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>

        <nav>
                <div class="nav-overlay" id="navOverlay"></div>
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link"><?= mb_strtoupper(__('home')) ?></a></li>
                    <li><a href="shop.php" class="nav-link"><?= mb_strtoupper(__('shop_all')) ?></a></li>
                    <li><a href="shop.php?new=true" class="nav-link"><?= mb_strtoupper(__('new_drops')) ?></a></li>
                    <li><a href="about.php" class="nav-link"><?= mb_strtoupper(__('about')) ?></a></li>
                    <li><a href="contact.php" class="nav-link"><?= mb_strtoupper(__('contact')) ?></a></li>
                    <li>
                        <a href="cart.php" class="cart-icon">
                            <?= mb_strtoupper(__('cart')) ?> 
                            <?php if($cart_count > 0): ?>
                                <span class="cart-count"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="user-info-mobile">
                        <span><?= __('hi_user') ?><a href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?></a>!</span>
                        <a href="logout.php" class="logout-link"><?= mb_strtoupper(__('nav_logout')) ?></a>
                    </li>
                    <?php else: ?>
                    <li class="auth-links-mobile">
                        <a href="login.php" class="nav-link"><?= mb_strtoupper(__('login')) ?></a>
                        <span class="divider">/</span>
                        <a href="register.php" class="nav-link"><?= mb_strtoupper(__('nav_register')) ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="lang-switch-mobile">
                        <a href="change_language.php?lang=th" class="<?= $_SESSION['lang'] === 'th' ? 'active' : '' ?>">TH</a> 
                        <span class="divider">|</span> 
                        <a href="change_language.php?lang=en" class="<?= $_SESSION['lang'] === 'en' ? 'active' : '' ?>">EN</a>
                        <button id="theme-toggle" class="theme-toggle-btn" aria-label="Toggle Theme" title="Switch Theme"></button>
                    </li>
                </ul>
        </nav>
    </div>
</header>

<script src="js/theme.js"></script>

<script>
// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navOverlay = document.getElementById('navOverlay');
    const body = document.body;
    
    function toggleMenu() {
        if (!mobileToggle || !navMenu || !navOverlay) return;
        mobileToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
        navOverlay.classList.toggle('active');
        body.classList.toggle('no-scroll');
    }

    if (mobileToggle && navMenu && navOverlay) {
        mobileToggle.addEventListener('click', toggleMenu);
        navOverlay.addEventListener('click', toggleMenu);
        
        // Close menu when clicking on links
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (navMenu.classList.contains('active')) {
                    toggleMenu();
                }
            });
        });
    }
});
</script>
