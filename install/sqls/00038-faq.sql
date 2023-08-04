CREATE TABLE `faq_categories`
(
    `id`              bigint(20) UNSIGNED NOT NULL,
    `title`           tinytext            NOT NULL,
    `for_agents_only` tinyint(1) DEFAULT NULL,
    `icon`            int(11)             NOT NULL,
    `language`        varchar(255)        NOT NULL,
    `deleted`         tinyint(1) DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

ALTER TABLE `faq_categories`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `faq_categories`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

CREATE TABLE `faq_articles`
(
    `id`               bigint(20) UNSIGNED NOT NULL,
    `category`         bigint(20) UNSIGNED DEFAULT NULL,
    `title`            mediumtext          NOT NULL,
    `text`             longtext            NOT NULL,
    `users_can_see`    tinyint(1)          DEFAULT NULL,
    `unlogged_can_see` tinyint(1)          DEFAULT NULL,
    `language`         varchar(255)        NOT NULL,
    `deleted`          tinyint(1)          DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

ALTER TABLE `faq_articles`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `faq_articles`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;
