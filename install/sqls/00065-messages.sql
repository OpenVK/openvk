SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `documents` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`;
ALTER TABLE `videos` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`, ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `link`;
ALTER TABLE `photos` ADD `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `height`;
ALTER TABLE `photos` ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `unlisted`;

ALTER TABLE `photos` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `videos` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `documents` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `audios` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `deleted`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `audios` ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `unlisted`;

CREATE TABLE `chats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(4096) NOT NULL DEFAULT '',
  `photo_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_chats_photo_id` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `blacklist_relations` CHANGE `author` `author` BIGINT NULL, CHANGE `target` `target` BIGINT NULL;
ALTER TABLE `blacklist_relations` ADD `reason` VARCHAR(255) NULL AFTER `target`, ADD `until` BIGINT UNSIGNED NULL AFTER `reason`;

DROP TABLE IF EXISTS `stickers`;
DROP TABLE IF EXISTS `stickerpacks`;
DROP TABLE IF EXISTS `sticker_relations`;

ALTER TABLE `chats` ADD COLUMN `photos_history` text DEFAULT NULL AFTER `photo_id`;
ALTER TABLE `topics` ADD `chat_id` BIGINT UNSIGNED DEFAULT NULL AFTER `flags`;
ALTER TABLE `chats` ADD `edited` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `description`;
ALTER TABLE `gift_user_relations` ADD `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `sent`;

ALTER TABLE `groups` ADD COLUMN `is_messages_enabled` tinyint(1) NOT NULL DEFAULT '0' AFTER `enforce_hiding_from_global_feed`;
ALTER TABLE `groups` ADD COLUMN `everyone_can_upload_videos` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_messages_enabled`;
ALTER TABLE `groups` ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_messages_enabled`;

CREATE TABLE IF NOT EXISTS `stickers` (
  `id` bigint(20) unsigned NOT NULL,
  `emoji` varchar(64) NOT NULL DEFAULT '',
  `unlisted` tinyint(1) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stickerpacks` (
  `id` bigint(20) unsigned NOT NULL,
  `name` varchar(256) NOT NULL DEFAULT 'Unnamed pack',
  `description` text,
  `main_sticker_id` bigint(20) unsigned DEFAULT NULL,
  `author` varchar(256) DEFAULT NULL,
  `author_id` varchar(256) DEFAULT NULL,
  `author_url` varchar(512) DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `slug` varchar(128) NOT NULL,
  `price` smallint(5) unsigned NOT NULL DEFAULT '0',
  `coins` decimal(20,6) NOT NULL DEFAULT '0.000000',
  `end_time` bigint(20) unsigned DEFAULT NULL,
  `unlisted` tinyint(1) NOT NULL DEFAULT '0',
  `gift_sticker_id` bigint(20) unsigned DEFAULT NULL,
  `created` bigint(20) unsigned NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stickerpack_relations` (
  `id` bigint(20) unsigned NOT NULL,
  `stickerpack` bigint(20) unsigned NOT NULL,
  `sticker` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sticker_purchases` (
  `id` bigint(20) unsigned NOT NULL,
  `user` bigint(20) unsigned NOT NULL,
  `stickerpack` bigint(20) unsigned NOT NULL,
  `purchased` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `stickers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted` (`deleted`);

ALTER TABLE `stickerpacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `deleted` (`deleted`),
  ADD KEY `main_sticker_id` (`main_sticker_id`),
  ADD KEY `gift_sticker_id` (`gift_sticker_id`),
  ADD KEY `owner_id` (`owner_id`);

ALTER TABLE `stickerpack_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stickerpack` (`stickerpack`),
  ADD KEY `sticker` (`sticker`);

ALTER TABLE `sticker_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `stickerpack` (`stickerpack`);

ALTER TABLE `stickers`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `stickerpacks`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `stickerpack_relations`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `sticker_purchases`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `stickerpacks`
  ADD CONSTRAINT `FK_stickerpack_main_sticker` FOREIGN KEY (`main_sticker_id`) REFERENCES `stickers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_stickerpack_gift_sticker` FOREIGN KEY (`gift_sticker_id`) REFERENCES `stickers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_stickerpack_owner` FOREIGN KEY (`owner_id`) REFERENCES `profiles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `stickerpack_relations`
  ADD CONSTRAINT `FK_sprel_stickerpack` FOREIGN KEY (`stickerpack`) REFERENCES `stickerpacks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_sprel_sticker` FOREIGN KEY (`sticker`) REFERENCES `stickers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `sticker_purchases`
  ADD CONSTRAINT `FK_spurchase_user` FOREIGN KEY (`user`) REFERENCES `profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_spurchase_stickerpack` FOREIGN KEY (`stickerpack`) REFERENCES `stickerpacks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `profiles`
  ADD `can_create_stickers` tinyint(1) NOT NULL DEFAULT '0';
