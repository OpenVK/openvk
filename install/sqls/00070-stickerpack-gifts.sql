ALTER TABLE `gifts` ADD `stickers_product_id` bigint(20) unsigned DEFAULT NULL;
ALTER TABLE `gifts` ADD KEY `stickers_product_id` (`stickers_product_id`);
ALTER TABLE `stickerpacks` ADD `gift_stickers` varchar(256) DEFAULT NULL AFTER `gift_sticker_id`;
