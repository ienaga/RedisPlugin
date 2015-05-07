<?php

namespace RedisPlugin;

use \Exception;
use \RedisPlugin\RedisDbConfig;

class RedisDb
{
    /**
     * @var \Phalcon\Mvc\Model
     */
    private static $selectModel = null;

    /**
     * @var \Phalcon\Mvc\Model\Transaction[]
     */
    private static $connections = array();

    /**
     * @var array
     */
    private static $redisConnections = array();

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
    private static $hashPrefix = '';


    /**
     * @param null $memberId
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction($memberId = null)
    {
        return self::addMasterConnection(self::getConnectionName($memberId) . 'Master');
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
        $type = (self::isTransaction()) ? 'Master' : 'Slave';

        self::setModel($model);

        $model->setReadConnectionService(self::getConnectionName() . $type);

        return $model;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  int|null           $memberId
     * @return \Phalcon\Mvc\Model
     */
    public static function setCon($model, $memberId = null)
    {
        $type = (self::isTransaction()) ? 'Master' : 'Slave';

        self::setModel($model);

        if (self::isTransaction() && $memberId !== null)
            self::addModels($memberId, $model);

        $model->setReadConnectionService(self::getConnectionName($memberId) . $type);

        return $model;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public static function getModel()
    {
        return self::$selectModel;
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
     * @param \Phalcon\Mvc\Model $model
     */
    public static function setModel($model)
    {
        self::$selectModel = $model;
    }

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        return \Phalcon\DI::getDefault()->get('config')->get('redis');
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param string $configName
     * @return \Phalcon\Mvc\Model
     */
    public static function setConForConfigName($model, $configName)
    {
        $type =  (self::isTransaction()) ? 'Master' : 'Slave';

        self::setModel($model);

        $model->setReadConnectionService($configName . $type);

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
            self::$redisConnections[$configName] = array();
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

        self::redisCommit();
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
        foreach (self::$connections as $configName => $connection) {

            // Activeなトランザクションがある場合だけrollbackする
            $service = \Phalcon\DI::getDefault()
                ->get('config')
                ->get('database')
                ->get($configName)
                ->get('transaction_name');

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
        self::$redisConnections = array();
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
        $model = self::getConfig()->get('shard')->get('model');
        $method = self::getConfig()->get('shard')->get('method');
        $column = self::getConfig()->get('shard')->get('column');

        $config = call_user_func(array($model, $method), $dbId);
        if ($config)
            return $config->{$column};

        return self::getConfig()->get('default')->get('name');
    }

    /**
     * @param  int $memberId
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function getAdminMember($memberId)
    {
        $model = self::getConfig()->get('admin')->get('model');
        $method = self::getConfig()->get('admin')->get('method');

        $adminMember = call_user_func(array($model, $method), $memberId);

        if (!$adminMember)
            throw new Exception('Not Created Admin Member');

        return $adminMember;
    }

    /**
     * @param  array $parameters
     * @return array
     */
    public static function _createKey($parameters)
    {
        if (!is_array($parameters) || !isset($parameters['where']))
            return $parameters;

        $where = array();
        $bind  = isset($parameters['bind']) ? $parameters['bind'] : array();

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
                    $where[]   = sprintf('%s %s :%s:', $column, $operator, $named_place);
                    $bind[ $named_place ] = $bindValue;
                } else {
                    $placeholders = array();
                    $len = count($value);
                    for ($i=0; $i<$len; $i++) {
                        $placeholders[] = sprintf(':%s:', $named_place . $i);
                        $bind[ $named_place . $i ] = $value[$i];
                    }
                    $where[] = sprintf('%s IN (%s)', $column, implode(',',$placeholders));
                }

            } else {

                if ($value === null){
                    $where[] = sprintf('%s IS NULL', $column);

                } else {
                    $where[] = sprintf('%s = :%s:', $column, $named_place);
                    $bind[ $named_place ] = $value;
                }

            }
        }

        if (count($where) > 0) {

            $conditions = isset($parameters[0]) ? $parameters[0] : '';

            $parameters[0] = $conditions
                ? ($conditions . ' AND ' . implode(' AND ', $where))
                : implode(' AND ', $where);

            $parameters['bind'] = $bind;

        }

        unset($parameters['where']);

        return $parameters;
    }


