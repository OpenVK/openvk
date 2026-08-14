-- Club notes (owner = -group_id): wiki fields, optional revisions, and materials flag.

ALTER TABLE `groups` ADD COLUMN `pages` TINYINT(1) NOT NULL DEFAULT 0 AFTER `everyone_can_upload_audios`;

ALTER TABLE `notes`
  ADD COLUMN `format` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `source`,
  ADD COLUMN `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `owner`,
  ADD COLUMN `is_main` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `deleted`,
  ADD COLUMN `view_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `is_main`,
  ADD COLUMN `edit_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 AFTER `view_access`,
  ADD COLUMN `comment_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `edit_access`,
  ADD COLUMN `keep_revisions` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `comment_access`;

ALTER TABLE `notes`
  ADD KEY `owner_deleted_main` (`owner`, `deleted`, `is_main`);

CREATE TABLE IF NOT EXISTS `note_revisions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `note` BIGINT(20) UNSIGNED NOT NULL,
    `editor` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(256) NOT NULL,
    `source` LONGTEXT NOT NULL,
    `created` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `note_created` (`note`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
