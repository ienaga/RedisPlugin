RedisPlugin for Phalcon (The correspondence of MySQL sharding.)
======


## Version

PHP 5.4.x/5.5.x/5.6.x

Phalcon 1.x/2.x  


## YAML

~~~
sudo yum install libyaml libyaml-devel

sudo pecl install YAML
sudo vim /etc/php.d/yaml.ini
extension=yaml.so
~~~


## Phalcon YAML [database.yml]

~~~
dev:
prd:
stg:
  database:
    dbMaster:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3301
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: true
      transaction_name: XXXXX # master
    dbSlave:
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
      transaction_name: XXXXX # member1
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
      transaction_name: XXXXX # member2
    dbMember2Slave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3317
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false
~~~

## Phalcon YAML [redis.yml]
~~~
  redis:
    default:
      name: db
      expire: 3600
    prefix:
      columns: column, column, column

    # common
      dbs: table, table, table... # common DB Table Name => master_, common_item,

    # shard admin
    admin:
      model:  XXXXX # AdminUser
      method: XXXXX # getUser
      column: XXXXX # user_id
      dbs: table, table, table... # common DB Table Name => admin_, common_members

    # shard config master
    shard:
      model:  XXXXX # AdminConfig
      method: XXXXX # getConfig
      column: XXXXX # db_id

    server:
      dbMaster:
        host: XXXXX
        port: 6379
        select: 1
      dbSlave:
        host: XXXXX
        port: 6379
        select: 1
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
        select: 2
      dbMember1Slave:
        host: XXXXX
        port: 6379
        select: 2
      dbMember2Master:
        host: XXXXX
        port: 6379
        select: 3
      dbMember2Slave:
        host: XXXXX
        port: 6379
        select: 3
~~~


## Phalcon config.php

~~~
$yml = yaml_parse_file(XXX.yml);
~~~

## Phalcon services.php

~~~
/**
 * Database connection is created based in the parameters defined in the configuration file
 */

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\Model\Transaction\Manager;

foreach ($config->get('database') as $db => $arguments)
{

    $di->setShared($db, function () use ($arguments)
    {

        return new DbAdapter($arguments->toArray());

    });

    if (isset($arguments['transaction']) && $arguments['transaction']) {

        $di->setShared($arguments['transaction_name'], function() use ($db)
        {
            $manager = new Manager();

            if ($db !== null)
                $manager->setDbService($db);

            return $manager;
        });

    }
}
~~~

## findFirst | find
~~~
    EQUAL
    ------------------
    return RedisDb::findFirst(array(
        'where' => array(
            'member_id' => $memberId,
            'status_number' => 1
        )
    ), new self);

    return RedisDb::find(array(
        'where' => array(
            'member_id' => $memberId,
            'status_number' => 1
        )
    ), new self);
    ------------------

    IN
    ------------------
    return RedisDb::findFirst(array(
        'where' => array(
            'member_id' => array(1, 3, 6),
            'status_number' => 1
        )
    ), new self);

    return RedisDb::find(array(
        'where' => array(
            'member_id' => array(1, 3, 6),
            'status_number' => 1
        )
    ), new self);
    ------------------

    IS NULL
    ------------------
    return RedisDb::findFirst(array(
        'where' => array(
            'member_id' => null,
            'status_number' => 1
        )
    ), new self);

    return RedisDb::find(array(
        'where' => array(
            'member_id' => null,
            'status_number' => 1
        )
    ), new self);
    ------------------


    OPERATOR
    ▼OPERATOR LIST
    ------------------
      RedisDb::EQUAL = '=';
      RedisDb::NOT_EQUAL = '<>';
      RedisDb::GREATER_THAN = '>';
      RedisDb::LESS_THAN = '<';
      RedisDb::GREATER_EQUAL = '>=';
      RedisDb::LESS_EQUAL = '<=';
      RedisDb::IS_NULL = 'IS NULL';
      RedisDb::IS_NOT_NULL = 'IS NOT NULL';
      RedisDb::LIKE = 'LIKE';
      RedisDb::I_LIKE = 'ILIKE';
      RedisDb::IN = 'IN';
      RedisDb::NOT_IN = 'NOT IN';
      RedisDb::BETWEEN = 'BETWEEN';
    ------------------

    NOT_EQUAL
    return RedisDb::findFirst(array(
        'where' => array(
            'member_id' => array('operator' => RedisDb::NOT_EQUAL, 'value' => 1),
            'status_number' => 1
        )
    ), new self);

    NOT_IN
    return RedisDb::find(array(
        'where' => array(
            'member_id' => array('operator' => RedisDb::NOT_IN, 'value' => array(1, 2, 5)),
            'status_number' => 1
        )
    ), new self);

    BETWEEN
    return RedisDb::find(array(
        'where' => array(
            'member_id' => array('operator' => RedisDb::BETWEEN, 'value' => array(1, 2)),
            'status_number' => 1
        )
    ), new self);

~~~


## save
~~~
    $model = new self;
    $model->setMemberId($memberId);
    $model->setStatus(1);
    RedisDb::save($model);
~~~