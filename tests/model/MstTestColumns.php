<?php

require_once __DIR__ . "/../../src/Phalcon/mvc/model/adapter/redis/Model.php";

use \Phalcon\Mvc\Model\Adapter\Redis\Model;

class MstTestColumns extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $alpha;

    /**
     * @var int
     */
    protected $beta;

    /**
     * @var int
     */
    protected $gamma;

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
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * @param int $alpha
     */
    public function setAlpha($alpha)
    {
        $this->alpha = $alpha;
    }

    /**
     * @return int
     */
    public function getBeta()
    {
        return $this->beta;
    }

    /**
     * @param int $beta
     */
    public function setBeta($beta)
    {
        $this->beta = $beta;
    }

    /**
     * @return int
     */
    public function getGamma()
    {
        return $this->gamma;
    }

    /**
     * @param int $gamma
     */
    public function setGamma($gamma)
    {
        $this->gamma = $gamma;
    }
}