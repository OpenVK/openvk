SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `coin_vouchers` (
  `id` bigint(20) unsigned NOT NULL,
  `coins` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rating` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `token` char(24) DEFAULT NULL,
  `usages_left` smallint(5) unsigned DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TRIGGER `coinVoucherTokenAutoGen` BEFORE INSERT ON `coin_vouchers`
 FOR EACH ROW IF NEW.token IS NULL THEN
	SET NEW.token = SUBSTRING(UPPER(REPLACE(UUID(), "-", "")), 1, 24);
END IF;

CREATE TABLE IF NOT EXISTS `gifts` (
  `id` bigint(20) unsigned NOT NULL,
  `internal_name` varchar(256) NOT NULL DEFAULT 'Unnamed gift',
  `price` smallint(5) unsigned NOT NULL,
  `usages` int(10) unsigned NOT NULL DEFAULT '0',
  `image` mediumblob NOT NULL,
  `limit` tinyint(3) unsigned DEFAULT NULL,
  `limit_period` bigint(20) unsigned DEFAULT NULL,
  `updated` bigint(20) unsigned NOT NULL DEFAULT '0',
  `deleted` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gift_categories` (
  `id` bigint(20) unsigned NOT NULL,
  `autoquery` varchar(512) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gift_categories_locales` (
  `id` bigint(20) unsigned NOT NULL,
  `category` bigint(20) unsigned NOT NULL,
  `language` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(1024) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gift_relations` (
  `id` bigint(20) unsigned NOT NULL,
  `gift` bigint(20) unsigned NOT NULL,
  `category` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gift_user_relations` (
  `id` bigint(20) unsigned NOT NULL,
  `gift` bigint(20) unsigned NOT NULL,
  `sender` bigint(20) unsigned NOT NULL,
  `receiver` bigint(20) unsigned NOT NULL,
  `comment` text,
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `sent` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `voucher_users` (
  `id` bigint(20) unsigned NOT NULL,
  `voucher` bigint(20) unsigned NOT NULL,
  `user` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `coin_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`,`deleted`);

ALTER TABLE `gifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted` (`deleted`);

ALTER TABLE `gift_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted` (`deleted`);

ALTER TABLE `gift_categories_locales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category` (`category`),
  ADD KEY `category_2` (`category`,`language`);

ALTER TABLE `gift_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gift` (`gift`),
  ADD KEY `category` (`category`);

ALTER TABLE `gift_user_relations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gift` (`gift`),
  ADD KEY `sender` (`sender`),
  ADD KEY `receiver` (`receiver`),
  ADD KEY `sent` (`sent`);

ALTER TABLE `voucher_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher` (`voucher`,`user`),
  ADD KEY `FK_userToVoucher` (`user`);

ALTER TABLE `coin_vouchers`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `gifts`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `gift_categories`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `gift_categories_locales`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `gift_relations`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `gift_user_relations`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `voucher_users`
  MODIFY `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE `gift_categories_locales`
  ADD CONSTRAINT `FK_localeToGiftCat` FOREIGN KEY (`category`) REFERENCES `gift_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `gift_relations`
  ADD CONSTRAINT `FK_categoryToGift` FOREIGN KEY (`category`) REFERENCES `gift_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_giftToCategory` FOREIGN KEY (`gift`) REFERENCES `gifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `gift_user_relations`
  ADD CONSTRAINT `FK_giftToReceiver` FOREIGN KEY (`receiver`) REFERENCES `profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_giftToSender` FOREIGN KEY (`sender`) REFERENCES `profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_receiverToGift` FOREIGN KEY (`gift`) REFERENCES `gifts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `voucher_users`
  ADD CONSTRAINT `FK_userToVoucher` FOREIGN KEY (`user`) REFERENCES `profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_voucherToVoucher` FOREIGN KEY (`voucher`) REFERENCES `coin_vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
