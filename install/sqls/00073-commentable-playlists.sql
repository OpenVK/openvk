ALTER TABLE `playlists` ADD `comment_status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`;
ALTER TABLE `albums` ADD `virtual_id` BIGINT UNSIGNED NULL AFTER `owner`;
