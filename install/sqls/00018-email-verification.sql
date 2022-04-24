ALTER TABLE `profiles` ADD `activated` tinyint(3) NULL DEFAULT '1' AFTER `2fa_secret`;

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profile` bigint(20) unsigned NOT NULL,
  `key` char(64) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `timestamp` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
