SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `chats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT 'chat',
  `title` varchar(255) NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `users` json NOT NULL,
  `push_settings` json NOT NULL,
  `photo_50` varchar(255) DEFAULT NULL,
  `photo_100` varchar(255) DEFAULT NULL,
  `photo_200` varchar(255) DEFAULT NULL,
  `left` tinyint(1) NOT NULL DEFAULT '0',
  `kicked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;