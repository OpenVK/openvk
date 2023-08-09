CREATE TABLE `support_templates_dirs`
(
    `id`        bigint(20) UNSIGNED                 NOT NULL,
    `owner`     bigint(20) UNSIGNED                 NOT NULL,
    `is_public` tinyint(1)                          NOT NULL DEFAULT 0,
    `title`     tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
    `deleted`   tinyint(1)                          NOT NULL DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

ALTER TABLE `support_templates_dirs`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `support_templates_dirs`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

CREATE TABLE `support_templates`
(
    `id`      bigint(20) UNSIGNED                 NOT NULL,
    `owner`   bigint(20) UNSIGNED                 NOT NULL,
    `dir`     bigint(20) UNSIGNED                 NOT NULL,
    `title`   tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
    `text`    longtext COLLATE utf8mb4_unicode_ci NOT NULL,
    `deleted` tinyint(1)                          NOT NULL DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

ALTER TABLE `support_templates`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `support_templates`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
