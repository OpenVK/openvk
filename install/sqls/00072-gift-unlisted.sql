-- Delisting of gifts and gift categories: hide from the catalog without touching sent gifts.
ALTER TABLE `gifts`           ADD `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `gift_categories` ADD `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