    /**
     * @param  array              $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @param  int                $expire
     * @return \Phalcon\Mvc\Model
     */
    public static function findFirst($parameters, $model, $expire = 0)
    {
        $params = array('where' => array());

        $col = null;
        if (!isset($parameters['where'])) {

            $sqlText = str_replace("AND", "", $parameters[0]);
            $sqlTexts = explode(" ", $sqlText);

            $count = 1;
            foreach ($sqlTexts as $value) {
                switch ($value) {
                    case "=":
                    case "!=":
                    case ">=":
                    case "<=":
                    case ">":
                    case "<":
                        continue;
                        break;
                    default:
                        if ($count % 2 !== 0) {
                            $col = $value;
                        } else {
                            $params['where'][$col] = $value;
                        }
                        $count++;
                        break;
                }
            }
        } else {
            $params = $parameters;
        }

        // redisから取得
        self::setModel($model);
        $result = self::findFirstRedis(self::generateKey($params['where']));

        // なければDBから
        if ($result === false) {

            $result = $model::findFirst(self::_createKey($parameters));

            if ($result) {
                self::setHash($params, $result, $expire);
            } else {
                self::setHash($params, null, $expire);
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public static function getHashKey()
    {
        return self::getModel()->getSource() . self::$hashPrefix;
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
     * @param array              $parameters
     * @param \Phalcon\Mvc\Model $value
     * @param int                $expire
     */
    public static function setHash($parameters, $value, $expire = 0)
    {
        // hash key
        $hashKey = self::getHashKey();

        // セット
        $key = self::generateKey($parameters['where']);
        self::getRedis()->hSet($hashKey, $key, $value);

        // 保持期間があればセット
        if ($expire > 0 && !self::getRedis()->isTimeout($hashKey)) {
            self::getRedis()->setTimeout($hashKey, $expire);
        }

        // 基本は1時間保持
        if (self::getConfigName() !== 'common'
            && !self::getRedis()->isTimeout($hashKey)
        ) {
            $expire = self::getConfig()->get('default')->get('expire');
            self::getRedis()->setTimeout($hashKey, $expire);
        }
    }

    /**
     * Redisから取得
     * @param  string $key
     * @return \Phalcon\Mvc\Model
     */
    public static function findFirstRedis($key)
    {
        $hashKey = self::getHashKey();
        $cacheKey = self::getCacheKey($key);

        if (isset($cache[$cacheKey]))
            return $cache[$cacheKey];

        $cache[$cacheKey] = self::getRedis()->hGet($hashKey, $key);

        return $cache[$cacheKey];
    }

    /**
     * Redisから取得
     * @param  string $key
     * @return \Phalcon\Mvc\Model[]
     */
    public static function execute($key)
    {
        $hashKey = self::getHashKey();
        $cacheKey = self::getCacheKey($key);

        if (isset($cache[$cacheKey]))
            return $cache[$cacheKey];

        $cache[$cacheKey] = self::getRedis()->hGet($hashKey, $key);

        return $cache[$cacheKey];
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
     * @param  array $keys
     * @return string
     */
    public static function generateKey($keys = array())
    {
        $keyValues = array();
        self::$hashPrefix = '';

        if (isset($keys['member_id'])) {

            self::$hashPrefix = '@'. $keys['member_id'];
            $keyValues[] = 'member_id_'.$keys['member_id'];

            unset($keys['member_id']);

        }

        if (isset($keys['id'])) {

            $keyValues[] = 'id_'.$keys['id'];
            if (self::$hashPrefix === '')
                self::$hashPrefix = '@'. $keys['id'];

            unset($keys['id']);

        }

        if (isset($keys['social_id'])) {

            $keyValues[] = 'social_id_'.$keys['social_id'];
            if (self::$hashPrefix === '')
                self::$hashPrefix = '@'. $keys['social_id'];

            unset($keys['social_id']);

        }

        if (count($keys) > 1) {
            asort($keys);
        }

        if (count($keys) > 0) {
            foreach ($keys as $key => $value) {
                $keyValues[] = $key .'_'. $value;
            }
        }

        return implode('_', $keyValues);
    }

    /**
     * redisを取得
     *
     * @return RedisManager
     */
    public static function getRedis()
    {
        $redis = RedisManager::getInstance();

        $redis->connect(
            self::getConfig()
                ->get('server')
                ->get(self::getConfigName())
                ->toArray()
        );

        return $redis;
    }

    /**
     * autoClear
     */
    public static function autoClear()
    {
        self::$hashPrefix = '';

        foreach (self::getModels() as $memberId => $models) {

            foreach ($models as $model) {

                $model->setReadConnectionService(self::getConnectionName($memberId) . 'Master');

                self::setModel($model);

                $source = self::getModel()->getSource();

                $key = $source .'@'. $memberId;
                self::getRedis()->delete($key);

                if (method_exists($model, 'getId')) {
                    $key = $source .'@'. $model->getId();
                    self::getRedis()->delete($key);
                }

                if (method_exists($model, 'getSocialId')) {
                    $key = $source .'@'. $model->getSocialId();
                    self::getRedis()->delete($key);
                }
            }

        }

        self::$models = array();
    }

    /**
     * キャッシュを削除
     * @param array $keys
     */
    public static function clearCache($keys = array())
    {
        self::getRedis()->hDel(self::getHashKey(), self::generateKey($keys));
    }

    /**
     * Redisにセット
     *
     * @param array    $keys
     * @param \Phalcon\Mvc\Model   $model
     * @param int      $expire
     */
    public static function setTransactionHash($keys, $model, $expire = 0)
    {
        $key = self::getModel()->getReadConnectionService();

        if (isset(self::$redisConnections[$key])) {

            self::$redisConnections[$key][] = array(
                'keys'     => $keys,
                'model'    => $model,
                'expire'   => $expire
            );

        }
    }

    /**
     * redisCommit
     */
    public static function redisCommit()
    {
        // キャッシュ削除
        self::autoClear();

        foreach (self::$redisConnections as $configName => $array) {

            foreach ($array as $values) {

                /** @var \Phalcon\Mvc\Model $model */
                $model = $values['model'];
                self::setModel($model);

                // key
                $key = self::generateKey($values['keys']);

                // セット
                self::getRedis()->set($key, $model);

                // 保持期間があればセット
                $expire = $values['expire'];
                if ($expire > 0 && !self::getRedis()->isTimeout($key)) {
                    self::getRedis()->setTimeout($key, $expire);
                }

                // 基本は24時間保持
                if ($configName !== 'common'
                    && !self::getRedis($configName)->isTimeout($key)
                ) {
                    $expire = self::getConfig()->get('default')->get('expire');
                    self::getRedis()->setTimeout($key, $expire);
                }
            }
        }

        self::$redisConnections = array();
    }

    /**
     * @param, \Phalcon\Mvc\Model $model
     * @param  \Phalcon\Mvc\Model\Criteria $criteria
     * @param  int $expire
     * @return \Phalcon\Mvc\Model[]
     */
    public static function query($model, $criteria, $expire = 0)
    {

        self::setModel($model);

        // 分解
        $sqlText = $criteria->getConditions();
        $sqlText = str_replace("(", "", $sqlText);
        $sqlText = str_replace(")", "", $sqlText);
        $sqlText = str_replace("!", "", $sqlText);
        $sqlText = str_replace(" ", "", $sqlText);
        $sqlText = str_replace("OR", "AND", $sqlText);

        $params = explode('AND', $sqlText);
        $parameters = array('where' => array());
        foreach ($params as $param) {

            $list = explode('=', $param);
            if (count($list) === 1) {
                continue;
            }

            list($column, $value) = $list;
            if (isset($parameters['where'][$column])) {
                $parameters['where'][$column.$value] = $value;
            } else {
                $parameters['where'][$column] = $value;
            }
        }

        $results = self::execute(self::generateKey($parameters['where']));
        if ($results === false) {

            $results = $criteria->execute();

            if (count($results) > 0) {
                self::setHash($parameters, $results, $expire);
            } else {
                self::setHash($parameters, array(), $expire);
            }
        }

        return $results;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @throws Exception
     */
    public static function generateErrorMessage($model)
    {

        $messages = null;

        foreach ($model->getMessages() as $message) {
            $messages .= $message;
        }

        throw new Exception($messages);

    }

}