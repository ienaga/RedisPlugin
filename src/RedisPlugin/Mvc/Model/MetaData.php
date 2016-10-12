<?php


namespace RedisPlugin;


use RedisPlugin\RedisConnection;


class MetaData extends \Phalcon\Mvc\Model\MetaData
{
    /**
     * @var string
     */
    const CACHE_KEY = "__MetaData";

    /**
     * @var string
     */
    const INDEXES_KEY = "meta-indexes-%s";


    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    private $_cache = array();


    /**
     * __construct
     */
    public function __construct()
    {
        $options = $this->getDI()
            ->get("config")
            ->get("redis")
            ->get("metadata")
            ->toArray();

        if (!isset($options["host"])) {
            $options["host"] = "127.0.0.1";
        }

        if (!isset($options["port"])) {
            $options["port"] = 6379;
        }

        if (!isset($options["lifetime"])) {
            $options["lifetime"] = $this->getDI()
                ->get("config")
                ->get("redis")
                ->get("default")
                ->get("expire");
        }

        if (!isset($options["select"])) {
            $options["select"] = 0;
        }

        $this->setOptions($options);

        // reset
        $this->_metaData = array();
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->getRedisConnection()->getRedis();
    }

    /**
     * @return \RedisPlugin\RedisConnection
     */
    public function getRedisConnection()
    {
        return RedisConnection::getInstance()->connect($this->getOptions());
    }

    /**
     * @param  string $key
     * @return bool|mixed
     */
    public function getRedisValue($key)
    {
        $value = $this->getRedis()->hGet(self::CACHE_KEY, $key);
        $this->setCache($key, $value);
        return $value;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setRedisValue($key, $value)
    {
        $this->getRedis()->hSet(self::CACHE_KEY, $key, $value);
        if (!$this->getRedisConnection()->isTimeout(self::CACHE_KEY)) {
            $options = $this->getOptions();
            $this->getRedis()->setTimeout(self::CACHE_KEY, $options["lifetime"]);
        }
        $this->setCache($key, $value);
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function hasCache($key)
    {
        return isset($this->_cache[$key]);
    }

    /**
     * @param  string $key
     * @return mixed|null
     */
    public function getCache($key)
    {
        $cache = (!$this->hasCache($key))
            ? $this->getRedisValue($key)
            : $this->_cache[$key];

        return ($cache) ? $cache : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setCache($key, $value)
    {
        $this->_cache[$key] = $value;
    }

    /**
     * @param  string $key
     * @return array
     */
    public function read($key)
    {
        return $this->getCache($key);
    }

    /**
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data)
    {
        $this->setRedisValue($key, $data);
        $this->writeIndexes($key);
    }

    /**
     * @param string $key
     */
    public function writeIndexes($key = null)
    {
        if (!$key) {
            return;
        }

        $keys = explode("-", $key);
        if (3 > count($keys)) {
            return;
        }

        $source = array_pop($keys);

        // class name
        $class = "";
        foreach (explode("_", $source) as $value) {
            $class .= ucfirst($value);
        }

        /** @var \Phalcon\Mvc\Model $model */
        $model    = new $class;
        $indexes  = $model->getReadConnection()->describeIndexes($source);

        // cache
        $cacheKey = $this->getIndexesKey($source);
        $this->setRedisValue($cacheKey, $indexes);
    }

    /**
     * @param  string $source
     * @return null
     */
    public function readIndexes($source)
    {
        return $this->getCache($this->getIndexesKey($source));
    }

    /**
     * @param  string $source
     * @return string
     */
    public function getIndexesKey($source)
    {
        return sprintf(self::INDEXES_KEY, $source);
    }

    /**
     * reset
     */
    public function reset()
    {
        $this->getRedis()->delete(self::CACHE_KEY);

        return parent::reset();
    }
}