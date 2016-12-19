<?php

namespace RedisPlugin;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Mvc\Model\Transaction\Manager;
use Phalcon\Events\Manager as EventManager;
use Phalcon\Logger\Adapter\File;
use Phalcon\Logger;

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
        $log = $this->getDI()
            ->get("config")
            ->get("redis")
            ->get("logger");

        $databases = $this->getConfig();
        foreach ($databases as $db => $arguments)
        {
            // set
            $this->getDI()->setShared($db, function () use ($db, $arguments, $log) {

                $connection = new Mysql($arguments->toArray());

                // logging
                if ($log->get("logging")) {
                    $eventsManager = new EventManager();
                    $logger        = new File($log->get("output"));
                    $eventsManager->attach("db", function($event, $connection) use ($logger)
                    {
                        if ($event->getType() === "beforeQuery") {
                            $sqlVariables = $connection->getSQLVariables();
                            if (count($sqlVariables)) {
                                $logger->log($connection->getSQLStatement() . " " . join(", ", $sqlVariables), Logger::INFO);
                            } else {
                                $logger->log($connection->getSQLStatement(), Logger::INFO);
                            }
                        }
                    });

                    // event set
                    $connection->setEventsManager($eventsManager);
                }

                return $connection;
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
        $log = $this->getDI()
            ->get("config")
            ->get("redis")
            ->get("logger");

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
            $this->getDI()->setShared($db, function () use ($db, $descriptor, $log) {
                $connection = new Mysql($descriptor);

                // logging
                if ($log->get("logging")) {
                    $eventsManager = new EventManager();
                    $logger        = new File($log->get("output"));
                    $eventsManager->attach("db", function($event, $connection) use ($logger)
                    {
                        if ($event->getType() === "beforeQuery") {
                            $sqlVariables = $connection->getSQLVariables();
                            if (count($sqlVariables)) {
                                $logger->log($connection->getSQLStatement() . " " . join(", ", $sqlVariables), Logger::INFO);
                            } else {
                                $logger->log($connection->getSQLStatement(), Logger::INFO);
                            }
                        }
                    });

                    // event set
                    $connection->setEventsManager($eventsManager);
                }

                return $connection;
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