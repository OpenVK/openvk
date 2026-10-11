ALTER TABLE `attachments`
  ADD KEY `attachable` (`attachable_type`, `attachable_id`);

ALTER TABLE `album_relations`
  ADD KEY `media` (`media`);
