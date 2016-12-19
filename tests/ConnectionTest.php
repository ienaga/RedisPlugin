<?php

require_once __DIR__ . "/../src/plugin/redis/Connection.php";

use \RedisPlugin\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * set up
     */
    public function setUp()
    {
        // FactoryDefault
        $di = new Phalcon\Di\FactoryDefault();
        \Phalcon\DI::setDefault($di);

        // config
        $config = new \Phalcon\Config();
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) { return $config; }, true);
    }

    /**
     * create test
     */
    public function testInstance()
    {
        $instance1 = Connection::getInstance();
        $instance2 = Connection::getInstance();
        $this->assertEquals($instance1, $instance2);
    }

    /**
     * local cache key test
     */
    public function testConnectionCacheKey()
    {
        $instance = Connection::getInstance();
        $key = $instance->getConnectionCacheKey("127.0.0.1", 6379, 0);
        $this->assertEquals($key, "127.0.0.1:6379:0");
    }

    /**
     * test has connection
     */
    public function testHasConnection()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect()
            ->hasConnections("127.0.0.1:6379:0");
        $this->assertEquals($bool, true);
    }

    /**
     * test connection paten 1
     */
    public function testConnection1()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect()
            ->hasConnections("127.0.0.1:6379:10");
        $this->assertEquals($bool, false);
    }

    /**
     * test connection paten 2
     */
    public function testConnection2()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect(array(
                "host"   => "127.0.0.1",
                "port"   => 6379,
                "select" => 10
            ))
            ->hasConnections("127.0.0.1:6379:10");
        $this->assertEquals($bool, true);
    }
}