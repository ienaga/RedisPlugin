<?php

require_once __DIR__ . "/../../src/Phalcon/mvc/model/adapter/redis/Model.php";

use \Phalcon\Mvc\Model\Adapter\Redis\Model;

class AdminUser extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $admin_db_config_id;

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
    public function getAdminDbConfigId()
    {
        return $this->admin_db_config_id;
    }

    /**
     * @param int $admin_db_config_id
     */
    public function setAdminDbConfigId($admin_db_config_id)
    {
        $this->admin_db_config_id = $admin_db_config_id;
    }
}