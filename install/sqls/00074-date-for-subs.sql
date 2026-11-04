ALTER TABLE `subscriptions`
ADD `date` bigint unsigned NOT NULL DEFAULT unix_timestamp() AFTER `flags`;
