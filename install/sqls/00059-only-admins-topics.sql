ALTER TABLE `topics`
ADD `restricted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `closed`;
