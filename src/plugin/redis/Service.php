<?php

namespace RedisPlugin;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\Model\Transaction\Manager;

class Service implements ServiceInterface
{

    /**
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return \Phalcon\DI::getDefault();
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->getDI()->get("config")->get("database");
    }

    /**
     * service registration
     */
    public function registration()
    {
        $databases = $this->getConfig();
        foreach ($databases as $db => $arguments)
        {
            // set
            $this->getDI()->setShared($db, function () use ($arguments) {
                return new Mysql($arguments->toArray());
            });

            // transaction
            if (isset($arguments['transaction']) && $arguments['transaction']) {

                $service = $arguments["dbname"]
                    .":". $arguments["host"]
                    .":". $arguments["port"];

                $this->getDI()->setShared($service, function() use ($db)
                {
                    $manager = new Manager();
                    if ($db !== null) {
                        $manager->setDbService($db);
                    }
                    return $manager;
                });

            }
        }
    }

    /**
     * 再登録(上書き)
     * @param array $overwrite
     */
    public function overwrite($overwrite = array())
    {
        $databases = $this->getConfig();
        foreach ($databases as $db => $arguments) {

            if (!isset($overwrite[$db])) {
                continue;
            }

            // 上書き
            $descriptor = array_merge(
                $arguments->toArray(), // 元データ
                $overwrite[$db]
            );

            // remove and set
            $this->getDI()->remove($db);
            $this->getDI()->setShared($db, function () use ($descriptor) {
                return new Mysql($descriptor);
            });

            // new config
            $config = $this->getDI()->get("config");
            $config["database"][$db] = $descriptor;

            // remove and set
            $this->getDI()->remove("config");
            $this->getDI()->set("config", function () use ($config) {
                return $config;
            }, true);

            // transaction
            if (isset($descriptor['transaction']) && $descriptor['transaction']) {

                $service = $descriptor["dbname"]
                    .":". $descriptor["host"]
                    .":". $descriptor["port"];

                $this->getDI()->setShared($service, function () use ($db) {
                    $manager = new Manager();
                    if ($db !== null) {
                        $manager->setDbService($db);
                    }
                    return $manager;
                });

            }
        }
    }

}