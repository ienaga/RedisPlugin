<?php


namespace RedisPlugin;


interface ServiceInterface
{
    /**
     * service registration
     */
    public function registration();

    /**
     * 再登録(上書き)
     * @param array $overwrite
     */
    public function overwrite($overwrite = array());
}