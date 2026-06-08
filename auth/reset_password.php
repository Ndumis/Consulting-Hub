<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$database = new Database();
$db       = $database->getConnection();

$token   = trim($_GET['token'] ?? '');
$error   = '';
$success = false;

// Validate token on every load
$tokenRow = null;
if ($token) {
    $stmt = $db->prepare("
        SELECT prt.id, prt.user_id, prt.expires_at, prt.used_at, u.username, u.email
        FROM password_reset_tokens prt
        JOIN users u ON u.id = prt.user_id
        WHERE prt.token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$tokenValid = $tokenRow
    && $tokenRow['used_at'] === null
    && strtotime($tokenRow['expires_at']) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (!$tokenValid) {
        $error = 'This reset link is invalid or has expired.';
    } else {
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Update password
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $tokenRow['user_id']]);

            // Mark token used
            $db->prepare("UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?")->execute([$tokenRow['id']]);

            // Log activity
            try {
                $_SESSION['user_id']  = $tokenRow['user_id'];
                $_SESSION['username'] = $tokenRow['username'];
                Utils::logActivity($db, 'password_reset', "Password reset completed for '{$tokenRow['username']}'");
                unset($_SESSION['user_id'], $_SESSION['username']);
            } catch (Exception $e) {}

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
    <title>Reset Password — KConsulting Hub</title>
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

        .auth-card-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .auth-card-sub   { font-size: .86rem; color: #64748b; margin-bottom: 28px; line-height: 1.5; }

        .auth-alert { border-radius: 8px; padding: 11px 14px; font-size: .85rem; margin-bottom: 18px; }
        .auth-alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

        .auth-invalid { text-align: center; padding: 16px 0; }
        .auth-invalid .icon { font-size: 48px; margin-bottom: 16px; }
        .auth-invalid h2 { font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .auth-invalid p  { font-size: .87rem; color: #64748b; line-height: 1.6; margin-bottom: 20px; }

        .auth-success-icon { text-align: center; font-size: 48px; margin-bottom: 16px; }
        .auth-success-body { text-align: center; }
        .auth-success-body h2 { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .auth-success-body p  { font-size: .87rem; color: #64748b; line-height: 1.6; margin-bottom: 20px; }

        .auth-form-group { margin-bottom: 18px; }
        .auth-form-group label { display: block; font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .auth-input-wrap { position: relative; }
        .auth-input {
            width: 100%; padding: 10px 52px 10px 12px;
            border: 1.5px solid #d1d5db; border-radius: 9px;
            font-size: .92rem; color: #111827; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .auth-input:focus { outline: none; border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,.07); }
        .auth-show-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; font-size: .8rem; padding: 2px 4px; }
        .auth-show-pw:hover { color: #374151; }

        .pw-strength { margin-top: 6px; height: 3px; border-radius: 2px; background: #e2e8f0; overflow: hidden; }
        .pw-strength-bar { height: 100%; width: 0; border-radius: 2px; transition: width .3s, background .3s; }

        .auth-hint { font-size: .78rem; color: #94a3b8; margin-top: 5px; }

        .auth-btn {
            width: 100%; padding: 11px; border: none; border-radius: 9px; cursor: pointer;
            font-size: .95rem; font-weight: 700;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            color: #fff; transition: opacity .2s; margin-top: 4px;
        }
        .auth-btn:hover { opacity: .9; }
        .auth-btn:disabled { opacity: .6; cursor: not-allowed; }
        .auth-btn-ghost {
            display: block; width: 100%; padding: 10px;
            border: 1.5px solid #d1d5db; border-radius: 9px;
            background: #fff; color: #374151; font-size: .9rem;
            font-weight: 600; cursor: pointer; text-align: center; text-decoration: none;
            margin-top: 10px; transition: border-color .2s;
        }
        .auth-btn-ghost:hover { border-color: #0f172a; }
    </style>
</head>
<body>

    <div class="auth-panel-left">
        <div class="auth-left-icon">🔑</div>
        <div class="auth-left-title">Set New Password</div>
        <div class="auth-left-sub">Choose a strong password with at least 8 characters. You'll use it to sign in going forward.</div>
    </div>

    <div class="auth-panel-right">
        <div class="auth-card">

            <div class="auth-mobile-logo">
                <img src="../img/KConsultingLogo.png" alt="KConsulting">
            </div>

            <?php if ($success): ?>
                <div class="auth-success-icon">✅</div>
                <div class="auth-success-body">
                    <h2>Password updated!</h2>
                    <p>Your password has been changed successfully. You can now sign in with your new password.</p>
                    <a href="login.php" class="auth-btn" style="display:block;text-decoration:none;">Sign In</a>
                </div>

            <?php elseif (!$token || !$tokenRow): ?>
                <div class="auth-invalid">
                    <div class="icon">🔗</div>
                    <h2>Invalid reset link</h2>
                    <p>This password reset link is missing or invalid. Please request a new one.</p>
                    <a href="forgot_password.php" class="auth-btn" style="display:block;text-decoration:none;">Request New Link</a>
                    <a href="login.php" class="auth-btn-ghost">Back to Sign In</a>
                </div>

            <?php elseif (!$tokenValid && $tokenRow['used_at'] !== null): ?>
                <div class="auth-invalid">
                    <div class="icon">✔️</div>
                    <h2>Link already used</h2>
                    <p>This reset link has already been used. If you need to change your password again, request a new link.</p>
                    <a href="forgot_password.php" class="auth-btn" style="display:block;text-decoration:none;">Request New Link</a>
                    <a href="login.php" class="auth-btn-ghost">Back to Sign In</a>
                </div>

            <?php elseif (!$tokenValid): ?>
                <div class="auth-invalid">
                    <div class="icon">⏰</div>
                    <h2>Link expired</h2>
                    <p>This reset link expired after 1 hour. Please request a new one.</p>
                    <a href="forgot_password.php" class="auth-btn" style="display:block;text-decoration:none;">Request New Link</a>
                    <a href="login.php" class="auth-btn-ghost">Back to Sign In</a>
                </div>

            <?php else: ?>
                <h1 class="auth-card-title">Set new password</h1>
                <p class="auth-card-sub">Resetting password for <strong><?= Security::escapeHTML($tokenRow['username']) ?></strong></p>

                <?php if ($error): ?>
                <div class="auth-alert auth-alert-error">⚠️ <?= Security::escapeHTML($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="rpForm">
                    <?= Security::getCSRFTokenField() ?>
                    <input type="hidden" name="token_check" value="<?= Security::escapeHTML($token) ?>">

                    <div class="auth-form-group">
                        <label for="password">New password</label>
                        <div class="auth-input-wrap">
                            <input type="password" id="password" name="password" class="auth-input"
                                   placeholder="At least 8 characters" autocomplete="new-password"
                                   required minlength="8" oninput="updateStrength(this.value)">
                            <button type="button" class="auth-show-pw" onclick="togglePw('password','t1')" id="t1">Show</button>
                        </div>
                        <div class="pw-strength"><div class="pw-strength-bar" id="strengthBar"></div></div>
                        <div class="auth-hint" id="strengthLabel">Minimum 8 characters</div>
                    </div>

                    <div class="auth-form-group">
                        <label for="password2">Confirm new password</label>
                        <div class="auth-input-wrap">
                            <input type="password" id="password2" name="password2" class="auth-input"
                                   placeholder="Repeat your password" autocomplete="new-password"
                                   required minlength="8">
                            <button type="button" class="auth-show-pw" onclick="togglePw('password2','t2')" id="t2">Show</button>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn" id="rpBtn">Update Password</button>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
    function togglePw(fieldId, btnId) {
        const inp = document.getElementById(fieldId);
        const btn = document.getElementById(btnId);
        if (inp.type === 'password') { inp.type = 'text';     btn.textContent = 'Hide'; }
        else                         { inp.type = 'password'; btn.textContent = 'Show'; }
    }

    function updateStrength(val) {
        const bar   = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');
        if (!bar) return;
        let score = 0;
        if (val.length >= 8)                      score++;
        if (val.length >= 12)                     score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val))                    score++;
        if (/[^A-Za-z0-9]/.test(val))             score++;
        const pct    = Math.min(score * 20, 100);
        const colors = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
        const labels = ['Very weak','Weak','Fair','Good','Strong'];
        bar.style.width   = pct + '%';
        bar.style.background = colors[Math.min(score-1, 4)] || '#ef4444';
        label.textContent = val.length === 0 ? 'Minimum 8 characters' : (labels[Math.min(score-1, 4)] || 'Very weak');
    }

    const form = document.getElementById('rpForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const p1 = document.getElementById('password').value;
            const p2 = document.getElementById('password2').value;
            if (p1 !== p2) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            const btn = document.getElementById('rpBtn');
            btn.disabled = true;
            btn.textContent = 'Updating…';
        });
    }
    </script>
</body>
</html>
