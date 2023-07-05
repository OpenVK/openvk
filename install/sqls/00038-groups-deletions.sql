ALTER TABLE `groups` ADD `deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `backdrop_2`; 
ALTER TABLE `profiles` ADD `profile_type` TINYINT(1) NOT NULL DEFAULT '0' AFTER `client_name`;