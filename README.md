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
  redis:
    default:
      name: db
      expire: 3600

    # shard admin
    admin:
      model: 
      method: 
      column: 

    # shard config master
    shard:
      model: 
      method: 
      column: 

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


