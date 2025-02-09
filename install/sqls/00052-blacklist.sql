CREATE TABLE `blacklist_relations` (
  `index` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author` BIGINT UNSIGNED NOT NULL,
  `target` BIGINT UNSIGNED NOT NULL,
  `created` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`index`)
) ENGINE = InnoDB;
ALTER TABLE `blacklist_relations` ADD INDEX(`author`, `target`);
