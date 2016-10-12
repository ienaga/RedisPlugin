<?php


namespace RedisPlugin;


use \Redis;
use \RedisPlugin\RedisConnectionInterface;


class RedisConnection implements RedisConnectionInterface
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
     * @var \RedisPlugin\RedisConnection
     */
    private static $instance = null;

    /**
     * @var Redis
     */
    protected static $redis = null;

    /**
     * @var Redis[]
     */
    private $_connections = array();


    /**
     * RedisConnection constructor.
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * @return RedisConnection
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
     */
    public function connect($config = array())
    {
        if (!$config) {
            $configs = $this->getDI()
                ->get("config")
                ->get("redis")
                ->get("server")
                ->toArray();
            $config = array_shift($configs);
        }

        // params
        $host   = $config["host"];
        $port   = $config["port"];
        $select = $config["select"];

        // cache key
        $key = $host .":". $port .":". $select;

        // cache set
        if (!$this->hasConnections($key)) {
            $this->_connections[$key] = $this->createClient($host, $port, $select);
        }

        self::$redis = $this->_connections[$key];

        return $this;
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function hasConnections($key)
    {
        return isset($this->_connections[$key]);
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @param  int    $select
     * @return Redis
     */
    public function createClient($host = self::HOST, $port = self::PORT, $select = self::SELECT)
    {
        $redis = new Redis();
        $redis->pconnect($host, $port, 0, "x");
        $redis->select($select);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
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
     * @throws \Exception
     */
    final function __clone()
    {
        throw new \Exception("Clone is not allowed against" . get_class($this));
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        foreach ($this->_connections as $redis) {
            $redis->close();
        }
        $this->_connections = array();
        self::$redis = null;
    }
}