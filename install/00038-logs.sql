CREATE TABLE `logs`
(
    `id`           bigint(20) UNSIGNED NOT NULL,
    `user`         bigint(20) UNSIGNED NOT NULL,
    `type`         int(11) NOT NULL,
    `object_table` tinytext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `object_model` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
    `object_id`    bigint(20) UNSIGNED NOT NULL,
    `xdiff_old`    longtext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `xdiff_new`    longtext COLLATE utf8mb4_unicode_ci   NOT NULL,
    `ts`           bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `logs`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `logs`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
