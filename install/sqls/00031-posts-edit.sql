ALTER TABLE `posts` ADD `change_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `deleted`;

CREATE TABLE `posts_changes` (
    `id` bigint NOT NULL,
    `wall_id` bigint NOT NULL,
    `virtual_id` bigint NOT NULL,
    `newContent` longtext NOT NULL,
    `created` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `posts_changes`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `posts_changes`
    MODIFY `id` bigint NOT NULL AUTO_INCREMENT;
COMMIT;
