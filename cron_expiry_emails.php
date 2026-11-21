<?php
declare(strict_types=1);

// Hostinger Cron Job Script: Run daily (e.g. php /home/USER/public_html/cron_expiry_emails.php)
require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Db.php';
require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/Mailer.php';
require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/ExpiryNotifier.php';

App\Env::load(__DIR__);
$res = App\ExpiryNotifier::run(true, 500);
echo json_encode($res) . PHP_EOL;
