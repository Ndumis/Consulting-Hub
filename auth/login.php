<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

$error   = '';
$success = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'Please enter your username or email and password.';
        } else {
            $database = new Database();
            $db       = $database->getConnection();

            $stmt = $db->prepare("SELECT id, username, email, password, role, department FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $row['id'];
                $_SESSION['username']   = $row['username'];
                $_SESSION['role']       = $row['role'];
                $_SESSION['department'] = $row['department'];
                $_SESSION['email']      = $row['email'];

                // Log login activity
                try {
                    Utils::logActivity($db, 'login', "User '{$row['username']}' logged in");
                } catch (Exception $e) {}

                header("Location: ../dashboard.php");
                exit();
            } else {
                $error = 'Incorrect username or password.';
                // Slow down brute-force
                sleep(1);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — KConsulting Hub</title>
    <link rel="icon" type="image/png" href="../img/KConsultingLogo1.png">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        /* ── Redesign overlay ────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f1f5f9;
            display: flex; align-items: stretch; min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* Left panel */
        .auth-panel-left {
            display: none;
            width: 44%;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            flex-direction: column; align-items: center; justify-content: center;
            padding: 48px 40px; text-align: center; position: relative; overflow: hidden;
        }
        @media(min-width:900px){ .auth-panel-left { display: flex; } }
        .auth-panel-left::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 30% 40%, rgba(255,255,255,.04) 0%, transparent 60%);
        }
        .auth-left-logo { width: 180px; margin-bottom: 32px; position: relative; z-index: 1; }
        .auth-left-title { font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 10px; position: relative; z-index: 1; }
        .auth-left-sub   { font-size: .9rem; color: rgba(255,255,255,.6); line-height: 1.6; max-width: 300px; position: relative; z-index: 1; }
        .auth-left-badges { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 32px; position: relative; z-index: 1; }
        .auth-badge { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 20px; padding: 5px 14px; font-size: .78rem; color: rgba(255,255,255,.8); }

        /* Right panel */
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

        /* Mobile logo (shown only when left panel hidden) */
        .auth-mobile-logo { text-align: center; margin-bottom: 24px; }
        .auth-mobile-logo img { height: 44px; }
        @media(min-width:900px){ .auth-mobile-logo { display: none; } }

        .auth-card-title { font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .auth-card-sub   { font-size: .86rem; color: #64748b; margin-bottom: 28px; }

        /* Alerts */
        .auth-alert { border-radius: 8px; padding: 11px 14px; font-size: .85rem; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 8px; }
        .auth-alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .auth-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #059669; }

        /* Form */
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

        .auth-input-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .auth-forgot-link { font-size: .8rem; color: #64748b; text-decoration: none; }
        .auth-forgot-link:hover { color: #0f172a; }

        .auth-show-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; font-size: .8rem; padding: 2px 4px; }
        .auth-show-pw:hover { color: #374151; }

        .auth-btn {
            width: 100%; padding: 11px; border: none; border-radius: 9px; cursor: pointer;
            font-size: .95rem; font-weight: 700; letter-spacing: .2px;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            color: #fff; transition: opacity .2s, transform .1s;
            margin-top: 4px;
        }
        .auth-btn:hover  { opacity: .9; }
        .auth-btn:active { transform: scale(.99); }
        .auth-btn:disabled { opacity: .6; cursor: not-allowed; }
    </style>
</head>
<body>

    <!-- Left branding panel -->
    <div class="auth-panel-left">
        <img src="../img/KConsultingLogo.png" alt="KConsulting" class="auth-left-logo">
        <div class="auth-left-title">Business Management Portal</div>
        <div class="auth-left-sub">One platform for Finance, HR, Projects, Clients, Marketing and Business Development.</div>
        <div class="auth-left-badges">
            <span class="auth-badge">Finance</span>
            <span class="auth-badge">HR</span>
            <span class="auth-badge">Projects</span>
            <span class="auth-badge">Clients</span>
            <span class="auth-badge">Marketing</span>
            <span class="auth-badge">BD</span>
        </div>
    </div>

    <!-- Right form panel -->
    <div class="auth-panel-right">
        <div class="auth-card">

            <div class="auth-mobile-logo">
                <img src="../img/KConsultingLogo.png" alt="KConsulting">
            </div>

            <h1 class="auth-card-title">Sign in</h1>
            <p class="auth-card-sub">Enter your credentials to access the hub</p>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-error">⚠️ <?= Security::escapeHTML($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="auth-alert auth-alert-success">✅ <?= Security::escapeHTML($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <?= Security::getCSRFTokenField() ?>

                <div class="auth-form-group">
                    <label for="username">Username or Email</label>
                    <div class="auth-input-wrap">
                        <input type="text" id="username" name="username" class="auth-input"
                               value="<?= Security::escapeHTML($_POST['username'] ?? '') ?>"
                               placeholder="Enter your username or email" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="auth-form-group">
                    <div class="auth-input-row">
                        <label for="password" style="margin:0;">Password</label>
                        <a href="forgot_password.php" class="auth-forgot-link">Forgot password?</a>
                    </div>
                    <div class="auth-input-wrap">
                        <input type="password" id="password" name="password" class="auth-input"
                               style="padding-right: 52px;"
                               placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="auth-show-pw" onclick="togglePw()" id="pwToggle">Show</button>
                    </div>
                </div>

                <button type="submit" class="auth-btn" id="loginBtn">Sign In</button>
            </form>
        </div>
    </div>

    <script>
    function togglePw() {
        const inp = document.getElementById('password');
        const btn = document.getElementById('pwToggle');
        if (inp.type === 'password') { inp.type = 'text';     btn.textContent = 'Hide'; }
        else                         { inp.type = 'password'; btn.textContent = 'Show'; }
    }
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.textContent = 'Signing in…';
    });
    </script>
</body>
</html>
