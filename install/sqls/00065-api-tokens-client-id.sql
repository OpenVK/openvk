ALTER TABLE `api_tokens`
    ADD COLUMN IF NOT EXISTS `client_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `platform`;

UPDATE `api_tokens` SET `client_id` = 2274003 WHERE `platform` IN ('vk_android', 'android', 'VK for Android') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 2685278 WHERE `platform` IN ('kate', 'Kate Mobile') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 3140623 WHERE `platform` IN ('vk_iphone', 'iphone', 'ios', 'VK for iPhone') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 3034484 WHERE `platform` IN ('vk_ipad', 'VK for iPad') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 3502561 WHERE `platform` IN ('vk_windows_8', 'windows_8', 'windows8', 'Windows 8') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 3680547 WHERE `platform` IN ('vk_ios', 'VK for iOS') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 3697615 WHERE `platform` IN ('vk_windows', 'VK for Windows') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 4083558 WHERE `platform` IN ('vfeed', 'VFeed') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 5027722 WHERE `platform` IN ('vk_wphone', 'wphone', 'windows_phone', 'VK for Windows Phone') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 5030499 WHERE `platform` IN ('vk_messenger', 'VK Messenger') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 6146827 WHERE `platform` IN ('vk_me', 'VK Me') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10001 WHERE `platform` = 'openvk_legacy_android' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10002 WHERE `platform` = 'openvk_refresh_android' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10003 WHERE `platform` IN ('openvk_flux_android', 'openvk_legacy_ios') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10004 WHERE `platform` IN ('openvk_native', 'openvk_native_ios') AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10005 WHERE `platform` = 'openvk_ios' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10006 WHERE `platform` = 'vk4me' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10007 WHERE `platform` = 'vika_touch' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 10008 WHERE `platform` = 'Matcha' AND `client_id` IS NULL;
UPDATE `api_tokens` SET `client_id` = 20000 WHERE `platform` = 'renaissance' AND `client_id` IS NULL;
