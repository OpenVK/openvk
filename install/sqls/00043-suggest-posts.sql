ALTER TABLE `posts` ADD `suggested` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' AFTER `deleted`, ADD INDEX (`suggested`); 
