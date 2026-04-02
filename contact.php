<?php
require_once 'includes/init.php';
require_once 'includes/header.php';
?>

<style>
    .contact-hero {
        height: 40vh;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }
    .contact-title {
        font-family: 'Outfit', sans-serif;
        font-size: 4rem;
        margin-bottom: 10px;
    }
    .contact-subtitle {
        font-family: 'Outfit', sans-serif;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #888;
    }
    
    .contact-container {
        max-width: 1000px;
        margin: -50px auto 100px;
        background: white;
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        display: grid;
        grid-template-columns: 1fr 1fr;
        position: relative;
        z-index: 10;
        overflow: hidden;
    }
    
    .contact-info {
        padding: 60px;
        background: #1a1a1a;
        color: white;
    }
    .info-item {
        margin-bottom: 40px;
    }
    .info-label {
        font-family: 'Outfit', sans-serif;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 10px;
        font-weight: 700;
    }
    .info-value {
        font-family: 'Outfit', sans-serif;
        font-size: 1.5rem;
    }
    .social-links a {
        color: white;
        margin-right: 20px;
        font-size: 1.2rem;
        text-decoration: none;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        padding-bottom: 5px;
        transition: 0.3s;
    }
    .social-links a:hover {
        border-color: white;
    }
    
    .contact-form-wrapper {
        padding: 60px;
    }
    .form-group { margin-bottom: 25px; }
    .form-group label {
        display: block;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 10px;
        letter-spacing: 1px;
    }
    .form-control {
        width: 100%;
        padding: 15px;
        border: 1px solid #eee;
        background: #f9f9f9;
        font-family: 'Kanit', sans-serif;
        transition: 0.3s;
    }
    .form-control:focus {
        background: white;
        border-color: #000;
        outline: none;
    }
    .btn-send {
        background: #000;
        color: white;
        border: none;
        padding: 15px 40px;
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        cursor: pointer;
        width: 100%;
        transition: 0.3s;
    }
    .btn-send:hover {
        background: #333;
    }
    
    @media (max-width: 768px) {
        .contact-container { grid-template-columns: 1fr; margin: 0; box-shadow: none; }
        .contact-hero { height: 30vh; }
        .contact-title { font-size: 2.5rem; }
    }
</style>

<div class="contact-hero">
    <div>
        <h1 class="contact-title">Get In Touch</h1>
        <div class="contact-subtitle">We'd love to hear from you</div>
    </div>
</div>

<div class="contact-container" style="grid-template-columns: 1fr;">
    <div class="contact-info">
        <div class="info-item">
            <div class="info-label">ที่อยู่ (Address)</div>
            <div class="info-value">Siam Square One<br>Bangkok, Thailand</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">ติดต่อ (Contact)</div>
            <div class="info-value">hello@xivex.com<br>02-123-4567</div>
        </div>
        
        <div class="info-item">
            <div class="info-label">โซเชียลมีเดีย (Socials)</div>
            <div class="social-links" style="margin-top: 15px;">
                <a href="#">Instagram</a>
                <a href="#">TikTok</a>
                <a href="#">Line OA</a>
            </div>
        </div>
    </div>
</div>

<!-- Map -->
<div style="height: 400px; filter: grayscale(100%);">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.645677763874!2d100.53265731483036!3d13.739832990355412!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x30e29ed016b8b0f1%3A0xe5a3e13d9405F2a2!2sSiam%20Square%20One!5e0!3m2!1sen!2sth!4v1645431234567!5m2!1sen!2sth" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
</div>

<?php require_once 'includes/footer.php'; ?>
