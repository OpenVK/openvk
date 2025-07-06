ALTER TABLE `profiles` 
ADD `events_counters` VARCHAR(299) NULL DEFAULT NULL AFTER `audio_broadcast_enabled`, 
ADD `events_refresh_time` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `events_counters`;
