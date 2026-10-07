ALTER TABLE `subscriptions`
  ADD INDEX `follower_target` (`follower`, `target`),
  ADD INDEX `target` (`target`);
