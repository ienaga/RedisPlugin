RedisPlugin for Phalcon (The correspondence of MySQL sharding.)
======


## Version

PHP 5.4.x/5.5.x/5.6.x

Phalcon 1.x/2.x  


## phpredis
~~~
sudo pecl install redis
sudo vim /etc/php.d/redis.ini
extension=redis.so
~~~


## YAML

~~~
sudo yum install libyaml libyaml-devel

sudo pecl install YAML
sudo vim /etc/php.d/yaml.ini
extension=yaml.so
~~~


## Phalcon YAML [database.yml]

~~~
prd:
stg:
dev:
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
      autoIndex: true
    prefix:
      columns: column, column, column

    # common
    common:
      dbs: table, table, table... # common DB Table Name => master_, common_item,


    # shard config master
    shard:
      enabled: true
      model:  XXXXX # AdminConfig
      method: XXXXX # getConfig
      column: XXXXX # db_id


    # shard admin
    admin:
      model:  XXXXX # AdminUser
      method: XXXXX # getUser
      column: XXXXX # user_id
      dbs: table, table, table... # common DB Table Name => admin_, common_members


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
 * Database connection
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


/**
 * modelsMetadata
 */
$di->set('modelsMetadata', function () { return new \RedisPlugin\MetaData(); });


~~~

## find | findFirst 簡易版

~~~
<php

use \RedisPlugin\RedisDb;

class Robot extends \Phalcon\Mvc\Model
{

    /**
     * @param  int    $id
     * @param  string $type
     * @return Robot
     */
    public static function findFirst($id, $type)
    {
        return RedisDb::findFirst(array(
            'where' => array(
                'id' => $id,
                'type' => $type
            )
        ), new self);
    }

    /**
     * @param  int    $id
     * @param  string $type
     * @return Robot[]
     */
    public static function find($id, $type)
    {
        return RedisDb::find(array(
            'where' => array(
                'id' => $id,
                'type' => $type
            )
        ), new self);
    }
}

~~~


## find | findFirst 比較演算子

~~~
<php

use \RedisPlugin\RedisDb;
use \RedisPlugin\Criteria;

class Robot extends \Phalcon\Mvc\Model
{
    // LIST
    // Criteria::EQUAL = '=';
    // Criteria::NOT_EQUAL = '<>';
    // Criteria::GREATER_THAN = '>';
    // Criteria::LESS_THAN = '<';
    // Criteria::GREATER_EQUAL = '>=';
    // Criteria::LESS_EQUAL = '<=';
    // Criteria::IS_NULL = 'IS NULL';
    // Criteria::IS_NOT_NULL = 'IS NOT NULL';
    // Criteria::LIKE = 'LIKE';
    // Criteria::I_LIKE = 'ILIKE';
    // Criteria::IN = 'IN';
    // Criteria::NOT_IN = 'NOT IN';
    // Criteria::BETWEEN = 'BETWEEN';


    public static function in($id, $type)
    {
        return RedisDb::findFirst(array(
            'where' => array(
                'id' => array(
                    'operator' => Criteria::IN,
                    'value' => array(1,6,10)
                ),
                'type' => $type
            )
        ), new self);
    }


    public static function not_in($id, $type)
    {
        return RedisDb::find(array(
            'where' => array(
                'id' => array(
                    'operator' => Criteria::NOT_IN,
                    'value' => array(1,6,10)
                ),
                'type' => $type
            )
        ), new self);
    }


    public static function between($start, $end)
    {
        return RedisDb::findFirst(array(
            'where' => array(
                'id' => array(
                    'operator' => Criteria::BETWEEN,
                    'value' => array($start, $end)
                ),
                'type' => $type
            )
        ), new self);
    }
}

~~~


## Criteria

~~~
<php

use \RedisPlugin\RedisDb;
use \RedisPlugin\Criteria;

class Robot extends \Phalcon\Mvc\Model
{

    public static function findFirst($id, $type)
    {
        $criteria = new Criteria(new self);
        return $criteria
            ->add('id', $id)
            ->add('type', $type, Criteria::NOT_EQUAL)
            ->group('type')
            ->findFirst();
    }

    public static function find($id, $start, $end)
    {
        $criteria = new Criteria(new self);
         return $criteria
            ->add('id', array($id), Criteria::IN)
            ->add('type', array($start, $end), Criteria::BETWEEN)
            ->limit(10, 30)
            ->order('type DESC')
            ->find();
    }
}

~~~


## save

~~~
<php

use \RedisPlugin\RedisDb;

class Robot extends \Phalcon\Mvc\Model
{
    /**
     * @param  int    $id
     * @param  string $type
     * @return Robot
     */
    public static function insert($id, $type)
    {
        $robot= new Robot;
        $robot->setId($id);
        $robot->setType($type);
        return RedisDb::save($robot);
    }
}

~~~


## autoIndex

※autoIndexをtrueにする事で、PRIMARYもしくはINDEXに一番マッチするクエリに並び替えて発行。

~~~

<php

use \RedisPlugin\RedisDb;
use \RedisPlugin\Criteria;

class Robot extends \Phalcon\Mvc\Model
{
    // e.g. PRIMARY = type, INDEX = id, status

    public static function find($id, $status, $name)
    {
        $criteria = new Criteria(new self);
        return $criteria
            ->limit(10)
            ->add('name', $name)
            ->group('type')
            ->add('id', $id)
            ->order('id DESC')
            ->add('status', $status)
            ->find();

        /**
         * この場合、INDEXにマッチしたカラムの数が多いのでINDEXにあわせて発行する
         * SELECT * FROM `table`
         * WHERE `id` = :id:
         * AND `status_number` = :status:
         * AND `type` = :type:
         * GROUP BY `type`
         * ORDER BY `id` DESC
         * LIMIT 10
         */
    }
}


~~~

