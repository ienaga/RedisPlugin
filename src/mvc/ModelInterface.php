<?php

namespace RedisPlugin\Mvc;

interface ModelInterface
{

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null);

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function update($data = null, $whiteList = null);

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function create($data = null, $whiteList = null);

    /**
     * @return bool
     */
    public function delete();

    /**
     * @param  null|string|array $parameters
     * @return \Phalcon\Mvc\Model
     */
    public static function findFirst($parameters = null);

    /**
     * @param  null|string|array $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public static function find($parameters = null);

    /**
     * @param  array $parameters
     * @return array
     */
    public static function buildParameters($parameters);
}