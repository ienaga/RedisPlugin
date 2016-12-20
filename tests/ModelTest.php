<?php

require_once __DIR__ . "/../src/mvc/Model.php";
require_once __DIR__ . "/../src/plugin/redis/Database.php";
require_once __DIR__ . "/model/MstItem.php";
require_once __DIR__ . "/model/AdminUser.php";
require_once __DIR__ . "/model/AdminConfigDb.php";
require_once __DIR__ . "/model/User.php";
require_once __DIR__ . "/model/UserItem.php";

use \RedisPlugin\Mvc\Model;
use \RedisPlugin\Database;

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

        // cache
        MstItem::criteria()->find();
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

    /**
     * test shard
     */
    public function testShard()
    {
        try {

            Database::beginTransaction();

            $totalGravity = AdminConfigDb::criteria()->sum("gravity");

            /** @var AdminConfigDb[] $adminDbConfigs */
            $adminDbConfigs = AdminConfigDb::criteria()->find();

            for ($i = 1; $i <= 10; $i++) {
                // 当選番号
                $prizeNo = mt_rand(0, $totalGravity);

                // 抽選
                $gravity  = 0;
                $configId = null;
                foreach ($adminDbConfigs as $adminDbConfig) {

                    $gravity += $adminDbConfig->getGravity();

                    if ($gravity >= $prizeNo) {
                        $configId = $adminDbConfig->getId();
                        break;
                    }
                }

                // 登録
                $adminUser = new AdminUser();
                $adminUser->setAdminConfigDbId($configId);
                $adminUser->save();

                $user = new User();
                $user->setId($adminUser->getId());
                $user->setName("test_user_". $i);
                $user->save();
            }

            Database::commit();

        } catch (\Exception $e) {

            Database::rollback($e);

        }

        sleep(1);

        /** @var User $user */
        $user = User::criteria()
            ->add("id", 1)
            ->findFirst();

        $this->assertEquals($user->getName(), "test_user_1");

        try {

            Database::beginTransaction();

            $user->setName("update_user_1");
            $user->save();

            Database::commit();

        } catch (\Exception $e) {

            Database::rollback($e);

        }

        sleep(1);

        /** @var User $user */
        $user = User::criteria()
            ->add("id", 1)
            ->findFirst();

        $this->assertEquals($user->getName(), "update_user_1");

        try {

            Database::beginTransaction();

            $userItem = new UserItem();
            $userItem->setUserId(1);
            $userItem->setItemId(10);
            $userItem->save();

            Database::commit();

        } catch (\Exception $e) {

            Database::rollback($e);

        }

        sleep(1);

        /** @var UserItem $userItem */
        $userItem = UserItem::criteria()
            ->add("user_id", 1)
            ->findFirst();

        $this->assertEquals($userItem->getItemId(), 10);
    }
}