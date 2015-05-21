<?php

namespace RedisPlugin;

use \Exception;

class RedisDb
{
    /** operator list */
    const EQUAL = '=';
    const NOT_EQUAL = '<>';
    const GREATER_THAN = '>';
    const LESS_THAN = '<';
    const GREATER_EQUAL = '>=';
    const LESS_EQUAL = '<=';
    const ISNULL = 'IS NULL';
    const ISNOTNULL = 'IS NOT NULL';
    const LIKE = 'LIKE';
    const ILIKE = 'ILIKE';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const BETWEEN = 'BETWEEN';

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
        $prefix = null;

        if (method_exists($model, 'getMemberId')) {

            $prefix = $model->getMemberId();

        } else if (method_exists($model, 'getId')) {

            $prefix = $model->getId();

        }

        self::setCon($model, $prefix);

        $model->setTransaction(self::getTransaction($prefix));

        if (!$model->save($data, $whiteList))
            RedisDb::outputErrorMessage();

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
        if ((int) $memberId > 0) {

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
        $parameters = self::_generateParameters($parameters);

        $key = self::_generateFindKey($parameters);

        self::setPrefix($parameters['bind']);

        self::connect($model);

        // redisから検索
        $result = self::findRedis($key);

        // なければDBから
        if ($result === false) {

            $result = $model::find($parameters);

            if (!$result)
                $result = array();

            self::setHash($key, $result, $expire);
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

        return $result[0];
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


        self::setPrefix($prefixKey);

        $key = self::generateKey($parameters);

        self::connect($model);

        $results = self::findRedis($key);

        if ($results === false) {

            $results = $criteria->execute();

            if (!$results)
                $results = array();

            self::setHash($key, $results, $expire);
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
     * @return \Phalcon\Mvc\Model
     */
    public static function connect($model)
    {
        $prefix = self::getPrefix();
        $source = $model->getSource();

        $isCommon = false;
        $isAdmin = false;

        // 共通DB
        $commonDbs = explode(',', self::getConfig()->get('common')->get('dbs'));
        if (is_array($commonDbs)) {

            foreach ($commonDbs as $name) {

                if (substr($source, 0, strlen($name)) !== $name)
                    continue;

                RedisDb::setCommon($model);
                $isCommon = true;

                break;
            }
        }

        // マスタDB
        if (!$isCommon) {
            $adminDbs = explode(',', self::getConfig()->get('admin')->get('dbs'));
            if (is_array($adminDbs)) {

                foreach ($adminDbs as $name) {

                    if (substr($source, 0, strlen($name)) !== $name)
                        continue;

                    RedisDb::setCon($model);
                    $isAdmin = true;

                    break;
                }
            }
        }

        // ユーザDB
        if (!$isCommon && !$isAdmin) {
            RedisDb::setCon($model, self::getPrefix());
        }

        // reset
        self::setModel($model);
        self::$hashPrefix = $prefix;

    }

    /**
     * Redisから取得
     * @param  string $key
     * @return \Phalcon\Mvc\Model
     */
    public static function findRedis($key)
    {
        $cacheKey = self::getCacheKey($key);

        if (!isset(self::$cache[$cacheKey]))
            self::$cache[$cacheKey] = self::getRedis()->hGet(self::getHashKey(), $key);

        return self::$cache[$cacheKey];
    }

    /**
     * @return string
     */
    public static function getHashKey()
    {
        $key = self::getModel()->getSource();

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
     * @param array $keys
     */
    public static function setPrefix($keys)
    {
        self::$hashPrefix = null;

        if (isset($keys['member_id'])) {

            self::$hashPrefix = $keys['member_id'];

        } else if (isset($keys['id'])) {

            self::$hashPrefix = $keys['id'];

        } else if (isset($keys['social_id'])) {

            self::$hashPrefix = $keys['social_id'];

        }
    }


    /**
     * @param  string $key
     * @return string
     */
    public static function getCacheKey($key)
    {
        return self::getHashKey() .'@'. $key;
    }

    /**
     * Redisにセット
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire
     */
    public static function setHash($key, $value, $expire = 0)
    {
        // hash key
        $hashKey = self::getHashKey();

        $redis = self::getRedis();
        $redis->hSet($hashKey, $key, $value);

        // 保持期間があればセット
        if ($expire > 0 && !$redis->isTimeout($hashKey)) {
            $redis->setTimeout($hashKey, $expire);
        }

        // 基本は1日保持
        if (!$redis->isTimeout($hashKey)) {
            $expire = 86400;

            if (self::getConfigName() !== 'common')
                $expire = self::getConfig()->get('default')->get('expire');

            $redis->setTimeout($hashKey, $expire);
        }
    }

    /**
     * @return string
     */
    public static function getConfigName()
    {
        return self::getConfig()
            ->get(self::getModel()->getReadConnectionService())
            ->get('name');
    }

    /**
     * redisを取得
     *
     * @return RedisManager
     */
    public static function getRedis()
    {
        $configs = self::getConfig()->get('server')->get(self::getConfigName())->toArray();
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

                $redis = self::getRedis();
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
     * @return array
     * @throws Exception
     */
    public static function _generateParameters($parameters)
    {
        if (!is_array($parameters) || !isset($parameters['where']))
            throw new Exception('findFirst Error Not Found where or String');

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
                        case $operator === self::ISNULL:
                        case $operator === self::ISNOTNULL:

                            $keys[$named_place] = str_replace(" ", "_", $operator);

                            $val = '';

                            break;

                        case $operator === self::IN:
                        case $operator === self::NOT_IN:

                            $len = count($bindValue);

                            $placeholders = array();
                            for ($i = 0; $i < $len; $i++) {

                                $placeholders[] = sprintf(':%s:', $named_place.$i);

                                $bind[$named_place.$i] = $bindValue[$i];

                            }

                            $keys[$named_place] = str_replace(" ", "_", $operator) . implode('_', $bindValue);

                            $val = sprintf('(%s)', implode(',', $placeholders));

                            break;

                        case $operator === self::BETWEEN:

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

                    $operator = self::ISNULL;

                    $keys[$named_place] = 'IS_NULL';

                    $val = '';

                } else {

                    $operator = self::EQUAL;

                    $bind[$named_place] = $value;

                    $keys[$named_place] = '='.$value;

                    $val = sprintf(':%s:', $named_place);

                }

            }

            $where[] = sprintf('%s %s %s', $column, $operator, $val);
        }

        if (count($where) > 0) {

            $conditions = isset($parameters[0]) ? $parameters[0] : '';

            $parameters[0] = $conditions
                ? ($conditions . ' AND ' . implode(' AND ', $where))
                : implode(' AND ', $where);

            $parameters['bind'] = $bind;

            ksort($keys);

            $parameters['keys'] = $keys;
        }

        unset($parameters['where']);

        return $parameters;
    }

    /**
     * @throws Exception
     */
    public static function outputErrorMessage()
    {
        $messages = '';
        foreach (self::getModel()->getMessages() as $message) {
            $messages .= $message;
        }

        throw new Exception($messages);
    }

}