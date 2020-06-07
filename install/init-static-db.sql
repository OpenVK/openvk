SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DROP TABLE IF EXISTS `albums`;
CREATE TABLE `albums` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `name` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_pragma` tinyint(3) UNSIGNED NOT NULL DEFAULT 255,
  `cover_photo` bigint(20) UNSIGNED DEFAULT NULL,
  `special_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `album_relations`;
CREATE TABLE `album_relations` (
  `album` bigint(20) UNSIGNED NOT NULL,
  `photo` bigint(20) UNSIGNED NOT NULL,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `attachable_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audios`;
CREATE TABLE `audios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) UNSIGNED NOT NULL,
  `virtual_id` bigint(20) UNSIGNED NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `hash` char(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(4) DEFAULT 0,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '(no name)',
  `performer` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unknown',
  `genre` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'K-POP',
  `lyrics` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `explicit` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `audio_relations`;
CREATE TABLE `audio_relations` (
  `user` bigint(20) UNSIGNED NOT NULL,
  `audio` bigint(20) UNSIGNED NOT NULL,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `model` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` bigint(20) UNSIGNED NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `flags` tinyint(1) UNSIGNED DEFAULT NULL,
  `ad` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0,
  `virtual_id` bigint(20) DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `conv_sockets`;
CREATE TABLE `conv_sockets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `origin` bigint(20) UNSIGNED NOT NULL,
  `modelId` tinyint(3) UNSIGNED NOT NULL,
  `destination` bigint(20) UNSIGNED NOT NULL,
  `open` bit(1) NOT NULL DEFAULT b'1',
  `visible` bit(1) NOT NULL DEFAULT b'1'
) ENGINE=Aria DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `event_turnouts`;
CREATE TABLE `event_turnouts` (
  `user` bigint(20) UNSIGNED NOT NULL,
  `event` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner` bigint(20) UNSIGNED DEFAULT NULL,
  `shortcode` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `type` int(2) UNSIGNED DEFAULT 1,
  `closed` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `wall` int(11) NOT NULL DEFAULT 1
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `group_coadmins`;
CREATE TABLE `group_coadmins` (
  `user` bigint(20) UNSIGNED NOT NULL,
  `club` bigint(20) UNSIGNED NOT NULL,
  `comment` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id` bigint(20) NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `likes`;
CREATE TABLE `likes` (
  `origin` bigint(20) UNSIGNED NOT NULL,
  `model` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` bigint(20) NOT NULL,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` bigint(20) NOT NULL,
  `sender_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `recipient_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` bigint(20) UNSIGNED NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` bigint(20) NOT NULL,
  `edited` bigint(20) DEFAULT NULL,
  `ad` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `msg_descriptors`;
CREATE TABLE `msg_descriptors` (
  `message` bigint(20) UNSIGNED NOT NULL,
  `socket` bigint(20) UNSIGNED NOT NULL,
  `ack` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `ack_time` bigint(20) UNSIGNED DEFAULT NULL,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `virtual_id` bigint(20) NOT NULL,
  `created` bigint(20) NOT NULL,
  `edited` bigint(20) DEFAULT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cached_content` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `number_verification`;
CREATE TABLE `number_verification` (
  `user` bigint(20) UNSIGNED NOT NULL,
  `number` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` mediumint(9) NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `profile` bigint(20) UNSIGNED NOT NULL,
  `key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `photos`;
CREATE TABLE `photos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `virtual_id` bigint(20) NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `hash` char(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `wall` bigint(20) NOT NULL,
  `virtual_id` bigint(20) UNSIGNED NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `flags` tinyint(1) UNSIGNED DEFAULT NULL,
  `ad` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Jane',
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Doe',
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `privacy` bigint(20) UNSIGNED NOT NULL DEFAULT 1099511627775,
  `left_menu` bigint(20) UNSIGNED NOT NULL DEFAULT 1099511627775,
  `sex` tinyint(1) NOT NULL DEFAULT 1,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `phone` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(90) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coins` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `since` datetime NOT NULL,
  `block_reason` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `reputation` bigint(20) NOT NULL DEFAULT 1000,
  `shortcode` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registering_ip` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '127.0.0.1',
  `online` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `birthday` bigint(20) DEFAULT 0,
  `hometown` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `polit_views` int(2) DEFAULT 0,
  `marital_status` int(2) DEFAULT 0,
  `email_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telegram` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interests` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fav_music` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fav_films` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fav_shows` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fav_books` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fav_quote` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `style` int(30) DEFAULT 0,
  `style_avatar` int(30) DEFAULT 0,
  `show_rating` tinyint(1) DEFAULT 1,
  `milkshake` tinyint(1) NOT NULL DEFAULT 0,
  `notification_offset` bigint(20) UNSIGNED DEFAULT 0,
  `deleted` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stickerpacks`;
CREATE TABLE `stickerpacks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `sold` tinyint(4) NOT NULL DEFAULT 0,
  `price` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `stickers`;
CREATE TABLE `stickers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `hash` char(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emojis` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sticker_relations`;
CREATE TABLE `sticker_relations` (
  `sticker` bigint(20) UNSIGNED NOT NULL,
  `pack` bigint(20) UNSIGNED NOT NULL,
  `index` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `follower` bigint(20) UNSIGNED NOT NULL,
  `model` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` bigint(20) UNSIGNED NOT NULL,
  `deleted` tinyint(2) NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext NOT NULL,
  `text` longtext NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `tickets_comments`;
CREATE TABLE `tickets_comments` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` int(11) NOT NULL DEFAULT 0,
  `text` longtext NOT NULL,
  `created` int(11) NOT NULL,
  `deleted` tinyint(2) NOT NULL DEFAULT 0,
  `ticket_id` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `videos`;
CREATE TABLE `videos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) NOT NULL,
  `virtual_id` bigint(20) NOT NULL,
  `created` bigint(20) UNSIGNED NOT NULL,
  `edited` bigint(20) UNSIGNED DEFAULT NULL,
  `hash` char(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `description` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `album_relations`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `attachments`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `audios`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `audio_relations`
  ADD UNIQUE KEY `index` (`index`);

ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `conv_sockets`
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shortcode` (`shortcode`);

ALTER TABLE `group_coadmins`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `likes`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `msg_descriptors`
  ADD UNIQUE KEY `index` (`index`);

ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `number_verification`
  ADD PRIMARY KEY (`user`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `posts` ADD FULLTEXT KEY `content` (`content`);

ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `shortcode` (`shortcode`),
  ADD KEY `user` (`user`);

ALTER TABLE `stickerpacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `stickers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash` (`hash`);

ALTER TABLE `sticker_relations`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `tickets_comments`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `albums`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `album_relations`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `attachments`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `audios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `audio_relations`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `conv_sockets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `group_coadmins`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `likes`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE `msg_descriptors`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `photos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stickerpacks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stickers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `sticker_relations`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `tickets_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `videos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;




INSERT INTO `ChandlerGroups` VALUES ("a8ced6a3-49d8-11ea-bf2f-424d781d39ac", "OVK\Subteno", NULL);

INSERT INTO `ChandlerACLRelations` VALUES ("ffffffff-ffff-ffff-ffff-ffffffffffff", "a8ced6a3-49d8-11ea-bf2f-424d781d39ac", 16);

INSERT INTO `profiles` (`id`, `user`, `first_name`, `last_name`, `pseudo`, `info`, `about`, `status`, `privacy`, `left_menu`, `sex`, `type`, `phone`, `email`, `coins`, `since`, `block_reason`, `verified`, `reputation`, `shortcode`, `registering_ip`, `online`, `birthday`, `hometown`, `polit_views`, `marital_status`, `email_contact`, `telegram`, `interests`, `fav_music`, `fav_films`, `fav_shows`, `fav_books`, `fav_quote`, `city`, `address`, `style`, `style_avatar`, `show_rating`, `milkshake`, `notification_offset`, `deleted`) VALUES ('1', 'ffffffff-ffff-ffff-ffff-ffffffffffff', 'System', 'Administrator', 'sysop', NULL, NULL, NULL, '1099511627775', '1099511627775', '1', '0', NULL, 'admin@localhost.localdomain6', '0', '2018-10-31 15:15:15', NULL, '1', '1000', 'sysop', '::1', '0', '0', NULL, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '1', '0', '0');
