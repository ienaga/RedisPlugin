<?php


namespace RedisPlugin;


use \Exception;


class Database implements DatabaseInterface
{

    /**
     * @var string
     */
    const DEFAULT_NAME = "db";

    /**
     * @var int
     */
    const DEFAULT_EXPIRE = 3600;

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

    /**
     * @var array
     */
    protected static $bind = array();

    /**
     * @var array
     */
    protected static $keys = array();

    /**
     * @var array
     */
    protected static $cache = array();

    /**
     * @var array
     */
    protected static $admin_cache = array();

    /**
     * @var array
     */
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
     * @return \Phalcon\DiInterface
     */
    public function getDI()
    {
        return \Phalcon\DI::getDefault();
    }
    
    /**
     * @param  null $prefix
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransaction($prefix = null)
    {
        return self::addMasterConnection(
            self::getConnectionName($prefix) . "Master"
        );
    }
    
    /**
     * @param  string $configName
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function getTransactionForConfigName($configName = "db")
    {
        return self::addMasterConnection($configName . "Master");
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  int|null $prefix
     */
    public static function setConnection($model, $prefix = null)
    {
        $configName  = self::getConnectionName($prefix);
        $configName .= (self::isCommon($model)) ? "Common" : "";
        $configName .= (self::isTransaction()) ? "Master" : "Slave";

        $model->setReadConnectionService($configName);
        self::setModel($model);
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
    public static function setModel(\Phalcon\Mvc\Model $model)
    {
        self::$model = $model;
    }

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
    public static function getModels()
    {
        return self::$models;
    }

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        return \Phalcon\DI::getDefault()->get("config")->get("redis");
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  string $configName
     * @return \Phalcon\Mvc\Model
     */
    public static function setConnectionForConfigName($model, $configName)
    {
        $configName .=  (self::isTransaction()) ? "Master" : "Slave";

        $model->setReadConnectionService($configName);

        return $model;
    }

    /**
     * @param  int|null $prefix
     * @return string
     */
    public static function getConnectionName($prefix = null)
    {

        $mode = self::getConfig()->get("shard")->get("enabled");

        if ($mode && (int) $prefix > 0) {

            if (!isset(self::$admin_cache[$prefix])) {

                self::$admin_cache[$prefix] = self::getAdminMember($prefix);

            }

            $adminMember = self::$admin_cache[$prefix];
            if (self::$admin_cache[$prefix]) {

                $column = self::getConfig()->get("admin")->get("column");
                return self::getMemberConfigName($adminMember->{$column});

            }
        }

        return self::DEFAULT_NAME;
    }

    /**
     * @param  string $configName
     * @return \Phalcon\Mvc\Model\TransactionInterface
     */
    public static function addMasterConnection($configName)
    {
        // ServiceName
        $service = self::getServiceName($configName);

        if (!isset(self::$connections[$service])) {

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);

            self::$connections[$service] = $manager->get();
        }

        return self::$connections[$service];
    }

