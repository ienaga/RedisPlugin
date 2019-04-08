<?php

namespace Phalcon\Mvc\Model\Adapter\Redis\Model;

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
     * @return mixed
     */
    public function count();

    /**
     * @param  string $column
     * @return mixed
     */
    public function sum(string $column);

    /**
     * @return bool
     */
    public function truncate();

    /**
     * @param  string $column
     * @return mixed
     */
    public function max(string $column);

    /**
     * @param  string $column
     * @return mixed
     */
    public function min(string $column);
}