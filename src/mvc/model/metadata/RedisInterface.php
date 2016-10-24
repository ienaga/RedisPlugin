<?php

namespace RedisPlugin\Mvc\Model\Metadata;

interface RedisInterface
{
    /**
     * @param  string $key
     * @return array
     */
    public function read($key);

    /**
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data);

    /**
     * @param  string $source
     * @return null
     */
    public function readIndexes($source);

    /**
     * writeIndexes
     */
    public function writeIndexes();

}