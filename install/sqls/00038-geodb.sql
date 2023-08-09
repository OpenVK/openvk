CREATE TABLE `geodb_cities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_request` bigint(20) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `geodb_cities` (`id`, `country`, `name`, `native_name`, `deleted`, `is_request`) VALUES
(1, 1, 'Voskresensk', 'Воскресенск', 0, 0),
(2, 2, 'Los Angeles', 'Лос-Анджелес', 0, 0);

CREATE TABLE `geodb_countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_log` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `geodb_countries` (`id`, `code`, `flag`, `name`, `native_name`, `deleted`, `is_log`) VALUES
(1, 'ru', 'ru', 'Russia', 'Россия', 0, 0),
(2, 'us', 'us', 'USA', 'США', 0, 0);

CREATE TABLE `geodb_editors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uid` bigint(20) UNSIGNED NOT NULL,
  `country` bigint(20) UNSIGNED NOT NULL,
  `edu` tinyint(1) NOT NULL DEFAULT 0,
  `cities` tinyint(1) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geodb_faculties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `university` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_request` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geodb_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` bigint(20) UNSIGNED NOT NULL,
  `type` int(11) NOT NULL,
  `object_table` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_model` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_id` bigint(20) NOT NULL,
  `logs_text` longtext COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geodb_schools` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country` bigint(20) UNSIGNED NOT NULL,
  `city` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_request` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geodb_specializations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country` bigint(20) UNSIGNED NOT NULL,
  `faculty` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_request` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `geodb_universities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `country` bigint(20) UNSIGNED NOT NULL,
  `city` bigint(20) UNSIGNED NOT NULL,
  `name` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_request` bigint(20) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `geodb_cities`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_countries`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_editors`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_faculties`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_schools`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_specializations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_universities`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `geodb_cities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `geodb_countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `geodb_editors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geodb_faculties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geodb_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geodb_schools`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geodb_specializations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geodb_universities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `profiles`
    ADD `country` VARCHAR(60) NULL DEFAULT NULL AFTER `activated`,
    ADD `country_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `country`,
    ADD `city_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `country_id`,
    ADD `school_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `city_id`,
    ADD `school_years` TINYTEXT NULL DEFAULT NULL AFTER `school_id`,
    ADD `school_specialization` TINYTEXT NULL DEFAULT NULL AFTER `school_years`,
    ADD `university_years` TINYTEXT NULL DEFAULT NULL AFTER `school_specialization`,
    ADD `university_specialization` TINYTEXT NULL DEFAULT NULL AFTER `university_years`,
    ADD `university` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `university_specialization`,
    ADD `university_faculty` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `university`;
