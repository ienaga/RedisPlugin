<?php

require_once __DIR__ . "/../src/plugin/redis/Database.php";
require_once __DIR__ . "/model/MstDatabase.php";

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
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/config/redis.yml");
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
        $model = new MstDatabase();

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

        Database::beginTransaction();

        $mstDatabase = new MstDatabase();
        $mstDatabase->setName("commit");
        $mstDatabase->save();

        Database::commit();

        /** @var MstDatabase $mstDatabase */
        $mstDatabase = MstDatabase::criteria()
            ->add("id", 7)
            ->findFirst();

        $this->assertEquals($mstDatabase->getName(), "commit");
    }

    /**
     * test rollback
     */
    public function testRollback()
    {
        /** @var MstDatabase $mstDatabase */
        $mstDatabase = MstDatabase::criteria()
            ->add("id", 4)
            ->findFirst();

        try {

            Database::beginTransaction();

            $mstDatabase->setName("rollback");
            $mstDatabase->save();

            $e = new \Exception("test rollback");

            Database::rollback($e);

        } catch (\Exception $e) {

            Database::rollback($e);

            $this->assertEquals($e->getMessage(), "test rollback");

        }

        $mstDatabase = MstDatabase::criteria()
            ->add("id", 4)
            ->findFirst();

        $this->assertEquals($mstDatabase->getName(), "mst_database_4");
    }


}