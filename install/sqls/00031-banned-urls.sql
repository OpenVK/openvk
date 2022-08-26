CREATE TABLE `links_banned` (
    `id` bigint NOT NULL,
    `domain` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
    `regexp_rule` text NOT NULL,
    `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
    `initiator` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `links_banned`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `links_banned`
    MODIFY `id` bigint NOT NULL AUTO_INCREMENT;
COMMIT;