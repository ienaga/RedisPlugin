<?php

namespace RedisPlugin\Mvc\Model\Metadata;

use RedisPlugin\Connection;
use RedisPlugin\Mvc\Model;

class Redis extends \Phalcon\Mvc\Model\Metadata\Redis implements RedisInterface
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
     * @var int
     */
    const EXPIRE = 86400;

    /**
     * @var string
     */
    const PREFIX_KEY = "__MetaData";

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
     * MetaData constructor.
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $options = array();
        }

        if (!isset($options["host"])) {
            $options["host"] = self::HOST;
        }

        if (!isset($options["port"])) {
            $options["port"] = self::PORT;
        }

        if (!isset($options["expire"])) {
            $options["expire"] = self::EXPIRE;
        }

        if (!isset($options["select"])) {
            $options["select"] = self::SELECT;
        }

        $this->setOptions($options);
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
     * @param  string $key
     * @return mixed|bool
     */
    public function getCache($key)
    {
        if (!$this->hasCache($key)) {
            $this->setCache($key, $this->getRedisValue($key));
        }
        return $this->_cache[$key];
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
     * @return bool
     */
    public function hasCache($key)
    {
        return isset($this->_cache[$key]);
    }

    /**
     * @return \RedisPlugin\Connection
     */
    public function getConnection()
    {
        return Connection::getInstance()->connect($this->getOptions());
    }

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->getConnection()->getRedis();
    }

    /**
     * @param  string $key
     * @return bool|mixed
     */
    public function getRedisValue($key)
    {
        return $this->getRedis()->hGet(self::PREFIX_KEY, $key);
    }

    /**
     * @param string $key
     * @param array  $value
     */
    public function setRedisValue($key, $value)
    {
        // redis
        $this->getRedis()->hSet(self::PREFIX_KEY, $key, $value);

        // set expire
        if (!$this->getConnection()->isTimeout(self::PREFIX_KEY)) {
            $options = $this->getOptions();
            $this->getRedis()->setTimeout(self::PREFIX_KEY, $options["expire"]);
        }
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
     * @param  string $key
     * @return array|null
     */
    public function read($key)
    {
        return ($this->getCache($key)) ? : null;
    }

    /**
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data)
    {
        // local cache
        $this->setCache($key, $data);

        // redis
        $this->setRedisValue($key, $data);

        // indexes
        $this->writeIndexes();
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
     * writeIndexes
     */
    public function writeIndexes()
    {
        // source
        $model  = Model::getCurrentModel();
        $source = $model->getSource();

        // indexes
        $indexes = $model->getReadConnection()->describeIndexes($source);

        // cache
        $key = $this->getIndexesKey($source);

        // local cache
        $this->setCache($key, $indexes);

        // redis
        $this->setRedisValue($key, $indexes);
    }

    /**
     * reset
     */
    public function reset()
    {
        $this->getRedis()->delete(self::PREFIX_KEY);
        return parent::reset();
    }
}