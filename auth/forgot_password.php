<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';
require_once '../includes/MailService.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $database = new Database();
            $db       = $database->getConnection();

            $stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Delete any existing unused tokens for this user
                $db->prepare("DELETE FROM password_reset_tokens WHERE user_id = ? AND used_at IS NULL")->execute([$user['id']]);

                // Generate secure token
                $token     = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $ins = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $ins->execute([$user['id'], $token, $expiresAt]);

                $reset_url = APP_URL . '/auth/reset_password.php?token=' . $token;
                MailService::sendPasswordReset($user['email'], $user['username'], $reset_url);
            }

            // Always show success (don't reveal whether email exists)
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f1f5f9; min-height: 100vh;
            display: flex; align-items: stretch;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .auth-panel-left {
            display: none; width: 44%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            flex-direction: column; align-items: center; justify-content: center;
            padding: 48px 40px; text-align: center; position: relative; overflow: hidden;
        }
        @media(min-width:900px){ .auth-panel-left { display: flex; } }
        .auth-panel-left::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 30% 40%, rgba(255,255,255,.04) 0%, transparent 60%);
        }
        .auth-left-icon  { font-size: 64px; margin-bottom: 24px; position: relative; z-index: 1; }
        .auth-left-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 10px; position: relative; z-index: 1; }
        .auth-left-sub   { font-size: .9rem; color: rgba(255,255,255,.6); line-height: 1.6; max-width: 300px; position: relative; z-index: 1; }

        .auth-panel-right {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 32px 20px; background: #f1f5f9;
        }
        .auth-card {
            background: #fff; border-radius: 16px; padding: 40px 36px;
            width: 100%; max-width: 420px;
            box-shadow: 0 4px 24px rgba(15,23,42,.08);
            border: 1px solid #e2e8f0;
        }

        .auth-mobile-logo { text-align: center; margin-bottom: 24px; }
        .auth-mobile-logo img { height: 44px; }
        @media(min-width:900px){ .auth-mobile-logo { display: none; } }

        .auth-back { display: inline-flex; align-items: center; gap: 6px; color: #64748b; text-decoration: none; font-size: .84rem; margin-bottom: 20px; }
        .auth-back:hover { color: #0f172a; }

        .auth-card-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .auth-card-sub   { font-size: .86rem; color: #64748b; margin-bottom: 28px; line-height: 1.5; }

        .auth-alert { border-radius: 8px; padding: 11px 14px; font-size: .85rem; margin-bottom: 18px; }
        .auth-alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .auth-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #059669; }

        .auth-success-icon { text-align: center; font-size: 48px; margin-bottom: 16px; }
        .auth-success-body { text-align: center; }
        .auth-success-body h2 { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .auth-success-body p { font-size: .88rem; color: #64748b; line-height: 1.6; margin-bottom: 20px; }

        .auth-form-group { margin-bottom: 18px; }
        .auth-form-group label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .auth-input-wrap { position: relative; }
        .auth-input {
            width: 100%; padding: 10px 12px;
            border: 1.5px solid #d1d5db; border-radius: 9px;
            font-size: .92rem; color: #111827; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .auth-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,.07); }

        .auth-btn {
            width: 100%; padding: 11px; border: none; border-radius: 9px; cursor: pointer;
            font-size: .95rem; font-weight: 700;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            color: #fff; transition: opacity .2s;
        }
        .auth-btn:hover { opacity: .9; }
        .auth-btn:disabled { opacity: .6; cursor: not-allowed; }
        .auth-btn-ghost {
            display: block; width: 100%; padding: 10px; border: 1.5px solid #d1d5db;
            border-radius: 9px; background: #fff; color: #374151; font-size: .9rem;
            font-weight: 600; cursor: pointer; text-align: center; text-decoration: none;
            margin-top: 10px; transition: border-color .2s;
        }
        .auth-btn-ghost:hover { border-color: #0f172a; }
    </style>
</head>
<body>

    <div class="auth-panel-left">
        <div class="auth-left-icon">🔐</div>
        <div class="auth-left-title">Password Reset</div>
        <div class="auth-left-sub">Enter the email address associated with your account and we'll send you a reset link.</div>
    </div>

    <div class="auth-panel-right">
        <div class="auth-card">

            <div class="auth-mobile-logo">
                <img src="../img/KConsultingLogo.png" alt="KConsulting">
            </div>

            <?php if ($success): ?>
                <div class="auth-success-icon">📧</div>
                <div class="auth-success-body">
                    <h2>Check your email</h2>
                    <p>If an account exists for that email address, we've sent a password reset link. It expires in <strong>1 hour</strong>.</p>
                    <p style="font-size:.82rem;color:#94a3b8;">Don't see it? Check your spam folder.</p>
                    <a href="login.php" class="auth-btn-ghost" style="display:block;margin-top:20px;">Back to Sign In</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="auth-back">← Back to Sign In</a>
                <h1 class="auth-card-title">Forgot password?</h1>
                <p class="auth-card-sub">Enter your account email and we'll send you a reset link.</p>

                <?php if ($error): ?>
                <div class="auth-alert auth-alert-error">⚠️ <?= Security::escapeHTML($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="fpForm">
                    <?= Security::getCSRFTokenField() ?>
                    <div class="auth-form-group">
                        <label for="email">Email address</label>
                        <div class="auth-input-wrap">
                            <input type="email" id="email" name="email" class="auth-input"
                                   value="<?= Security::escapeHTML($_POST['email'] ?? '') ?>"
                                   placeholder="you@example.com" autocomplete="email" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn" id="fpBtn">Send Reset Link</button>
                    <a href="login.php" class="auth-btn-ghost">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const form = document.getElementById('fpForm');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('fpBtn');
            btn.disabled = true;
            btn.textContent = 'Sending…';
        });
    }
    </script>
</body>
</html>
