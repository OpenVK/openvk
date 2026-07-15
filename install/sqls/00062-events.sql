ALTER TABLE `groups`
ADD `location` varchar(128) COLLATE 'utf8mb4_unicode_520_ci' NULL AFTER `website`,
ADD `host` bigint NULL AFTER `location`,
ADD `start_date` bigint unsigned NULL AFTER `host`,
ADD `finish_date` bigint unsigned NULL AFTER `start_date`;
