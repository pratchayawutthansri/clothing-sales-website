<?php
require_once 'includes/init.php';
require_once 'includes/header.php';

$heroImg  = "https://images.unsplash.com/photo-1523381210434-271e8be1f52b?q=80&w=2000&auto=format&fit=crop";
$storyImg = "https://images.unsplash.com/photo-1556905055-8f358a7a47b2?q=80&w=2000&auto=format&fit=crop";
?>

<!-- About Page — Premium Streetwear Editorial -->
<style>

    .about-page { background: #faf9f7; }

    /* ── HERO ── */
    .ab-hero {
        min-height: 92vh;
        display: grid;
        grid-template-columns: 1fr 1fr;
        overflow: hidden;
        margin-top: -1px;
    }
    .ab-hero-left {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 120px 72px 80px;
        background: #faf9f7;
    }
    .ab-eyebrow {
        font-family: 'Outfit', monospace;
        font-size: 0.7rem;
        letter-spacing: 0.35em;
        text-transform: uppercase;
        color: #999;
        margin-bottom: 28px;
        opacity: 0;
        animation: abSlideUp 0.7s ease forwards 0.2s;
    }
    .ab-hero-left h1 {
        font-family: 'Outfit', sans-serif !important;
        font-size: clamp(3.5rem, 6vw, 6rem) !important;
        font-weight: 800 !important;
        font-style: normal !important;
        line-height: 0.95 !important;
        letter-spacing: -0.04em !important;
        color: #0a0a0a !important;
        text-transform: uppercase !important;
        margin-bottom: 36px;
        opacity: 0;
        animation: abSlideUp 0.8s ease forwards 0.35s;
    }
    .ab-hero-sub {
        font-size: 1rem;
        font-weight: 300;
        color: #666;
        line-height: 1.85;
        max-width: 400px;
        margin-bottom: 48px;
        opacity: 0;
        animation: abSlideUp 0.8s ease forwards 0.5s;
    }
    .ab-hero-cta {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        width: fit-content;
        font-family: 'Outfit', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: #0a0a0a;
        text-decoration: none;
        padding: 16px 36px;
        border: 2px solid #0a0a0a;
        border-radius: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        animation: abSlideUp 0.8s ease forwards 0.65s;
    }
    .ab-hero-cta:hover {
        background: #0a0a0a;
        color: #fff;
    }
    .ab-hero-cta svg {
        transition: transform 0.3s;
    }
    .ab-hero-cta:hover svg {
        transform: translateX(4px);
    }
    .ab-hero-right {
        overflow: hidden;
        position: relative;
    }
    .ab-hero-right img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center top;
        filter: saturate(0.85);
        transition: transform 6s ease;
    }
    .ab-hero:hover .ab-hero-right img { transform: scale(1.04); }

    /* ── STATS TICKER ── */
    .ab-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        border-top: 1px solid #e8e4dd;
        border-bottom: 1px solid #e8e4dd;
        background: #fff;
    }
    .ab-stat {
        padding: 48px 40px;
        text-align: center;
        border-right: 1px solid #e8e4dd;
        transition: background 0.3s ease;
    }
    .ab-stat:last-child { border-right: none; }
    .ab-stat:hover { background: #faf9f7; }
    .ab-stat-num {
        font-family: 'Kanit', sans-serif;
        font-size: 2.8rem;
        font-weight: 300;
        font-style: italic;
        color: #0a0a0a;
        line-height: 1;
        margin-bottom: 8px;
    }
    .ab-stat-label {
        font-family: 'Outfit', sans-serif;
        font-size: 0.7rem;
        font-weight: 500;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #999;
    }

    /* ── STORY ── */
    .ab-story {
        padding: 120px 72px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: center;
        background: #faf9f7;
    }
    .ab-label {
        font-family: 'Outfit', monospace;
        font-size: 0.65rem;
        letter-spacing: 0.35em;
        text-transform: uppercase;
        color: #b8a080;
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .ab-label::before {
        content: '';
        width: 40px;
        height: 1px;
        background: #b8a080;
    }
    .ab-story h2 {
        font-family: 'Kanit', sans-serif !important;
        font-size: clamp(2rem, 3.5vw, 3.2rem) !important;
        font-weight: 300 !important;
        font-style: italic !important;
        line-height: 1.15 !important;
        letter-spacing: -0.01em !important;
        text-transform: none !important;
        color: #0a0a0a !important;
        margin-bottom: 32px;
    }
    .ab-story p {
        font-size: 0.95rem;
        font-weight: 300;
        color: #555;
        line-height: 1.95;
        margin-bottom: 18px;
    }
    .ab-img-wrap {
        overflow: hidden;
        border-radius: 2px;
        position: relative;
    }
    .ab-img-wrap::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,0.08) 100%);
        pointer-events: none;
    }
    .ab-img-wrap img {
        width: 100%;
        aspect-ratio: 4/5;
        object-fit: cover;
        filter: saturate(0.85) contrast(1.03);
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .ab-img-wrap:hover img { transform: scale(1.04); }

    /* ── VALUES ── */
    .ab-values {
        padding: 120px 72px;
        background: #0a0a0a;
        color: #fff;
    }
    .ab-values-top {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: end;
        margin-bottom: 80px;
    }
    .ab-values-top h2 {
        font-family: 'Kanit', sans-serif !important;
        font-size: clamp(2.2rem, 4.5vw, 4.5rem) !important;
        font-weight: 300 !important;
        font-style: italic !important;
        line-height: 1.05 !important;
        text-transform: none !important;
        color: #fff !important;
    }
    .ab-values-top p {
        font-size: 0.95rem;
        font-weight: 300;
        color: rgba(255,255,255,0.5);
        line-height: 1.9;
    }
    .ab-values-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        border-top: 1px solid rgba(255,255,255,0.12);
    }
    .ab-val {
        padding: 56px 48px 56px 0;
        border-right: 1px solid rgba(255,255,255,0.08);
        transition: opacity 0.4s ease;
    }
    .ab-val:nth-child(2) { padding-left: 48px; }
    .ab-val:last-child {
        border-right: none;
        padding-right: 0;
        padding-left: 48px;
    }
    .ab-values-list:hover .ab-val       { opacity: 0.3; }
    .ab-values-list:hover .ab-val:hover { opacity: 1; }
    .ab-val-num {
        font-family: 'Outfit', monospace;
        font-size: 0.65rem;
        letter-spacing: 0.25em;
        color: rgba(255,255,255,0.25);
        margin-bottom: 32px;
    }
    .ab-val h3 {
        font-family: 'Kanit', sans-serif !important;
        font-size: 1.55rem !important;
        font-weight: 400 !important;
        font-style: italic !important;
        text-transform: none !important;
        color: #fff !important;
        margin-bottom: 14px;
    }
    .ab-val p {
        font-size: 0.85rem;
        font-weight: 300;
        color: rgba(255,255,255,0.45);
        line-height: 1.85;
    }

    /* ── MISSION / CTA ── */
    .ab-mission {
        padding: 140px 72px;
        background: #faf9f7;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .ab-mission::before {
        content: 'XIVEX';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-family: 'Outfit', sans-serif;
        font-size: 18vw;
        font-weight: 900;
        color: rgba(0,0,0,0.025);
        pointer-events: none;
        white-space: nowrap;
    }
    .ab-mission blockquote {
        font-family: 'Kanit', sans-serif;
        font-size: clamp(1.8rem, 4.5vw, 4rem);
        font-weight: 300;
        font-style: italic;
        line-height: 1.25;
        letter-spacing: -0.01em;
        color: #0a0a0a;
        max-width: 850px;
        margin: 0 auto 56px;
        position: relative;
        z-index: 1;
    }
    .ab-cta-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-family: 'Outfit', sans-serif;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: #fff;
        text-decoration: none;
        background: #0a0a0a;
        padding: 18px 48px;
        border-radius: 0;
        transition: all 0.4s ease;
        position: relative;
        z-index: 1;
    }
    .ab-cta-link:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.15);
    }

    /* ── SCROLL REVEAL ── */
    .ab-r {
        opacity: 0;
        transform: translateY(35px);
        transition: opacity 0.8s cubic-bezier(0.16,1,0.3,1),
                    transform 0.8s cubic-bezier(0.16,1,0.3,1);
    }
    .ab-r.on { opacity: 1; transform: none; }
    .ab-d1 { transition-delay: 0.1s; }
    .ab-d2 { transition-delay: 0.2s; }
    .ab-d3 { transition-delay: 0.3s; }
    .ab-d4 { transition-delay: 0.4s; }

    /* ── KEYFRAMES ── */
    @keyframes abSlideUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: none; }
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
        .ab-hero {
            grid-template-columns: 1fr;
            min-height: auto;
        }
        .ab-hero-left {
            padding: 100px 28px 60px;
            order: 2;
        }
        .ab-hero-right {
            height: 55vw;
            min-height: 280px;
            order: 1;
        }
        .ab-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .ab-stat {
            padding: 32px 24px;
        }
        .ab-stat:nth-child(2) { border-right: none; }
        .ab-stat:nth-child(3),
        .ab-stat:nth-child(4) { border-top: 1px solid #e8e4dd; }
        .ab-stat-num { font-size: 2.2rem; }
        .ab-story {
            grid-template-columns: 1fr;
            gap: 48px;
            padding: 80px 28px;
        }
        .ab-values { padding: 80px 28px; }
        .ab-values-top {
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 48px;
        }
        .ab-values-list { grid-template-columns: 1fr; }
        .ab-val,
        .ab-val:nth-child(2),
        .ab-val:last-child {
            padding: 40px 0;
            border-right: none;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .ab-val:last-child { border-bottom: none; }
        .ab-mission { padding: 80px 28px; }
        .ab-mission blockquote { font-size: clamp(1.5rem, 6vw, 2.5rem); }
    }
</style>

<div class="about-page">

    <!-- ── HERO ── -->
    <section class="ab-hero">
        <div class="ab-hero-left">
            <p class="ab-eyebrow">2025 — BANGKOK</p>
            <h1><?= __('abt_hero_h1') ?></h1>
            <p class="ab-hero-sub"><?= __('abt_hero_p') ?></p>
            <a href="shop.php" class="ab-hero-cta">
                Shop Collection
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
        <div class="ab-hero-right">
            <img src="<?= $heroImg ?>" alt="XIVEX Streetwear">
        </div>
    </section>

    <!-- ── STATS TICKER ── -->
    <section class="ab-stats">
        <div class="ab-stat ab-r">
            <div class="ab-stat-num">6+</div>
            <div class="ab-stat-label"><?= __('abt_stat_collections') ?? 'Collections' ?></div>
        </div>
        <div class="ab-stat ab-r ab-d1">
            <div class="ab-stat-num">100%</div>
            <div class="ab-stat-label"><?= __('abt_stat_cotton') ?? 'Premium Cotton' ?></div>
        </div>
        <div class="ab-stat ab-r ab-d2">
            <div class="ab-stat-num">BKK</div>
            <div class="ab-stat-label"><?= __('abt_stat_designed') ?? 'Designed In' ?></div>
        </div>
        <div class="ab-stat ab-r ab-d3">
            <div class="ab-stat-num">∞</div>
            <div class="ab-stat-label"><?= __('abt_stat_style') ?? 'Unique Styles' ?></div>
        </div>
    </section>
    
    <!-- ── STORY ── -->
    <section class="ab-story">
        <div class="ab-r">
            <p class="ab-label"><?= __('abt_vision_label') ?? 'เรื่องราวของเรา' ?></p>
            <h2><?= __('abt_vision_h2') ?></h2>
            <p><?= __('abt_vision_p') ?></p>
            <p><?= __('abt_craft_p') ?></p>
        </div>
        <div class="ab-img-wrap ab-r ab-d1">
            <img src="<?= $storyImg ?>" alt="<?= __('abt_vision_h2') ?>" loading="lazy">
        </div>
    </section>

    <!-- ── VALUES (Dark Section) ── -->
    <section class="ab-values">
        <div class="ab-values-top ab-r">
            <h2><?= __('abt_values_h2') ?></h2>
            <p><?= __('abt_values_sub') ?? 'ทุกการตัดสินใจใน XIVEX วัดจากหลักการเดียวกัน — ถ้าไม่ดีพอสำหรับเรา มันก็ไม่ดีพอสำหรับคุณ' ?></p>
        </div>
        <div class="ab-values-list">
            <div class="ab-val ab-r ab-d1">
                <p class="ab-val-num">01</p>
                <h3><?= __('abt_val_1_title') ?></h3>
                <p><?= __('abt_val_1_desc') ?></p>
            </div>
            <div class="ab-val ab-r ab-d2">
                <p class="ab-val-num">02</p>
                <h3><?= __('abt_val_2_title') ?></h3>
                <p><?= __('abt_val_2_desc') ?></p>
            </div>
            <div class="ab-val ab-r ab-d3">
                <p class="ab-val-num">03</p>
                <h3><?= __('abt_val_3_title') ?></h3>
                <p><?= __('abt_val_3_desc') ?></p>
            </div>
        </div>
    </section>

    <!-- ── MISSION / CTA ── -->
    <section class="ab-mission ab-r">
        <blockquote><?= __('abt_cta_h2') ?></blockquote>
        <a href="shop.php" class="ab-cta-link">
            <?= __('abt_cta_btn') ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </section>

</div>

<script>
    const io = new IntersectionObserver(
        entries => entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('on'); }),
        { threshold: 0.1 }
    );
    document.querySelectorAll('.ab-r').forEach(el => io.observe(el));
</script>

<?php require_once 'includes/footer.php'; ?>