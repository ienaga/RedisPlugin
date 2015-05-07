RedisPlugin for Phalcon
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


## Phalcon YAML

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
      transaction_name: XXXXX
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
      transaction_name: XXXXX
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
      transaction_name: XXXXX
    dbMember2Slave:
      adapter:  Mysql
      host:     127.0.0.1
      port:     3317
      username: root
      password: XXXXX
      dbname:   XXXXX
      charset:  utf8
      transaction: false


  redis:
    default:
      name: db
      expire: 3600

    # shard admin
    admin:
      model:  XXXXX
      method: XXXXX
      column: XXXXX

    # shard config master
    shard:
      model:  XXXXX
      method: XXXXX
      column: XXXXX

    dbMaster:
      name: platform
    dbSlave:
      name: platform
    dbCommonMaster:
      name: common
    dbCommonSlave:
      name: common
    dbMember1Master:
      name: member1
    dbMember1Slave:
      name: member1
    dbMember2Master:
      name: member2
    dbMember2Slave:
      name: member2

    server:
      common:
        host: 127.0.0.1
        port: 6379
        select: 0
      platform:
        host: 127.0.0.1
        port: 6379
        select: 1
      member1:
        host: 127.0.0.1
        port: 6379
        select: 2
      member2:
        host: 127.0.0.1
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
