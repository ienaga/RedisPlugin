<?php

namespace RedisPlugin;

use \Exception;

class RedisDb
{
    /**
     * @var \Phalcon\Mvc\Model
     */
    private static $model = null;

    /**
     * @var \Phalcon\Mvc\Model\Transaction[]
     */
    private static $connections = array();

    /**
     * @var bool
     */
    private static $isTransaction = false;

    /**
     * @var array
     */
    private static $models = array();

    /**
     * @var array
     */
    private static $cache = array();

    /**
     * @var string
     */
    private static $hashPrefix = null;

    /**
     * @param  null $memberId
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction($memberId = null)
    {
        return self::addMasterConnection(self::getConnectionName($memberId) . 'Master');
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  array $data
     * @param  array $whiteList
     * @return \Phalcon\Mvc\Model
     */
    public static function save($model, $data = null, $whiteList = null)
    {
        self::setPrefix($model);
        self::connect($model, self::getPrefix());

        $prefix = (self::isCommon($model) || self::isAdmin($model)) ? null : self::getPrefix();
        $model->setTransaction(self::getTransaction($prefix));

        if (!$model->save($data, $whiteList))
            RedisDb::outputErrorMessage($model);

        return $model;
    }

    /**
     * @param  string $configName
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransactionForConfigName($configName)
    {
        return self::addMasterConnection($configName . 'Master');
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return \Phalcon\Mvc\Model
     */
    public static function setCommon($model)
    {
        self::setModel($model);

        $configName  = self::getConnectionName();
        $configName .= (self::isTransaction()) ? 'CommonMaster' : 'CommonSlave';

        $model->setReadConnectionService($configName);

        return $model;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  int|null           $memberId
     * @return \Phalcon\Mvc\Model
     */
    public static function setCon($model, $memberId = null)
    {
        self::setModel($model);

        $configName  = self::getConnectionName($memberId);
        $configName .= (self::isTransaction()) ? 'Master' : 'Slave';

        $model->setReadConnectionService($configName);

        if (self::isTransaction() && $memberId !== null)
            self::addModels($memberId, $model);

        return $model;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public static function getModel()
    {
        return self::$model;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     */
    public static function setModel($model)
    {
        self::$model = $model;
    }

    /**
     * @param $memberId
     * @param $model
     */
    public static function addModels($memberId, $model)
    {
        if (!isset(self::$models[$memberId]))
            self::$models[$memberId] = array();

        self::$models[$memberId][] = $model;
    }

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    public static function getModels()
    {
        return self::$models;
    }

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        return \Phalcon\DI::getDefault()->get('config')->get('redis');
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  string $configName
     * @return \Phalcon\Mvc\Model
     */
    public static function setConForConfigName($model, $configName)
    {
        $type =  (self::isTransaction()) ? 'Master' : 'Slave';

        $model->setReadConnectionService($configName . $type);
        self::setModel($model);

        return $model;
    }

    /**
     * @param  int $memberId
     * @return string
     */
    public static function getConnectionName($memberId = null)
    {

        $mode = self::getConfig()->get('shard')->get('enabled');

        if ($mode && (int) $memberId > 0) {

            $adminMember = self::getAdminMember($memberId);

            if ($adminMember) {
                $column = self::getConfig()->get('admin')->get('column');
                return self::getMemberConfigName($adminMember->{$column});
            }
        }

        return self::getConfig()->get('default')->get('name');
    }

    /**
     * @param  string $configName
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function addMasterConnection($configName)
    {
        if (!isset(self::$connections[$configName])) {

            $service = \Phalcon\DI::getDefault()
                ->get('config')
                ->get('database')
                ->get($configName)
                ->get('transaction_name');

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $transactionManager */
            $transactionManager = \Phalcon\DI::getDefault()->getShared($service);

            self::$connections[$configName] = $transactionManager->get();
        }

        return self::$connections[$configName];
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
    public static function isTransaction()
    {
        return self::$isTransaction;
    }

    /**
     * commit
     */
    public static function commit()
    {
        if (self::isTransaction()) {

            foreach (self::$connections as $connection) {

                if (!$connection->isValid()) {
                    continue;
                }

                $connection->commit();
            }
        }

        self::autoClear();
        self::$connections = array();
        self::$isTransaction = false;
    }

    /**
     * rollback
     * @param Exception $e
     */
    public static function rollback(Exception $e)
    {
        $rollback = false;

        $config = \Phalcon\DI::getDefault()->get('config')->get('database');

        foreach (self::$connections as $configName => $connection) {

            // Activeなトランザクションがある場合だけrollbackする
            $service = $config->get($configName)->get('transaction_name');

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $transactionManager */
            $transactionManager = \Phalcon\DI::getDefault()->getShared($service);

            if ($transactionManager->has()) {

                try {

                    $connection->rollback();

                } catch (\Exception $e) {

                    array_shift(self::$connections);
                    self::rollback($e);

                }

                $rollback = true;
            }
        }

        // 初期化
        self::$models = array();
        self::$connections = array();
        self::$isTransaction = false;

        // 有効なトランザクションが無くrollbackしなかったときはエラーログいらない
        if ($rollback === false)
            return;

        error_log(
            '[rollback] MESSAGE:'. $e->getMessage()
            .' - FILE:'.$e->getFile()
            .' - LINE:'.$e->getLine()
            .$e->getTraceAsString()
        );
    }

    /**
     * @param  mixed $dbId
     * @return \Phalcon\Mvc\Model
     */
    public static function getMemberConfigName($dbId)
    {
        $config = self::getConfig()->get('shard');

        $dbConfig = call_user_func(array(
            $config->get('model'),
            $config->get('method')
        ), $dbId);

        if ($dbConfig)
            return $dbConfig->{$config->get('column')};

        return self::getConfig()->get('default')->get('name');
    }

    /**
     * @param  int $memberId
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function getAdminMember($memberId)
    {
        $config = self::getConfig()->get('admin');

        $adminMember = call_user_func(array(
            $config->get('model'),
            $config->get('method')
        ), $memberId);

        if (!$adminMember)
            throw new Exception('Not Created Admin Member');

        return $adminMember;
    }

    /**
     * @param  array              $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @param  int                $expire
     * @return \Phalcon\Mvc\Model
     */
    public static function find($parameters, $model, $expire = 0)
    {
        $parameters = self::_createKey($parameters, $model);

        self::setPrefix($model, $parameters['bind']);

        self::connect($model, self::getPrefix());


        $key = self::_generateFindKey($parameters);
        unset($parameters['keys']);

        // redisから検索
        $result = self::findRedis($model, $key);

        // なければDBから
        if ($result === false) {

            $result = $model::find($parameters);

            if (!$result)
                $result = array();

            self::setHash($model, $key, $result, $expire);
        }

        return $result;
    }

    /**
     * @param  array              $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @param  int                $expire
     * @return \Phalcon\Mvc\Model
     */
    public static function findFirst($parameters, $model, $expire = 0)
    {
        $result = self::find($parameters, $model, $expire);

        return (isset($result[0])) ? $result[0] : false;
    }

    /**
     * @param  \Phalcon\Mvc\Model\Criteria $criteria
     * @param, \Phalcon\Mvc\Model $model
     * @param  int $expire
     * @return \Phalcon\Mvc\Model[]
     */
    public static function query($criteria, $model, $expire = 0)
    {
        $parameters = array();
        $prefixKey = array();

        // 分解
        $sqlText = $criteria->getConditions();
        $sqlText = str_replace("(", "", $sqlText);
        $sqlText = str_replace(")", "", $sqlText);
        $sqlText = str_replace(" ", "", $sqlText);
        $sqlText = str_replace("OR", "AND", $sqlText);

        $params = explode('AND', $sqlText);
        foreach ($params as $param) {

            // reset
            $delimiter = '';
            $formula = array('!=', '<>', '<=', '>=', '>', '<', '=');
            $list = array();

            // key value
            while (count($formula)) {

                $delimiter = array_shift($formula);

                $list = explode($delimiter, $param);

                if (count($list) === 1)
                    continue;

                break;
            }

            list($column, $value) = $list;

            if (isset($parameters[$column])) {

                if (is_array($parameters[$column])) {

                    $parameters[$column][] = $delimiter.$value;

                } else {

                    $parameters[$column] = array($parameters[$column], $delimiter.$value);

                }

            } else {

                $parameters[$column] = $delimiter.$value;

                $prefixKey[$column] = $value;

            }
        }

        if ($criteria->getOrder())
            $parameters['order'] = str_replace(" ", "_", $criteria->getOrder());

        if ($criteria->getLimit())
            $parameters['limit'] = str_replace(" ", "_", $criteria->getLimit());


        self::setPrefix($model, $prefixKey);

        self::connect($model, self::getPrefix());

        $key = self::generateKey($parameters);
        $results = self::findRedis($model, $key);

        if ($results === false) {

            $results = $criteria->execute();

            if (!$results)
                $results = array();

            self::setHash($model, $key, $results, $expire);
        }

        return $results;
    }

    /**
     * @param  array $parameters
     * @return string
     */
    private static function _generateFindKey($parameters)
    {

        $key = self::generateKey($parameters['keys']);

        // order by
        if (isset($parameters['order']))
            $key .= '_order_' . str_replace(" ", "_", $parameters['order']);

        // limit
        if (isset($parameters['limit'])) {
            $value = $parameters['limit'];
            if (is_array($parameters['limit'])) {
                foreach ($parameters['limit'] as $value) {
                    $value .= '_'. $value;
                }
            }

            $key .= '_limit_' . $value;
        }

        // group by
        if (isset($parameters['group'])) {
            $value = $parameters['group'];
            if (is_array($parameters['group'])) {
                foreach ($parameters['group'] as $value) {
                    $value .= '_'. $value;
                }
            }

            $key .= '_group_' . $value;
        }

        return $key;
    }

    /**
     * @param  array $keys
     * @return string
     */
    public static function generateKey($keys = array())
    {
        $keyValues = array();

        if (count($keys) > 0) {
            foreach ($keys as $key => $value) {

                if (is_array($key)) {
                    foreach ($key as $col => $val) {
                        $keyValues[] = $col . $val;
                    }

                    continue;
                }

                $keyValues[] = $key . $value;
            }
        }

        return implode('_', $keyValues);
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return bool
     */
    public static function isCommon($model)
    {
        $source = $model->getSource();
        $dbs = self::getConfig()->get('common')->get('dbs');

        if (!$dbs)
            return false;

        $commonDbs = explode(',', $dbs);
        if (is_array($commonDbs)) {

            foreach ($commonDbs as $name) {
                $name = trim($name);

                if (substr($source, 0, strlen($name)) !== $name)
                    continue;

                return true;
            }
        }

        return false;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return bool
     */
    public static function isAdmin($model)
    {
        $source = $model->getSource();
        $dbs = self::getConfig()->get('admin')->get('dbs');

        if (!$dbs)
            return false;

        $adminDbs = explode(',', $dbs);
        if (is_array($adminDbs)) {

            foreach ($adminDbs as $name) {
                $name = trim($name);

                if (substr($source, 0, strlen($name)) !== $name)
                    continue;

                return true;
            }
        }

        return false;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param $prefix
     */
    public static function connect($model, $prefix)
    {
        if (!self::isCommon($model)) {
            RedisDb::setCon($model, !self::isAdmin($model) ? $prefix : null);
        } else {
            RedisDb::setCommon($model);
        }

        // reset
        self::setModel($model);
        self::$hashPrefix = $prefix;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  string $key
     * @return mixed
     */
    public static function findRedis($model, $key)
    {
        $cacheKey = self::getCacheKey($model, $key);

        if (!isset(self::$cache[$cacheKey]))
            self::$cache[$cacheKey] = self::getRedis($model)->hGet(self::getHashKey($model), $key);

        return self::$cache[$cacheKey];
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return string
     */
    public static function getHashKey($model)
    {
        $key = $model->getSource();

        if (self::getPrefix())
            $key .= '@'. self::getPrefix();

        return $key;
    }

    /**
     * @return null|string
     */
    public static function getPrefix()
    {
        return self::$hashPrefix;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  null $keys
     * @throws \Exception
     */
    public static function setPrefix($model, $keys = null)
    {
        self::$hashPrefix = null;

        $columns = self::getConfig()->get('prefix')->get('columns');

        if (!$columns)
            throw new Exception('not found prefix columns');

        $columns = explode(',', $columns);

        foreach ($columns as $column) {

            $property = trim($column);

            if (!property_exists($model, $property) || !$keys || !isset($keys[$property]))
                continue;

            self::$hashPrefix = (!$keys) ? $model->{$property} : $keys[$property];

            break;

        }
    }

    /**
     * \Phalcon\Mvc\Model $model
     * @param $model
     * @param $key
     * @return string
     */
    public static function getCacheKey($model, $key)
    {
        return self::getHashKey($model) .'@'. $key;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param string $key
     * @param mixed $value
     * @param int $expire
     */
    public static function setHash($model, $key, $value, $expire = 0)
    {
        $hashKey = self::getHashKey($model);

        $redis = self::getRedis($model);
        $redis->hSet($hashKey, $key, $value);

        $expire = (!$expire)
            ? self::getConfig()->get('default')->get('expire')
            : $expire;

        if ($expire > 0 && !$redis->isTimeout($hashKey))
            $redis->setTimeout($hashKey, $expire);
    }

    /**
     * redisを取得
     * @param  \Phalcon\Mvc\Model $model
     * @return RedisManager
     */
    public static function getRedis($model)
    {
        $configs = self::getConfig()
            ->get('server')
            ->get($model->getReadConnectionService())
            ->toArray();

        return RedisManager::getInstance()->connect($configs);
    }

    /**
     * autoClear
     */
    public static function autoClear()
    {

        foreach (self::getModels() as $memberId => $models) {

            /** @var \Phalcon\Mvc\Model[] $models */
            foreach ($models as $model) {

                $model->setReadConnectionService(
                    self::getConnectionName($memberId) . 'Master'
                );

                $source = $model->getSource();

                self::setModel($model);

                $redis = self::getRedis($model);
                $redis->delete($source .'@'. $memberId);

                if (method_exists($model, 'getId')) {
                    $redis->delete($source .'@'. $model->getId());
                }

                if (method_exists($model, 'getSocialId')) {
                    $redis->delete($source .'@'. $model->getSocialId());
                }
            }
        }

        self::$models = array();
    }

    /**
     * @param  array $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @return array
     * @throws Exception
     */
    public static function _createKey($parameters, $model)
    {
        if (!is_array($parameters) || !isset($parameters['where']))
            throw new Exception('Error Not Found where or String');

        $where = array();

        $bind  = isset($parameters['bind']) ? $parameters['bind'] : array();

        $keys = array();

        foreach ($parameters['where'] as $column => $value) {

            if (count($aliased = explode('.', $column)) > 1) {
                $named_place = $aliased[1];
                $column = sprintf('[%s].[%s]', $aliased[0], $aliased[1]);
            } else {
                $named_place = $column;
                $column = sprintf('[%s]', $column);
            }

            if (is_array($value)) {

                // where句で"="以外のオペレータを利用する為の拡張
                if (isset($value['operator'])) {
                    $operator  = $value['operator'];
                    $bindValue = $value['value'];

                    switch ($operator) {
                        case $operator === Criteria::IS_NULL:
                        case $operator === Criteria::IS_NOT_NULL:

                            $keys[$named_place] = str_replace(" ", "_", $operator);

                            $val = '';

                            break;

                        case $operator === Criteria::IN:
                        case $operator === Criteria::NOT_IN:

                            $len = count($bindValue);

                            $placeholders = array();
                            for ($i = 0; $i < $len; $i++) {

                                $placeholders[] = sprintf(':%s:', $named_place.$i);

                                $bind[$named_place.$i] = $bindValue[$i];

                            }

                            $keys[$named_place] = str_replace(" ", "_", $operator) . implode('_', $bindValue);

                            $val = sprintf('(%s)', implode(',', $placeholders));

                            break;

                        case $operator === Criteria::BETWEEN:

                            $bind[$named_place.'0'] = $bindValue[0];
                            $bind[$named_place.'1'] = $bindValue[1];

                            $keys[$named_place] = $operator . implode('_', $bindValue);

                            $val = sprintf(':%s: AND :%s:', $bindValue[0], $bindValue[1]);

                            break;

                        default:

                            $bind[$named_place] = $bindValue;

                            $keys[$named_place] = $operator.$bindValue;

                            $val = sprintf(':%s:', $named_place);

                            break;
                    }

                } else {

                    $operator = self::IN;

                    $placeholders = array();
                    $len = count($value);

                    for ($i = 0; $i < $len; $i++) {

                        $placeholders[] = sprintf(':%s:', $named_place.$i);

                        $bind[$named_place.$i] = $value[$i];

                    }

                    $keys[$named_place] = str_replace(" ", "_", $operator) . implode('_', $value);

                    $val = sprintf('(%s)', implode(',', $placeholders));
                }

            } else {

                if ($value === null){

                    $operator = Criteria::ISNULL;

                    $keys[$named_place] = 'IS_NULL';

                    $val = '';

                } else {

                    $operator = Criteria::EQUAL;

                    $bind[$named_place] = $value;

                    $keys[$named_place] = '='.$value;

                    $val = sprintf(':%s:', $named_place);

                }

            }

            $where[] = sprintf('%s %s %s', $column, $operator, $val);
        }

        if (count($where) > 0) {

            $parameters[0] = implode(' AND ', $where);

            $parameters['bind'] = $bind;

            ksort($keys);

            $parameters['keys'] = $keys;

        }

        unset($parameters['where']);

        return $parameters;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @throws \Exception
     */
    public static function outputErrorMessage($model)
    {
        $messages = '';
        foreach ($model->getMessages() as $message) {
            $messages .= $message;
        }

        throw new Exception($messages);
    }

}