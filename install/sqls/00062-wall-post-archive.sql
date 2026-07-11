ALTER TABLE `posts`
    ADD COLUMN `archived` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `suggested`,
    DROP INDEX `wall_deleted_suggested`,
    ADD INDEX `wall_deleted_suggested_archived` (`wall`, `deleted`, `suggested`, `archived`);
