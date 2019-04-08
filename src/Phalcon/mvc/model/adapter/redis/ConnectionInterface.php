<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

interface ConnectionInterface
{
    /**
     * @var string
     */
    const HOST = "127.0.0.1";

    /**
     * @var int
     */
    const PORT = 6379;

    /**
     * @var int
     */
    const SELECT = 0;

    /**
     * @return Connection
     */
    static function getInstance();

    /**
     * @param  array $config
     * @return $this
     */
    public function connect($config = array());

    /**
     * @param  string $key
     * @return bool
     */
    public function hasConnections(string $key);

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return string
     */
    public function getConnectionCacheKey(string $host = self::HOST, int $port = self::PORT, int $select = self::SELECT);

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return \Redis
     */
    public function createClient(string $host = self::HOST, int $port = self::PORT, int $select = self::SELECT);

    /**
     * @return \Redis
     */
    public function getRedis();
}