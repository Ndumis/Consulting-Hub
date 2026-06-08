<?php
// Session bootstrap - centralized session configuration
// Must be included before any session_start() calls

// Enable PHP error logging — use a cross-platform writable path
ini_set('log_errors', 1);
ini_set('error_log', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kconsulting_errors.log');
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

// Use PHP's default session save path (always writable on any OS/WAMP install)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}