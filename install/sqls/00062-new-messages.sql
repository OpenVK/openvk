SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `groups` ADD COLUMN `is_messages_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `enforce_hiding_from_global_feed`;
ALTER TABLE `groups` ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_messages_enabled`;

CREATE TABLE `chats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(4096) NOT NULL DEFAULT '',
  `photo_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_chats_photo_id` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;