CREATE DATABASE IF NOT EXISTS `admin`;
CREATE DATABASE IF NOT EXISTS `common`;
CREATE DATABASE IF NOT EXISTS `user1`;
CREATE DATABASE IF NOT EXISTS `user2`;

USE `admin`;

CREATE TABLE IF NOT EXISTS `admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_config_db_id` tinyint(3) unsigned NOT NULL COMMENT 'AdminConfigDb.ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `admin_config_db` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `gravity` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `admin_config_db` (`id`, `name`, `gravity`) VALUES
(1, 'dbUser1', 50),
(2, 'dbUser2', 50);

USE `common`;

CREATE TABLE IF NOT EXISTS `mst_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

USE `user1`;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

USE `user2`;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;