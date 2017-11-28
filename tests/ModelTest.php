<?php

require_once __DIR__ . "/../src/mvc/model/Criteria.php";
require_once __DIR__ . "/../src/mvc/Model.php";
require_once __DIR__ . "/../src/plugin/redis/Database.php";
require_once __DIR__ . "/model/MstIndex.php";
require_once __DIR__ . "/model/AdminUser.php";
require_once __DIR__ . "/model/AdminDbConfig.php";
require_once __DIR__ . "/model/User.php";
require_once __DIR__ . "/model/UserItem.php";
require_once __DIR__ . "/model/UserQuest.php";
require_once __DIR__ . "/model/MstEqual.php";
require_once __DIR__ . "/model/MstNotEqual.php";
require_once __DIR__ . "/model/MstGreaterThan.php";
require_once __DIR__ . "/model/MstLessThan.php";
require_once __DIR__ . "/model/MstGreaterEqual.php";
require_once __DIR__ . "/model/MstLessEqual.php";
require_once __DIR__ . "/model/MstIsNull.php";
require_once __DIR__ . "/model/MstIsNotNull.php";
require_once __DIR__ . "/model/MstLike.php";
require_once __DIR__ . "/model/MstNotLike.php";
require_once __DIR__ . "/model/MstIn.php";
require_once __DIR__ . "/model/MstNotIn.php";
require_once __DIR__ . "/model/MstBetween.php";
require_once __DIR__ . "/model/MstOr.php";
require_once __DIR__ . "/model/MstTestSum.php";
require_once __DIR__ . "/model/MstTestCount.php";
require_once __DIR__ . "/model/MstTestColumns.php";
require_once __DIR__ . "/model/MstTestMinMax.php";
require_once __DIR__ . "/model/MstTestDistinct.php";
require_once __DIR__ . "/model/MstTruncate.php";


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
        $yml = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/config/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) {
            return $config;
        }, true);

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
     * test indexes pattern 4
     */
    public function testIndexes4()
    {
        $criteria = MstIndex::criteria()
            ->add("type", 2)
            ->add("mode", 1)
            ->add("level", 1);

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[level] = :level: AND [mode] = :mode: AND [type] = :type:");
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
                $gravity = 0;
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
                $user->setName("test_user_" . $i);
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

        $this->assertEquals($mstEqual->getId(), 1);

        /** @var MstEqual[] $mstEqual */
        $mstEqual = MstEqual::criteria()
            ->add("type", 2)
            ->find();

        $this->assertEquals(count($mstEqual), 3);

        foreach ($mstEqual as $key => $equal) {
            $this->assertEquals($equal->getType(), 2);
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

        $this->assertEquals(count($mstNotEqual), 3);

        foreach ($mstNotEqual as $notEqual) {
            $this->assertEquals($notEqual->getType(), 2);
        }
    }

    /**
     * test GREATER_THAN
     */
    public function testGreaterThan()
    {
        /** @var MstGreaterThan $mstGreaterThan */
        $mstGreaterThan = MstGreaterThan::criteria()
            ->add("id", 5, Criteria::GREATER_THAN)
            ->findFirst();

        $this->assertEquals($mstGreaterThan->getId(), 6);

        /** @var MstGreaterThan[] $mstGreaterThan */
        $mstGreaterThan = MstGreaterThan::criteria()
            ->add("type", 0, Criteria::GREATER_THAN)
            ->find();

        $this->assertEquals(count($mstGreaterThan), 4);

        foreach ($mstGreaterThan as $greaterThan) {
            $this->assertEquals($greaterThan->getType(), 1);
        }
    }

    /**
     * test LESS_THAN
     */
    public function testLessThan()
    {
        /** @var MstLessThan $mstLessThan */
        $mstLessThan = MstLessThan::criteria()
            ->add("id", 2, Criteria::LESS_THAN)
            ->findFirst();

        $this->assertEquals($mstLessThan->getId(), 1);

        /** @var MstLessThan[] $mstLessThan */
        $mstLessThan = MstLessThan::criteria()
            ->add("type", 1, Criteria::LESS_THAN)
            ->find();

        $this->assertEquals(count($mstLessThan), 2);

        foreach ($mstLessThan as $lessThan) {
            $this->assertEquals($lessThan->getType(), 0);
        }
    }

    /**
     * test GREATER_EQUAL
     */
    public function testGreaterEqual()
    {
        /** @var MstGreaterEqual $mstGreaterEqual */
        $mstGreaterEqual = MstGreaterEqual::criteria()
            ->add("id", 6, Criteria::GREATER_EQUAL)
            ->findFirst();

        $this->assertEquals($mstGreaterEqual->getId(), 6);

        /** @var MstGreaterEqual[] $mstGreaterEqual */
        $mstGreaterEqual = MstGreaterEqual::criteria()
            ->add("type", 2, Criteria::GREATER_EQUAL)
            ->find();

        $this->assertEquals(count($mstGreaterEqual), 4);

        foreach ($mstGreaterEqual as $greaterEqual) {
            $this->assertEquals($greaterEqual->getType(), 2);
        }
    }

    /**
     * test LESS_EQUAL
     */
    public function testLessEqual()
    {
        /** @var MstLessEqual $mstLessEqual */
        $mstLessEqual = MstLessEqual::criteria()
            ->add("id", 1, Criteria::LESS_EQUAL)
            ->findFirst();

        $this->assertEquals($mstLessEqual->getId(), 1);

        /** @var MstLessEqual[] $mstLessEqual */
        $mstLessEqual = MstLessEqual::criteria()
            ->add("type", 1, Criteria::LESS_EQUAL)
            ->find();

        $this->assertEquals(count($mstLessEqual), 2);

        foreach ($mstLessEqual as $lessEqual) {
            $this->assertEquals($lessEqual->getMode(), 2);
        }
    }

    /**
     * test IS_NULL
     */
    public function testIsNull()
    {
        /** @var MstIsNull $mstIsNull */
        $mstIsNull = MstIsNull::criteria()
            ->isNull("type")
            ->findFirst();

        $this->assertEquals($mstIsNull->getId(), 1);

        /** @var MstIsNull[] $mstIsNull */
        $mstIsNull = MstIsNull::criteria()
            ->isNull("type")
            ->find();

        $this->assertEquals(count($mstIsNull), 3);

        foreach ($mstIsNull as $isNull) {
            $this->assertEquals($isNull->getMode(), 2);
        }
    }

    /**
     * test IS_NOT_NULL
     */
    public function testIsNotNull()
    {
        /** @var MstIsNotNull $mstIsNotNull */
        $mstIsNotNull = MstIsNotNull::criteria()
            ->isNotNull("type")
            ->findFirst();

        $this->assertEquals($mstIsNotNull->getId(), 4);

        /** @var MstIsNotNull[] $mstIsNotNull */
        $mstIsNotNull = MstIsNotNull::criteria()
            ->isNotNull("type")
            ->find();

        $this->assertEquals(count($mstIsNotNull), 3);

        foreach ($mstIsNotNull as $isNotNull) {
            $this->assertEquals($isNotNull->getMode(), 3);
        }
    }

    /**
     * test LIKE
     */
    public function testLike()
    {
        /** @var MstLike $mstLike */
        $mstLike = MstLike::criteria()
            ->add("name", "a", Criteria::LIKE)
            ->findFirst();

        $this->assertEquals($mstLike->getId(), 1);

        /** @var MstLike[] $mstLike */
        $mstLike = MstLike::criteria()
            ->add("name", "%d%", Criteria::LIKE)
            ->find();

        $this->assertEquals(count($mstLike), 3);

        foreach ($mstLike as $like) {
            $this->assertEquals($like->getMode(), 3);
        }
    }

    /**
     * test I_LIKE
     */
    public function testNotLike()
    {
        /** @var MstNotLike $mstNotLike */
        $mstNotLike = MstNotLike::criteria()
            ->add("name", "b", Criteria::NOT_LIKE)
            ->findFirst();

        $this->assertEquals($mstNotLike->getId(), 1);

        /** @var MstNotLike[] $mstNotLike */
        $mstNotLike = MstNotLike::criteria()
            ->add("name", "%D%", Criteria::NOT_LIKE)
            ->find();

        $this->assertEquals(count($mstNotLike), 3);

        foreach ($mstNotLike as $notLike) {
            $this->assertEquals($notLike->getMode(), 2);
        }
    }

    /**
     * test IN
     */
    public function testIn()
    {
        /** @var MstIn $mstIn */
        $mstIn = MstIn::criteria()
            ->in("type", array(0))
            ->findFirst();

        $this->assertEquals($mstIn->getId(), 1);

        /** @var MstIn[] $mstIn */
        $mstIn = MstIn::criteria()
            ->in("type", array(0, 1))
            ->find();

        $this->assertEquals(count($mstIn), 4);

        foreach ($mstIn as $in) {
            $this->assertEquals($in->getMode(), 1);
        }
    }

    /**
     * test NOT_IN
     */
    public function testNotIn()
    {
        /** @var MstNotIn $mstNotIn */
        $mstNotIn = MstNotIn::criteria()
            ->notIn("type", array(1, 2))
            ->findFirst();

        $this->assertEquals($mstNotIn->getId(), 1);

        /** @var MstNotIn[] $mstNotIn */
        $mstNotIn = MstNotIn::criteria()
            ->notIn("type", array(0))
            ->find();

        $this->assertEquals(count($mstNotIn), 5);

        foreach ($mstNotIn as $notIn) {
            $this->assertEquals($notIn->getMode(), 2);
        }
    }

    /**
     * test BETWEEN
     */
    public function testBetween()
    {
        /** @var MstBetween $mstBetween */
        $mstBetween = MstBetween::criteria()
            ->between("type", 0, 1)
            ->findFirst();

        $this->assertEquals($mstBetween->getId(), 1);

        /** @var MstBetween[] $mstBetween */
        $mstBetween = MstBetween::criteria()
            ->between("type", 1, 5)
            ->find();

        $this->assertEquals(count($mstBetween), 5);

        foreach ($mstBetween as $between) {
            $this->assertEquals($between->getMode(), 2);
        }
    }

    /**
     * test ADD_OR
     */
    public function testOr()
    {
        /** @var MstOr $mstOr */
        $mstOr = MstOr::criteria()
            ->addOr("type", 5)
            ->addOr("mode", 6)
            ->findFirst();

        $this->assertEquals($mstOr->getId(), 6);

        /** @var MstOr[] $mstOr */
        $mstOr = MstOr::criteria()
            ->addOr("type", 0)
            ->addOr("mode", 1)
            ->find();

        $this->assertEquals(count($mstOr), 4);

        foreach ($mstOr as $or) {
            $this->assertEquals($or->getName(), "OK");
        }
    }

    /**
     * test sum 1
     */
    public function testSum1()
    {
        $point = MstTestSum::criteria()
            ->add("type", 1)
            ->sum("point");

        $this->assertEquals($point, 620);

        /** @var MstTestSum $mstTestSum */
        $mstTestSum = MstTestSum::criteria()
            ->add("type", 1)
            ->addGroup("mode")
            ->sum("point");

        $values = array(120, 500);
        foreach ($mstTestSum as $key => $testSum) {
            $this->assertEquals((int)$testSum->sumatory, $values[$key]);
        }
    }

    /**
     * test count 1
     */
    public function testCount1()
    {
        $count = MstTestCount::criteria()
            ->add("type", 1)
            ->count();

        $this->assertEquals($count, 3);

        /** @var MstTestCount $mstTestCount */
        $mstTestCount = MstTestCount::criteria()
            ->addGroup("type")
            ->count();

        $values = array(3, 2, 1);
        foreach ($mstTestCount as $key => $testCount) {
            $this->assertEquals((int)$testCount->rowcount, $values[$key]);
        }
    }

    /**
     * test truncate
     */
    public function testTruncate()
    {
        $data = MstTruncate::criteria()->find();
        $this->assertEquals(count($data), 6);

        MstTruncate::criteria()->truncate();

        $data = MstTruncate::criteria()->find();
        $this->assertEquals(count($data), 0);
    }

    /**
     * test columns
     */
    public function testColumns()
    {
        $data = MstTestColumns::criteria()->find();
        $this->assertTrue(isset($data["alpha"]));
        $this->assertTrue(isset($data["beta"]));
        $this->assertTrue(isset($data["gamma"]));

        $data = MstTestColumns::criteria()
            ->setColumns("alpha")
            ->add("alpha", 1)
            ->findFirst();
        $this->assertTrue(isset($data["alpha"]));
        $this->assertFalse(isset($data["beta"]));
        $this->assertFalse(isset($data["gamma"]));


        $data = MstTestColumns::criteria()
            ->setColumns("alpha,beta")
            ->add("beta", 1)
            ->findFirst();
        $this->assertTrue(isset($data["alpha"]));
        $this->assertTrue(isset($data["beta"]));
        $this->assertFalse(isset($data["gamma"]));


        $data = MstTestColumns::criteria()
            ->setColumns("beta,gamma")
            ->add("gamma", 1)
            ->findFirst();
        $this->assertFalse(isset($data["alpha"]));
        $this->assertTrue(isset($data["beta"]));
        $this->assertTrue(isset($data["gamma"]));

    }

    /**
     * test min
     */
    public function testMin()
    {
        $data = MstTestMinMax::criteria()->find();

        $dataList = [];
        foreach ($data as $d) {
            $dataList[] = $d->value;
        }
        $minValue = min($dataList);
        $minData = MstTestMinMax::criteria()->min("value");
        $this->assertEquals($minData, $minValue);
    }

    /**
     * test max
     */
    public function testMax()
    {
        $data = MstTestMinMax::criteria()->find();

        $dataList = [];
        foreach ($data as $d) {
            $dataList[] = $d->value;
        }
        $maxValue = max($dataList);
        $maxData = MstTestMinMax::criteria()->max("value");
        $this->assertEquals($maxData, $maxValue);
    }

    /**
     * test distinct
     */
    public function testDistinct()
    {
        $type = MstTestDistinct::criteria()
            ->setDistinct("type")
            ->addOrder("type", "ASC")
            ->find();

        $this->assertEquals(count($type), 2);
        $this->assertEquals($type[0]["type"], 1);
        $this->assertEquals($type[1]["type"], 2);

        $name = MstTestDistinct::criteria()
            ->setDistinct("name")
            ->addOrder("name", "ASC")
            ->find();
        $this->assertEquals(count($name), 3);
        $this->assertEquals($name[0]["name"], "A");
        $this->assertEquals($name[1]["name"], "B");
        $this->assertEquals($name[2]["name"], "C");


    }

    /**
     * Multiple primary key
     */
    public function testUserQuest()
    {
        try {

            Database::beginTransaction();

            // 登録
            $adminUser = new AdminUser();
            $adminUser->setAdminDbConfigId(1);
            $adminUser->save();

            $user = new User();
            $user->setId($adminUser->getId());
            $user->setName("test_user_" . $adminUser->getId());
            $user->save();

            // test data
            for ($i = 1; $i <= 5; $i++) {
                $userQuest = new UserQuest();
                $userQuest->setUserId($user->getId());
                $userQuest->setQuestId($i);
                $userQuest->setClearFlag(1);
                $userQuest->setStatusNumber(0);
                $userQuest->save();
            }

            Database::commit();

        } catch (\Exception $e) {

            Database::rollback($e);

        }

        sleep(2);

        $criteria = UserQuest::criteria()
            ->add("status_number", 0)
            ->in("quest_id", array(1,2,3))
            ->add("user_id", $user->getId());

        $param = $criteria->getConditions();
        $query = Model::buildParameters($param);

        $this->assertEquals($query[0], "[user_id] = :user_id: AND [quest_id] IN (:quest_id0:,:quest_id1:,:quest_id2:) AND [status_number] = :status_number:");
    }

}