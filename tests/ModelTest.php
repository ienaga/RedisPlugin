<?php

require_once __DIR__ . "/../src/mvc/model/Criteria.php";
require_once __DIR__ . "/../src/mvc/Model.php";
require_once __DIR__ . "/../src/plugin/redis/Database.php";
require_once __DIR__ . "/model/MstIndex.php";
require_once __DIR__ . "/model/AdminUser.php";
require_once __DIR__ . "/model/AdminDbConfig.php";
require_once __DIR__ . "/model/User.php";
require_once __DIR__ . "/model/UserItem.php";
require_once __DIR__ . "/model/MstEqual.php";
require_once __DIR__ . "/model/MstNotEqual.php";



use \RedisPlugin\Mvc\Model;
use \RedisPlugin\Mvc\Model\Criteria;
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

        // meta data cache
        MstIndex::criteria()->find();
    }

    /**
     * test indexes pattern 1
     */
    public function testIndexes1()
    {
        $criteria = MstIndex::criteria()
            ->add("mode", 1)
            ->add("level", 2)
            ->add("id", 2);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[id] = :id: AND [mode] = :mode: AND [level] = :level:");
    }

    /**
     * test indexes pattern 2
     */
    public function testIndexes2()
    {
        $criteria = MstIndex::criteria()
            ->add("name", "test")
            ->add("mode", 10)
            ->add("type", 2);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[type] = :type: AND [name] = :name: AND [mode] = :mode:");
    }

    /**
     * test indexes pattern 3
     */
    public function testIndexes3()
    {
        $criteria = MstIndex::criteria()
            ->add("mode", 1)
            ->add("level", 2)
            ->add("name", "test");

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[level] = :level: AND [mode] = :mode: AND [name] = :name:");
    }

    /**
     * test shard
     */
    public function testShard()
    {
        try {

            Database::beginTransaction();

            $totalGravity = AdminDbConfig::criteria()->sum("gravity");

            /** @var AdminDbConfig[] $adminDbConfigs */
            $adminDbConfigs = AdminDbConfig::criteria()->find();

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
                $adminUser->setAdminDbConfigId($configId);
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

    /**
     * test Equal
     */
    public function testEqual()
    {
        /** @var MstEqual $mstEqual */
        $mstEqual = MstEqual::criteria()
            ->add("id", 1)
            ->findFirst();

        $this->assertEquals($mstEqual->getName(), "equal1");

        /** @var MstEqual[] $mstEqual */
        $mstEqual = MstEqual::criteria()
            ->add("type", 2)
            ->find();

        foreach ($mstEqual as $key => $equal) {
            $this->assertEquals($equal->getName(), "equal". (4+$key));
        }
    }

    /**
     * test Not Equal
     */
    public function testNotEqual()
    {
        /** @var MstNotEqual $mstNotEqual */
        $mstNotEqual = MstNotEqual::criteria()
            ->add("mode", 1, Criteria::NOT_EQUAL)
            ->findFirst();

        $this->assertEquals($mstNotEqual->getMode(), 0);

        /** @var MstNotEqual[] $mstNotEqual */
        $mstNotEqual = MstNotEqual::criteria()
            ->add("type", 1, Criteria::NOT_EQUAL)
            ->find();

        foreach ($mstNotEqual as $notEqual) {
            $this->assertEquals($notEqual->getType(), 2);
        }
    }
}