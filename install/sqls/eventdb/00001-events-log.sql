CREATE TABLE `user-events` 
(
    `initiatorId` BIGINT(20) NOT NULL, 
    `initiatorIp` VARBINARY(16) NULL DEFAULT NULL,
    `receiverId` BIGINT(20) NOT NULL, 
    `eventType` CHAR(25) NOT NULL, 
    `eventTime` BIGINT(20) NOT NULL
) ENGINE = InnoDB;
