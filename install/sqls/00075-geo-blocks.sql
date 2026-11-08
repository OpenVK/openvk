CREATE TABLE IF NOT EXISTS `geo_blocks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(16) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `external_ref` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` bigint(20) UNSIGNED NOT NULL,
  `revoked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `revoked_at` bigint(20) UNSIGNED DEFAULT NULL,
  `revoke_reason` text COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `active` (`entity_type`, `entity_id`, `country_code`, `revoked_at`),
  KEY `country` (`country_code`, `revoked_at`),
  KEY `parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `photos`
  ADD KEY `hash` (`hash`);

ALTER TABLE `videos`
  ADD KEY `hash` (`hash`);

ALTER TABLE `documents`
  ADD KEY `hash` (`hash`);

ALTER TABLE `audios`
  ADD KEY `hash` (`hash`);

ALTER TABLE `album_relations`
  ADD KEY `media` (`media`);
