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
  (4, 2),
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
  `type` int(10) unsigned NOT NULL,
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




CREATE TABLE IF NOT EXISTS `common`.`mst_less_equal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_less_equal`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_less_equal` (`id`, `type`, `mode`) VALUES
  (1, 0, 2),
  (2, 1, 2),
  (3, 2, 3),
  (4, 2, 3),
  (5, 2, 3),
  (6, 2, 3);


CREATE TABLE IF NOT EXISTS `common`.`mst_is_null` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_is_null`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_is_null` (`id`, `type`, `mode`) VALUES
  (1, null, 2),
  (2, null, 2),
  (3, null, 2),
  (4, 2, 3),
  (5, 2, 3),
  (6, 2, 3);



CREATE TABLE IF NOT EXISTS `common`.`mst_is_not_null` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_is_not_null`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_is_not_null` (`id`, `type`, `mode`) VALUES
  (1, null, 2),
  (2, null, 2),
  (3, null, 2),
  (4, 2, 3),
  (5, 2, 3),
  (6, 2, 3);



CREATE TABLE IF NOT EXISTS `common`.`mst_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_like` (`id`, `name`, `mode`) VALUES
  (1, 'A', 2),
  (2, 'ab', 2),
  (3, 'ABC', 2),
  (4, 'ABCD', 3),
  (5, 'abcde', 3),
  (6, 'ABCDEF', 3);




CREATE TABLE IF NOT EXISTS `common`.`mst_not_like` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_not_like` (`id`, `name`, `mode`) VALUES
  (1, 'A', 2),
  (2, 'ab', 2),
  (3, 'ABC', 2),
  (4, 'ABCD', 3),
  (5, 'abcde', 3),
  (6, 'ABCDEF', 3);




CREATE TABLE IF NOT EXISTS `common`.`mst_in` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_in`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_in` (`id`, `type`, `mode`) VALUES
  (1, 0, 1),
  (2, 1, 1),
  (3, 1, 1),
  (4, 1, 1),
  (5, 2, 2),
  (6, 2, 2);


CREATE TABLE IF NOT EXISTS `common`.`mst_not_in` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_not_in`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_not_in` (`id`, `type`, `mode`) VALUES
  (1, 0, 1),
  (2, 1, 2),
  (3, 1, 2),
  (4, 1, 2),
  (5, 2, 2),
  (6, 2, 2);



CREATE TABLE IF NOT EXISTS `common`.`mst_between` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `common`.`mst_between`
  ADD KEY `type_idx` (`type`) USING BTREE;

INSERT INTO `common`.`mst_between` (`id`, `type`, `mode`) VALUES
  (1, 0, 1),
  (2, 1, 2),
  (3, 2, 2),
  (4, 3, 2),
  (5, 4, 2),
  (6, 5, 2);



CREATE TABLE IF NOT EXISTS `common`.`mst_or` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_or` (`id`, `type`, `mode`, `name`) VALUES
  (1, 0, 0, 'OK'),
  (2, 0, 0, 'OK'),
  (3, 1, 1, 'OK'),
  (4, 1, 1, 'OK'),
  (5, 2, 2, 'NO'),
  (6, 5, 6, 'NO');



CREATE TABLE IF NOT EXISTS `common`.`mst_test_sum` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `mode` int(10) unsigned NOT NULL,
  `point` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_test_sum` (`id`, `type`, `mode`, `point`) VALUES
  (1, 1, 0, 100),
  (2, 1, 0, 20),
  (3, 1, 1, 500),
  (4, 2, 1, 70),
  (5, 2, 2, 10),
  (6, 2, 6, 30);



CREATE TABLE IF NOT EXISTS `common`.`mst_test_count` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_test_count` (`id`, `type`) VALUES
  (1, 1),
  (2, 1),
  (3, 1),
  (4, 2),
  (5, 2),
  (6, 3);


CREATE TABLE IF NOT EXISTS `common`.`mst_truncate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_truncate` (`id`) VALUES
  (1),(2),(3),(4),(5),(6);


CREATE TABLE IF NOT EXISTS `common`.`mst_test_columns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alpha` int(10) unsigned NOT NULL,
  `beta` int(10) unsigned NOT NULL,
  `gamma` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_test_columns` (`id`,`alpha`,`beta`,`gamma`) VALUES
  (1,1,1,1);

CREATE TABLE IF NOT EXISTS `common`.`mst_test_min_max` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `value` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_test_min_max` (`id`,`value`) VALUES
  (1,1),
  (2,2),
  (3,3),
  (4,4),
  (5,5);

CREATE TABLE IF NOT EXISTS `common`.`mst_test_distinct` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `common`.`mst_test_distinct` (`id`,`type`,`name`) VALUES
  (1,1,'A'),
  (2,1,'B'),
  (3,1,'C'),
  (4,2,'A'),
  (5,2,'C');


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


CREATE TABLE IF NOT EXISTS `user1`.`user_quest` (
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ユーザーID',
  `quest_id` int(10) UNSIGNED NOT NULL COMMENT 'クエストID',
  `clear_flag` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0=挑戦中、1=クリアした',
  `status_number` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `user1`.`user_quest`
  ADD PRIMARY KEY (`user_id`,`quest_id`),
  ADD KEY `idx_user_id_clear_flag` (`user_id`,`clear_flag`) USING BTREE;

CREATE TABLE IF NOT EXISTS `user2`.`user_quest` (
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ユーザーID',
  `quest_id` int(10) UNSIGNED NOT NULL COMMENT 'クエストID',
  `clear_flag` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0=挑戦中、1=クリアした',
  `status_number` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `user2`.`user_quest`
  ADD PRIMARY KEY (`user_id`,`quest_id`),
  ADD KEY `idx_user_id_clear_flag` (`user_id`,`clear_flag`) USING BTREE;

