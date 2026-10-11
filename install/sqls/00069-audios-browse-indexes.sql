ALTER TABLE `audios`
  ADD INDEX `browse_id` (`unlisted`, `deleted`, `id`),
  ADD INDEX `browse_listens` (`unlisted`, `deleted`, `listens`);
