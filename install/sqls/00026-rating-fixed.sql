ALTER TABLE `profiles` CHANGE `coins` `coins` bigint(20) unsigned NOT NULL DEFAULT '0' AFTER `email`;
ALTER TABLE `profiles` ADD COLUMN `rating` bigint(20) unsigned NOT NULL DEFAULT '0' AFTER `coins`;
