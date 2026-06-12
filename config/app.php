<?php
// ============================================================
//  KConsulting Hub — Central Application Configuration
//  Edit ONLY this file when moving between environments.
// ============================================================

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_PORT',     3306);
define('DB_NAME',     'kconsulting');
define('DB_USER',     'root');
define('DB_PASSWORD', '');

// ── App URL (auto-detected — no hardcoding needed) ───────────
// Detects protocol, host, and port from the current request so
// the app works on localhost, LAN IPs, and production domains.
if (!defined('APP_URL')) {
    $__proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';

    $__host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Derive the app base path by comparing the app's filesystem root
    // (the parent of this config/ directory) against the web server's
    $__appRoot = str_replace('\\', '/', rtrim(dirname(__DIR__), '\\/'));
    $__docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/'));

    $__base = '';
    if ($__docRoot !== '' && stripos($__appRoot, $__docRoot) === 0) {
        $__base = substr($__appRoot, strlen($__docRoot));
    }

    define('APP_URL', $__proto . '://' . $__host . $__base);
}

// ── Mail / SMTP ───────────────────────────────────────────────
define('SMTP_HOST', 'fyre.aserv.co.za');
define('SMTP_PORT', 465);
define('SMTP_USER', 'mail@thekconsult.co.za');
define('SMTP_PASS', '8GAzt_-NK=#7}SE]');

define('MAIL_FROM',       'no-reply@thekconsult.co.za');
define('MAIL_ADMIN_ADDR', 'info@thekconsult.co.za');
define('MAIL_ADMIN_NAME', 'KConsulting');

// ── App meta ──────────────────────────────────────────────────
define('APP_NAME', 'KConsulting Hub');
