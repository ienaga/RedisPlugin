<?php

return array(
    "database" => array(
        "dbAdminMaster" => array(
            "adapter"     => "Mysql",
            "host"        => "127.0.0.1",
            "port"        => 3301,
            "username"    => "root",
            "password"    => "",
            "dbname"      => "admin",
            "options"     => array(20 => false),
            "charset"     => "utf8",
            "transaction" => true
        ),
        "dbAdminSlave" => array(
            "adapter"  => "Mysql",
            "host"     => "127.0.0.1",
            "port"     => 3301,
            "username" => "root",
            "dbname"   => "admin",
            "password" => "",
            "options"  => array(20 => false),
            "charset"  => "utf8",
        ),
        "dbCommonMaster" => array(
            "adapter"     => "Mysql",
            "host"        => "127.0.0.1",
            "port"        => 3301,
            "username"    => "root",
            "password"    => "",
            "dbname"      => "common",
            "options"     => array(20 => false),
            "charset"     => "utf8",
            "transaction" => true
        ),
        "dbCommonSlave" => array(
            "adapter"  => "Mysql",
            "host"     => "127.0.0.1",
            "port"     => 3301,
            "username" => "root",
            "dbname"   => "common",
            "password" => "",
            "options"  => array(20 => false),
            "charset"  => "utf8",
        ),
        "dbUser1Master" => array(
            "adapter"     => "Mysql",
            "host"        => "127.0.0.1",
            "port"        => 3301,
            "username"    => "root",
            "password"    => "",
            "dbname"      => "user1",
            "options"     => array(20 => false),
            "charset"     => "utf8",
            "transaction" => true
        ),
        "dbUser1Slave" => array(
            "adapter"  => "Mysql",
            "host"     => "127.0.0.1",
            "port"     => 3301,
            "username" => "root",
            "dbname"   => "user1",
            "password" => "",
            "options"  => array(20 => false),
            "charset"  => "utf8",
        ),
        "dbUser2Master" => array(
            "adapter"     => "Mysql",
            "host"        => "127.0.0.1",
            "port"        => 3301,
            "username"    => "root",
            "password"    => "",
            "dbname"      => "user2",
            "options"     => array(20 => false),
            "charset"     => "utf8",
            "transaction" => true
        ),
        "dbUser2Slave" => array(
            "adapter"  => "Mysql",
            "host"     => "127.0.0.1",
            "port"     => 3301,
            "username" => "root",
            "dbname"   => "user2",
            "password" => "",
            "options"  => array(20 => false),
            "charset"  => "utf8",
        )
    ),
    "redis" => array(
        "logger"    => array(
            "logging" => false,
            "output"  => "/",
        ),
        "enabled"   => true,
        "autoIndex" => true,
        "prefix"    => array(
            "columns" => array("user_id", "id")
        ),
        "common" => array(
            "enabled" => true,
            "service" => array("name" => "dbCommon"),
            "dbs"     => array("mst_")
        ),
        "shard" => array("enabled" => true),
        "admin" => array(
            "service" => array("name" => "dbAdmin"),
            "model"   => "AdminUser",
            "column"  => "admin_db_config_id",
            "dbs"     => array("admin_"),
            "control" => array(
                "model"  => "AdminDbConfig",
                "column" => "name"
            )
        ),
        "server" => array(
            "dbAdminMaster" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 0
            ),
            "dbAdminSlave" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 1
            ),
            "dbCommonMaster" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 0
            ),
            "dbCommonSlave" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 1
            ),
            "dbUser1Master" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 0
            ),
            "dbUser1Slave" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 1
            ),
            "dbUser2Master" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 0
            ),
            "dbUser2Slave" => array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 1
            ),
        ),
        "metadata" => array(
            "host"   => "127.0.0.1",
            "port"   => 6379,
            "select" => 0
        )
    )
);

