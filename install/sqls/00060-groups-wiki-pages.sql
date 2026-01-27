ALTER TABLE `groups`
ADD `main_note_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `audio_broadcast_enabled`,
ADD `is_main_note_expanded` TINYINT(1) NOT NULL DEFAULT '0' AFTER `main_note_id`,
ADD `enforce_main_note_expanded` TINYINT(1) NOT NULL DEFAULT '0' AFTER `is_main_note_expanded`,
ADD `enforce_wiki_pages_disabled` TINYINT(1) NOT NULL DEFAULT '0' AFTER `enforce_main_note_expanded`;
