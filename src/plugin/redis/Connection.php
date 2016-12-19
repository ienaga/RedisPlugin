<?php

namespace RedisPlugin;

use \Redis;
use \RedisPlugin\Exception\RedisPluginException;

class Connection implements ConnectionInterface
{
    /**
     * @var \RedisPlugin\Connection
     */
    private static $instance = null;

    /**
     * @var Redis
     */
    protected static $redis = null;

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
    static function getInstance()
    {
        return (self::$instance === null)
            ? self::$instance = new static
            : self::$instance;
    }

    /**
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return \Phalcon\DI::getDefault();
    }

    /**
     * @param  array $config
     * @return $this
     * @throws RedisPluginException
     */
    public function connect($config = array())
    {
        if (!$config) {
            $configs = $this->getDI()
                ->get("config")
                ->get("redis")
                ->get("server")
                ->toArray();

            if (!count($configs)) {
                throw new RedisPluginException("Initial setting can not be found.");
            }

            $config = array_shift($configs);
        }

        // set params
        $host   = (isset($config["host"]))   ? $config["host"]   : self::HOST;
        $port   = (isset($config["port"]))   ? $config["port"]   : self::PORT;
        $select = (isset($config["select"])) ? $config["select"] : self::SELECT;

        // cache key
        $key = $host .":". $port .":". $select;

        // cache set
        if (!$this->hasConnections($key)) {
            $this->connections[$key] = $this->createClient($host, $port, $select);
        }

        self::$redis = $this->connections[$key];

        return $this;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function hasConnections($key)
    {
        return isset($this->connections[$key]);
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return Redis
     */
    public function createClient($host = self::HOST, $port = self::PORT, $select = self::SELECT)
    {
        try {
            $redis = new Redis();
            $redis->pconnect($host, $port, 0, "x");
            $redis->select($select);
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        } catch (RedisPluginException $e) {
            die($e->getMessage());
        }

        return $redis;
    }

    /**
     * @return Redis
     */
    public function getRedis()
    {
        return self::$redis;
    }

    /**
     * 期限が設定されているか
     *
     * @param  string $key
     * @return bool
     */
    public function isTimeout($key)
    {
        return ($this->getRedis()->ttl($key) > 0);
    }

    /**
     * @throws RedisPluginException
     */
    final function __clone()
    {
        throw new RedisPluginException("Clone is not allowed against" . get_class($this));
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
        $this->connections = array();
        self::$redis       = null;
    }
}