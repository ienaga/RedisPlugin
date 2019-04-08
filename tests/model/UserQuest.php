<?php

require_once __DIR__ . "/../../src/Phalcon/mvc/model/adapter/redis/Model.php";

use \Phalcon\Mvc\Model\Adapter\Redis\Model;

class UserQuest extends Model
{
    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var int
     */
    protected $quest_id;

    /**
     * @var int
     */
    protected $clear_flag = 0;

    /**
     * @var int
     */
    protected $status_number = 0;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * @return int
     */
    public function getQuestId()
    {
        return $this->quest_id;
    }

    /**
     * @param int $quest_id
     */
    public function setQuestId($quest_id)
    {
        $this->quest_id = $quest_id;
    }

    /**
     * @return int
     */
    public function getClearFlag()
    {
        return $this->clear_flag;
    }

    /**
     * @param int $clear_flag
     */
    public function setClearFlag($clear_flag)
    {
        $this->clear_flag = $clear_flag;
    }

    /**
     * @return int
     */
    public function getStatusNumber()
    {
        return $this->status_number;
    }

    /**
     * @param int $status_number
     */
    public function setStatusNumber($status_number)
    {
        $this->status_number = $status_number;
    }


}
