SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `groups` ADD COLUMN `is_messages_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `enforce_hiding_from_global_feed`;
ALTER TABLE `groups` ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_messages_enabled`;
