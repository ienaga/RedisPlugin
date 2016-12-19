<?php

require_once __DIR__ . "/../src/mvc/model/metadata/Redis.php";
require_once __DIR__ . "/MstItem.php";

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

        // modelsMetadata
        $di->setShared("modelsMetadata", function () use ($di) {
            return new \RedisPlugin\Mvc\Model\Metadata\Redis(
                $this->getConfig()->get("redis")->get("metadata")->toArray()
            );
        });

        \Phalcon\DI::setDefault($di);

        $service = new \RedisPlugin\Service();
        $service->registration();
    }

    /**
     * test meta data
     */
    public function testModelsMetadata()
    {
        $model = MstItem::criteria()
            ->add("id", 1)
            ->findFirst();

        $indexes = $model->getModelsMetadata()->readIndexes($model->getSource());
        $this->assertEquals(count($indexes), 2);
    }
}