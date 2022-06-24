ALTER TABLE `notifications` CHANGE `modelAction` `modelAction` mediumint(3) unsigned NOT NULL AFTER `targetModelId`;
ALTER TABLE `notifications` CHANGE `additionalData` `additionalData` text COLLATE 'utf8mb4_general_ci' NOT NULL AFTER `modelAction`;
