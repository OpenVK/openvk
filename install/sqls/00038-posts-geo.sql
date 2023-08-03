ALTER TABLE `posts`
    ADD `geo`     LONGTEXT       NULL DEFAULT NULL AFTER `deleted`,
    ADD `geo_lat` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `geo`,
    ADD `geo_lon` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `geo_lat`;
