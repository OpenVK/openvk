ALTER TABLE `groups` ADD COLUMN `pages` TINYINT(1) NOT NULL DEFAULT 0 AFTER `everyone_can_upload_audios`;

CREATE TABLE `group_pages` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` BIGINT(20) UNSIGNED NOT NULL,
    `virtual_id` BIGINT(20) UNSIGNED NOT NULL,
    `owner` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `source` LONGTEXT NOT NULL,
    `cached_html` LONGTEXT NULL DEFAULT NULL,
    `is_main` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `view_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `edit_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 2,
    `created` BIGINT(20) UNSIGNED NOT NULL,
    `edited` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_virtual` (`group`, `virtual_id`),
    KEY `group_deleted_main` (`group`, `deleted`, `is_main`),
    KEY `deleted` (`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

CREATE TABLE `group_page_revisions` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page` BIGINT(20) UNSIGNED NOT NULL,
    `editor` BIGINT(20) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `source` LONGTEXT NOT NULL,
    `created` BIGINT(20) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `page_created` (`page`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
