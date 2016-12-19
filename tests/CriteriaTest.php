<?php

require_once __DIR__ . "/../src/mvc/model/Criteria.php";
require_once __DIR__ . "/MstItem.php";

use \RedisPlugin\Mvc\Model\Criteria;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * test find first
     */
    public function testFindFirst()
    {
        /** @var MstItem $mstItem */
        $mstItem = MstItem::criteria()
            ->add("id", 1)
            ->findFirst();

        $this->assertEquals($mstItem->getName(), "test_1");
    }

    /**
     * test find
     */
    public function testFind()
    {
        /** @var MstItem[] $mstItem */
        $mstItem = MstItem::criteria()
            ->add("level", 1)
            ->find();

        $this->assertEquals(count($mstItem), 3);

        $names = array("item_1", "item_5", "item_5");
        foreach ($mstItem as $idx => $item) {
            $this->assertEquals($item->getName(), $names[$idx]);
        }
    }
}