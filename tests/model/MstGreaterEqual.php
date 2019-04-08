<?php

require_once __DIR__ . "/../../src/Phalcon/mvc/model/adapter/redis/Model.php";

use \Phalcon\Mvc\Model\Adapter\Redis\Model;

class MstGreaterEqual extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}