SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `groups` ADD COLUMN `is_messages_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `enforce_hiding_from_global_feed`;
ALTER TABLE `groups` ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_messages_enabled`;

ALTER TABLE `documents` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`;
ALTER TABLE `videos` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`, ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `link`;
ALTER TABLE `photos` ADD `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `height`;
ALTER TABLE `photos` ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `unlisted`;
