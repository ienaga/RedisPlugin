<?php

namespace RedisPlugin\Mvc;

use RedisPlugin\Connection;
use RedisPlugin\Database;
use RedisPlugin\Mvc\Model\Criteria;
use RedisPlugin\Exception\RedisPluginException;


class Model extends \Phalcon\Mvc\Model
{

    /**
     * @var string
     */
    const DEFAULT_NAME = "db";

    /**
     * @var int
     */
    const DEFAULT_EXPIRE = 3600;

    /** operator list */
    const EQUAL         = "=";
    const NOT_EQUAL     = "<>";
    const GREATER_THAN  = ">";
    const LESS_THAN     = "<";
    const GREATER_EQUAL = ">=";
    const LESS_EQUAL    = "<=";
    const IS_NULL       = "IS NULL";
    const IS_NOT_NULL   = "IS NOT NULL";
    const LIKE          = "LIKE";
    const I_LIKE        = "ILIKE";
    const IN            = "IN";
    const NOT_IN        = "NOT IN";
    const BETWEEN       = "BETWEEN";
    const ADD_OR        = "OR";
    const ASC           = "ASC";
    const DESC          = "DESC";

    /**
     * @var null
     */
    private static $prefix = null;

    /**
     * @var array
     */
    private static $keys = array();

    /**
     * @var array
     */
    private static $bind = array();

    /**
     * @var array
     */
    private static $cache = array();

    /**
     * @var array
     */
    private static $stack = array();

    /**
     * @var array
     */
    private static $_admin_query = array();

    /**
     * @var array
     */
    private static $_config_query = array();



    /**
     * initialize
     */
    public function initialize()
    {
        // mysql connection
        $this->setReadConnectionService($this->getServiceNames());

        // stack model
        self::$stack[] = $this;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public static function getCurrentModel()
    {
        return end(self::$stack);
    }

    /**
     * redisを取得
     * @return |Redis
     */
    private static function getRedis()
    {
        return self::getConnection()->getRedis();
    }

    /**
     * @return Connection
     */
    private static function getConnection()
    {
        $config = self::getConfig()
            ->get("server")
            ->get(self::getCurrentModel()->getReadConnectionService())
            ->toArray();

        return Connection::getInstance()->connect($config);
    }

    /**
     * @return Criteria
     */
    public static function criteria()
    {
        return new Criteria(new static());
    }

    /**
     * @param  string $field
     * @return mixed
     */
    private static function getLocalCache($field)
    {
        // cache key
        $key = self::getCacheKey();

        // init
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
        }

        return isset(self::$cache[$key][$field])
            ? self::$cache[$key][$field]
            : null;
    }

    /**
     * @param string $field
     * @param mixed  $value
     */
    private static function setLocalCache($field, $value)
    {
        // cache key
        $key = self::getCacheKey();

        // init
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
        }

