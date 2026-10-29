CREATE TABLE `geoblocks` (
    `id` bigint UNSIGNED NOT NULL,
    `owner` bigint NOT NULL,
    `modelCode` int NOT NULL,
    `modelId` bigint NOT NULL,
    `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
    `created` bigint NOT NULL,
    `edited` bigint DEFAULT NULL,
    `time` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
    `places` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

ALTER TABLE `geoblocks`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `geoblocks`
    MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `geoblocks` ADD INDEX(`modelCode`, `modelId`);
