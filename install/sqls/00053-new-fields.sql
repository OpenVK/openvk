ALTER TABLE `profiles` ADD `fav_games` MEDIUMTEXT NULL DEFAULT NULL AFTER `fav_quote`;
CREATE TABLE `additional_fields` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT, 
    `owner` BIGINT(20) UNSIGNED NOT NULL, 
    `name` VARCHAR(255) COLLATE utf8mb4_unicode_520_ci NOT NULL, 
    `text` MEDIUMTEXT COLLATE utf8mb4_unicode_520_ci NOT NULL, 
    `place` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0', 
    PRIMARY KEY (`id`)
) ENGINE = InnoDB;
ALTER TABLE `additional_fields` ADD INDEX(`owner`);
