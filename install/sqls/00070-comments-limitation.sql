ALTER TABLE `photos` ADD `comment_status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `description`;
ALTER TABLE `notes` ADD `comment_status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `deleted`;
ALTER TABLE `posts` ADD `comment_status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `flags`;
ALTER TABLE `videos` ADD `comment_status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `deleted`;
