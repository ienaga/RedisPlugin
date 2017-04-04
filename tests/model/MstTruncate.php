<?php

require_once __DIR__ . "/../../src/mvc/Model.php";

use \RedisPlugin\Mvc\Model;

class MstTruncate extends Model
{
    /**
     * @var int
     */
    protected $id;

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
}