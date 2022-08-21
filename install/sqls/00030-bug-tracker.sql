/* bugs */

CREATE TABLE `bugs` (
  `id` bigint(20) NOT NULL,
  `reporter` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` varchar(1000) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 2,
  `device` varchar(255) NOT NULL,
  `reproduced` bigint(20) NOT NULL DEFAULT 0,
  `deleted` tinyint(1) DEFAULT NULL,
  `created` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `bugs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bugs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/* bt_products */

CREATE TABLE `bt_products` (
  `id` bigint(20) NOT NULL,
  `creator_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` varchar(10000) NOT NULL,
  `created` bigint(20) NOT NULL,
  `closed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `bt_products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bt_products`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/* bt_comments */

CREATE TABLE `bt_comments` (
  `id` bigint(20) NOT NULL,
  `report` bigint(20) NOT NULL,
  `author` bigint(20) NOT NULL,
  `is_moder` tinyint(1) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT NULL,
  `text` longtext NOT NULL,
  `label` varchar(50) NOT NULL,
  `point_actions` bigint(20) DEFAULT NULL,
  `created` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `bt_comments`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `bt_comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;