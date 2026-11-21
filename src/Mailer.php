<?php
declare(strict_types=1);

namespace App;

final class Mailer
{
    private const DEFAULT_HOST = 'smtp.hostinger.com';
    private const DEFAULT_PORT = 465;
    private const DEFAULT_USER = 'support@flowbypak.com';
    private const DEFAULT_PASS = ';Tl$=VhWa3wI:m9J';
    private const DEFAULT_FROM_NAME = 'PakFlow Support';

    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $host = Env::get('SMTP_HOST', self::DEFAULT_HOST);
        $port = (int)Env::get('SMTP_PORT', (string)self::DEFAULT_PORT);
        $user = Env::get('SMTP_USER', self::DEFAULT_USER);
        $pass = Env::get('SMTP_PASS', self::DEFAULT_PASS);
        $fromName = Env::get('SMTP_FROM_NAME', self::DEFAULT_FROM_NAME);

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $errno = 0;
        $errstr = '';
        $scheme = ($port === 465) ? 'ssl://' : 'tcp://';
        $sock = @stream_socket_client($scheme . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        if (!$sock) {
            error_log("[Mailer] Connect failed ($host:$port): $errstr ($errno)");
            return false;
        }
        stream_set_timeout($sock, 10);

        $readResp = static function () use ($sock): string {
            $data = '';
            while (!feof($sock)) {
                $line = fgets($sock, 515);
                if ($line === false) {
                    break;
                }
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };

        $cmd = static function (string $line, string $expectedCode) use ($sock, $readResp): bool {
            fwrite($sock, $line . "\r\n");
            $resp = $readResp();
            if (!str_starts_with($resp, $expectedCode)) {
                error_log("[Mailer] Unexpected SMTP response for '$line': $resp");
                return false;
            }
            return true;
        };

        $greeting = $readResp();
        if (!str_starts_with($greeting, '220')) {
            fclose($sock);
            return false;
        }

        $ehloHost = $_SERVER['SERVER_NAME'] ?? 'flowbypak.com';
        if (!$cmd("EHLO $ehloHost", '250')) {
            fclose($sock);
            return false;
        }

        if ($port === 587) {
            if (!$cmd('STARTTLS', '220')) {
                fclose($sock);
                return false;
            }
            if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($sock);
                return false;
            }
            if (!$cmd("EHLO $ehloHost", '250')) {
                fclose($sock);
                return false;
            }
        }

        if (!$cmd('AUTH LOGIN', '334')
            || !$cmd(base64_encode($user), '334')
            || !$cmd(base64_encode($pass), '235')) {
            fclose($sock);
            return false;
        }

        if (!$cmd("MAIL FROM:<$user>", '250')
            || !$cmd("RCPT TO:<$toEmail>", '250')
            || !$cmd('DATA', '354')) {
            fclose($sock);
            return false;
        }

        $encodedSubj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            "From: $fromName <$user>",
            "To: <$toEmail>",
            "Reply-To: $user",
            "Subject: $encodedSubj",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'Date: ' . date('r'),
        ];

        $bodyB64 = chunk_split(base64_encode($htmlBody));
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $bodyB64 . "\r\n.";
        if (!$cmd($message, '250')) {
            fclose($sock);
            return false;
        }

