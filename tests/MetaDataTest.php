<?php

require_once __DIR__ . "/../src/Phalcon/mvc/model/adapter/redis/metadata/Redis.php";
require_once __DIR__ . "/model/MstIndex.php";

use \Phalcon\Mvc\Model\Adapter\Redis\Service;
use \Phalcon\Mvc\Model\Adapter\Redis\Metadata\Redis;

class MetaDataTest extends \PHPUnit_Framework_TestCase
{

    /**
     * set up
     */
    public function setUp()
    {
        // FactoryDefault
        $di = new Phalcon\Di\FactoryDefault();
        \Phalcon\DI::setDefault($di);

        // config
        $config = new \Phalcon\Config();
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/config/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) { return $config; }, true);

        // service
        $service = new Service();
        $service->registration();

        // modelsMetadata
        $di->setShared("modelsMetadata", function () use ($di) {
            return new Redis(
                $this->getConfig()->get("redis")->get("metadata")->toArray()
            );
        });
    }

    /**
     * test meta data
     */
    public function testReadIndexes()
    {
        // find
        MstIndex::criteria()
            ->add("id", 1)
            ->findFirst();

        $model   = new MstIndex();
        $indexes = $model->getModelsMetadata()->readIndexes($model->getSource());
        $this->assertEquals(count($indexes), 3);
    }
}