    /**
     * @param  string $configName
     * @return string
     */
    public static function getServiceName($configName)
    {
        // config
        $c = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("database")
            ->get($configName);

        return $c["dbname"] . ":" . $c["host"] . ":" . $c["port"];
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

        foreach (self::$connections as $configName => $connection) {

            // Activeなトランザクションがある場合だけrollbackする
            $service = self::getServiceName($configName);

            /** @var \Phalcon\Mvc\Model\Transaction\Manager $manager */
            $manager = \Phalcon\DI::getDefault()->getShared($service);

            if ($manager->has()) {

                try {

                    $connection->rollback();

                } catch (RedisPluginException $e) {

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
        if ($rollback === false) {
            return;
        }

        error_log(
            "[rollback] MESSAGE:". $e->getMessage()
            ." - FILE:".$e->getFile()
            ." - LINE:".$e->getLine()
            .$e->getTraceAsString()
        );
    }

    /**
     * @param  mixed $id
     * @return string
     */
    public static function getMemberConfigName($id)
    {
        $config = self::getConfig()
            ->get("shard")
            ->get("control");

        if (!self::$_configModel) {

            $class = $config->get("model");

            $model = new $class;

            self::$_configModel = $model;

            $indexes = self::getIndexes($model);

            $primary = "id";
            if (isset($indexes["PRIMARY"])) {

                $primary = $indexes["PRIMARY"]->getColumns()[0];

            }

            self::$_configQuery = array("query" => array($primary => $id));
        }

        if (!isset(self::$config_cache[$id])) {
            self::$config_cache[$id] = self::findFirst(
                self::$_configQuery,
                self::$_configModel
            );
        }


        $dbConfig = self::$config_cache[$id];
        if ($dbConfig) {
            return $dbConfig->{$config->get("column")};
        }

        return self::DEFAULT_NAME;
    }

    /**
     * @param  int $primaryId$primaryId
     * @return \Phalcon\Mvc\Model
     * @throws RedisPluginException
     */
    public static function getAdminMember($primaryId)
    {
        $config = self::getConfig()->get("admin");

        if (!self::$_adminModel) {

            $class = $config->get("model");

            $model = new $class;

            self::$_adminModel = $model;

            $indexes = self::getIndexes($model);

            $primary = "id";
            if (isset($indexes["PRIMARY"])) {

                $primary = $indexes["PRIMARY"]->getColumns()[0];

            }

            self::$_adminQuery = array("query" => array($primary => $primaryId));

        }

        $adminMember = self::findFirst(self::$_adminQuery, self::$_adminModel);
        if (!$adminMember) {
            throw new RedisPluginException("Not Created Admin Member");
        }

        return $adminMember;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return \Phalcon\Db\Index[]
     */
    public static function getIndexes($model)
    {
        $source  = $model->getSource();
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
     * @return \Phalcon\Mvc\Model[]
     */
    public static function find($parameters, $model, $expire = 0)
    {

        $parameters = self::_createKey($parameters, $model);

        self::setPrefix($model, $parameters["bind"]);

        self::connect($model, self::getPrefix());

        $key = self::_generateFindKey($parameters);
        unset($parameters["keys"]);

        // redisから検索
        $result = self::findRedis($model, $key);

        // なければDBから
        if ($result === false) {

            // cache on or off
            $cache = self::getConfig()->get("enabled");
            if (isset($parameters["cache"])) {
                $cache = $parameters["cache"];
                unset($parameters["cache"]);
            }

            $result = $model::find($parameters);
            if (!$result) {
                $result = array();
            }

            // cache on
            if ($cache) {
                self::setHash($model, $key, $result, $expire);
            }
        }

        return $result;
    }

    /**
     * @param  array              $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @param  int                $expire
     * @return \Phalcon\Mvc\Model|bool
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
        $key = self::generateKey($parameters["keys"]);

        $addKeys = array();

        // order by
        if (isset($parameters["order"])) {

            $addKeys[] = "order";

            $fields = explode(",", $parameters["order"]);

            foreach ($fields as $field) {

                $field = trim($field);

                $values = explode(" ", $field);

                if (count($values) === 2) {

                    $addKeys[] = $values[0];
                    $addKeys[] = strtoupper($values[1]);

                } else {

                    $addKeys[] = $field;

                }
            }
        }

        // limit
        if (isset($parameters["limit"])) {

            $addKeys[] = "limit";

            if (is_array($parameters["limit"])) {

                foreach ($parameters["limit"] as $value) {

                    $addKeys[] = $value;

                }

            } else {

                $addKeys[] = $parameters["limit"];

            }
        }

        // group by
        if (isset($parameters["group"])) {

            $addKeys[] = "group";

            $fields = explode(",", $parameters["group"]);

            foreach ($fields as $field) {

                $addKeys[] = trim($field);

            }
        }

        if ($addKeys) {

            $key .= "_" . implode("_", $addKeys);

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

        return implode("_", $keyValues);
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return bool
     */
    public static function isCommon($model)
    {
        $dbs = self::getConfig()
            ->get("common")
            ->get("dbs")
            ->toArray();

        if (!$dbs) {
            return false;
        }

        if (is_array($dbs)) {

            $source = $model->getSource();

            foreach ($dbs as $name) {

                $name = trim($name);

                if (substr($source, 0, strlen($name)) !== $name) {
                    continue;
                }

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
        $config  = self::getConfig();
        $enabled = $config->get("shard")->get("enabled");

        if (!$enabled) {
            return false;
        }

        $source = $model->getSource();
        $dbs    = $config->get("admin")->get("dbs")->toArray();

        if (!$dbs) {
            return false;
        }

        if (is_array($dbs)) {

            foreach ($dbs as $name) {

                $name = trim($name);
                if (substr($source, 0, strlen($name)) !== $name) {
                    continue;
                }

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

        Database::setConnection($model, $prefix);

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
        // local cache
        $cache = self::getLocalCache($model, $key);

        // redis
        if (!$cache) {
            $cacheKey = self::createCacheKey($model);
            $cache    = self::getRedis($model)->hGet($cacheKey, $key);
            self::setLocalCache($model, $key, $cache);
        }

        return $cache;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @param  string $key
     * @return mixed
     */
    public static function getLocalCache($model, $key)
    {
        // cache key
        $cacheKey = self::createCacheKey($model);

        // init
        if (!isset(self::$cache[$cacheKey])) {
            self::$cache[$cacheKey] = [];
        }

        return isset(self::$cache[$cacheKey][$key])
            ? self::$cache[$cacheKey][$key]
            : null;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param string $key
     * @param mixed  $value
     */
    public static function setLocalCache($model, $key, $value)
    {
        // cache key
        $cacheKey = self::createCacheKey($model);

        // init
        if (!isset(self::$cache[$cacheKey])) {
            self::$cache[$cacheKey] = [];
        }

        self::$cache[$cacheKey][$key] = $value;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return string
     */
    public static function getHashKey($model)
    {
        $key  = ":";
        $key .= $model->getSource();

        if (self::getPrefix()) {
            $key .= ":". self::getPrefix();
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
     * @throws RedisPluginException
     */
    public static function setPrefix($model, $keys = null)
    {
        self::$hashPrefix = null;

        $mode = self::getConfig()->get("shard")->get("enabled");
        if (!$mode) {
            return;
        }

        $columns = self::getConfig()->get("prefix")->get("columns");
        if (!$columns) {
            throw new RedisPluginException("not found prefix columns");
        }

        foreach ($columns as $column) {

            $property = trim($column);

            if ($keys) {

                if (!isset($keys[$property])) {
                    continue;
                }

                self::$hashPrefix = $keys[$property];

            } else {

                if (!property_exists($model, $property)) {
                    continue;
                }

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
        return self::getHashKey($model) .":". $key;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param string $key
     * @param mixed  $value
     * @param int    $expire
     */
    public static function setHash($model, $key, $value, $expire = 0)
    {
        // cache key
        $cacheKey = self::createCacheKey($model);

        // set redis
        $redis = self::getRedis($model);
        $redis->hSet($cacheKey, $key, $value);

        // local cache
        self::getLocalCache($model, $key, $value);

        // EXPIRE
        $expire = (!$expire) ? self::DEFAULT_EXPIRE : $expire;
        if ($expire > 0 && !self::getConnection($model)->isTimeout($cacheKey)) {
            $redis->setTimeout($cacheKey, $expire);
        }
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return string
     */
    public static function createCacheKey($model)
    {
        $key  = "";
        $key .= self::getServiceName($model->getReadConnectionService());
        $key .= self::getHashKey($model);
        return $key;
    }

    /**
     *
     * @param  \Phalcon\Mvc\Model $model
     * @return Connection
     */
    public static function getConnection($model)
    {
        $configs = self::getConfig()
            ->get("server")
            ->get($model->getReadConnectionService())
            ->toArray();

        return Connection::getInstance()->connect($configs);
    }

    /**
     * redisを取得
     * @param  \Phalcon\Mvc\Model $model
     * @return |Redis
     */
    public static function getRedis($model)
    {
        return self::getConnection($model)->getRedis();
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
     * @throws RedisPluginException
     */
    public static function _createKey($parameters, $model)
    {
        if (!is_array($parameters) || !isset($parameters["query"])) {
            throw new RedisPluginException("Not Found query in parameters");
        }

        // init
        $indexQuery  = array();
        $where       = array();
        self::$keys  = array();
        self::$bind  = isset($parameters["bind"]) ? $parameters["bind"] : array();

        // 設定確認・個別確認
        $autoIndex = self::getConfig()->get("autoIndex");
        if (isset($parameters["autoIndex"])) {

            $autoIndex = $parameters["autoIndex"];
            unset($parameters["autoIndex"]);

        }

        $query = $parameters["query"];
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

            $where[] = implode(" ", self::buildQuery($column, $value));

        }

        if (count($where) > 0) {

            $parameters[0] = implode(" AND ", $where);

            $parameters["bind"] = self::$bind;

            ksort(self::$keys);

            $parameters["keys"] = self::$keys;

        }

        unset($parameters["query"]);

        return $parameters;
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $value
     * @return array
     */
    public static function buildQuery($column, $value)
    {

        if (count($aliased = explode(".", $column)) > 1) {

            $named_place = $aliased[1];
            $column = sprintf("[%s].[%s]", $aliased[0], $aliased[1]);

        } else if (is_int($column)) {

            $column = "";
            $value["operator"] = Criteria::ADD_OR;

        } else {

            $named_place = $column;
            $column = sprintf("[%s]", $column);

        }

        if (is_array($value)) {

            if (isset($value["operator"])) {

                $operator  = $value["operator"];
                $bindValue = $value["value"];

                switch ($operator) {
                    case $operator === Criteria::IS_NULL:
                    case $operator === Criteria::IS_NOT_NULL:

                        $keys[$named_place] = str_replace(" ", "_", $operator);

                        $query = "";

                        break;

                    case $operator === Criteria::IN:
                    case $operator === Criteria::NOT_IN:

                        $len = count($bindValue);

                        $placeholders = array();
                        for ($i = 0; $i < $len; $i++) {

                            $placeholders[] = sprintf(":%s:", $named_place.$i);

                            self::$bind[$named_place.$i] = $bindValue[$i];

                        }

                        self::$keys[$named_place] =
                            str_replace(" ", "_", $operator)
                                . implode("_", $bindValue);

                        $query = sprintf("(%s)", implode(",", $placeholders));

                        break;

                    case $operator === Criteria::BETWEEN:

                        self::$bind[$named_place."0"] = $bindValue[0];
                        self::$bind[$named_place."1"] = $bindValue[1];

                        self::$keys[$named_place] = $operator . implode("_", $bindValue);

                        $query = sprintf(":%s: AND :%s:", $bindValue[0], $bindValue[1]);

                        break;

                    case $operator === Criteria::ADD_OR:

                        self::$keys[] = $operator;

                        $operator = "";

                        $queryStrings = array();
                        foreach ($value as $col => $val) {

                            $queryStrings[] = implode(" ", self::buildQuery($col, $val));

                        }

                        $query = "(" . implode(" OR ", $queryStrings) . ")";

                        break;

                    default:

                        self::$bind[$named_place] = $bindValue;

                        self::$keys[$named_place] = $operator.$bindValue;

                        $query = sprintf(":%s:", $named_place);

                        break;
                }

            } else {

                $operator = self::IN;

                $placeholders = array();
                $len = count($value);

                for ($i = 0; $i < $len; $i++) {

                    $placeholders[] = sprintf(":%s:", $named_place.$i);

                    self::$bind[$named_place.$i] = $value[$i];

                }

                self::$keys[$named_place] = str_replace(" ", "_", $operator) . implode("_", $value);

                $query = sprintf("(%s)", implode(",", $placeholders));
            }

        } else {

            if ($value === null) {

                $operator = Criteria::ISNULL;

                self::$keys[$named_place] = "IS_NULL";

                $query = "";

            } else if (is_array($value)) {

                $operator = "";

                $queryStrings = array();
                foreach ($value as $col => $val) {

                    $queryStrings[] = implode(" ", self::buildQuery($col, $val));

                }

                $query = "(" . implode(" OR ", $queryStrings) . ")";

            } else {

                $operator = Criteria::EQUAL;

                self::$bind[$named_place] = $value;

                self::$keys[$named_place] = "=".$value;

                $query = sprintf(":%s:", $named_place);

            }

        }

        return array(
            "column" => $column,
            "operator" => $operator,
            "query" => $query
        );
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @throws RedisPluginException
     */
    public static function outputErrorMessage(\Phalcon\Mvc\Model $model)
    {
        $messages = "";
        foreach ($model->getMessages() as $message) {
            $messages .= $message;
        }
        throw new RedisPluginException($messages);
    }

}