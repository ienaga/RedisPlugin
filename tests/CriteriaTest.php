<?php

require_once __DIR__ . "/../src/mvc/model/Criteria.php";

use \RedisPlugin\Mvc\Model\Criteria;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test EQUAL
     */
    public function testEqual()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::EQUAL)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "=");
        }
    }

    /**
     * test NOT_EQUAL
     */
    public function testNotEqual()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::NOT_EQUAL)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "<>");
        }
    }

    /**
     * test GREATER_THAN
     */
    public function testGreaterThan()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::GREATER_THAN)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], ">");
        }
    }

    /**
     * test LESS_THAN
     */
    public function testLessThan()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::LESS_THAN)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "<");
        }
    }

    /**
     * test GREATER_EQUAL
     */
    public function testGreaterEqual()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::GREATER_EQUAL)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], ">=");
        }
    }

    /**
     * test LESS_EQUAL
     */
    public function testLessEqual()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::LESS_EQUAL)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "<=");
        }
    }

    /**
     * test IS_NULL
     */
    public function testIsNull()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->isNull("id")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], null);
            $this->assertEquals($values["operator"], "IS NULL");
        }
    }

    /**
     * test IS_NOT_NULL
     */
    public function testIsNotNull()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->isNotNull("id")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], null);
            $this->assertEquals($values["operator"], "IS NOT NULL");
        }
    }

    /**
     * test LIKE
     */
    public function testLike()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::LIKE)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "LIKE");
        }
    }

    /**
     * test NOT_LIKE
     */
    public function testNotLike()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->add("id", 1, Criteria::NOT_LIKE)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["value"], 1);
            $this->assertEquals($values["operator"], "NOT LIKE");
        }
    }

    /**
     * test IN
     */
    public function testIn()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->in("id", array(1, 2, 3))
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals(count($values["value"]), 3);
            $this->assertEquals($values["value"][0], 1);
            $this->assertEquals($values["value"][1], 2);
            $this->assertEquals($values["value"][2], 3);
            $this->assertEquals($values["operator"], "IN");
        }
    }

    /**
     * test NOT IN
     */
    public function testNotIn()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->notIn("id", array(1, 2, 3))
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals(count($values["value"]), 3);
            $this->assertEquals($values["value"][0], 1);
            $this->assertEquals($values["value"][1], 2);
            $this->assertEquals($values["value"][2], 3);
            $this->assertEquals($values["operator"], "NOT IN");
        }
    }

    /**
     * test BETWEEN
     */
    public function testBetween()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->between("id", 1, 2)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];
        foreach ($query as $column => $values) {
            $this->assertEquals($column, "id");

            $this->assertArrayHasKey("value",    $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals(count($values["value"]), 2);
            $this->assertEquals($values["value"][0], 1);
            $this->assertEquals($values["value"][1], 2);
            $this->assertEquals($values["operator"], "BETWEEN");
        }
    }

    /**
     * test OR
     */
    public function testOr()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addOr("id", 1)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);

        // operator test
        $query = $condition["query"];

        foreach ($query as $values) {
            $this->assertArrayHasKey("id",       $values);
            $this->assertArrayHasKey("operator", $values);

            $this->assertEquals($values["operator"], "OR");

            $this->assertArrayHasKey("value",    $values["id"]);
            $this->assertArrayHasKey("operator", $values["id"]);

            $this->assertEquals($values["id"]["value"], 1);
            $this->assertEquals($values["id"]["operator"], "=");
        }
    }

    /**
     * test order by asc
     */
    public function testOrderAsc()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addOrder("value1", "ASC")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("order", $condition);

        // operator test
        $order = $condition["order"];
        $this->assertEquals($order, "value1 ASC");
    }

    /**
     * test order by desc
     */
    public function testOrderDesc()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addOrder("value1", "DESC")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("order", $condition);

        // operator test
        $order = $condition["order"];
        $this->assertEquals($order, "value1 DESC");
    }

    /**
     * test order by asc and desc
     */
    public function testOrderAscAndDesc()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addOrder("value1", "ASC")
            ->addOrder("value2", "DESC")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("order", $condition);

        // operator test
        $order = $condition["order"];
        $this->assertEquals($order, "value1 ASC, value2 DESC");
    }

    /**
     * test group by
     */
    public function testGroup()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addGroup("user_id")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("group", $condition);

        $this->assertEquals($condition["group"], "user_id");
    }

    /**
     * test group by
     */
    public function testGroup2()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->addGroup("user_id")
            ->addGroup("type")
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("group", $condition);

        $this->assertEquals($condition["group"], "user_id, type");
    }

    /**
     * test limit
     */
    public function testLimit()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->limit(10, 0)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("limit", $condition);

        $limit = $condition["limit"];

        $this->assertArrayHasKey("number", $limit);
        $this->assertArrayHasKey("offset", $limit);

        $this->assertEquals($limit["number"], 10);
        $this->assertEquals($limit["offset"], 0);
    }

    /**
     * test cache
     */
    public function testCache()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->cache(false)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("cache", $condition);

        $cache = $condition["cache"];
        $this->assertEquals($cache, false);
    }

    /**
     * test AutoIndex
     */
    public function testAutoIndex()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->autoIndex(false)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("autoIndex", $condition);

        $autoIndex = $condition["autoIndex"];
        $this->assertEquals($autoIndex, false);
    }

    /**
     * test set
     */
    public function testSet()
    {
        $criteria  = new Criteria();
        $condition = $criteria
            ->set("name", "test")
            ->set("age", 10)
            ->buildCondition();

        $this->assertArrayHasKey("query", $condition);
        $this->assertArrayHasKey("update", $condition);

        $update = $condition["update"];
        $this->assertArrayHasKey("name", $update);
        $this->assertArrayHasKey("age",  $update);

        $this->assertEquals($update["name"], "test");
        $this->assertEquals($update["age"], 10);
    }
}