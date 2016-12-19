<?php

require_once __DIR__ . "/../src/plugin/redis/Ranking.php";

use \RedisPlugin\Ranking;

class RankingTest extends \PHPUnit_Framework_TestCase
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

        $score = 10;
        for ($i = 1; $i <= 10; $i++) {
            Ranking::getInstance()
                ->connect()
                ->getRedis()
                ->zAdd("ranking", ($score + $i), $i);
        }
    }

    /**
     * test rank
     */
    public function testGetRank()
    {

    }

}