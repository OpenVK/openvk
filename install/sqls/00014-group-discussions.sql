ALTER TABLE `groups` ADD COLUMN `everyone_can_create_topics` boolean NOT NULL DEFAULT FALSE AFTER `administrators_list_display`;

CREATE TABLE IF NOT EXISTS `topics` (
  `id` bigint(20) unsigned NOT NULL,
  `group` bigint(20) unsigned NOT NULL,
  `owner` bigint(20) unsigned NOT NULL,
  `virtual_id` bigint(20) unsigned NOT NULL,
  `created` bigint(20) unsigned NOT NULL,
  `edited` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(128) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `closed` boolean NOT NULL DEFAULT FALSE,
  `pinned` boolean NOT NULL DEFAULT FALSE,
  `anonymous` boolean NOT NULL DEFAULT FALSE,
  `flags` tinyint(3) unsigned DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group` (`group`);

ALTER TABLE `topics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
