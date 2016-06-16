<?php


namespace RedisPlugin;


use \Exception;


class RedisDb
{
    /**
     * @var \Phalcon\Mvc\Model
     */
    protected static $model = null;

    /**
     * @var \Phalcon\Mvc\Model\Transaction[]
     */
    protected static $connections = array();

    /**
     * @var bool
     */
    protected static $isTransaction = false;

    /**
     * @var array
     */
    protected static $models = array();
    protected static $bind = array();
    protected static $keys = array();

    /**
     * @var array
     */
    protected static $cache = array();
    protected static $admin_cache = array();
    protected static $config_cache = array();

    /**
     * @var string
     */
    protected static $hashPrefix = null;

    /**
     * @var \Phalcon\Mvc\Model
     */
    private static $_configModel = null;

    /**
     * @var array
     */
    private static $_configQuery = array();

    /**
     * @var \Phalcon\Mvc\Model
     */
    private static $_adminModel = null;

    /**
     * @var array
     */
    private static $_adminQuery = array();


    /**
     * @param  null $memberId
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction($memberId = null)
    {
        return self::addMasterConnection(
            self::getConnectionName($memberId) . 'Master'
        );
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  array $data
     * @param  array $whiteList
     * @return \Phalcon\Mvc\Model
     * 
     */
    public static function save($model, $data = null, $whiteList = null)
    {
        self::setPrefix($model);

        self::connect($model, self::getPrefix());

        $model->setTransaction(self::getTransaction(self::getPrefix()));

        if (!$model->save($data, $whiteList)) {

            RedisDb::outputErrorMessage($model);

        }

        self::addModels($model);

        return $model;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  array $data
     * @param  array $whiteList
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public static function delete($model, $data = null, $whiteList = null)
    {
        self::setPrefix($model);

        self::connect($model, self::getPrefix());

        $model->setTransaction(self::getTransaction(self::getPrefix()));

        if (!$model->delete($data, $whiteList)) {

            RedisDb::outputErrorMessage($model);

        }

        self::addModels($model);

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
     * @param  mixed $memberId
     * @return mixed
     */
    public static function setCon($model, $memberId = null)
    {

        $configName  = self::getConnectionName($memberId);
        $configName .= (self::isCommon($model)) ? 'Common' : '';
        $configName .= (self::isTransaction()) ? 'Master' : 'Slave';

        $model->setReadConnectionService($configName);

        return $model;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     */
    public static function addModels($model)
    {
        self::$models[] = $model;
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
        $configName .=  (self::isTransaction()) ? 'Master' : 'Slave';

        $model->setReadConnectionService($configName);

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

            if (!isset(self::$admin_cache[$memberId])) {

                self::$admin_cache[$memberId] = self::getAdminMember($memberId);

            }

            $adminMember = self::$admin_cache[$memberId];
            if (self::$admin_cache[$memberId]) {

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

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);

            self::$connections[$configName] = $manager->get();
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

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);

            if ($manager->has()) {

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
     * @param  mixed $id
     * @return \Phalcon\Mvc\Model
     */
    public static function getMemberConfigName($id)
    {
        $config = self::getConfig()->get('shard')->get('control');

        if (!self::$_configModel) {

            $class = $config->get('model');

            $model = new $class;

            self::$_configModel = $model;

            $indexes = self::getIndexes($model);

            $primary = 'id';
            if (isset($indexes['PRIMARY'])) {

                $primary = $indexes['PRIMARY']->getColumns()[0];

            }

            self::$_configQuery = array('query' => array($primary => $id));
        }

        if (!isset(self::$config_cache[$id])) {
            self::$config_cache[$id] = self::findFirst(
                self::$_configQuery,
                self::$_configModel
            );
        }


        $dbConfig = self::$config_cache[$id];

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

        if (!self::$_adminModel) {

            $class = $config->get('model');

            $model = new $class;

            self::$_adminModel = $model;

            $indexes = self::getIndexes($model);

            $primary = 'id';
            if (isset($indexes['PRIMARY'])) {

                $primary = $indexes['PRIMARY']->getColumns()[0];

            }

            self::$_adminQuery = array('query' => array($primary => $memberId));

        }

        $adminMember = self::findFirst(self::$_adminQuery, self::$_adminModel);

        if (!$adminMember)
            throw new Exception('Not Created Admin Member');

        return $adminMember;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return \Phalcon\Db\Index[]
     */
    public static function getIndexes($model)
    {
        $source = $model->getSource();
        $indexes = $model->getModelsMetaData()->readIndexes($source);

        if (self::isCommon($model) || self::isAdmin($model)) {

            self::connect($model);
            $indexes = $model->getReadConnection()->describeIndexes($source);

        }

        return $indexes;
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

            $cache = true;
            if (isset($parameters['cache'])) {

                $cache = $parameters['cache'];
                unset($parameters['cache']);

            }

            $result = $model::find($parameters);

            if (!$result) {

                $result = array();

            }

            if ($cache && self::getConfig()->get('enabled')) {

                self::setHash($model, $key, $result, $expire);

            }

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
     * @param  array $parameters
     * @return string
     */
    private static function _generateFindKey($parameters)
    {
        // base
        $key = self::generateKey($parameters['keys']);

        $addKeys = array();

        // order by
        if (isset($parameters['order'])) {

            $addKeys[] = 'order';

            $fields = explode(',', $parameters['order']);

            foreach ($fields as $field) {

                $field = trim($field);

                $values = explode(' ', $field);

                if (count($values) === 2) {

                    $addKeys[] = $values[0];
                    $addKeys[] = strtoupper($values[1]);

                } else {

                    $addKeys[] = $field;

                }
            }
        }

        // limit
        if (isset($parameters['limit'])) {

            $addKeys[] = 'limit';

            if (is_array($parameters['limit'])) {

                foreach ($parameters['limit'] as $value) {

                    $addKeys[] = $value;

                }

            } else {

                $addKeys[] = $parameters['limit'];

            }
        }

        // group by
        if (isset($parameters['group'])) {

            $addKeys[] = 'group';

            $fields = explode(',', $parameters['group']);

            foreach ($fields as $field) {

                $addKeys[] = trim($field);

            }
        }

        if ($addKeys) {

            $key .= '_' . implode('_', $addKeys);

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
     * @param mixed $prefix
     */
    public static function connect($model, $prefix = null)
    {
        $_prefix = $prefix;
        if ($prefix) {

            $prefix = (!self::isCommon($model) && !self::isAdmin($model))
                ? $prefix
                : null;

        }

        RedisDb::setCon($model, $prefix);

        // reset
        self::$hashPrefix = $_prefix;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  string $key
     * @return mixed
     */
    public static function findRedis($model, $key)
    {
        $cacheKey = self::getCacheKey($model, $key);

        if (!isset(self::$cache[$cacheKey])) {

            self::$cache[$cacheKey] = self::getRedis($model)
                ->hGet(self::getHashKey($model), $key);

        }


        return self::$cache[$cacheKey];
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return string
     */
    public static function getHashKey($model)
    {
        $key = $model->getSource();

        if (self::getPrefix()) {

            $key .= '@'. self::getPrefix();

        }

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

            if ($keys) {

                if (!isset($keys[$property]))
                    continue;

                self::$hashPrefix = $keys[$property];

            } else {

                if (!property_exists($model, $property))
                    continue;

                self::$hashPrefix = $model->{$property};

            }

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

        if ($expire > 0 && !$redis->isTimeout($hashKey)) {

            $redis->setTimeout($hashKey, $expire);

        }
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

        foreach (self::getModels() as $model) {

            self::setPrefix($model);

            self::connect($model, self::getPrefix());

            $redis = self::getRedis($model);
            $redis->delete(self::getHashKey($model));
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

        if (!is_array($parameters) || !isset($parameters['query']))
            throw new Exception('Not Found query in parameters');

        $query = $parameters['query'];

        $indexQuery = array();
        $where = array();
        self::$keys = array();
        self::$bind  = isset($parameters['bind']) ? $parameters['bind'] : array();

        // 設定確認・個別確認
        $autoIndex = self::getConfig()->get('default')->get('autoIndex');
        if (isset($parameters['autoIndex'])) {

            $autoIndex = $parameters['autoIndex'];
            unset($parameters['autoIndex']);

        }

        if ($autoIndex) {

            $indexes = self::getIndexes($model);

            if ($indexes) {

                // 一番マッチするindexにあわせてクエリを発行(PRIMARY優先)
                foreach ($indexes as $key => $index) {

                    $columns = $index->getColumns();

                    if (!isset($query[$columns[0]]))
                        continue;

                    $chkQuery = array();
                    foreach ($columns as $column) {

                        if (!isset($query[$column]))
                            break;

                        $chkQuery[$column] = $query[$column];
                    }

                    if (count($chkQuery) > count($indexQuery)) {
                        $indexQuery = $chkQuery;
                    }

                    // PRIMARY優先
                    if ($key === 0)
                        break;
                }
            }

            $query = array_merge($indexQuery, $query);
        }

        // クエリを発行
        foreach ($query as $column => $value) {

            $where[] = implode(' ', self::buildQuery($column, $value));

        }

        if (count($where) > 0) {

            $parameters[0] = implode(' AND ', $where);

            $parameters['bind'] = self::$bind;

            ksort(self::$keys);

            $parameters['keys'] = self::$keys;

        }

        unset($parameters['query']);

        $filePath = '/tmp/' . 'thomas.log';
        $logString =  str_repeat('#', 80) . "\n";
        $logString .= var_export($parameters, true) . "\n";
        @file_put_contents($filePath, $logString, FILE_APPEND);

        return $parameters;
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $value
     * @return array
     */
    public static function buildQuery($column, $value)
    {

        if (count($aliased = explode('.', $column)) > 1) {

            $named_place = $aliased[1];
            $column = sprintf('[%s].[%s]', $aliased[0], $aliased[1]);

        } else if (is_int($column)) {

            $column = '';
            $value['operator'] = Criteria::ADD_OR;

        } else {

            $named_place = $column;
            $column = sprintf('[%s]', $column);

        }

        if (is_array($value)) {

            if (isset($value['operator'])) {

                $operator  = $value['operator'];
                $bindValue = $value['value'];

                switch ($operator) {
                    case $operator === Criteria::IS_NULL:
                    case $operator === Criteria::IS_NOT_NULL:

                        $keys[$named_place] = str_replace(" ", "_", $operator);

                        $query = '';

                        break;

                    case $operator === Criteria::IN:
                    case $operator === Criteria::NOT_IN:

                        $len = count($bindValue);

                        $placeholders = array();
                        for ($i = 0; $i < $len; $i++) {

                            $placeholders[] = sprintf(':%s:', $named_place.$i);

                            self::$bind[$named_place.$i] = $bindValue[$i];

                        }

                        self::$keys[$named_place] =
                            str_replace(" ", "_", $operator)
                                . implode('_', $bindValue);

                        $query = sprintf('(%s)', implode(',', $placeholders));

                        break;

                    case $operator === Criteria::BETWEEN:

                        self::$bind[$named_place.'0'] = $bindValue[0];
                        self::$bind[$named_place.'1'] = $bindValue[1];

                        self::$keys[$named_place] = $operator . implode('_', $bindValue);

                        $query = sprintf(':%s: AND :%s:', $bindValue[0], $bindValue[1]);

                        break;

                    case $operator === Criteria::ADD_OR:

                        self::$keys[] = $operator;

                        $operator = '';

                        $queryStrings = array();
                        foreach ($value as $col => $val) {

                            $queryStrings[] = implode(' ', self::buildQuery($col, $val));

                        }

                        $query = '(' . implode(' OR ', $queryStrings) . ')';

                        break;

                    default:

                        self::$bind[$named_place] = $bindValue;

                        self::$keys[$named_place] = $operator.$bindValue;

                        $query = sprintf(':%s:', $named_place);

                        break;
                }

            } else {

                $operator = self::IN;

                $placeholders = array();
                $len = count($value);

                for ($i = 0; $i < $len; $i++) {

                    $placeholders[] = sprintf(':%s:', $named_place.$i);

                    self::$bind[$named_place.$i] = $value[$i];

                }

                self::$keys[$named_place] = str_replace(" ", "_", $operator) . implode('_', $value);

                $query = sprintf('(%s)', implode(',', $placeholders));
            }

        } else {

            if ($value === null) {

                $operator = Criteria::ISNULL;

                self::$keys[$named_place] = 'IS_NULL';

                $query = '';

            } else if (is_array($value)) {

                $operator = '';

                $queryStrings = array();
                foreach ($value as $col => $val) {

                    $queryStrings[] = implode(' ', self::buildQuery($col, $val));

                }

                $query = '(' . implode(' OR ', $queryStrings) . ')';

            } else {

                $operator = Criteria::EQUAL;

                self::$bind[$named_place] = $value;

                self::$keys[$named_place] = '='.$value;

                $query = sprintf(':%s:', $named_place);

            }

        }

        return array(
            'column' => $column,
            'operator' => $operator,
            'query' => $query
        );
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