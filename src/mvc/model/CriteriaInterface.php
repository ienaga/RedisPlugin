<?php

namespace RedisPlugin\Mvc\Model;

interface CriteriaInterface
{
    /**
     * @return \Phalcon\Mvc\Model | bool
     */
    public function findFirst();

    /**
     * @return \Phalcon\Mvc\Model[] | array
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
     * @return mixed
     */
    public function count();

    /**
     * @param  string $column
     * @return mixed
     */
    public function sum($column);
}