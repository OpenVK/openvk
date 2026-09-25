SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `im_message_folders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(64) NOT NULL DEFAULT 'custom',
  `position` int(11) NOT NULL DEFAULT '0',
  `created` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `im_message_folder_peers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `folder` bigint(20) unsigned NOT NULL,
  `peer` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folder_peer` (`folder`,`peer`),
  KEY `folder` (`folder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
