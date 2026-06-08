<?php
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class MailService {

    /**
     * Send a single email.
     * Returns ['success'=>bool, 'error'=>string]
     */
    public static function send(string $to_email, string $to_name, string $subject, string $html, string $text = ''): array {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host        = SMTP_HOST;
            $mail->SMTPAuth    = true;
            $mail->Username    = SMTP_USER;
            $mail->Password    = SMTP_PASS;
            $mail->SMTPSecure  = PHPMailer::ENCRYPTION_SMTPS;  // port 465 = SSL
            $mail->Port        = SMTP_PORT;
            $mail->CharSet     = 'UTF-8';

            $mail->setFrom(MAIL_FROM, MAIL_ADMIN_NAME);
            $mail->addReplyTo(MAIL_ADMIN_ADDR, MAIL_ADMIN_NAME);
            $mail->addAddress($to_email, $to_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text ?: strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html));

            $mail->send();
            return ['success' => true, 'error' => ''];
        } catch (MailException $e) {
            error_log("MailService::send failed to $to_email: " . $mail->ErrorInfo);
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }

    /** Send password-reset email */
    public static function sendPasswordReset(string $to_email, string $to_name, string $reset_url): array {
        $subject = 'Reset your KConsulting Hub password';
        $html    = self::templatePasswordReset($to_name, $reset_url);
        return self::send($to_email, $to_name, $subject, $html);
    }

    /** Send welcome / credentials email (new employee auto-account) */
    public static function sendWelcome(string $to_email, string $to_name, string $username, string $tmp_password): array {
        $subject = 'Welcome to KConsulting Hub — your portal account';
        $html    = self::templateWelcome($to_name, $username, $tmp_password);
        return self::send($to_email, $to_name, $subject, $html);
    }

    /** Generic notification email */
    public static function sendNotification(string $to_email, string $to_name, string $title, string $body, string $action_url = '', string $action_label = ''): array {
        $subject = $title . ' — KConsulting Hub';
        $html    = self::templateNotification($to_name, $title, $body, $action_url, $action_label);
        return self::send($to_email, $to_name, $subject, $html);
    }

    // ── Email templates ───────────────────────────────────────────────────────

    private static function wrap(string $title, string $content): string {
        $app = APP_NAME;
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0;">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
    <!-- Header -->
    <tr><td style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-radius:12px 12px 0 0;padding:28px 36px;text-align:center;">
      <p style="margin:0;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">{$app}</p>
      <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.6);">Business Management Portal</p>
    </td></tr>
    <!-- Body -->
    <tr><td style="background:#ffffff;padding:36px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
      {$content}
    </td></tr>
    <!-- Footer -->
    <tr><td style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:20px 36px;text-align:center;">
      <p style="margin:0;font-size:12px;color:#94a3b8;">This is an automated message from {$app}. Please do not reply directly to this email.</p>
      <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;">&copy; {$app} &mdash; <a href="mailto:info@thekconsult.co.za" style="color:#64748b;">info@thekconsult.co.za</a></p>
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private static function templatePasswordReset(string $name, string $reset_url): string {
        $expires = '1 hour';
        $content = <<<HTML
<h2 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#0f172a;">Reset your password</h2>
<p style="margin:0 0 20px;font-size:13px;color:#64748b;">We received a request to reset the password for your account.</p>

<p style="margin:0 0 8px;font-size:15px;color:#374151;">Hi <strong>{$name}</strong>,</p>
<p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;">
  Click the button below to set a new password. This link is valid for <strong>{$expires}</strong>.
</p>

<div style="text-align:center;margin:28px 0;">
  <a href="{$reset_url}" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;letter-spacing:0.3px;">
    Reset Password
  </a>
</div>

<p style="margin:0 0 8px;font-size:13px;color:#64748b;">Or copy this link into your browser:</p>
<p style="margin:0 0 24px;word-break:break-all;font-size:12px;color:#0f172a;background:#f8fafc;border:1px solid #e2e8f0;padding:10px 12px;border-radius:6px;">
  {$reset_url}
</p>

<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;margin-top:4px;">
  <p style="margin:0;font-size:13px;color:#92400e;">
    <strong>Didn't request this?</strong> You can safely ignore this email. Your password will not change.
  </p>
</div>
HTML;
        return self::wrap('Reset your password', $content);
    }

    private static function templateWelcome(string $name, string $username, string $tmp_password): string {
        $app_url = APP_URL;
        $content = <<<HTML
<h2 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#0f172a;">Welcome to KConsulting Hub!</h2>
<p style="margin:0 0 20px;font-size:13px;color:#64748b;">Your portal account has been created.</p>

<p style="margin:0 0 8px;font-size:15px;color:#374151;">Hi <strong>{$name}</strong>,</p>
<p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;">
  Your portal account is ready. Use the credentials below to log in and update your password.
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
  <tr>
    <td style="padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px 6px 0 0;font-size:13px;font-weight:600;color:#374151;">Username</td>
    <td style="padding:10px 14px;background:#ffffff;border:1px solid #e2e8f0;border-top:none;font-size:15px;font-weight:700;color:#0f172a;font-family:monospace;">{$username}</td>
  </tr>
  <tr>
    <td style="padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 6px 6px;font-size:13px;font-weight:600;color:#374151;">Temp Password</td>
    <td style="padding:10px 14px;background:#ffffff;border:1px solid #e2e8f0;border-top:none;font-size:15px;font-weight:700;color:#0f172a;font-family:monospace;">{$tmp_password}</td>
  </tr>
</table>

<div style="text-align:center;margin:24px 0;">
  <a href="{$app_url}/auth/login.php" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;">
    Log In Now
  </a>
</div>

<div style="background:#dbeafe;border:1px solid #bfdbfe;border-radius:8px;padding:14px 16px;">
  <p style="margin:0;font-size:13px;color:#1e40af;">
    <strong>Security reminder:</strong> Please change your password immediately after logging in via My Profile.
  </p>
</div>
HTML;
        return self::wrap('Welcome to KConsulting Hub', $content);
    }

    private static function templateNotification(string $name, string $title, string $body, string $action_url, string $action_label): string {
        $btn = $action_url ? <<<HTML
<div style="text-align:center;margin:24px 0;">
  <a href="{$action_url}" style="display:inline-block;padding:11px 28px;background:linear-gradient(135deg,#0f172a,#1e3a5f);color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;">{$action_label}</a>
</div>
HTML : '';
        $content = <<<HTML
<h2 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#0f172a;">{$title}</h2>
<p style="margin:0 0 20px;font-size:13px;color:#64748b;">KConsulting Hub notification</p>

<p style="margin:0 0 8px;font-size:15px;color:#374151;">Hi <strong>{$name}</strong>,</p>
<div style="margin:0 0 20px;font-size:15px;color:#374151;line-height:1.6;background:#f8fafc;border-left:4px solid #0f172a;padding:14px 16px;border-radius:0 8px 8px 0;">
  {$body}
</div>
{$btn}
HTML;
        return self::wrap($title, $content);
    }
}
