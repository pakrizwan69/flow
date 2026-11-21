<?php
declare(strict_types=1);

namespace App;

final class ExpiryNotifier
{
    public static function run(bool $force = false, int $maxPerRun = 50): array
    {
        $useDb = Database::isEnabled();
        $dataDir = Db::dataDir();
        $lastRunFile = $dataDir . DIRECTORY_SEPARATOR . 'expiry_last_run.txt';
        $logFile = $dataDir . DIRECTORY_SEPARATOR . 'expiry_email_log.json';
        $now = time();

        $lastRun = $useDb
            ? (int)(Db::getMetaValue('expiry_last_run', 0))
            : (is_file($lastRunFile) ? (int)@file_get_contents($lastRunFile) : 0);
        if (!$force && $lastRun > 0 && ($now - $lastRun) < 1800) {
            return ['skipped' => true, 'sent' => 0, 'failed' => 0];
        }
        if ($useDb) {
            Db::setMetaValue('expiry_last_run', $now);
        } else {
            @file_put_contents($lastRunFile, (string)$now, LOCK_EX);
        }

        $log = $useDb
            ? (is_array($mLog = Db::getMetaValue('expiry_email_log', [])) ? $mLog : [])
            : (is_file($logFile) ? (json_decode((string)@file_get_contents($logFile), true) ?: []) : []);
        $today = gmdate('Y-m-d');

        $users = Db::readUsers();
        $sent = 0;
        $failed = 0;
        $checked = 0;

        foreach ($users as $u) {
            if (!is_array($u)) {
                continue;
            }
            if (($u['status'] ?? 'active') === 'suspended') {
                continue;
            }
            if (Helpers::isPlanBasicOrLocked($u['plan'] ?? 'basic')) {
                continue;
            }
            $email = Db::sanitizeEmail($u['email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $daysLeft = Db::calculateDaysRemaining($u['planExpiresAt'] ?? null);
            if ($daysLeft < 1 || $daysLeft > 3) {
                continue;
            }
            $checked++;

            if (($log[$email] ?? '') === $today) {
                continue;
            }

            if ($sent >= $maxPerRun) {
                break;
            }

            $planLabel = (string)($u['plan'] ?? 'Pro');
            if (Mailer::sendPlanExpiringSoonEmail($email, $daysLeft, $planLabel)) {
                $log[$email] = $today;
                $sent++;
            } else {
                $failed++;
            }
        }

        // Prune old log entries not from today
        $pruned = [];
        foreach ($log as $k => $d) {
            if ($d === $today) {
                $pruned[$k] = $d;
            }
        }
        if ($useDb) {
            Db::setMetaValue('expiry_email_log', $pruned);
        } else {
            @file_put_contents($logFile, json_encode($pruned, JSON_UNESCAPED_SLASHES), LOCK_EX);
        }

        return ['skipped' => false, 'eligible' => $checked, 'sent' => $sent, 'failed' => $failed, 'date' => $today];
    }
}
