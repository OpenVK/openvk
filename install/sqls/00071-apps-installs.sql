ALTER TABLE `apps`
  ADD COLUMN `installs` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `coins`,
  ADD INDEX `catalog_installs` (`enabled`, `deleted`, `installs`);

UPDATE `apps`
  JOIN (SELECT `app`, COUNT(*) AS `n` FROM `app_users` WHERE `deleted` = 0 GROUP BY `app`) AS `counts` ON `counts`.`app` = `apps`.`id`
  SET `apps`.`installs` = `counts`.`n`;
