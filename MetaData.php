<?php

namespace RedisPlugin;


class MetaData extends \Phalcon\Mvc\Model\MetaData
{
    /**
     * @var string
     */
    const CACHE_KEY = '__MetaData';

    /**
     * @var int
     */
    const EXPIRE = 3600;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $_cache = array();

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {

        if (!$options) {
            $configs = \Phalcon\DI::getDefault()
                ->get('config')
                ->get('redis')
                ->get('server')
                ->toArray();

            $options = array_shift($configs);
        }

        if (!isset($options['lifetime']))
            $options['lifetime'] = self::EXPIRE;

        $this->setOptions($options);

        $this->_metaData = array();
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return RedisManager
     */
    public function getRedis()
    {
        return RedisManager::getInstance()->connect($this->getOptions());
    }

    /**
     * @param  string $key
     * @return array
     */
    public function read($key)
    {
        if (!isset($this->_cache[$key]))
            $this->_cache[$key] = $this->getRedis()->hGet(self::CACHE_KEY, $key);

        return ($this->_cache[$key]) ? $this->_cache[$key] : null;
    }

    /**
     * @param string $key
     * @param array  $data
     */
    public function write($key, $data)
    {
        $this->getRedis()->hSet(self::CACHE_KEY, $key, $data);

        if (!$this->getRedis()->isTimeout(self::CACHE_KEY)) {
            $options = $this->getOptions();
            $this->getRedis()->setTimeout(self::CACHE_KEY, $options['lifetime']);
        }

        $this->_cache[$key] = $data;
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