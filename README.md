RedisPlugin for Phalcon (The correspondence of MySQL sharding.)
======

[![Build Status](https://travis-ci.org/ienaga/RedisPlugin.svg?branch=master)](https://travis-ci.org/ienaga/RedisPlugin)

[![Latest Stable Version](https://poser.pugx.org/ienaga/phalcon-redis-plugin/v/stable)](https://packagist.org/packages/ienaga/phalcon-redis-plugin) [![Total Downloads](https://poser.pugx.org/ienaga/phalcon-redis-plugin/downloads)](https://packagist.org/packages/ienaga/phalcon-redis-plugin) [![Latest Unstable Version](https://poser.pugx.org/ienaga/phalcon-redis-plugin/v/unstable)](https://packagist.org/packages/ienaga/phalcon-redis-plugin) [![License](https://poser.pugx.org/ienaga/phalcon-redis-plugin/license)](https://packagist.org/packages/ienaga/phalcon-redis-plugin)

# Composer

```json
{
    "require": {
       "ienaga/phalcon-redis-plugin": "2.*"
    }
}
```


## Version

PHP 5.x/7.x

Phalcon 3.x 


## phpredis and YAML

```linux
sudo yum install libyaml libyaml-devel php-pecl-yaml php-pecl-redis
```

## app/config/config.php

### @see [PhalconConfig](https://github.com/ienaga/PhalconConfig)

```php
$configLoader = new \PhalconConfig\Loader();
return $configLoader
    ->setIgnore(["routing"]) // ignore yml names
    ->setEnvironment("stg") // default dev
    ->setBasePath(realpath(dirname(__FILE__) . "/../.."))
    ->load();
```

## app/config/database.yml

```yaml
prd:
stg:
dev:
  database:
    dbAdminMaster:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3301
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: true
    dbAdminSlave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3311
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
    dbCommonMaster:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3301
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
    dbCommonSlave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3311
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
    dbMember1Master:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3306
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: true
    dbMember1Slave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3316
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
    dbMember2Master:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3307
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: true
    dbMember2Slave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3317
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
```

## app/config/redis.yml

```yaml
prd:
stg:
dev:
  redis:
    # log出力
    logger:
      logging: true # logging ON OFF
      output:  /var/www/project/log/sql.log # output log file path
      
    enabled:   true # false => cache off
    autoIndex: true # false => auto index off

    # 対象のカラムがModelに存在したら使用。上から順に優先。存在が確認できた時点でbreak
    prefix:
      columns:  # e.g. user_id, id, social_id, [account, password]
        - user_id
        - social_id
        - [account, password]
        - id

    # 共通のマスタがあれば登録「table_」と共有部分だけの記載はtable_*と同義
    # common
    common:
      enabled: false
      service:
        name: dbCommon

      dbs: # e.g.  master_, access_log
        - mst_

    # Sharding設定
    shard:
      enabled: true # Shardingを使用しない時はfalse

    # Shardingのマスタ設定
    admin:
      service:
        name: dbAdmin
        
    # Shardingのマスタ設定
    admin:
      service:
        name: dbAdmin
      # ユーザマスタ
      # e.g.
      #    CREATE TABLE IF NOT EXISTS `admin_user` (
      #      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      #      `social_id` varchar(255) NOT NULL,
      #      `admin_db_config_id` int(10) unsigned NOT NULL,
      #      PRIMARY KEY (`id`)
      #    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      model: AdminUser # e.g. AdminUser or namespace \Project\AdminUser
      column: admin_db_config_id # e.g. admin_db_config_id

      # ユーザマスタの登録「table_」と共有部分だけの記載はtable_*と同義
      dbs: # e.g. admin_, user_ranking
        - admin_
        
      # Shardingをコントロールするテーブルとカラム
      #
      # e.g.
      #    CREATE TABLE IF NOT EXISTS `admin_db_config` (
      #      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      #      `name` varchar(50) NOT NULL,
      #      `gravity` tinyint(3) unsigned NOT NULL DEFAULT '0',
      #      PRIMARY KEY (`id`)
      #    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      #    INSERT INTO `admin_db_config` (`id`, `name`, `gravity`) VALUES
      #    (1, 'dbUser1', 50),
      #    (2, 'dbUser2', 50);
      # shard config master
      control:
        model: AdminDbConfig # e.g. AdminConfigDb or namespace \Project\AdminConfigDb
        column: name # e.g. name

    # schemaをキャッシュ
    metadata:
      host:   XXXXX
      port:   6379
      select: 0
      
    # servers
    server:
      dbMaster:
        host: XXXXX
        port: 6379
        select: 0 # redis select [データベースインデックス]
      dbSlave:
        host: XXXXX
        port: 6379
        select: 0
      dbCommonMaster:
        host: XXXXX
        port: 6379
        select: 0
      dbCommonSlave:
        host: XXXXX
        port: 6379
        select: 0
      dbMember1Master:
        host: XXXXX
        port: 6379
        select: 0
      dbMember1Slave:
        host: XXXXX
        port: 6379
        select: 0
      dbMember2Master:
        host: XXXXX
        port: 6379
        select: 0
      dbMember2Slave:
        host: XXXXX
        port: 6379
        select: 0
```


## app/config/services.php

```php
/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$dbService = new \RedisPlugin\Service();
$dbService->registration();

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new \RedisPlugin\Mvc\Model\Metadata\Redis(
        $this->getConfig()->get("redis")->get("metadata")->toArray()
    );
});
```


## findFirst

```php
class Robot extends \RedisPlugin\Mvc\Model {}

# findFirst
$robot = Robot::criteria()
    ->add('id', $id)
    ->add('type', $type, Criteria::NOT_EQUAL)
    ->group('type')
    ->findFirst();
```


## find

```php
class Robot extends \RedisPlugin\Mvc\Model {}

$robot = Robot::criteria()
    ->add('id', array($id), Criteria::IN)
    ->add('type', array($start, $end), Criteria::BETWEEN)
    ->limit(10, 5) // limit, offset
    ->order('type DESC')
    ->find();
```
            
            
## cache Control

```php
class Robot extends \RedisPlugin\Mvc\Model {}

$robot = Robot::criteria()
    ->add('id', array($id), Criteria::IN)
    ->add('type', array($start, $end), Criteria::BETWEEN)
    ->limit(10, 30)
    ->order('type DESC')
    ->cache(false)
    ->find();
```


## autoIndex Control

```php
class Robot extends \RedisPlugin\Mvc\Model {}

$robot = Robot::criteria()
    ->add('id', array($id), Criteria::IN)
    ->add('type', array($start, $end), Criteria::BETWEEN)
    ->limit(10, 30)
    ->order('type DESC')
    ->autoIndex(false)
    ->find();
```


## save

```php
class UserItem extends \RedisPlugin\Mvc\Model {}

$userItem = new UserItem();
$userItem->setId($id);
$userItem->setType($type);
$userItem->save();

```


## update

```php
class Robot extends \RedisPlugin\Mvc\Model {}

Robot::criteria()
    ->add("user_status", 1)
    ->add("power", 100)
    ->set("status", 2)
    ->set("name", "robot")
    ->update();
```

```mysql
UPDATE `robot` SET `status` = 2, `name` = "robot" WHERE `user_status` = 1 AND `power` = 100;
```


## delete

```php
class Robot extends \RedisPlugin\Mvc\Model {}

Robot::criteria()
    ->add("user_status", 1)
    ->add("power", 100, Robot::GREATER_EQUAL)
    ->delete();
```

```mysql
DELETE FROM `robot` WHERE `user_status` = 1 AND `power` >= 100;
```


## count

```php
class Robot extends \RedisPlugin\Mvc\Model {}

$count = Robot::criteria()
    ->add("user_status", 1)
    ->add("power", 100)
    ->add("status", 2)
    ->count();
```


## sum

```php
class Robot extends \RedisPlugin\Mvc\Model {}

$sum = Robot::criteria()
    ->add("user_status", 1)
    ->sum("price");
```


## autoIndex

e.g. PRIMARY = type, INDEX = id, status
※autoIndexをtrueにする事で、PRIMARYもしくはINDEXに一番マッチするクエリに並び替えて発行。

 
```php
class Robot extends \Phalcon\Mvc\Model {}

$robot = Robot::criteria()
    ->limit(10)
    ->add("type", $type)
    ->addGroup("type")
    ->addOrder("id", "DESC")
    ->add("status", $status)
    ->add("id", $id)
    ->find();
```

```mysql
SELECT * FROM `robot` 
WHERE `id` = :id: 
AND `type` = :type:
AND `status` = :status: 
GROUP BY `type`
ORDER BY `id` DESC
LIMIT 10
```

