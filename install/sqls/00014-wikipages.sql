CREATE TABLE IF NOT EXISTS `wikipages` (
  `id` bigint(20) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `owner` bigint(20) NOT NULL,
  `virtual_id` bigint(20) NOT NULL,
  `created` bigint(20) NOT NULL,
  `edited` bigint(20) DEFAULT NULL,
  `title` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `hits` bigint(20) NOT NULL DEFAULT 0,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `wikipages` ADD INDEX( `owner`, `virtual_id`);
ALTER TABLE `wikipages` ADD UNIQUE( `owner`, `title`);

ALTER TABLE `groups` ADD `pages` BOOLEAN NOT NULL DEFAULT FALSE AFTER `wall`;