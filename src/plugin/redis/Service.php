<?php

namespace RedisPlugin;

use \Phalcon\Db\Adapter\Pdo\Mysql;
use \Phalcon\Mvc\Model\Transaction\Manager;
use \Phalcon\Events\Manager as EventManager;
use \Phalcon\Logger\Adapter\File;
use \Phalcon\Logger;

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

        /** @var \Phalcon\Config $arguments */
        foreach ($databases as $db => $arguments)
        {
            // set
            $this->getDI()->setShared($db, function () use ($db, $arguments, $log) {

                $connection = new Mysql($arguments->toArray());

                // logging
                if ($log->get("logging")) {
                    $eventsManager = new EventManager();
                    $logger        = new File(getcwd()."/../log/".$log->get("output"));
                    $eventsManager->attach("db", function($event, $connection) use ($logger)
                    {
                        /** @var \Phalcon\Db\Adapter $connection */
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
            if (isset($arguments['transaction']) && (bool) $arguments['transaction']) {
                $this->getDI()->setShared($this->getService($arguments), function() use ($db)
                {
                    $manager = new Manager();
                    $manager->setDbService($db);
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
        $config    = $this->getDI()->get("config");

        /** @var \Phalcon\Config $arguments */
        foreach ($databases as $db => $arguments) {

            if (!isset($overwrite[$db])) {
                continue;
            }

            // 上書き
            $descriptor = array_merge(
                $arguments->toArray(), // 元データ
                $overwrite[$db]
            );

            // remove
            $this->getDI()->remove($db);

            // set
            $this->getDI()->setShared($db, function () use ($db, $descriptor, $log) {
                $connection = new Mysql($descriptor);

                // logging
                if ($log->get("logging")) {
                    $eventsManager = new EventManager();
                    $logger        = new File($log->get("output"));
                    $eventsManager->attach("db", function($event, $connection) use ($logger)
                    {
                        /** @var \Phalcon\Db\Adapter $connection */
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

            // overwrite config
            $config["database"][$db] = $descriptor;

            // transaction
            if (isset($descriptor['transaction']) && (bool) $descriptor['transaction']) {
                $this->getDI()->setShared($this->getService($descriptor), function () use ($db) {
                    $manager = new Manager();
                    $manager->setDbService($db);
                    return $manager;
                });
            }
        }

        // remove
        $this->getDI()->remove("config");

        // set
        $this->getDI()->set("config", function () use ($config) { return $config; }, true);
    }

    /**
     * @param  array $config
     * @return string
     */
    public function getService($config = array())
    {
        return sprintf("%s:%s:%s", $config["dbname"], $config["host"], $config["port"]);
    }
}