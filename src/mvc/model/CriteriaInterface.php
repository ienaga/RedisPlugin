<?php

namespace RedisPlugin\Mvc\Model;

interface CriteriaInterface
{
    /**
     * @return \Phalcon\Mvc\Model | null
     */
    public function findFirst();

    /**
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public function find();

    /**
     * @return bool
     */
    public function update();

    /**
     * @return bool
     */
    public function delete();

    /**
     * @return int
     */
    public function count();

    /**
     * @param  string $column
     * @return int
     */
    public function sum($column);
}