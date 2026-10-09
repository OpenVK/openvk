ALTER TABLE `gifts` ADD `stickers_product_id` bigint(20) unsigned DEFAULT NULL;
ALTER TABLE `gifts` ADD KEY `stickers_product_id` (`stickers_product_id`);
