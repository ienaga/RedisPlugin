<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

class Database implements DatabaseInterface
{

    /**
     * @var \Phalcon\Mvc\Model[]
     */
    protected static $models = array();

    /**
     * @var bool
     */
    protected static $useMasterConnection = false;

    /**
     * @var bool
     */
    protected static $isTransaction = false;

    /**
     * @var \Phalcon\Mvc\Model\TransactionInterface[]
     */
    protected static $transactions = array();

    /**
     * @param \Phalcon\Mvc\Model $model
     */
    public static function addModel(\Phalcon\Mvc\Model $model)
    {
        self::$models[] = $model;
    }

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    private static function getModels(): array
    {
        return self::$models;
    }

    /**
     * @return null|\Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction(\Phalcon\Mvc\Model $model)
    {
        if (!self::isTransaction()) {
            return null;
        }

        $service = self::getServiceName($model);
        if (!isset(self::$transactions[$service])) {
            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);
            self::$transactions[$service] = $manager->get();
        }
        return self::$transactions[$service];
    }

    /**
     * @param  string $name
     * @return Connection
     * @throws Exception
     */
    private static function getConnection($name)
    {
        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("server")
            ->get($name)
            ->toArray();

        return Connection::getInstance()->connect($config);
    }

    /**
     * @param  string $name
     * @return \Redis
     * @throws Exception
     */
    public static function getRedis($name): \Redis
    {
        return self::getConnection($name)->getRedis();
    }

    /**
     * master connection on
     */
    public static function masterConnectionOn()
    {
        self::$useMasterConnection = true;
    }

    /**
     * master connection off
     */
    public static function masterConnectionOff()
    {
        self::$useMasterConnection = false;
    }

    /**
     * @return bool
     */
    public static function useMasterConnection()
    {
        return self::$useMasterConnection;
    }

    /**
     * beginTransaction
     */
    public static function beginTransaction()
    {
        self::$isTransaction = true;
    }

    /**
     * @return bool
     */
    public static function isTransaction(): bool
    {
        return self::$isTransaction;
    }

    /**
     * commit
     */
    public static function commit()
    {
        if (self::isTransaction()) {

            foreach (self::$transactions as $transaction) {

                if (!$transaction->isValid()) {
                    continue;
                }

                $transaction->commit();
            }
        }

        // reset
        self::$transactions  = array();
        self::$isTransaction = false;

        // redis clear
        self::autoClear();
    }

    /**
     * autoClear
     */
    private static function autoClear()
    {
        $models = self::getModels();

        $columns = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("prefix")
            ->get("columns");

        $databases = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("database");

        foreach ($models as $model) {

            foreach ($columns as $values) {

                if (is_string($values)) {
                    $values = [$values];
                }

                $matches = [];
                foreach ($values as $column) {
                    $property = trim($column);

                    if (!property_exists($model, $property)) {
                        continue;
                    }

                    $matches[] = $model->{$property};
                }

                // match case
                if (count($matches) !== count($values)) {
                    continue;
                }

                // cache clear
                $prefix = implode(":", $matches);
                foreach ($databases as $db => $arguments) {
                    $key = self::getCacheKey($model, $arguments, $prefix);
                    self::getRedis($db)->delete($key);

                }
            }

            // DEFAULT PREFIX
            foreach ($databases as $db => $arguments) {
                $key = self::getCacheKey($model, $arguments, Model::DEFAULT_PREFIX);
                self::getRedis($db)->delete($key);
            }

            // local cache clear
            $model::localCacheClear();

        }

        // reset
        self::$models = array();
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  array              $arguments
     * @param  mixed              $prefix
     * @return string
     */
    public static function getCacheKey(\Phalcon\Mvc\Model $model, $arguments, $prefix = Model::DEFAULT_PREFIX): string
    {
        return sprintf("%s:%s:%s",
            self::generateServiceName($arguments),
            $model->getSource(),
            $prefix
        );
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return string
     */
    private static function getServiceName(\Phalcon\Mvc\Model $model): string
    {
        // config
        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("database")
            ->get($model->getReadConnectionService());

        return self::generateServiceName($config);
    }

    /**
     * @param  array $config
     * @return string
     */
    private static function generateServiceName($config = array()): string
    {
        return sprintf("%s:%s:%s",
            $config["dbname"],
            $config["host"],
            $config["port"]
        );
    }

    /**
     * rollback
     * @param Exception $e
     */
    public static function rollback(Exception $e)
    {
        foreach (self::$transactions as $service => $transaction) {

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);

            if ($manager->has()) {

                try {

                    $transaction->rollback($e->getMessage());

                } catch (Exception $e) {

                    array_shift(self::$transactions);
                    self::rollback($e);

                }

            }
        }

        // auto clear
        self::autoClear();

        // reset
        self::$models        = array();
        self::$transactions  = array();
        self::$isTransaction = false;

        // error log
        error_log(
            sprintf("[rollback] MESSAGE: %s  - FILE: %s - LINE: %s - Trace: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            )
        );
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @throws Exception
     */
    public static function outputErrorMessage(\Phalcon\Mvc\Model $model)
    {
        $messages = "";
        foreach ($model->getMessages() as $message) {
            $messages .= $message;
        }

        throw new Exception($messages);
    }
}