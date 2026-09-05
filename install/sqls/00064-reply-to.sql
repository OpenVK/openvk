ALTER TABLE `comments`
ADD `reply_to` bigint(20) unsigned NULL AFTER `anonymous`;
