<?php
require_once 'includes/init.php';

// Must be logged in to view notifications
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Check if a specific notification is being viewed
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $notification_id = (int)$_GET['view'];
    
    // Verify notification belongs to this user before marking as read
    $stmtVerify = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND notification_id = ? AND is_read = 0");
    $stmtVerify->execute([$user_id, $notification_id]);
    
    if ($stmtVerify->fetchColumn() > 0) {
        $stmtMarkRead = $pdo->prepare("UPDATE user_notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND notification_id = ?");
        $stmtMarkRead->execute([$user_id, $notification_id]);
    }
    
    redirect('notifications.php');
}

// Check if "Mark all as read" was clicked (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    if (isset($_POST['csrf_token']) && verifyCsrfToken($_POST['csrf_token'])) {
        $stmtMarkAllRead = $pdo->prepare("UPDATE user_notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = 0");
        $stmtMarkAllRead->execute([$user_id]);
    }
    redirect('notifications.php');
}

// Fetch user's notifications
$stmtNotifs = $pdo->prepare("
    SELECT n.*, un.is_read, un.read_at 
    FROM notifications n 
    JOIN user_notifications un ON n.id = un.notification_id 
    WHERE un.user_id = ? 
    ORDER BY n.created_at DESC
");
$stmtNotifs->execute([$user_id]);
$notifications = $stmtNotifs->fetchAll();

// Count unread
$unreadCount = 0;
foreach ($notifications as $n) { if (!$n['is_read']) $unreadCount++; }

include 'includes/header.php';
?>

<style>
    .notif-page {
        padding: 60px 0 80px;
        min-height: 75vh;
        background: linear-gradient(160deg, #fafafa 0%, #f0f0f0 100%);
    }

    /* Header Section */
    .notif-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        gap: 20px;
    }
    .notif-hero-left h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 2.8rem;
        font-weight: 800;
        color: #0a0a0a;
        margin: 0;
        letter-spacing: -1px;
        line-height: 1;
    }
    .notif-hero-left p {
        margin: 10px 0 0;
        color: #64748b;
        font-size: 0.95rem;
        font-family: 'Kanit', sans-serif;
    }
    .notif-hero-left p span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #000;
        color: #fff;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 6px;
    }
    .notif-mark-all {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #000;
        color: #fff;
        padding: 12px 24px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.88rem;
        font-family: 'Kanit', sans-serif;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .notif-mark-all:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .notif-mark-all svg {
        width: 16px;
        height: 16px;
    }

    /* Empty State */
    .notif-empty {
        text-align: center;
        padding: 80px 40px;
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        max-width: 500px;
        margin: 0 auto;
    }
    .notif-empty-icon {
        font-size: 3.5rem;
        margin-bottom: 20px;
        animation: floatEmoji 3s ease-in-out infinite;
    }
    @keyframes floatEmoji {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .notif-empty h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.3rem;
        color: #1e293b;
        margin: 0 0 8px;
    }
    .notif-empty p {
        color: #94a3b8;
        margin: 0 0 25px;
        font-size: 0.9rem;
    }
    .notif-empty a {
        display: inline-block;
        background: #000;
        color: #fff;
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .notif-empty a:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    /* Notification Cards */
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-width: 800px;
        margin: 0 auto;
    }

    .notif-card {
        background: #fff;
        border-radius: 18px;
        padding: 0;
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        border: 1px solid rgba(0,0,0,0.04);
        animation: cardSlideIn 0.4s ease-out backwards;
    }
    .notif-card:nth-child(1) { animation-delay: 0.05s; }
    .notif-card:nth-child(2) { animation-delay: 0.1s; }
    .notif-card:nth-child(3) { animation-delay: 0.15s; }
    .notif-card:nth-child(4) { animation-delay: 0.2s; }
    .notif-card:nth-child(5) { animation-delay: 0.25s; }

    @keyframes cardSlideIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .notif-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.08);
    }

    .notif-card.unread {
        border-left: 4px solid #000;
        background: #fefffe;
    }

    .notif-card-inner {
        display: flex;
        gap: 18px;
        padding: 24px 28px;
        align-items: flex-start;
    }

    /* Type Indicator Strip */
    .notif-type-strip {
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 4px;
        border-radius: 0 0 0 4px;
    }
    .notif-type-strip.promo { background: linear-gradient(90deg, #f59e0b, #eab308); }
    .notif-type-strip.alert { background: linear-gradient(90deg, #ef4444, #f97316); }
    .notif-type-strip.info  { background: linear-gradient(90deg, #3b82f6, #6366f1); }

    /* Icon */
    .notif-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
        position: relative;
    }
    .notif-icon-wrap.promo { background: linear-gradient(135deg, #fef3c7, #fde68a); }
    .notif-icon-wrap.alert { background: linear-gradient(135deg, #fee2e2, #fecaca); }
    .notif-icon-wrap.info  { background: linear-gradient(135deg, #dbeafe, #bfdbfe); }

    .notif-icon-wrap .unread-dot {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        background: #000;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    /* Content */
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    .notif-content h3 {
        font-family: 'Kanit', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 6px;
        line-height: 1.4;
    }
    .notif-card.unread .notif-content h3 {
        font-weight: 700;
    }
    .notif-content .notif-msg {
        color: #475569;
        font-size: 0.9rem;
        line-height: 1.65;
        margin: 0 0 14px;
        font-family: 'Kanit', sans-serif;
    }

    /* Footer */
    .notif-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .notif-time {
        font-size: 0.78rem;
        color: #94a3b8;
        font-family: 'Outfit', sans-serif;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .notif-time svg {
        width: 13px;
        height: 13px;
        opacity: 0.5;
    }
    .notif-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        color: #334155;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
        font-family: 'Kanit', sans-serif;
        transition: all 0.25s;
    }
    .notif-action:hover {
        background: #000;
        color: #fff;
    }
    .notif-action svg {
        width: 12px;
        height: 12px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .notif-hero { flex-direction: column; align-items: flex-start; }
        .notif-hero-left h1 { font-size: 2rem; }
        .notif-card-inner { padding: 18px 20px; gap: 14px; }
        .notif-list { gap: 10px; }
    }
</style>

<div class="notif-page">
    <div class="container">
        <!-- Hero Header -->
        <div class="notif-hero">
            <div class="notif-hero-left">
                <h1><?= __('notifications') ?></h1>
                <p>
                    <?php if ($unreadCount > 0): ?>
                        <?= __('notif_unread_msg') ?>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                            <?= $unreadCount ?> <?= __('notif_items') ?>
                        </span>
                    <?php else: ?>
                        <?= __('notif_all_read') ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="notif-mark-all" style="border:none; cursor:pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?= __('mark_all_read') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notification List -->
        <div class="notif-list">
            <?php if (empty($notifications)): ?>
                <div class="notif-empty">
                    <div class="notif-empty-icon">🔔</div>
                    <h3><?= __('no_notifications') ?></h3>
                    <p><?= __('notif_empty_desc') ?></p>
                    <a href="shop.php"><?= __('continue_shopping_btn') ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $idx => $notif): ?>
                    <?php
                        $typeClass = 'info';
                        $icon = 'ℹ️';
                        if ($notif['type'] === 'promo') { $typeClass = 'promo'; $icon = '🎉'; }
                        if ($notif['type'] === 'alert') { $typeClass = 'alert'; $icon = '⚠️'; }
                        
                        // Relative time
                        $diff = time() - strtotime($notif['created_at']);
                        if ($diff < 60) $timeAgo = __('notif_just_now');
                        elseif ($diff < 3600) $timeAgo = floor($diff/60) . __('notif_min_ago');
                        elseif ($diff < 86400) $timeAgo = floor($diff/3600) . __('notif_hours_ago');
                        elseif ($diff < 604800) $timeAgo = floor($diff/86400) . __('notif_days_ago');
                        else $timeAgo = date('d M Y', strtotime($notif['created_at']));
                    ?>
                    <div class="notif-card <?= !$notif['is_read'] ? 'unread' : '' ?>" style="animation-delay: <?= min($idx * 0.05, 0.3) ?>s;">
                        <div class="notif-type-strip <?= $typeClass ?>"></div>
                        <div class="notif-card-inner">
                            <div class="notif-icon-wrap <?= $typeClass ?>">
                                <?= $icon ?>
                                <?php if (!$notif['is_read']): ?>
                                    <div class="unread-dot"></div>
                                <?php endif; ?>
                            </div>
                            <div class="notif-content">
                                <h3><?= htmlspecialchars($notif['title']) ?></h3>
                                <p class="notif-msg"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                                <div class="notif-footer">
                                    <span class="notif-time">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <?= $timeAgo ?>
                                    </span>
                                    <?php if (!$notif['is_read']): ?>
                                        <a href="notifications.php?view=<?= $notif['id'] ?>" class="notif-action">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <?= __('mark_read') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
