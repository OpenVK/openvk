SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `chats` ADD COLUMN `photos_history` text DEFAULT NULL AFTER `photo_id`;
ALTER TABLE `topics` ADD `chat_id` BIGINT UNSIGNED DEFAULT NULL AFTER `flags`;
ALTER TABLE `chats` ADD `edited` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `description`;