        self::$cache[$key][$field] = $value;
    }

    /**
     * @return string
     */
    private static function getCacheKey()
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
    private static function getServiceName()
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
     * @param  null|string|array $parameters
     * @return \Phalcon\Mvc\Model
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
     * @param  null|string|array $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public static function find($parameters = null)
    {
        // parent
        if (!is_array($parameters) || !isset($parameters["query"])) {
            return parent::find($parameters);
        }

        // params
        $params = self::buildParameters($parameters);

        // prefix
        self::setPrefix($params["bind"]);

        // field key
        $field = self::buildFieldKey($parameters);
        unset($parameters["keys"]);

        // redis
        $result = self::findRedis($field);

        // database
        if (!$result) {
            // cache on or off
            $cache = self::getConfig()->get("enabled");
            if (isset($parameters["cache"])) {
                $cache = $parameters["cache"];
                unset($parameters["cache"]);
            }

            $result = parent::find($params);
            if (!$result) {
                $result = array();
            }

            // cache on
            if ($cache) {
                self::setHash($field, $result, $params["expire"]);
            }
        }

        // delete
        array_pop(self::$stack);

        return $result;
    }

    /**
     * @param  string $field
     * @return mixed
     */
    private static function findRedis($field)
    {
        // local cache
        $cache = self::getLocalCache($field);

        // redis
        if (!$cache) {
            $cache = self::getRedis()->hGet(self::getCacheKey(), $field);
            self::setLocalCache($field, $cache);
        }

        return $cache;
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param int    $expire
     */
    private static function setHash($field, $value, $expire = 0)
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
     * @param  array $parameters
     * @return string
     */
    private static function buildFieldKey($parameters)
    {
        // base
        $key = self::buildBaseKey($parameters["keys"]);

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
     * @param  array  $keys
     * @return string
     */
    private static function buildBaseKey($keys = array())
    {
        $array = array();

        if (count($keys) > 0) {
            foreach ($keys as $key => $value) {

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
     */
    private static function buildParameters($parameters)
    {
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

            $indexes = self::getIndexes();

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
    private static function buildQuery($column, $value)
    {
        if (count($aliased = explode(".", $column)) > 1) {

            $named_place = $aliased[1];
            $column = sprintf("[%s].[%s]", $aliased[0], $aliased[1]);

        } else if (is_int($column)) {

            $column = "";
            $value["operator"] = Criteria::OR;

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

                    case $operator === Criteria::OR:

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
     * @param  \Phalcon\Mvc\Model|null $model
     * @return mixed
     */
    private static function getIndexes(\Phalcon\Mvc\Model $model = null)
    {
        /** @var \Phalcon\Mvc\Model $model */
        $model = ($model) ? : self::getCurrentModel();
        return $model->getModelsMetaData()->readIndexes($model->getSource());
    }

    /**
     * @return string
     */
    private static function getPrefix()
    {
        return self::$prefix;
    }

    /**
     * @param  null|array $keys
     * @throws RedisPluginException
     */
    private static function setPrefix($keys = null)
    {
        self::$prefix = null;

        $columns = self::getConfig()->get("prefix")->get("columns");
        if (!$columns) {
            throw new RedisPluginException("not found prefix columns");
        }

        $model = null;
        if (!$keys) {
            /** @var \Phalcon\Mvc\Model $model */
            $model = new static();
        }

        foreach ($columns as $column) {

            $property = trim($column);

            if ($keys) {

                if (!isset($keys[$property])) {
                    continue;
                }

                self::$prefix = $keys[$property];

            } else {

                if (!property_exists($model, $property)) {
                    continue;
                }

                self::$prefix = $model->{$property};

            }

            break;
        }
    }

    /**
     * @return string
     */
    public function getShardServiceName()
    {
        $mode   = self::getConfig()->get("shard")->get("enabled");
        $prefix = self::getPrefix();

        if ($mode && $prefix) {

            if (!isset(self::$admin_cache[$prefix])) {

                self::$admin_cache[$prefix] = self::getAdminMember($prefix);

            }

            $adminMember = self::$admin_cache[$prefix];
            if ($adminMember) {

                $column = self::getConfig()->get("admin")->get("column");
                return self::getMemberConfigName($adminMember->{$column});

            }
        }

        return self::DEFAULT_NAME;
    }

    /**
     * @param  mixed $primary_key
     * @return string
     */
    public function getMemberConfigName($primary_key)
    {
        // local cache
        $_prefix = self::getPrefix();

        $config = self::getConfig()
            ->get("shard")
            ->get("control");

        $class = $config->get("model");
        if (!isset(self::$_config_query["query"])) {

            $primary = "id";
            $indexes = self::getIndexes(new $class);

            if (isset($indexes["PRIMARY"])) {

                $primary = $indexes["PRIMARY"]->getColumns()[0];

            }

            self::$_config_query = array(
                "query" => array($primary => $primary_key)
            );

        }

        $dbConfig = $class::findFirst(self::$_config_query);

        // reset
        self::setPrefix($_prefix);

        return ($dbConfig)
            ? $dbConfig->{$config->get("column")}
            : self::DEFAULT_NAME;
    }

    /**
     * @param  mixed $prefix
     * @return \Phalcon\Mvc\Model
     * @throws RedisPluginException
     */
    public function getAdminMember($prefix)
    {
        // local cache
        $_prefix = self::getPrefix();

        $class = self::getConfig()->get("admin")->get("model");

        if (!isset(self::$_admin_query["query"])) {

            $primary = "id";
            $indexes = self::getIndexes(new $class);

            if (isset($indexes["PRIMARY"])) {

                $primary = $indexes["PRIMARY"]->getColumns()[0];

            }

            self::$_admin_query = array("query" => array($primary => $prefix));

        }

        $adminMember = $class::findFirst(self::$_admin_query);
        if (!$adminMember) {
            throw new RedisPluginException("Not Created Admin Member");
        }

        // reset
        self::setPrefix($_prefix);

        return $adminMember;
    }

    public static function queryDelete($parameters = null)
    {

    }

    public static function queryUpdate($parameters = null)
    {

    }

    /**
     * @return string
     */
    public function getServiceNames()
    {
        switch (true) {
            case $this->isCommon():
                $configName = $this->getCommonServiceName();
                break;
            case $this->isAdmin():
                $configName = $this->getAdminServiceName();
                break;
            default:
                $configName = "db";
                break;
        }

        $slaveName  = $configName;
        $slaveName .= (Database::isTransaction()) ? "Master" : "Slave";
        return $slaveName;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->getDI()
            ->get("config")
            ->get("redis");
    }

    /**
     * @return bool
     */
    public function isCommon()
    {
        $config = self::getConfig()->get("common");

        $enabled = $config->get("enabled");
        if (!$enabled) {
            return false;
        }

        $dbs = $config->get("dbs")->toArray();
        if (!$dbs || !is_array($dbs)) {
            return false;
        }

        return $this->isMatch($dbs);
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        $config  = self::getConfig();

        $enabled = $config->get("shard")->get("enabled");
        if (!$enabled) {
            return false;
        }

        $dbs = $config->get("admin")->get("dbs")->toArray();
        if (!$dbs || !is_array($dbs)) {
            return false;
        }

        return $this->isMatch($dbs);
    }

    /**
     * @param  array $databases
     * @return bool
     */
    public function isMatch($databases = array())
    {
        $source = $this->getSource();
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
    public function getCommonServiceName()
    {
        return $this->getConfig()
            ->get("common")
            ->get("service")
            ->get("name");
    }

    /**
     * @return string
     */
    public function getAdminServiceName()
    {
        return self::getConfig()
            ->get("admin")
            ->get("service")
            ->get("name");
    }


    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null)
    {
        // pre
        $this->initialize();
        $this->setTransaction(Database::getTransaction());

        // execute
        if (!parent::save($data, $whiteList)) {
            Database::outputErrorMessage($this);
            return false;
        }

        Database::addModel($this);

        return true;
    }

    /**
     * @param null $data
     * @param null $whiteList
     * @return bool
     */
    public function create($data = null, $whiteList = null)
    {
        return $this->save($data, $whiteList);
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function update($data = null, $whiteList = null)
    {
        return $this->save($data, $whiteList);
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function delete($data = null, $whiteList = null)
    {
        Database::preExecute($this);

        if (!parent::delete($data, $whiteList)) {
            Database::outputErrorMessage($this);
            return false;
        }

        Database::postExecute($this);

        return true;
    }

    /**
     * @param  null|\Phalcon\Mvc\Model $caller
     * @param  array $options
     * @return array
     */
    public function toViewArray($caller = null, $options = array())
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

            $method = "get" . ucfirst(str_replace(" ", "", ucwords(str_replace("_", " ", $attribute))));

            if (!method_exists($this, $method)) {
                continue;
            }

            $obj[$attribute] = call_user_func([$this, $method]);
        }

        return $obj;
    }
}