-- PakFlow MySQL Schema (InnoDB, utf8mb4_unicode_ci)
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB 10.4+ (Hostinger Shared & Cloud)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL DEFAULT 'Flow by Pak Administrator',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(32) NOT NULL DEFAULT 'admin',
  `created_at` VARCHAR(64) NULL,
  `updated_at` VARCHAR(64) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `username` VARCHAR(191) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `plain_password_hint` VARCHAR(255) NULL,
  `plan` VARCHAR(32) NOT NULL DEFAULT 'basic',
  `credits` BIGINT NOT NULL DEFAULT 0,
  `credits_total` BIGINT NOT NULL DEFAULT 0,
  `plan_expires_at` VARCHAR(64) NULL,
  `days_remaining` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `session_version` INT UNSIGNED NOT NULL DEFAULT 1,
  `active_device_id` VARCHAR(255) NULL,
  `last_login_at` VARCHAR(64) NULL,
  `last_login_ip` VARCHAR(64) NULL,
  `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
  `reseller_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(64) NULL,
  `updated_at` VARCHAR(64) NULL,
  `extra_json` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_username` (`username`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_plan` (`plan`),
  KEY `idx_users_reseller_id` (`reseller_id`),
  KEY `idx_users_last_login_ip` (`last_login_ip`),
  KEY `idx_users_created_id` (`created_at`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resellers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `plain_password_hint` VARCHAR(255) NULL,
  `allowed_users` INT NOT NULL DEFAULT 50,
  `credits_quota` BIGINT NOT NULL DEFAULT 1000000,
  `users_created` INT NOT NULL DEFAULT 0,
  `total_paid` BIGINT NOT NULL DEFAULT 0,
  `deleted_charges` BIGINT NOT NULL DEFAULT 0,
  `whatsapp` VARCHAR(64) NULL DEFAULT '',
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` VARCHAR(64) NULL,
  `extra_json` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_resellers_email` (`email`),
  KEY `idx_resellers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reseller_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reseller_id` BIGINT UNSIGNED NOT NULL,
  `reseller_name` VARCHAR(255) NOT NULL DEFAULT '',
  `reseller_email` VARCHAR(255) NOT NULL DEFAULT '',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `proof` LONGTEXT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `created_at` VARCHAR(64) NULL,
  `reviewed_by` VARCHAR(255) NULL,
  `approved_at` VARCHAR(64) NULL,
  `rejected_at` VARCHAR(64) NULL,
  `rejected_reason` TEXT NULL,
  `extra_json` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rp_reseller_id` (`reseller_id`),
  KEY `idx_rp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cookie_slots` (
  `slot_number` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL DEFAULT '',
  `server_type` VARCHAR(64) NOT NULL DEFAULT 'Images',
  `status` VARCHAR(32) NOT NULL DEFAULT 'EMPTY',
  `cookie_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` VARCHAR(64) NULL,
  `updated_by` VARCHAR(191) NULL DEFAULT 'Super Admin',
  `cookies_json` LONGTEXT NOT NULL,
  `version_ts` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`slot_number`),
  KEY `idx_slots_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deleted_users` (
  `entry_type` ENUM('id', 'email') NOT NULL,
  `entry_val` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`entry_type`, `entry_val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(64) NOT NULL,
  `value_json` LONGTEXT NOT NULL,
  `updated_at` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions_by_ip` (
  `session_key` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(255) NULL,
  `plan` VARCHAR(64) NULL,
  `locked` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at_ms` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`session_key`),
  KEY `idx_sessions_updated` (`updated_at_ms`),
  KEY `idx_sessions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pending_signups` (
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `username` VARCHAR(191) NOT NULL DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `plain_password_hint` VARCHAR(255) NULL,
  `device_id` VARCHAR(255) NULL,
  `code` VARCHAR(16) NOT NULL DEFAULT '',
  `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
  `extra_json` LONGTEXT NULL,
  PRIMARY KEY (`email`),
  KEY `idx_ps_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `token` VARCHAR(128) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`token`),
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
