CREATE TABLE `ignored_sources` (`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT , `owner` BIGINT(20) UNSIGNED NOT NULL , `source` BIGINT(20) NOT NULL , PRIMARY KEY (`id`), INDEX (`owner`)) ENGINE = InnoDB;
ALTER TABLE `ignored_sources` ADD INDEX `owner_source` (`owner`, `source`);
