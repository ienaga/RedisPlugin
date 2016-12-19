<?php

require_once __DIR__ . "/../src/plugin/redis/Database.php";
require_once __DIR__ . "/MstItem.php";

use \RedisPlugin\Database;

class DatabaseTest extends \PHPUnit_Framework_TestCase
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
     * test transaction
     */
    public function testTransaction()
    {
        $model = new MstItem();

        // not transaction
        $transaction = Database::getTransaction($model);
        $this->assertEquals($transaction, null);

        // begin transaction
        Database::beginTransaction();
        $transaction = Database::getTransaction($model);
        $this->assertEquals($transaction->isManaged(), true);
    }

    /**
     * test commit
     */
    public function testCommit()
    {
        /** @var MstItem $mstItem */
        $mstItem = MstItem::criteria()
            ->add("id", 6)
            ->findFirst();

        Database::beginTransaction();

        $mstItem->setName("commit");
        $mstItem->save();

        Database::commit();

        $mstItem = MstItem::criteria()
            ->add("id", 6)
            ->findFirst();

        $this->assertEquals($mstItem->getName(), "commit");
    }

    /**
     * test rollback
     */
    public function testRollback()
    {
        /** @var MstItem $mstItem */
        $mstItem = MstItem::criteria()
            ->add("id", 4)
            ->findFirst();

        try {
            Database::beginTransaction();

            $mstItem->setName("rollback");
            $mstItem->save();

            throw new \Exception("test rollback");

            Database::commit();

        } catch (\Exception $e) {

            $this->assertEquals($e->getMessage(), "test rollback");

            Database::rollback($e);
        }

        $mstItem = MstItem::criteria()
            ->add("id", 4)
            ->findFirst();

        $this->assertEquals($mstItem->getName(), "item_4");
    }


}