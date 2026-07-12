SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `stickers`;
DROP TABLE IF EXISTS `stickerpacks`;
DROP TABLE IF EXISTS `sticker_relations`;

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
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `slug` varchar(128) NOT NULL,
  `price` smallint(5) unsigned NOT NULL DEFAULT '0',
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
