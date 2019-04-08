<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

use \Phalcon\Mvc\Model\Adapter\Redis\Model\Criteria;
use \Phalcon\Mvc\Model\Adapter\Redis\Model\OperatorInterface;
use \Phalcon\Db\Adapter\Pdo\Mysql;
use \Redis;

class Model extends \Phalcon\Mvc\Model implements ModelInterface, OperatorInterface
{

    /**
     *  @var string
     */
    const DEFAULT_PREFIX = "all";

    /**
     * @var string
     */
    const DEFAULT_NAME = "db";

    /**
     * @var int
     */
    const DEFAULT_EXPIRE = 3600;

    /**
     * @var bool
     */
    private static $_test = false;

    /**
     * @var null
     */
    private static $_prefix = self::DEFAULT_PREFIX;

    /**
     * @var array
     */
    private static $_keys = array();

    /**
     * @var array
     */
    private static $_bind = array();

    /**
     * @var array
     */
    private static $_cache = array();

    /**
     * @var \Phalcon\Mvc\Model|null
     */
    private static $_current_model = null;

    /**
     * @var array
     */
    private static $_admin_class_cache = array();

    /**
     * @var array
     */
    private static $_config_class_cache = array();

    /**
     * @var null|string
     */
    private static $config_name = null;

    /**
     * initialize
     * @param array $data
     */
    public function initialize($data = array())
    {
        $this->useDynamicUpdate(true);

        // reset
        self::$_prefix = self::DEFAULT_PREFIX;

        // init set data
        if (is_array($data) && count($data)) {
            foreach ($data as $property => $value) {
                if (!property_exists($this, $property)) {
                    continue;
                }

                $this->{$property} = $value;
            }
        }

        // execute model
        self::setCurrentModel($this);

        // mysql connection
        $this->setReadConnectionService(self::getServiceNames());
    }

