ALTER TABLE `documents` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`;
ALTER TABLE `videos` ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `unlisted`, ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `link`;
ALTER TABLE `photos` ADD `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `height`;
ALTER TABLE `photos` ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `unlisted`;

ALTER TABLE `photos` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `videos` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `documents` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `private`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `audios` ADD `context_id` BIGINT(20) DEFAULT NULL AFTER `deleted`, ADD `context_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_id`, ADD `context_unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `context_admin`, ADD `context_vid` BIGINT(20) UNSIGNED DEFAULT NULL AFTER `context_unlisted`;
ALTER TABLE `audios` ADD `access_key` VARCHAR(100) NULL DEFAULT NULL AFTER `unlisted`;
