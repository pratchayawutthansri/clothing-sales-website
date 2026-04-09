<?php
require_once 'includes/init.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = "Security Check Failed: Invalid Token";
    } else {
        $username_or_email = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username_or_email) || empty($password)) {
            $error = "Please enter both username/email and password.";
        } else {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Prevent Session Fixation
                session_regenerate_id(true);
                
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Set CSRF token again for the new session
                generateCsrfToken();
                
                // Redirect user
                if ($user['role'] === 'admin') {
                    // Redirect to Admin Login to require the 2nd step authentication
                    redirect('admin/login.php');
                } else {
                    // Check if they came from checkout
                    $redirect_to = isset($_GET['redirect']) && $_GET['redirect'] === 'checkout' ? 'checkout.php' : 'index.php';
                    redirect($redirect_to);
                }
            } else {
                $error = "Invalid username or password.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 100px 0; min-height: 80vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%; max-width: 450px;">
        <h2 style="text-align: center; margin-bottom: 10px; font-family: 'Outfit', sans-serif; font-size: 2rem; color: #000;"><?= __('login_title') ?></h2>
        <p style="text-align: center; color: #666; margin-bottom: 30px;"><?= __('login_subtitle') ?></p>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 0.95rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; color: #000;"><?= __('username_email') ?> <span style="color:red">*</span></label>
                <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" style="background: #fff; color: #000; width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#000'" onblur="this.style.borderColor='#e5e7eb'">
            </div>

            <div class="form-group" style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.95rem; color: #000;"><?= __('password') ?> <span style="color:red">*</span></label>
                <input type="password" name="password" required style="background: #fff; color: #000; width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#000'" onblur="this.style.borderColor='#e5e7eb'">
            </div>

            <button type="submit" style="width: 100%; background: #000; color: #fff; border: none; padding: 15px; border-radius: 8px; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 1rem; letter-spacing: 1px; text-transform: uppercase; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#333'" onmouseout="this.style.background='#000'">
                <?= __('btn_login') ?>
            </button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="forgot_password.php" style="color: #666; font-size: 0.9rem; text-decoration: underline; transition: color 0.3s;" onmouseover="this.style.color='#000'" onmouseout="this.style.color='#666'"><?= __('forgot_password_link') ?? 'Forgot Password?' ?></a>
            </div>
        </form>

        <p style="text-align: center; margin-top: 25px; color: #666; font-size: 0.95rem;">
            <?= __('no_account') ?> 
            <a href="register.php" style="color: #000; font-weight: 600; text-decoration: underline; transition: color 0.3s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#000'"><?= __('register_here') ?></a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
