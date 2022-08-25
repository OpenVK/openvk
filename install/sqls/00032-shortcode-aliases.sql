CREATE TABLE `aliases` (
    `id` bigint NOT NULL,
    `shortcode` varchar(36) NOT NULL,
    `index` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `aliases`
    ADD PRIMARY KEY (`index`);

ALTER TABLE `aliases`
    MODIFY `index` bigint NOT NULL AUTO_INCREMENT;
COMMIT;