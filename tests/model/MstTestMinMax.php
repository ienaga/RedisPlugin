<?php

require_once __DIR__ . "/../../src/mvc/Model.php";

use \RedisPlugin\Mvc\Model;

class MstTestMinMax extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $value;

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
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->type = $value;
    }
}