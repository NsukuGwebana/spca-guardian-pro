-- SPCA Guardian Pro: upgrade for the November 2026 improvements.
-- Run once in phpMyAdmin (Import tab) on the existing database.
-- It adds columns and tables, and marks old "Approved" adoptions whose animal
-- has already gone home as "Adopted". Nothing is deleted.

-- Staff accounts get an email address, used for password reset links.
ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) NULL DEFAULT NULL AFTER `phonenumber`;

-- One-time password reset links (only a hash of the link is stored).
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token_hash`),
  KEY `idx_reset_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every login attempt, so repeated wrong passwords can be slowed down.
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(35) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_user_time` (`username`, `attempted_at`),
  KEY `idx_login_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who did what, and when.
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(35) NOT NULL,
  `user_role` VARCHAR(45) NOT NULL DEFAULT '',
  `action` VARCHAR(100) NOT NULL,
  `details` VARCHAR(500) NOT NULL DEFAULT '',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_time` (`created_at`),
  KEY `idx_activity_user` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adoption pipeline: Pending -> Home visit -> Approved -> Adopted (or Rejected at any step).
ALTER TABLE `adoptions`
  MODIFY `status` ENUM('Pending','Home visit','Approved','Adopted','Rejected') DEFAULT 'Pending',
  ADD COLUMN `status_updated_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN `home_visit_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN `staff_note` VARCHAR(255) NULL DEFAULT NULL;

-- Post-adoption follow-up appointments: fix the broken default status.
ALTER TABLE `appointment`
  MODIFY `status` ENUM('Pending','Completed') DEFAULT 'Pending';

-- Before the pipeline, "Approved" meant the animal had already gone home.
-- Those records become "Adopted"; approvals still waiting for handover stay "Approved".
UPDATE `adoptions` a
  JOIN `animals` ani ON ani.id = a.animal_id
   SET a.status = 'Adopted'
 WHERE a.status = 'Approved' AND ani.status = 'Adopted';
