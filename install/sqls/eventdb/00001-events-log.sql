CREATE TABLE `user-events` 
(
    `initiatorId` BIGINT(20) UNSIGNED NOT NULL, 
    `initiatorIp` VARBINARY(16) NULL DEFAULT NULL,
    `receiverId` BIGINT(20) NULL DEFAULT NULL, 
    `eventType` CHAR(25) NOT NULL, 
    `eventTime` BIGINT(20) UNSIGNED NOT NULL
) ENGINE = InnoDB;
