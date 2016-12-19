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
        $config = require_once __DIR__ . "/config.php";
        var_dump($config);
        $config = new \Phalcon\Config($config);
        \Phalcon\DI::getDefault()->set("config", function () use ($config) { return $config; }, true);
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
     * test connection
     */
    public function testConnection()
    {
        $redis = Connection::getInstance();
        $bool  = $redis
            ->connect()
            ->hasConnections("127.0.0.1:6379:0");
        $this->assertEquals($bool, true);
    }
}