    /**
     * @param bool $bool
     */
    public static function test(bool $bool = false)
    {
        self::$_test = $bool;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public static function getCurrentModel()
    {
        return self::$_current_model;
    }

    /**
     * @param \Phalcon\Mvc\Model|null $model
     */
    public static function setCurrentModel(\Phalcon\Mvc\Model $model)
    {
        self::$_current_model = $model;
    }

    /**
     * @return Redis
     * @throws Exception
     */
    private static function getRedis(): Redis
    {
        return self::getConnection()->getRedis();
    }

    /**
     * @return Connection
     * @throws Exception
     */
    private static function getConnection(): Connection
    {
        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("server")
            ->get(self::getCurrentModel()->getReadConnectionService())
            ->toArray();

        return Connection::getInstance()->connect($config);
    }

    /**
     * @return Criteria
     */
    public static function criteria(): Criteria
    {
        return new Criteria(new static());
    }

    /**
     * @param  string $field
     * @return mixed
     */
    private static function getLocalCache(string $field)
    {
        // cache key
        $key = self::getCacheKey();

        // init
        if (!isset(self::$_cache[$key])) {
            self::$_cache[$key] = [];
        }

        return isset(self::$_cache[$key][$field])
            ? self::$_cache[$key][$field]
            : null;
    }

    /**
     * @param string $field
     * @param mixed  $value
     */
    private static function setLocalCache(string $field, $value)
    {
        // cache key
        $key = self::getCacheKey();

        // init
        if (!isset(self::$_cache[$key])) {
            self::$_cache[$key] = [];
        }

        self::$_cache[$key][$field] = $value;
    }

    /**
     * @return string
     */
    private static function getCacheKey(): string
    {
        $key  = self::getServiceName();
        $key .= ":". self::getCurrentModel()->getSource();
        if (self::getPrefix()) {
            $key .= ":". self::getPrefix();
        }
        return $key;
    }

    /**
     * @return string
     */
    private static function getServiceName(): string
    {
        $service = self::getCurrentModel()->getReadConnectionService();

        // config
        $c = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("database")
            ->get($service);

        return $c["dbname"] .":". $c["host"] .":". $c["port"];
    }

    /**
     * @param  array $primary_keys
     * @param  array $merge_data
     * @return \Phalcon\Mvc\Model
     */
    public static function findPrimaryKey($primary_keys = array(), $merge_data = array())
    {
        $model      = self::getCurrentModel();
        $attributes = $model->getModelsMetaData()->getPrimaryKeyAttributes($model);

        // set primary keys
        $parameters = array();
        foreach ($attributes as $attribute) {
            if (!isset($primary_keys[$attribute])) {
                continue;
            }

            $parameters[$attribute] = array(
                "value"    => $primary_keys[$attribute],
                "operator" => "="
            );
        }

        // merge
        foreach ($merge_data as $attribute => $value) {
            $parameters[$attribute] = array(
                "value"    => $value,
                "operator" => "="
            );
        }

        return self::findFirst(array("query" => $parameters));
    }

    /**
     * @param  mixed $parameters
     * @return mixed
     * @throws Exception
     */
    public static function sum($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::sum($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // bind to prefix
        self::bindToPrefix($params);

        // field key
        $field  = self::getFieldKey($params, __FUNCTION__);

        return ((self::getRedis()->hExists(self::getCacheKey(), $field)))
            ? self::findRedis($field)
            : self::findDatabase($field, $params, __FUNCTION__);
    }

    /**
     * @param  mixed $parameters
     * @return mixed
     * @throws Exception
     */
    public static function count($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::count($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // bind to prefix
        self::bindToPrefix($params);

        // field key
        $field  = self::getFieldKey($params, __FUNCTION__);

        return ((self::getRedis()->hExists(self::getCacheKey(), $field)))
            ? self::findRedis($field)
            : self::findDatabase($field, $params, __FUNCTION__);
    }

    /**
     * @param  mixed $parameters
     * @return mixed
     * @throws Exception
     */
    public static function max($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::maximum($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // bind to prefix
        self::bindToPrefix($params);

        // field key
        $field  = self::getFieldKey($params, __FUNCTION__);

        return ((self::getRedis()->hExists(self::getCacheKey(), $field)))
            ? self::findRedis($field)
            : self::findDatabase($field, $params, __FUNCTION__);
    }

    /**
     * @param  mixed $parameters
     * @return mixed
     * @throws Exception
     */
    public static function min($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::minimum($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // bind to prefix
        self::bindToPrefix($params);

        // field key
        $field  = self::getFieldKey($params, __FUNCTION__);

        return ((self::getRedis()->hExists(self::getCacheKey(), $field)))
            ? self::findRedis($field)
            : self::findDatabase($field, $params, __FUNCTION__);
    }

    /**
     * @param  mixed $parameters
     * @return null|\Phalcon\Mvc\Model
     */
    public static function findFirst($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::findFirst($parameters);
        }

        $results = self::find($parameters);
        return (isset($results[0])) ? $results[0] : null;
    }

    /**
     * @param  mixed $parameters
     * @return mixed|\Phalcon\Mvc\Model\ResultsetInterface
     * @throws Exception
     */
    public static function find($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::find($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // bind to prefix
        self::bindToPrefix($params);

        // field key
        $field  = self::getFieldKey($params, __FUNCTION__);

        return (self::getRedis()->hExists(self::getCacheKey(), $field))
            ? self::findRedis($field)
            : self::findDatabase($field, $params, __FUNCTION__);
    }

    /**
     * @param  string $field
     * @param  array  $params
     * @param  string $mode
     * @return mixed
     */
    private static function findDatabase(string $field, array $params, string $mode = "find")
    {
        // cache on or off
        $_cache = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("enabled");

        if (isset($params["cache"])) {
            $_cache = $params["cache"];
            unset($params["cache"]);
        }

        $expire = self::DEFAULT_EXPIRE;
        if (isset($params["expire"])) {
            $expire = $params["expire"];
            unset($params["expire"]);
        }

        // execute mode
        $result = null;

        switch ($mode) {
            case "find":
                $result = parent::find($params);
                break;
            case "sum":
                $result = parent::sum($params);
                break;
            case "count":
                $result = parent::count($params);
                break;
            case "max":
                $result = parent::maximum($params);
                break;
            case "min":
                $result = parent::minimum($params);
                break;
        }

        // cache on
        if ($_cache) {
            self::setHash($field, $result, $expire);
        }

        return $result;
    }

    /**
     * @param  string $field
     * @return mixed|string
     * @throws Exception
     */
    private static function findRedis(string $field)
    {
        // local cache
        $_cache = self::getLocalCache($field);

        // redis
        if (!$_cache) {
            $_cache = self::getRedis()->hGet(self::getCacheKey(), $field);
            self::setLocalCache($field, $_cache);
        }

        return $_cache;
    }

    /**
     * @param  string $field
     * @param  mixed  $value
     * @param  int    $expire
     * @throws Exception
     */
    private static function setHash(string $field, $value, int $expire = 0)
    {
        // cache key
        $key = self::getCacheKey();

        // set redis
        $redis = self::getRedis();
        $redis->hSet($key, $field, $value);

        // local cache
        self::setLocalCache($field, $value);

        // EXPIRE
        $expire = (!$expire) ? self::DEFAULT_EXPIRE : $expire;
        if ($expire > 0 && !self::getConnection()->isTimeout($key)) {
            $redis->setTimeout($key, $expire);
        }
    }

    /**
     * @param  array  $params
     * @param  string $mode
     * @return string
     */
    private static function getFieldKey(array $params, string $mode = "find"): string
    {
        // field key
        $field  = $mode. "@";
        $field .= self::buildFieldKey($params);
        if (isset($params["keys"])) {
            unset($params["keys"]);
        }
        return $field;
    }

    /**
     * @param  array $parameters
     * @return string
     */
    private static function buildFieldKey(array $parameters): string
    {
        // base
        $key = self::DEFAULT_PREFIX;
        if (isset($parameters["keys"])) {
            $key = self::buildBaseKey($parameters["keys"]);
        }

        $addKeys = array();

        // sum, max, min
        if (isset($parameters["column"])) {

            $addKeys[] = "column";

            $fields = explode(",", $parameters["column"]);

            foreach ($fields as $field) {

                $addKeys[] = trim($field);

            }
        }

        // order by
        if (isset($parameters["order"])) {

            $addKeys[] = "order";

            $fields = explode(",", $parameters["order"]);

            foreach ($fields as $field) {

                $field  = trim($field);

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

        if (isset($parameters["columns"])) {

            $addKeys[] = "columns";

            $addKeys[] = str_replace(" ", "_", str_replace(",", "_", $parameters["columns"]));

        }

        if ($addKeys) {

            $key .= "_" . implode("_", $addKeys);

        }

        return $key;
    }

    /**
     * @param  array  $_keys
     * @return string
     */
    private static function buildBaseKey($_keys = array()): string
    {
        $array = array();

        if (count($_keys) > 0) {
            foreach ($_keys as $key => $value) {

                if (is_array($key)) {

                    foreach ($key as $col => $val) {

                        $array[] = $col . $val;

                    }

                    continue;
                }

                $array[] = $key . $value;
            }
        }

        return implode("_", $array);
    }

    /**
     * @param  array $parameters
     * @return array
     * @throws Exception
     */
    public static function buildParameters($parameters): array
    {
        // init
        $indexQuery  = array();
        $where       = array();
        self::$_keys  = array();
        self::$_bind  = isset($parameters["bind"]) ? $parameters["bind"] : array();

        // 設定確認・個別確認
        $autoIndex = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("autoIndex");

        if (isset($parameters["autoIndex"])) {
            $autoIndex = $parameters["autoIndex"];
            unset($parameters["autoIndex"]);
        }

        $query = $parameters["query"];
        if (count($query) && $autoIndex) {

            $indexes = self::getIndexes();

            if ($indexes) {

                $test = self::$_test;
                if (isset($parameters["test"])) {
                    $test = $parameters["test"];
                    unset($parameters["test"]);
                }

                // 一番マッチするindexにあわせてクエリを発行(PRIMARY優先)
                foreach ($indexes as $key => $index) {

                    $columns = $index->getColumns();

                    $chkQuery = array();
                    foreach ($columns as $column) {

                        if (!isset($query[$column])) {
                            break;
                        }

                        $chkQuery[$column] = $query[$column];
                    }

                    if (count($chkQuery) > count($indexQuery)) {
                        $indexQuery = $chkQuery;
                    }

                    // PRIMARY優先
                    if ($key === "PRIMARY" && count($chkQuery)) {
                        break;
                    }
                }

                if ($test && !count($indexQuery)) {
                    throw new Exception("index not match.");
                }
            }

            $query = array_merge($indexQuery, $query);
        }

        // クエリを発行
        foreach ($query as $column => $value) {

            if ($value === Criteria::ADD_OR) {
                $value = $parameters["or"];
            }

            $where[] = implode(" ", self::buildQuery($column, $value));

        }

        if (count($where) > 0) {

            $parameters[0] = implode(" AND ", $where);

            $parameters["bind"] = self::$_bind;

            ksort(self::$_keys);

            $parameters["keys"] = self::$_keys;

        }

        unset($parameters["query"]);

        if (isset($parameters["or"])) {
            unset($parameters["or"]);
        }

        return $parameters;
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $value
     * @return array
     */
    private static function buildQuery($column, $value): array
    {

        if (count($aliased = explode(".", $column)) > 1) {

            $named_place = $aliased[1];
            $column      = sprintf("[%s].[%s]", $aliased[0], $aliased[1]);

        } else if (is_int($column)) {

            $column = null;

        } else {

            $named_place = $column;
            $column      = sprintf("[%s]", $column);

        }

        if (is_array($value) && $column) {

            if (isset($value["operator"])) {

                $operator   = $value["operator"];
                $_bindValue = $value["value"];

                switch ($operator) {
                    case $operator === Criteria::IS_NULL:
                    case $operator === Criteria::IS_NOT_NULL:

                        self::$_keys[$named_place] = str_replace(" ", "_", $operator);

                        $query = "";

                        break;

                    case $operator === Criteria::IN:
                    case $operator === Criteria::NOT_IN:

                        $len = count($_bindValue);

                        $placeholders = array();
                        for ($i = 0; $i < $len; $i++) {

                            $placeholders[] = sprintf(":%s:", $named_place.$i);

                            self::$_bind[$named_place.$i] = $_bindValue[$i];

                        }

                        self::$_keys[$named_place] =
                            str_replace(" ", "_", $operator)
                            . implode("_", $_bindValue);

                        $query = sprintf("(%s)", implode(",", $placeholders));

                        break;

                    case $operator === Criteria::BETWEEN:

                        $start = $named_place."0";
                        $end   = $named_place."1";

                        self::$_bind[$start] = $_bindValue[0];
                        self::$_bind[$end] = $_bindValue[1];

                        self::$_keys[$named_place] = $operator
                            . implode("_", str_replace(" ", "_", $_bindValue));

                        $query = sprintf(":%s: AND :%s:", $start, $end);

                        break;

                    default:

                        self::$_bind[$named_place] = $_bindValue;

                        self::$_keys[$named_place] = $operator.str_replace(" ", "_", $_bindValue);

                        $query = sprintf(":%s:", $named_place);

                        break;
                }

            } else {

                $operator = self::IN;

                $placeholders = array();
                $len = count($value);

                for ($i = 0; $i < $len; $i++) {

                    $placeholders[] = sprintf(":%s:", $named_place.$i);

                    self::$_bind[$named_place.$i] = $value[$i];

                }

                self::$_keys[$named_place] = str_replace(" ", "_", $operator) . implode("_", $value);

                $query = sprintf("(%s)", implode(",", $placeholders));
            }

        } else {

            if ($value === null) {

                $operator = Criteria::IS_NULL;

                self::$_keys[$named_place] = "IS_NULL";

                $query = "";

            } else if ($column === null) {

                $operator = "";

                $queryStrings = array();
                foreach ($value as $conditions) {

                    $map = each($conditions);

                    $queryStrings[] = implode(" ", self::buildQuery($map["key"], $map["value"]));

                }

                $query = "(" . implode(" OR ", $queryStrings) . ")";

            } else {

                $operator = Criteria::EQUAL;

                self::$_bind[$named_place] = $value;

                self::$_keys[$named_place] = "=". str_replace(" ", "_", $value);

                $query = sprintf(":%s:", $named_place);

            }

        }

        return array(
            "column"   => $column,
            "operator" => $operator,
            "query"    => $query
        );
    }

    /**
     * @param  \Phalcon\Mvc\Model|null $model
     * @return mixed
     */
    private static function getIndexes(\Phalcon\Mvc\Model $model = null)
    {
        /** @var \Phalcon\Mvc\Model $model */
        $model   = ($model) ? : self::getCurrentModel();
        $indexes = $model->getModelsMetaData()->readIndexes($model->getSource());
        if (!$indexes) {

            $databases = \Phalcon\DI::getDefault()
                ->get("config")
                ->get("database")
                ->toArray();

            foreach ($databases as $db => $config) {

                if (!isset($config["dbname"])) {
                    continue;
                }

                if (!isset($config["transaction"]) || !$config["transaction"]) {
                    continue;
                }

                try{

                    $connection = new Mysql([
                        "host"     => $config["host"],
                        "username" => $config["username"],
                        "password" => $config["password"],
                        "dbname"   => $config["dbname"],
                        "port"     => $config["port"],
                        "charset"  => $config["charset"]
                    ]);

                } catch (Exception $e) {
                    continue;
                }

                $tables = $connection->fetchAll("SHOW TABLES");

                $targetTable = null;
                foreach ($tables as $table) {
                    $values = each($table);
                    if ($model->getSource() !== $values["value"]) {
                        continue;
                    }

                    // execute cache
                    $model->setReadConnectionService($db);
                    $model->getModelsMetaData()->writeIndexes();
                    $indexes = $model->getModelsMetaData()->readIndexes($model->getSource());

                    break;
                }

                if ($indexes) {
                    break;
                }
            }
        }

        return $indexes;
    }

    /**
     * @return string
     */
    private static function getPrefix(): string
    {
        return self::$_prefix;
    }

    /**
     * @param  null|array $_keys
     * @throws Exception
     */
    private static function setPrefix($_keys = null)
    {
        self::$_prefix = null;

        $columns = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("prefix")
            ->get("columns");

        if (!$columns) {
            throw new Exception("not found prefix columns");
        }

        $model = self::getCurrentModel();
        foreach ($columns as $values) {

            if (is_string($values)) {
                $values = [$values];
            }

            $matches = [];
            foreach ($values as $column) {
                $property = trim($column);

                if ($_keys) {

                    if (!isset($_keys[$property])) {
                        continue;
                    }

                    $matches[] = $_keys[$property];

                } else {
                    if (!property_exists($model, $property)) {
                        continue;
                    }

                    $matches[] = $model->{$property};

                }
            }

            // match case
            if (count($matches) === count($values)) {

                self::$_prefix = implode(":", $matches);

                break;
            }
        }

        if (!self::$_prefix) {
            self::$_prefix = self::DEFAULT_PREFIX;
        }
    }

    /**
     * @param  array $params
     * @throws Exception
     */
    private static function bindToPrefix($params)
    {
        self::$_prefix = self::DEFAULT_PREFIX;
        if (isset($params["bind"])) {
            self::setPrefix($params["bind"]);
            self::getCurrentModel()->setReadConnectionService(self::getServiceNames());
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    private static function getShardServiceName(): string
    {
        $mode = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("shard")
            ->get("enabled");

        $prefix = self::getPrefix();
        if ($prefix === self::DEFAULT_PREFIX) {
            self::setPrefix();
            $prefix = self::getPrefix();
        }

        if ($mode && $prefix && $prefix !== self::DEFAULT_PREFIX) {

            $adminClass = self::getAdminClass($prefix);
            if ($adminClass) {

                $column = \Phalcon\DI::getDefault()
                    ->get("config")
                    ->get("redis")
                    ->get("admin")
                    ->get("column");

                if (property_exists($adminClass, $column)) {
                    return self::getAdminConfigName($adminClass->{$column});
                }
            }
        }

        return self::DEFAULT_NAME;
    }

    /**
     * @param  mixed $primary_key
     * @return string
     * @throws Exception
     */
    private static function getAdminConfigName($primary_key): string
    {
        // local cache
        if (isset(self::$_config_class_cache[$primary_key])) {
            return self::$_config_class_cache[$primary_key];
        }

        // local cache
        $_prefix        = self::getPrefix();
        $_current_model = self::getCurrentModel();

        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("admin")
            ->get("control");

        $class   = $config->get("model");
        $indexes = self::getIndexes(new $class);

        $primary = "id";
        if (isset($indexes["PRIMARY"])) {
            $primary = $indexes["PRIMARY"]->getColumns()[0];
        }

        // config
        $configClass = $class::criteria()
            ->add($primary, $primary_key)
            ->findFirst();

        if (!$configClass) {
            throw new Exception(
                sprintf("Not Found Admin Config: %s : %s", $primary, $primary_key)
            );
        }

        // local cache
        self::$_config_class_cache[$primary_key] = ($configClass)
            ? $configClass->{$config->get("column")}
            : self::DEFAULT_NAME;

        // reset
        self::$_prefix = $_prefix;
        self::setCurrentModel($_current_model);

        return self::$_config_class_cache[$primary_key];
    }

    /**
     * @param  mixed $_prefix
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    private static function getAdminClass($_prefix)
    {
        // local cache
        if (isset(self::$_admin_class_cache[$_prefix])) {
            return self::$_admin_class_cache[$_prefix];
        }

        // local cache
        $_prefix        = self::getPrefix();
        $_current_model = self::getCurrentModel();

        $class = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("admin")
            ->get("model");

        $indexes = self::getIndexes(new $class);

        $primary = "id";
        if (isset($indexes["PRIMARY"])) {
            $primary = $indexes["PRIMARY"]->getColumns()[0];
        }

        $adminClass = $class::criteria()
            ->add($primary, $_prefix)
            ->findFirst();

        if (!$adminClass) {
            throw new Exception("Not Created Admin Member: Prefix: ". $_prefix);
        }

        self::$_admin_class_cache[$_prefix] = $adminClass;

        // reset
        self::$_prefix = $_prefix;
        self::setCurrentModel($_current_model);

        return $adminClass;
    }

    /**
     * @param  null               $parameters
     * @param  \Phalcon\Mvc\Model $model
     * @return \Phalcon\Mvc\Model\QueryInterface
     * @throws Exception
     */
    public static function queryUpdate($parameters = null, \Phalcon\Mvc\Model $model)
    {
        if (!is_array($parameters) || !isset($parameters["query"])) {
            throw new Exception("parameters array only.");
        }

        // initialize
        $model->initialize();

        // build parameters
        $params = self::buildParameters($parameters);

        // replace
        $where = 1;
        if (isset($params[0])) {
            $where = $params[0];
            $where = str_replace("[", "`", $where);
            $where = str_replace("]", "`", $where);

            // bind
            $bind  = $params["bind"];
            foreach ($bind as $column => $value) {
                $where = str_replace(":". $column .":", $value, $where);
            }
        }

        // update
        $update = $params["update"];
        $sets   = [];
        foreach ($update as $column => $value) {
            if (is_string($value)) {
                $value = "\"". $value ."\"";
            } else if ($value === null) {
                $value = "NULL";
            }

            $sets[] = $column ." = ". $value;
        }
        $set = implode(", ", $sets);

        // execute
        $service = $model->getReadConnectionService();
        $adapter = \Phalcon\DI::getDefault()->getShared($service);
        $sql     = sprintf("UPDATE %s SET %s WHERE %s", $model->getSource(), $set, $where);
        $result  = $adapter->execute($sql);

        // cache delete
        self::cacheAllDelete($model);

        return $result;
    }

    /**
     * @param null $parameters
     * @param \Phalcon\Mvc\Model $model
     * @return mixed
     * @throws Exception
     */
    public static function queryDelete($parameters = null, \Phalcon\Mvc\Model $model)
    {
        if (!is_array($parameters) || !isset($parameters["query"])) {
            throw new Exception("parameters array only.");
        }

        // initialize
        $model->initialize();

        // build parameters
        $params = self::buildParameters($parameters);

        // replace
        $where = 1;
        if (isset($params[0])) {
            $where = $params[0];
            $where = str_replace("[", "", $where);
            $where = str_replace("]", "", $where);

            // bind
            $bind  = $params["bind"];
            foreach ($bind as $column => $value) {
                $where = str_replace(":". $column .":", $value, $where);
            }
        }

        // execute
        $service = $model->getReadConnectionService();
        $adapter = \Phalcon\DI::getDefault()->getShared($service);
        $sql     = sprintf("DELETE FROM %s WHERE %s", $model->getSource(), $where);
        $result  = $adapter->execute($sql);

        // cache delete
        self::cacheAllDelete($model);

        return $result;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @return mixed
     * @throws Exception
     */
    public static function truncate(\Phalcon\Mvc\Model $model)
    {
        // initialize
        $model->initialize();

        // execute
        $service = $model->getReadConnectionService();
        $adapter = \Phalcon\DI::getDefault()->getShared($service);
        $sql     = sprintf("TRUNCATE %s", $model->getSource());
        $result  = $adapter->execute($sql);

        // cache delete
        self::cacheAllDelete($model);

        return $result;
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @throws Exception
     */
    private static function cacheAllDelete(\Phalcon\Mvc\Model $model)
    {
        // cache all delete
        $databases = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("database");

        foreach ($databases as $db => $arguments) {

            $key  = trim(Database::getCacheKey($model, $arguments, " "));
            $key .= "*";
            $keys = Database::getRedis($db)->keys($key);
            foreach ($keys as $cKey) {
                Database::getRedis($db)->delete($cKey);
            }

        }

        self::localCacheClear();
    }

    /**
     * @param mixed $config_name
     */
    public static function setServiceName($config_name)
    {
        self::$config_name = $config_name;
    }

    /**
     * @return string
     * @throws Exception
     */
    private static function getServiceNames(): string
    {
        switch (true) {
            case self::isCommon():
                $configName = self::getCommonServiceName();
                break;
            case self::isAdmin():
                $configName = self::getAdminServiceName();
                break;
            default:
                $configName = (self::$config_name === null)
                    ? self::getShardServiceName()
                    : self::$config_name;
                break;
        }

        return (Database::isTransaction() || Database::useMasterConnection())
            ? $configName ."Master"
            : $configName ."Slave";
    }

    /**
     * @return bool
     */
    private static function isCommon(): bool
    {
        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("common");

        $enabled = $config->get("enabled");
        if (!$enabled) {
            return false;
        }

        $dbs = $config->get("dbs")->toArray();
        if (!$dbs || !is_array($dbs)) {
            return false;
        }

        return self::isMatch($dbs);
    }

    /**
     * @return bool
     */
    private static function isAdmin(): bool
    {
        $config = \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis");

        $enabled = $config->get("shard")->get("enabled");
        if (!$enabled) {
            return false;
        }

        $dbs = $config->get("admin")->get("dbs")->toArray();
        if (!$dbs || !is_array($dbs)) {
            return false;
        }

        return self::isMatch($dbs);
    }

    /**
     * @param  array $databases
     * @return bool
     */
    private static function isMatch($databases = array()): bool
    {
        $source = self::getCurrentModel()->getSource();
        foreach ($databases as $name) {
            $name = trim($name);
            if (substr($source, 0, strlen($name)) !== $name) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public static function getCommonServiceName(): string
    {
        return \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("common")
            ->get("service")
            ->get("name");
    }

    /**
     * @return string
     */
    public static function getAdminServiceName(): string
    {
        return \Phalcon\DI::getDefault()
            ->get("config")
            ->get("redis")
            ->get("admin")
            ->get("service")
            ->get("name");
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     * @throws Exception
     */
    public function save($data = null, $whiteList = null): bool
    {
        // pre
        $this->_pre($data);

        // execute
        if (!parent::save($data, $whiteList)) {
            Database::outputErrorMessage($this);
            return false;
        }

        // post
        $this->_post();

        return true;
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     * @throws Exception
     */
    public function create($data = null, $whiteList = null): bool
    {
        return $this->save($data, $whiteList);
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     * @throws Exception
     */
    public function update($data = null, $whiteList = null): bool
    {
        return $this->save($data, $whiteList);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        // pre
        $this->_pre();

        if (!parent::delete()) {
            Database::outputErrorMessage($this);
            return false;
        }

        // post
        $this->_post();

        return true;
    }

    /**
     * @param array $data
     */
    private function _pre($data = array())
    {
        $this->initialize($data);
        $this->setTransaction(Database::getTransaction($this));
    }

    /**
     * @throws Exception
     */
    private function _post()
    {
        Database::addModel($this);

        // case: admin_user first insert
        $enabled = $this->getDI()
            ->get("config")
            ->get("redis")
            ->get("shard")
            ->get("enabled");

        if ($enabled) {
            $className = $this->getDI()
                ->get("config")
                ->get("redis")
                ->get("admin")
                ->get("model");

            if (get_class($this) === $className ||
                "\\". get_class($this) === $className
            ) {
                $column = $this->getDI()
                    ->get("config")
                    ->get("redis")
                    ->get("admin")
                    ->get("column");

                $this::setServiceName(self::getAdminConfigName($this->{$column}));
            }
        }
    }

    /**
     * local cache clear
     */
    public static function localCacheClear()
    {
        self::$config_name         = null;
        self::$_prefix             = null;
        self::$_keys               = array();
        self::$_bind               = array();
        self::$_cache              = array();
        self::$_current_model      = null;
        self::$_admin_class_cache  = array();
        self::$_config_class_cache = array();
    }

    /**
     * @param  null|\Phalcon\Mvc\Model $caller
     * @param  array $options
     * @return array
     */
    public function toViewArray($caller = null, $options = array()): array
    {

        $ignore = [];
        if (isset($options["ignore"])) {
            $ignore = $options["ignore"];
        }

        $obj = [];

        $attributes = $this->getModelsMetaData()->getAttributes($this);

        foreach ($attributes as $attribute) {

            if (in_array($attribute, $ignore)) {
                continue;
            }

            if (!property_exists($this, $attribute)) {
                continue;
            }

            $obj[$attribute] = $this->{$attribute};
        }

        return $obj;
    }
}
