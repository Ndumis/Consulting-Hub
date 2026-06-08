<?php
// Session bootstrap - centralized session configuration
// Must be included before any session_start() calls

// Enable PHP error logging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to detect HTTPS
function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

// Set session cookie parameters based on protocol
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Ensure session directory is writable
$session_save_path = sys_get_temp_dir() . '/sessions';
if (!is_dir($session_save_path)) {
    mkdir($session_save_path, 0755, true);
}
session_save_path($session_save_path);

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}