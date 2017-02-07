-- Main user to TFA methods table
CREATE TABLE `#__loginguard_tfa` (
  `id` SERIAL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `method` varchar(100) NOT NULL,
  `options` MEDIUMTEXT null,
  INDEX idx_user_id (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
