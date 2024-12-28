CREATE TABLE `documents` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, 
    `owner` BIGINT(20) NOT NULL, 
    `virtual_id` BIGINT(20) UNSIGNED NOT NULL, 
    `hash` CHAR(128) NOT NULL, 
    `owner_hidden` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1', 
    `copy_of` BIGINT(20) UNSIGNED NULL DEFAULT NULL, 
    `created` BIGINT(20) UNSIGNED NOT NULL, 
    `edited` BIGINT(20) UNSIGNED NULL DEFAULT NULL, 
    `name` VARCHAR(256) NOT NULL,
    `original_name` VARCHAR(500) NULL DEFAULT NULL, 
    `access_key` VARCHAR(100) NULL DEFAULT NULL,
    `format` VARCHAR(20) NOT NULL DEFAULT 'gif', 
    `type` TINYINT(10) UNSIGNED NOT NULL DEFAULT '0', 
    `folder_id` TINYINT(10) UNSIGNED NOT NULL DEFAULT '0', 
    `preview` VARCHAR(200) NULL DEFAULT NULL,
    `tags` VARCHAR(500) NULL DEFAULT NULL, 
    `filesize` BIGINT(20) UNSIGNED NOT NULL, 
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', 
    `unlisted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', 
    PRIMARY KEY (`id`)
) ENGINE = InnoDB COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `documents` ADD INDEX (`deleted`);
ALTER TABLE `documents` ADD INDEX (`unlisted`);
ALTER TABLE `documents` ADD INDEX `virtual_id_id` (`virtual_id`, `id`);
ALTER TABLE `documents` ADD INDEX `folder_id` (`folder_id`);
ALTER TABLE `photos` ADD `system` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `anonymous`, ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `system`;
