<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

interface DatabaseInterface
{
    /**
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction(\Phalcon\Mvc\Model $model);

    /**
     * commit
     */
    public static function commit();

    /**
     * rollback
     * @param Exception $e
     */
    public static function rollback(Exception $e);
}