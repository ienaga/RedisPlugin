CREATE DATABASE IF NOT EXISTS `admin`;
CREATE DATABASE IF NOT EXISTS `common`;
CREATE DATABASE IF NOT EXISTS `user1`;
CREATE DATABASE IF NOT EXISTS `user2`;



CREATE TABLE IF NOT EXISTS `admin`.`admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_db_config_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `admin`.`admin_db_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `gravity` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `admin`.`admin_db_config` (`id`, `name`, `gravity`) VALUES
(1, 'dbUser1', 50),
(2, 'dbUser2', 50);




CREATE TABLE IF NOT EXISTS `common`.`mst_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` int(10) unsigned NOT NULL,
  `level` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_index`
  ADD KEY `type_idx` (`type`) USING BTREE,
  ADD KEY `level_mode_idx` (`level`, `mode`) USING BTREE;




CREATE TABLE IF NOT EXISTS `common`.`mst_database` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_database` (`id`, `name`) VALUES
  (1, 'database_1'),
  (2, 'database_2'),
  (3, 'database_3'),
  (4, 'database_4'),
  (5, 'database_5'),
  (6, 'database_6');



CREATE TABLE IF NOT EXISTS `common`.`mst_equal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_equal`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_equal` (`id`, `type`) VALUES
  (1, 1),
  (2, 1),
  (3, 1),
  (4, 2)
  (5, 2),
  (6, 2);




CREATE TABLE IF NOT EXISTS `common`.`mst_not_equal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_not_equal`
  ADD KEY `type_idx` (`type`) USING BTREE,
  ADD KEY `mode_idx` (`mode`) USING BTREE;

INSERT INTO `common`.`mst_not_equal` (`id`, `type`, `mode`) VALUES
  (1, 1, 0),
  (2, 1, 1),
  (3, 1, 1),
  (4, 2, 1),
  (5, 2, 1),
  (6, 2, 1);


CREATE TABLE IF NOT EXISTS `common`.`mst_greater_than` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOTNULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_greater_than`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_greater_than` (`id`, `type`) VALUES
  (1, 0),
  (2, 0),
  (3, 1),
  (4, 1),
  (5, 1),
  (6, 1);



CREATE TABLE IF NOT EXISTS `common`.`mst_less_than` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_less_than`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_less_than` (`id`, `type`) VALUES
  (1, 0),
  (2, 0),
  (3, 1),
  (4, 1),
  (5, 1),
  (6, 1);



CREATE TABLE IF NOT EXISTS `common`.`mst_greater_equal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_greater_equal`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_greater_equal` (`id`, `type`) VALUES
  (1, 0),
  (2, 1),
  (3, 2),
  (4, 2),
  (5, 2),
  (6, 2);




CREATE TABLE IF NOT EXISTS `user1`.`user` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user1`.`user_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user1`.`user_item`
  ADD KEY `user_id_idx` (`user_id`) USING BTREE;



CREATE TABLE IF NOT EXISTS `user2`.`user` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user2`.`user_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user2`.`user_item`
  ADD KEY `user_id_idx` (`user_id`) USING BTREE;