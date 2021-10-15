DROP TABLE IF EXISTS `group_contacts`;
CREATE TABLE `group_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` bigint(20) unsigned NOT NULL,
  `user` bigint(20) unsigned NOT NULL,
  `content` varchar(64) COLLATE utf8mb4_general_nopad_ci NOT NULL,
  `email` varchar(64) COLLATE utf8mb4_general_nopad_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `group` (`group`),
  KEY `user` (`user`),
  CONSTRAINT `group_contacts_ibfk_1` FOREIGN KEY (`group`) REFERENCES `groups` (`id`),
  CONSTRAINT `group_contacts_ibfk_2` FOREIGN KEY (`user`) REFERENCES `profiles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_nopad_ci;
