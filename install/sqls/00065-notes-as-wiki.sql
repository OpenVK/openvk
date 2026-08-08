-- Unify group wiki pages into notes (club notes use owner = -group_id).

ALTER TABLE `notes`
  ADD COLUMN `format` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `source`,
  ADD COLUMN `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `owner`,
  ADD COLUMN `is_main` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `deleted`,
  ADD COLUMN `view_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `is_main`,
  ADD COLUMN `edit_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2 AFTER `view_access`,
  ADD COLUMN `keep_revisions` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `edit_access`;

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

-- Migrate existing group wiki pages into notes (idempotent-ish: skip if already migrated).
INSERT INTO `notes` (
    `owner`, `created_by`, `virtual_id`, `created`, `edited`, `name`, `source`, `cached_content`,
    `format`, `deleted`, `is_main`, `view_access`, `edit_access`, `keep_revisions`, `anonymous`
)
SELECT
    -CAST(`group` AS SIGNED),
    `owner`,
    `virtual_id`,
    `created`,
    `edited`,
    `title`,
    `source`,
    `cached_html`,
    1,
    `deleted`,
    `is_main`,
    `view_access`,
    `edit_access`,
    1,
    0
FROM `group_pages` gp
WHERE NOT EXISTS (
    SELECT 1 FROM `notes` n
    WHERE n.`owner` = -CAST(gp.`group` AS SIGNED)
      AND n.`virtual_id` = gp.`virtual_id`
);

INSERT INTO `note_revisions` (`note`, `editor`, `title`, `source`, `created`)
SELECT
    n.`id`,
    r.`editor`,
    r.`title`,
    r.`source`,
    r.`created`
FROM `group_page_revisions` r
INNER JOIN `group_pages` gp ON gp.`id` = r.`page`
INNER JOIN `notes` n
    ON n.`owner` = -CAST(gp.`group` AS SIGNED)
   AND n.`virtual_id` = gp.`virtual_id`
WHERE NOT EXISTS (
    SELECT 1 FROM `note_revisions` nr
    WHERE nr.`note` = n.`id` AND nr.`created` = r.`created` AND nr.`editor` = r.`editor`
);
