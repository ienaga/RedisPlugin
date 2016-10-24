<?php

namespace RedisPlugin\Mvc\Model;

class Criteria implements CriteriaInterface
{

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
    const OR            = "OR";
    const ASC           = "ASC";
    const DESC          = "DESC";


    /**
     * @var array
     */
    protected $conditions = array("query" => array());

    /**
     * @var \Phalcon\Mvc\Model
     */
    protected $model = null;

    /**
     * @var int
     */
    protected $expire = 0;

    /**
     * @var array
     */
    protected $orders = array();

    /**
     * @var array
     */
    protected $groups = array();


    /**
     * @param \Phalcon\Mvc\Model $model
     * @param int                $expire
     */
    public function __construct($model = null, $expire = 0)
    {
        $this
            ->setModel($model)
            ->setExpire($expire);
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param  array $conditions
     * @return $this
     */
    public function setConditions($conditions = array())
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return \Phalcon\Mvc\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param  \Phalcon\Mvc\Model $model
     * @return $this
     */
    public function setModel(\Phalcon\Mvc\Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @param  int $expire
     * @return $this
     */
    public function setExpire($expire = 0)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * @return array
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param  mixed  $value
     * @param  string $operator
     * @return array
     */
    public function queryToArray($value, $operator = self::EQUAL)
    {
        return array(
            "value"    => $value,
            "operator" => $operator
        );
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function add($column, $value, $operator = self::EQUAL)
    {
        $this->conditions["query"][$column] = $this->queryToArray($value, $operator);

        return $this;
    }

    /**
     * @param  string $column
     * @param  array  $values
     * @return Criteria
     */
    public function in($column, $values = array())
    {
        return $this->add($column, $values, self::IN);
    }

    /**
     * @param  string $column
     * @param  array  $values
     * @return Criteria
     */
    public function notIn($column, $values = array())
    {
        return $this->add($column, $values, self::NOT_IN);
    }

    /**
     * @param  string $column
     * @param  mixed $start
     * @param  mixed $end
     * @return Criteria
     */
    public function between($column, $start, $end)
    {
        return $this->add($column, array($start, $end), self::BETWEEN);
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function addOr($column, $value, $operator = self::EQUAL)
    {
        $this->conditions["query"][] = array(
            "operator" => self::OR,
            $column    => $this->queryToArray($value, $operator)
        );

        return $this;
    }

    /**
     * @param  int $limit
     * @param  int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0)
    {
        $this->conditions["limit"] = array(
            "number" => $limit,
            "offset" => $offset
        );

        return $this;
    }

    /**
     * @param  string $value
     * @param  string $sort
     * @return $this
     */
    public function addOrder($value, $sort = self::ASC)
    {
        $this->orders[] = $value ." ". $sort;

        return $this;
    }

    /**
     * @param  string $value
     * @return $this
     */
    public function addGroup($value)
    {
        $this->groups[] = $value;

        return $this;
    }

    /**
     * @param  bool $bool
     * @return $this
     */
    public function cache($bool = false)
    {
        $this->conditions["cache"] = $bool;

        return $this;
    }

    /**
     * @param  bool $bool
     * @return $this
     */
    public function autoIndex($bool = false)
    {
        $this->conditions["autoIndex"] = $bool;

        return $this;
    }

    /**
     * @return array
     */
    public function buildCondition()
    {
        // order by
        if (count($this->getOrders())) {
            $this->conditions["order"] = join(", ", $this->getOrders());
        }

        // group by
        if (count($this->getGroups())) {
            $this->conditions["group"] = join(", ", $this->getGroups());
        }

        // expire
        $this->conditions["expire"] = $this->getExpire();

        return $this->conditions;
    }

    /**
     * @return \Phalcon\Mvc\Model | bool
     */
    public function findFirst()
    {
        $model = $this->getModel();
        return $model::findFirst($this->buildCondition());
    }

    /**
     * @return \Phalcon\Mvc\Model[] | array
     */
    public function find()
    {
        $model = $this->getModel();
        return Database::find($this->buildCondition(), $this->getModel(), $this->getExpire());
    }

    /**
     * @return bool
     */
    public function update()
    {
        $model = $this->getModel();
        return Database::update($this->buildCondition(), $this->getModel(), $this->getExpire());
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $model = $this->getModel();
        return Database::delete($this->buildCondition(), $this->getModel(), $this->getExpire());
    }
}