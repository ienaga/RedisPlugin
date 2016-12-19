<?php

require_once __DIR__ . "/../src/mvc/Model.php";
require_once __DIR__ . "/MstItem.php";

use \RedisPlugin\Mvc\Model;

class ModelTest extends \PHPUnit_Framework_TestCase
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
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) { return $config; }, true);

        // service
        $service = new \RedisPlugin\Service();
        $service->registration();

        // modelsMetadata
        $di->setShared("modelsMetadata", function () use ($di) {
            return new \RedisPlugin\Mvc\Model\Metadata\Redis(
                $this->getConfig()->get("redis")->get("metadata")->toArray()
            );
        });
    }

    /**
     * test indexes pattern 1
     */
    public function testIndexes1()
    {
        $criteria = MstItem::criteria()
            ->add("mode", 1)
            ->add("id", 2);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[id] = :id: AND [mode] = :mode:");
    }

    /**
     * test indexes pattern 2
     */
    public function testIndexes2()
    {
        $criteria = MstItem::criteria()
            ->add("mode", 1)
            ->add("level", 2);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[level] = :level: AND [mode] = :mode:");
    }

    /**
     * test indexes pattern 3
     */
    public function testIndexes3()
    {
        $criteria = MstItem::criteria()
            ->add("mode", 1)
            ->add("level", 2)
            ->add("id", 6);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[id] = :id: AND [mode] = :mode: AND [level] = :level:");
    }
}