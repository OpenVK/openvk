SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `chats` ADD COLUMN `photos_history` text DEFAULT NULL AFTER `photo_id`;
ALTER TABLE `chats` ADD COLUMN `group_id` bigint(20) unsigned DEFAULT NULL AFTER `photos_history`;
