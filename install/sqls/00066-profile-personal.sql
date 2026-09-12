ALTER TABLE `profiles` ADD COLUMN `main_in_life` TINYINT(1) NOT NULL DEFAULT '0' AFTER `polit_views`,
    ADD COLUMN `main_in_people` TINYINT(1) NOT NULL DEFAULT '0' AFTER `main_in_life`,
    ADD COLUMN `views_on_smoking` TINYINT(1) NOT NULL DEFAULT '0' AFTER `main_in_people`,
    ADD COLUMN `views_on_alcohol` TINYINT(1) NOT NULL DEFAULT '0' AFTER `views_on_smoking`,
    ADD COLUMN `worldview` VARCHAR(256) NOT NULL DEFAULT '' AFTER `views_on_alcohol`,
    ADD COLUMN `inspires` VARCHAR(256) NOT NULL DEFAULT '' AFTER `worldview`;