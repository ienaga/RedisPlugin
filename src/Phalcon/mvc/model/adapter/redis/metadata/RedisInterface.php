<?php

namespace Phalcon\Mvc\Model\Adapter\Redis\Metadata;

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
     * @return mixed
     */
    public function readIndexes(string $source);

    /**
     * writeIndexes
     */
    public function writeIndexes();

}