        fwrite($sock, "QUIT\r\n");
        fclose($sock);
        return true;
    }

    public static function sendWelcomeEmail(string $toEmail, string $name): bool
    {
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $host = $_SERVER['HTTP_HOST'] ?? 'flowbypak.com';
        $isLocal = str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost');
        $baseUrl = $isLocal ? ('http://' . $host) : 'https://flowbypak.com';
        $dashUrl = $baseUrl . '/login';

        $safeUrl = htmlspecialchars($dashUrl, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<div style="background:linear-gradient(135deg,#6d28d9,#7c3aed);padding:28px 24px;text-align:center;color:#ffffff;">'
            . '<div style="font-size:24px;font-weight:800;letter-spacing:-0.02em;">PakFlow</div>'
            . '<div style="font-size:13px;opacity:0.9;margin-top:4px;">AI Image &amp; Video Generation</div>'
            . '</div>'
            . '<div style="padding:28px 28px 22px;color:#374151;font-size:14.5px;line-height:1.6;">'
            . '<p style="margin:0 0 14px;">Hi <a href="mailto:' . $safeEmail . '" style="color:#2563eb;font-weight:600;text-decoration:underline;">' . $safeEmail . '</a>,</p>'
            . '<p style="margin:0 0 22px;">Welcome to <strong>PakFlow</strong>! Your account has been created and is ready to use.<br/>Click the button below to access your dashboard and start creating with Google Flow &amp; Nano Banana Pro.</p>'
            . '<div style="text-align:center;margin:24px 0;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:#6d28d9;color:#ffffff;font-weight:700;font-size:14px;padding:13px 28px;border-radius:10px;text-decoration:none;">Access My Account</a>'
            . '</div>'
            . '<p style="margin:0 0 12px;color:#4b5563;font-size:13.5px;">For your security, only <strong>1 active device</strong> is allowed at a time.</p>'
            . '<p style="margin:0 0 6px;color:#6b7280;font-size:12.5px;">If the button doesn\'t work, copy and paste this link into your browser:</p>'
            . '<p style="margin:0 0 18px;word-break:break-all;font-size:12.5px;"><a href="' . $safeUrl . '" style="color:#2563eb;text-decoration:underline;">' . $safeUrl . '</a></p>'
            . '<p style="margin:0;color:#4b5563;font-size:13.5px;">If you didn\'t create this PakFlow account, you can safely ignore this email.</p>'
            . '</div>'
            . '<div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 24px;text-align:center;font-size:12px;color:#6b7280;">'
            . '<div style="margin-bottom:6px;">You\'re receiving this because a new PakFlow account was registered with this email.</div>'
            . '<div>🌐 <a href="https://flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">flowbypak.com</a> &nbsp;&nbsp; ✉️ <a href="mailto:support@flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">support@flowbypak.com</a></div>'
            . '</div></div></body></html>';

        return self::send($toEmail, 'Welcome to PakFlow — Your Account is Ready!', $html);
    }

    public static function sendPasswordResetLink(string $toEmail, string $resetUrl): bool
    {
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<div style="background:linear-gradient(135deg,#6d28d9,#7c3aed);padding:28px 24px;text-align:center;color:#ffffff;">'
            . '<div style="font-size:24px;font-weight:800;letter-spacing:-0.02em;">PakFlow</div>'
            . '<div style="font-size:13px;opacity:0.9;margin-top:4px;">AI Image &amp; Video Generation</div>'
            . '</div>'
            . '<div style="padding:28px 28px 22px;color:#374151;font-size:14.5px;line-height:1.6;">'
            . '<p style="margin:0 0 14px;">Hi <a href="mailto:' . $safeEmail . '" style="color:#2563eb;font-weight:600;text-decoration:underline;">' . $safeEmail . '</a>,</p>'
            . '<p style="margin:0 0 22px;">We received a request to reset the password for your PakFlow account.<br/>Click the button below to choose a new password.</p>'
            . '<div style="text-align:center;margin:24px 0;">'
            . '<a href="' . $safeUrl . '" style="display:inline-block;background:#6d28d9;color:#ffffff;font-weight:700;font-size:14px;padding:13px 28px;border-radius:10px;text-decoration:none;">Reset My Password</a>'
            . '</div>'
            . '<p style="margin:0 0 12px;color:#4b5563;font-size:13.5px;">This link expires in <strong>30 minutes</strong> and can only be used once.</p>'
            . '<p style="margin:0 0 6px;color:#6b7280;font-size:12.5px;">If the button doesn\'t work, copy and paste this link into your browser:</p>'
            . '<p style="margin:0 0 18px;word-break:break-all;font-size:12.5px;"><a href="' . $safeUrl . '" style="color:#2563eb;text-decoration:underline;">' . $safeUrl . '</a></p>'
            . '<p style="margin:0;color:#4b5563;font-size:13.5px;">If you didn\'t request a password reset, you can safely ignore this email — your password will not change.</p>'
            . '</div>'
            . '<div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 24px;text-align:center;font-size:12px;color:#6b7280;">'
            . '<div style="margin-bottom:6px;">You\'re receiving this because someone requested a password reset for your PakFlow account.</div>'
            . '<div>🌐 <a href="https://flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">flowbypak.com</a> &nbsp;&nbsp; ✉️ <a href="mailto:support@flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">support@flowbypak.com</a></div>'
            . '</div></div></body></html>';

        return self::send($toEmail, 'Reset Your PakFlow Password', $html);
    }

    public static function sendSignupVerificationCode(string $toEmail, string $code): bool
    {
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<div style="background:linear-gradient(135deg,#6d28d9,#7c3aed);padding:28px 24px;text-align:center;color:#ffffff;">'
            . '<div style="font-size:24px;font-weight:800;letter-spacing:-0.02em;">PakFlow</div>'
            . '<div style="font-size:13px;opacity:0.9;margin-top:4px;">AI Image &amp; Video Generation</div>'
            . '</div>'
            . '<div style="padding:28px 28px 22px;color:#374151;font-size:14.5px;line-height:1.6;">'
            . '<p style="margin:0 0 14px;">Hi <a href="mailto:' . $safeEmail . '" style="color:#2563eb;font-weight:600;text-decoration:underline;">' . $safeEmail . '</a>,</p>'
            . '<p style="margin:0 0 18px;">Please verify your email address to complete your <strong>PakFlow</strong> account registration. Enter the 6-digit verification code below:</p>'
            . '<div style="margin:22px 0;padding:16px;text-align:center;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;font-size:30px;font-weight:800;letter-spacing:8px;color:#6d28d9;">'
            . $safeCode
            . '</div>'
            . '<p style="margin:0 0 12px;color:#4b5563;font-size:13.5px;">This verification code expires in <strong>15 minutes</strong>.</p>'
            . '<p style="margin:0;color:#4b5563;font-size:13.5px;">If you did not request this signup, you can safely ignore this email — no account will be created.</p>'
            . '</div>'
            . '<div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 24px;text-align:center;font-size:12px;color:#6b7280;">'
            . '<div style="margin-bottom:6px;">You\'re receiving this because someone started a signup for PakFlow with this email.</div>'
            . '<div>🌐 <a href="https://flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">flowbypak.com</a> &nbsp;&nbsp; ✉️ <a href="mailto:support@flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">support@flowbypak.com</a></div>'
            . '</div></div></body></html>';

        return self::send($toEmail, 'PakFlow Email Verification Code: ' . $safeCode, $html);
    }

    public static function sendPlanExpiringSoonEmail(string $toEmail, int $daysLeft, string $planName = 'Pro'): bool
    {
        $safeEmail = htmlspecialchars($toEmail, ENT_QUOTES, 'UTF-8');
        $safePlan = htmlspecialchars(ucfirst($planName), ENT_QUOTES, 'UTF-8');
        $dayLabel = $daysLeft === 1 ? '1 day' : "$daysLeft days";
        $renewUrl = 'https://flowbypak.com/pricing';

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<div style="background:linear-gradient(135deg,#6d28d9,#7c3aed);padding:28px 24px;text-align:center;color:#ffffff;">'
            . '<div style="font-size:24px;font-weight:800;letter-spacing:-0.02em;">PakFlow</div>'
            . '<div style="font-size:13px;opacity:0.9;margin-top:4px;">AI Image &amp; Video Generation</div>'
            . '</div>'
            . '<div style="padding:28px 28px 22px;color:#374151;font-size:14.5px;line-height:1.6;">'
            . '<p style="margin:0 0 14px;">Hi <a href="mailto:' . $safeEmail . '" style="color:#2563eb;font-weight:600;text-decoration:underline;">' . $safeEmail . '</a>,</p>'
            . '<p style="margin:0 0 18px;">Your <strong>PakFlow ' . $safePlan . ' Plan</strong> is expiring soon — you only have <strong>' . $dayLabel . '</strong> remaining.</p>'
            . '<p style="margin:0 0 22px;">Renew now to keep uninterrupted access to Google Flow &amp; Nano Banana Pro.</p>'
            . '<div style="text-align:center;margin:24px 0;">'
            . '<a href="' . $renewUrl . '" style="display:inline-block;background:#6d28d9;color:#ffffff;font-weight:700;font-size:14px;padding:13px 28px;border-radius:10px;text-decoration:none;">Renew Now</a>'
            . '</div>'
            . '<p style="margin:0 0 6px;color:#6b7280;font-size:12.5px;">If the button doesn\'t work, copy and paste this link into your browser:</p>'
            . '<p style="margin:0 0 18px;word-break:break-all;font-size:12.5px;"><a href="' . $renewUrl . '" style="color:#2563eb;text-decoration:underline;">' . $renewUrl . '</a></p>'
            . '<p style="margin:0;color:#4b5563;font-size:13.5px;">Need help renewing? Contact your reseller or reply to support@flowbypak.com.</p>'
            . '</div>'
            . '<div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 24px;text-align:center;font-size:12px;color:#6b7280;">'
            . '<div style="margin-bottom:6px;">You\'re receiving this daily reminder because your PakFlow plan expires within 3 days.</div>'
            . '<div>🌐 <a href="https://flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">flowbypak.com</a> &nbsp;&nbsp; ✉️ <a href="mailto:support@flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">support@flowbypak.com</a></div>'
            . '</div></div></body></html>';

        return self::send($toEmail, "Your PakFlow Plan is Expiring Soon ($dayLabel left) — Renew Now!", $html);
    }
}
