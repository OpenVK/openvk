ALTER TABLE `apps` 
ADD `deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `enabled`;

ALTER TABLE `app_users`
ADD `deleted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `access`;
