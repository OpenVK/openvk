ALTER TABLE `groups` ADD COLUMN `hide_from_global_feed` boolean DEFAULT 0 NOT NULL AFTER `display_topics_above_wall`;
