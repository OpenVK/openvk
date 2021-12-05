SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `notifications` (
  `recipientType` tinyint(3) UNSIGNED NOT NULL,
  `recipientId` bigint(20) UNSIGNED NOT NULL,
  `originModelType` tinyint(3) UNSIGNED NOT NULL,
  `originModelId` bigint(20) UNSIGNED NOT NULL,
  `targetModelType` tinyint(3) UNSIGNED NOT NULL,
  `targetModelId` bigint(20) UNSIGNED NOT NULL,
  `modelAction` tinyint(3) UNSIGNED NOT NULL,
  `additionalData` char(24) NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

CREATE TABLE `postViews` (
  `profile` bigint(20) UNSIGNED NOT NULL,
  `post` bigint(20) UNSIGNED NOT NULL,
  `owner` bigint(20) UNSIGNED NOT NULL,
  `group` tinyint(3) UNSIGNED NOT NULL,
  `subscribed` tinyint(3) UNSIGNED NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL,
  `verified` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=Aria DEFAULT CHARSET=utf8;

ALTER TABLE `postViews` ADD INDEX(`owner`, `group`, `subscribed`);
ALTER TABLE `notifications` ADD INDEX(`recipientType`, `recipientId`, `timestamp`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
