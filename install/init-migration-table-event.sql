CREATE TABLE `ovk_events_upgrade_history` (
    `level` smallint UNSIGNED NOT NULL,
    `timestamp` bigint(20) UNSIGNED NOT NULL,
    `operator` varchar(256) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT "Maintenance Script"
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;