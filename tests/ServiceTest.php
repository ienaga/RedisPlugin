<?php

require_once __DIR__ . "/../src/plugin/redis/Service.php";

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \RedisPlugin\Service
     */
    protected $service = null;

    /**
     * set up
     */
    public function setUp()
    {
        // FactoryDefault
        $di = new Phalcon\Di\FactoryDefault();

        // config
        $config = new \Phalcon\Config();
        $yml    = new \Phalcon\Config\Adapter\Yaml(__DIR__ . "/redis.yml");
        $config->merge($yml->get("test"));
        $di->set("config", function () use ($config) { return $config; }, true);

        // service class
        $this->service = new \RedisPlugin\Service();
    }

    /**
     * test registration
     */
    public function testRegistration()
    {
        // 登録
        $this->service->registration();

        $databases = $this
            ->service
            ->getDI()
            ->get("config")
            ->get("database");

        foreach ($databases as $db => $arguments) {
            $this->assertEquals($this->service->getDI()->has($db), true);
        }
    }

    /**
     * test over write
     */
    public function testOverWrite()
    {
        $databases = $this
            ->service
            ->getDI()
            ->get("config")
            ->get("database");

        // 上書き
        $array = array();
        foreach ($databases as $db => $arguments) {
            $descriptor = $arguments->toArray();
            $descriptor["port"] = 3301;
            $array[$db] = $descriptor;
        }

        $this->service->overwrite($array);

        foreach ($databases as $db => $arguments) {
            $this->assertEquals($this->service->getDI()->has($db), true);
        }
    }
}