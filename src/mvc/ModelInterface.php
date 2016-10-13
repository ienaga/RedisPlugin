<?php


namespace RedisPlugin;


interface ModelInterface
{

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null);


}