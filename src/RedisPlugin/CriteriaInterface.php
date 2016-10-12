<?php


namespace RedisPlugin;


interface CriteriaInterface
{

    /**
     * @return \Phalcon\Mvc\Model
     */
    public function findFirst();

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    public function find();

    /**
     * @return void
     */
    public function delete();

    /**
     * @return void
     */
    public function update();

    /**
     * @return array
     */
    public function buildCondition();

    /**
     * @return null|\Phalcon\Mvc\Model
     */
    public function getModel();

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return $this
     */
    public function setModel(\Phalcon\Mvc\Model $model);

    /**
     * @return int
     */
    public function getExpire();

    /**
     * @param  int $expire
     * @return int
     */
    public function setExpire($expire = 0);

}