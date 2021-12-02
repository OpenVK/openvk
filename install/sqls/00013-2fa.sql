ALTER TABLE `profiles` ADD COLUMN `2fa_secret` VARCHAR(26);
ALTER TABLE `groups` ADD COLUMN `2fa_required` BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS `2fa_backup_codes` (
    `owner` bigint(20) unsigned NOT NULL,
    `code` int unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `2fa_backup_codes`
  ADD KEY `code` (`code`,`owner`),
  ADD KEY `FK_ownerToCode` (`owner`);
