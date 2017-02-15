CREATE TABLE IF NOT EXISTS `#__loginguard_tfa` (
  `id` SERIAL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `method` VARCHAR(100) NOT NULL,
  `default` TINYINT(1) NOT NULL DEFAULT 0,
  `options` MEDIUMTEXT null,
  `created_on` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_used` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  INDEX idx_user_id (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
