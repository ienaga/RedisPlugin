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
        parent::setUp();

        // config
        $config = new \Phalcon\Config();
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/redis.yml");
        $config->merge($yml->get("test"));

        $di = new Phalcon\Di\FactoryDefault();
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
     * test connection paten 1
     */
    public function testConnection1()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect()
            ->hasConnections("127.0.0.1:6379:0");
        $this->assertEquals($bool, true);
    }

    /**
     * test connection paten 2
     */
    public function testConnection2()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect()
            ->hasConnections("127.0.0.1:6379:10");
        $this->assertEquals($bool, false);
    }

    /**
     * test connection paten 3
     */
    public function testConnection3()
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