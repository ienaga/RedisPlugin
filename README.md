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

## app/config/config.php

~~~

$dir = __DIR__ .'/../../app/';
$env = getenv('PLATFORM');
$ignore_file = array('routing');


$configYml = array();
if ($configDir = opendir($dir.'config')) {

    while (($file = readdir($configDir)) !== false) {

        $exts = explode('.', $file);

        if ($exts[1] !== 'yml')
            continue;

        $file_name = $exts[0];
        if ($ignore_file && in_array($file_name, $ignore_file))
            continue;

        $yml = yaml_parse_file($dir . "config/{$file_name}.yml");
        $configYml = array_merge($configYml, $yml[$env]);

        if (isset($yml['all'])) {
            $configYml = array_merge($configYml, $yml['all']);
        }

    }

    closedir($configDir);
}

$application = array(
    'application' => array(
        'controllersDir' => $dir . 'controllers/',
        'modelsDir'      => $dir . 'models/',
        'viewsDir'       => $dir . 'views/',
        'pluginsDir'     => $dir . 'plugins/',
        'libraryDir'     => $dir . 'library/',
        'cacheDir'       => $dir . 'cache/',
    )
);

return new \Phalcon\Config(array_merge($application, $configYml));

~~~

## app/config/database.yml

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

## app/config/redis.yml
~~~
prd:
stg:
dev:
  redis:
    enabled: true # false => cache off
    default:
      name: db
      expire: 3600
      autoIndex: true
    prefix: # 対象のカラムがModelに存在したら使用。左から順に優先。存在が確認できた時点でbreak
      columns: column, column, column # e.g. user_id, id, social_id


    # 共通のマスタがあれば登録「table_」と共有部分だけの記載はtable_*と同義
    # common
    common:
      dbs: table, table, table... # e.g.  master_, access_log


    admin:
      # ユーザマスタ
      # e.g.
      #    CREATE TABLE IF NOT EXISTS `admin_user` (
      #      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      #      `social_id` varchar(255) NOT NULL COMMENT 'ソーシャルID',
      #      `admin_config_db_id` tinyint(3) unsigned NOT NULL COMMENT 'AdminConfigDb.ID',
      #      `admin_flag` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0=一般、1=管理者',
      #      `status_number` tinyint(3) unsigned NOT NULL DEFAULT '0',
      #      `created_at` datetime NOT NULL,
      #      `updated_at` datetime NOT NULL,
      #      PRIMARY KEY (`id`),
      #      UNIQUE KEY `social_id` (`social_id`)
      #    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      model:  XXXXX # AdminUser
      column: XXXXX # admin_config_db_id

      # ユーザマスタの登録「table_」と共有部分だけの記載はtable_*と同義
      dbs: table, table, table... # e.g. admin_, user_ranking


    shard:
      enabled: true # Shardingを使用しないばあいはfalse

      # Shardingをコントロールするテーブルとカラム
      #
      # e.g.
      #    CREATE TABLE IF NOT EXISTS `admin_config_db` (
      #      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      #      `name` varchar(50) NOT NULL COMMENT 'DBコンフィグ名',
      #      `gravity` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '重み(振り分け用)',
      #      `status_number` tinyint(3) unsigned NOT NULL DEFAULT '0',
      #      PRIMARY KEY (`id`)
      #    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
      #    INSERT INTO `admin_config_db` (`id`, `name`, `gravity`, `status_number`) VALUES
      #    (1, 'dbMember1', 50, 0),
      #    (2, 'dbMember2', 50, 0);
      # shard config master
      control:
        model:  XXXXX # AdminConfigDb
        column: XXXXX # name


    server:
      dbMaster:
        host: XXXXX
        port: 6379
        select: 1 # redis select [データベースインデックス]
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


## app/config/services.php

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
            'query' => array(
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
            'query' => array(
                'id' => $id,
                'type' => $type
            )
        ), new self);
    }

    // cache => falseで個別にキャッシュをコントロール
    public static function no_cache($id, $type)
    {
        return RedisDb::find(array(
            'query' => array(
                'id' => $id,
                'type' => $type
            ),
            'cache' => false
        ), new self);
    }

    public static function order($id, $type)
    {
        return RedisDb::find(array(
            'query' => array(
                'id' => $id,
                'type' => $type
            ),
            'order' => 'id DESC'
        ), new self);
    }

    public static function group($id, $type)
    {
        return RedisDb::find(array(
            'query' => array(
                'id' => $id,
                'type' => $type
            ),
            'group' => 'id'
        ), new self);
    }

    public static function limit($id, $type)
    {
        return RedisDb::find(array(
            'query' => array(
                'id' => $id,
                'type' => $type
            ),
            'limit' => 10 // array('number' => 10, 'offset' => 5)
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
            'query' => array(
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
            'query' => array(
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
            'query' => array(
                'id' => array(
                    'operator' => Criteria::BETWEEN,
                    'value' => array($start, $end)
                ),
                'type' => $type
            )
        ), new self);
    }

    // ->cache($boolean)でキャッシュをコントロール
    public static function no_cache($id, $start, $end)
    {
        $criteria = new Criteria(new self);
         return $criteria
            ->add('id', array($id), Criteria::IN)
            ->add('type', array($start, $end), Criteria::BETWEEN)
            ->limit(10, 30)
            ->order('type DESC')
            ->cache(false)
            ->find();
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
            ->limit(10, 5) // limit, offset
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

