<?php

require_once __DIR__ . "/../../src/mvc/Model.php";

use \RedisPlugin\Mvc\Model;

class AdminUser extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $admin_config_db_id;

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
    public function getAdminConfigDbId()
    {
        return $this->admin_config_db_id;
    }

    /**
     * @param int $admin_config_db_id
     */
    public function setAdminConfigDbId($admin_config_db_id)
    {
        $this->admin_config_db_id = $admin_config_db_id;
    }
}