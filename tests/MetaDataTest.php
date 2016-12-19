<?php

require_once __DIR__ . "/../src/mvc/model/metadata/Redis.php";
require_once __DIR__ . "/MstItem.php";

use \RedisPlugin\Mvc\Model\Metadata\Redis;

class MetaDataTest extends \PHPUnit_Framework_TestCase
{

    /**
     * set up
     */
    public function setUp()
    {
        // FactoryDefault
        $di = new Phalcon\Di\FactoryDefault();

        // config
        $config = new \Phalcon\Config();
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) { return $config; }, true);

        \Phalcon\DI::setDefault($di);
    }

    /**
     * test meta data
     */
    public function testModelsMetadata()
    {
        $di = \Phalcon\DI::getDefault();
        $di->setShared("modelsMetadata", function () use ($di) {
            return new Redis($di->get("redis")->get("metadata")->toArray());
        });
        \Phalcon\DI::setDefault($di);

        $model   = new MstItem();
        $indexes = $model->getModelsMetadata()->readIndexes($model);
        $this->assertEquals(count($indexes), 2);
    }
}