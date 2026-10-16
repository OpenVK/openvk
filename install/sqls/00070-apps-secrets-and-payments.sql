ALTER TABLE `apps`
  ADD COLUMN `secret` CHAR(43) NULL DEFAULT NULL,
  ADD COLUMN `strict` BIT(1) NOT NULL DEFAULT b'0';

CREATE TABLE IF NOT EXISTS `app_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `app` bigint unsigned NOT NULL,
  `user` bigint unsigned NOT NULL,
  `order_id` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL, -- exact: Order-A and order-a are different orders
  `amount` decimal(20,6) NOT NULL,
  `created` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_order` (`app`, `order_id`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
