<?php
// forgot_password.php
// Step 1: User enters their email → generate a token → "send" reset link
session_start();
require_once '../db.php';

// ── Auto-create password_resets table if it doesn't exist ──────────────────
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) NOT NULL,
    token       VARCHAR(64)  NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'error';
    } else {
        $safe_email = mysqli_real_escape_string($conn, $email);

        // Check if email exists in users table
        $user_q = $conn->query("SELECT id, fullname FROM users WHERE email = '$safe_email' LIMIT 1");

        if ($user_q && $user_q->num_rows > 0) {
            $user = $user_q->fetch_assoc();

            // Delete any old unused tokens for this email
            $conn->query("DELETE FROM password_resets WHERE email = '$safe_email'");

            // Generate a secure 64-char token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $conn->query("INSERT INTO password_resets (email, token, expires_at)
                          VALUES ('$safe_email', '$token', '$expires')");

            // Log it
            $name = $user['fullname'];
            $conn->query("INSERT INTO system_logs (log_message) VALUES ('Password reset requested for: $safe_email')");

            // ── RESET LINK ──────────────────────────────────────────────────
            // In a real production system you would email this link.
            // Since this runs on XAMPP (no mail server), we display it on screen.
            // Replace the base URL below with your actual domain when deploying.
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                        . '://' . $_SERVER['HTTP_HOST'];
            $reset_link = $base_url . '/DreamBoundBookStrore_System/Auth/reset_password.php?token=' . $token;

            // Store link in session to show on success screen
            $_SESSION['reset_link']  = $reset_link;
            $_SESSION['reset_email'] = $email;

            header("Location: forgot_password.php?sent=1");
            exit();

        } else {
            // Don't reveal whether email exists (security best practice)
            // Show same "check your email" message regardless
            $_SESSION['reset_link']  = null; // no link — email not found
            $_SESSION['reset_email'] = $email;
            header("Location: forgot_password.php?sent=1");
            exit();
        }
    }
}

$show_sent = isset($_GET['sent']);
$reset_link  = $_SESSION['reset_link']  ?? null;
$reset_email = $_SESSION['reset_email'] ?? '';
if ($show_sent) {
    unset($_SESSION['reset_link'], $_SESSION['reset_email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dreambound Bookstore – Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Englebert&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Englebert', sans-serif; }
        body { background-color: #0A2647; display: flex; min-height: 100vh; overflow: hidden; }
        .sidebar { width: 20%; min-width: 220px; background-color: #0A2647; display: flex; align-items: center; justify-content: center; }
        .sidebar-logo { text-align: center; color: white; }
        .sidebar-logo img { width: 80px; margin-bottom: 12px; opacity: 0.85; }
        .sidebar-logo span { font-size: 20px; letter-spacing: 2px; display: block; color: #FC9D01; }
        .main-content { flex: 1; background-color: #FC9D01; border-top-left-radius: 30px; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .card { background: #fff; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); overflow: hidden; padding: 38px 36px 30px; text-align: center; }
        .icon-container { width: 58px; height: 58px; background-color: #0A2647; color: #FC9D01; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 22px; font-size: 22px; }
        h2 { color: #0A2647; font-size: 28px; margin-bottom: 10px; }
        .subtitle { color: #64748b; font-size: 16px; margin-bottom: 26px; line-height: 1.5; }
        .input-wrap { position: relative; margin-bottom: 20px; }
        .input-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; }
        input[type="email"] { width: 100%; padding: 14px 16px 14px 44px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 16px; color: #0A2647; outline: none; font-family: 'Englebert', sans-serif; transition: border-color 0.2s; }
        input[type="email"]:focus { border-color: #0A2647; box-shadow: 0 0 0 3px rgba(10,38,71,0.08); }
        .btn-group { border-top: 1px solid #f1f5f9; padding-top: 20px; display: flex; gap: 12px; }
        .btn { flex: 1; padding: 13px; border-radius: 10px; font-size: 16px; cursor: pointer; font-family: 'Englebert', sans-serif; transition: all 0.2s; text-decoration: none; text-align: center; border: none; display: inline-block; }
        .btn-cancel { background: #fff; border: 1.5px solid #e2e8f0; color: #64748b; }
        .btn-cancel:hover { background: #f8fafc; }
        .btn-reset { background: #0A2647; color: #FC9D01; }
        .btn-reset:hover { background: #071c35; }
        .error-box { background: #fee2e2; border: 1px solid #fca5a5; color: #dc2626; padding: 12px 16px; border-radius: 10px; font-size: 15px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; }
        /* Sent state */
        .sent-icon { width: 70px; height: 70px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 28px; color: #10b981; }
        .reset-link-box { background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 14px 16px; margin: 18px 0; text-align: left; word-break: break-all; }
        .reset-link-box p { font-size: 13px; color: #64748b; margin-bottom: 8px; }
        .reset-link-box a { color: #0A2647; font-size: 14px; font-weight: bold; text-decoration: none; }
        .reset-link-box a:hover { text-decoration: underline; }
        .dev-note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; font-size: 13px; color: #92400e; margin-bottom: 18px; text-align: left; }
        .dev-note strong { display: block; margin-bottom: 4px; }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { display: none; }
            .main-content { border-top-left-radius: 0; padding: 40px 20px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="../img/logo1.png" alt="Dreambound Logo" onerror="this.style.display='none'">
            <span>DREAMBOUND</span>
        </div>
    </div>

    <div class="main-content">
        <div class="card">

        <?php if ($show_sent): ?>

            <!-- ── SUCCESS SCREEN ── -->
            <div class="sent-icon"><i class="fas fa-envelope-open-text"></i></div>
            <h2>Check your email</h2>
            <p class="subtitle">If <strong><?php echo htmlspecialchars($reset_email); ?></strong> is registered, a reset link has been sent.</p>

            <?php if ($reset_link): ?>
            <div class="dev-note">
                <strong><i class="fas fa-code"></i> Developer / XAMPP Mode</strong>
                This app has no email server configured. Use the link below to reset the password directly:
            </div>
            <div class="reset-link-box">
                <p>Your password reset link:</p>
                <a href="<?php echo htmlspecialchars($reset_link); ?>">
                    <?php echo htmlspecialchars($reset_link); ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="btn-group" style="border-top:none; padding-top:0;">
                <a href="forgot_password.php" class="btn btn-cancel">Try again</a>
                <a href="login.php" class="btn btn-reset"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>

        <?php else: ?>

            <!-- ── FORM SCREEN ── -->
            <div class="icon-container"><i class="fas fa-lock"></i></div>
            <h2>Forgot password?</h2>
            <p class="subtitle">No worries! Enter your registered email and we'll send you a reset link.</p>

            <?php if (!empty($message)): ?>
            <div class="error-box"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email address" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="btn-group">
                    <a href="login.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-reset"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
                </div>
            </form>

        <?php endif; ?>

        </div>
    </div>
</body>
</html>
