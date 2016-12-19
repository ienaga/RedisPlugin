<?php

require_once __DIR__ . "/../src/plugin/redis/Service.php";

class ServiceTest extends \PHPUnit_Framework_TestCase
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
     * test registration
     */
    public function testRegistration()
    {
        // 登録
        $dbService = new \RedisPlugin\Service();
        $dbService->registration();

        $di = \Phalcon\DI::getDefault();

        $databases = $di
            ->get("config")
            ->get("database");

        foreach ($databases as $db => $arguments) {
            $this->assertEquals($di->has($db), true);
        }
    }

    /**
     * test over write
     */
    public function testOverWrite()
    {
        $di = \Phalcon\DI::getDefault();

        $databases = $di
            ->get("config")
            ->get("database");

        // 上書き
        $config = array();
        foreach ($databases as $db => $arguments) {
            $descriptor = $arguments->toArray();
            $descriptor["port"] = 3301;
            $config[$db] = $descriptor;
        }

        $dbService = new \RedisPlugin\Service();
        $dbService->overwrite($config);

        foreach ($databases as $db => $arguments) {
            $this->assertEquals($di->has($db), true);
        }
    }
}