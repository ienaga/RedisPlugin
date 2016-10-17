<?php


namespace RedisPlugin;


class MetaData extends \Phalcon\Mvc\Model\MetaData
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
    const CACHE_KEY = "__MetaData";

    /**
     * @var string
     */
    const INDEXES_KEY = "meta-indexes-%s";

    /**
     * @var array
     */
    private $_options = array(
        "host"   => self::HOST,
        "port"   => self::PORT,
        "select" => self::SELECT,
        "expire" => self::EXPIRE,
    );

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
     * @param array $options
     */
    public function __construct($options = array())
    {
        // default array
        if (!$options) {
            $options = $this->_options;
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
        return $this->getConnection()->getRedis();
    }

    /**
     * @return \RedisPlugin\Connection
     */
    public function getConnection()
    {
        return Connection::getInstance()->connect($this->getOptions());
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
        if (!$this->getConnection()->isTimeout(self::CACHE_KEY)) {
            $options = $this->getOptions();
            $this->getRedis()->setTimeout(self::CACHE_KEY, $options["expire"]);
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
        $this->writeIndexes();
    }

    /**
     * writeIndexes
     */
    public function writeIndexes()
    {
        $model    = Database::getModel();
        $source   = $model->getSource();

        // INDEX
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