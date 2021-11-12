ALTER TABLE `groups` ADD COLUMN `owner_club_pinned` BOOLEAN NOT NULL DEFAULT FALSE AFTER `owner_hidden`;
ALTER TABLE `group_coadmins` ADD COLUMN `club_pinned` BOOLEAN DEFAULT FALSE NOT NULL; 
