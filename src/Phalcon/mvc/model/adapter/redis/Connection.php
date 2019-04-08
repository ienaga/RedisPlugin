<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

use \Redis;

class Connection implements ConnectionInterface
{

    /**
     * @var string
     */
    const CONNECTION_CACHE_KEY = "%s:%s:%s";

    /**
     * @var Connection
     */
    private static $_instance = null;

    /**
     * @var Redis
     */
    protected static $current_client = null;

    /**
     * @var Redis[]
     */
    protected $connections = array();


    /**
     * Connection constructor.
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * @return Connection
     */
    static function getInstance(): Connection
    {
        return (self::$_instance === null)
            ? self::$_instance = new static
            : self::$_instance;
    }

    /**
     * @return \Phalcon\DiInterface
     */
    public function getDI(): \Phalcon\DiInterface
    {
        return \Phalcon\DI::getDefault();
    }

    /**
     * @param  array $config
     * @return Connection
     * @throws Exception
     */
    public function connect($config = array()): Connection
    {
        if (!$config) {
            $configs = $this->getDI()
                ->get("config")
                ->get("redis")
                ->get("server")
                ->toArray();

            if (!count($configs)) {
                throw new Exception("Initial setting can not be found.");
            }

            $config = array_shift($configs);
        }

        // set params
        $host   = (isset($config["host"]))   ? $config["host"]   : self::HOST;
        $port   = (isset($config["port"]))   ? $config["port"]   : self::PORT;
        $select = (isset($config["select"])) ? $config["select"] : self::SELECT;

        self::$current_client = $this->createClient($host, $port, $select);

        return $this;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function hasConnections(string $key): bool
    {
        return isset($this->connections[$key]);
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return Redis
     */
    public function createClient(string $host = self::HOST, int $port = self::PORT, int $select = self::SELECT): Redis
    {
        // local cache key
        $key = $this->getConnectionCacheKey($host, $port, $select);

        if ($this->hasConnections($key)) {
            return $this->connections[$key];
        }

        try {

            $redis = new Redis();
            $redis->pconnect($host, $port, 0, "x");
            $redis->select($select);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        } catch (Exception $e) {

            die($e->getMessage());

        }

        // set local cache
        $this->connections[$key] = $redis;

        return $redis;
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return string
     */
    public function getConnectionCacheKey(string $host = self::HOST, int $port = self::PORT, int $select = self::SELECT): string
    {
        return sprintf(self::CONNECTION_CACHE_KEY, $host, $port, $select);
    }

    /**
     * @return Redis
     */
    public function getRedis(): Redis
    {
        return self::$current_client;
    }

    /**
     * 期限が設定されているか
     *
     * @param  string $key
     * @return bool
     */
    public function isTimeout(string $key): bool
    {
        return ($this->getRedis()->ttl($key) > 0);
    }

    /**
     * @throws Exception
     */
    final function __clone()
    {
        throw new Exception("Clone is not allowed against" . get_class($this));
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        // connection close
        foreach ($this->connections as $redis) {
            $redis->close();
        }

        // local params reset
        $this->connections    = array();
        self::$current_client = null;
    }
}