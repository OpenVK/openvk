
CREATE TABLE `support_names` (
    `id` bigint(20) UNSIGNED NOT NULL,
    `agent` bigint(20) UNSIGNED NOT NULL,
    `name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
    `icon` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `numerate` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `support_names`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `support_names`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;