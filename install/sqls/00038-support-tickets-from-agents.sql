ALTER TABLE `tickets`
    ADD `support_sender` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `created`;
