ALTER TABLE `notes`
  ADD COLUMN `comment_access` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `edit_access`;
