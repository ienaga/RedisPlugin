<?php

namespace RedisPlugin\Mvc\Model;

class Criteria implements CriteriaInterface, OperatorInterface
{

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
     * @var array
     */
    protected $or = array();


    /**
     * @param \Phalcon\Mvc\Model $model
     * @param int                $expire
     */
    public function __construct(\Phalcon\Mvc\Model $model = null, $expire = 0)
    {
        if ($model) {
            $this->setModel($model);
        }
        if ($expire) {
            $this->setExpire($expire);
        }
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
        $model->initialize();

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
     * @return array
     */
    public function getOr()
    {
        return $this->or;
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
        sort($values);
        return $this->add($column, $values, self::IN);
    }

    /**
     * @param  string $column
     * @param  array  $values
     * @return Criteria
     */
    public function notIn($column, $values = array())
    {
        sort($values);
        return $this->add($column, $values, self::NOT_IN);
    }

    /**
     * @param  string $column
     * @param  mixed  $start
     * @param  mixed  $end
     * @return Criteria
     */
    public function between($column, $start, $end)
    {
        return $this->add($column, array($start, $end), self::BETWEEN);
    }

    /**
     * @param  string $column
     * @return Criteria
     */
    public function isNull($column)
    {
        return $this->add($column, null, self::IS_NULL);
    }

    /**
     * @param  string $column
     * @return Criteria
     */
    public function isNotNull($column)
    {
        return $this->add($column, null, self::IS_NOT_NULL);
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function addOr($column, $value, $operator = self::EQUAL)
    {
        if (!isset($this->conditions["query"][0])) {
            $this->conditions["query"][0] = self::ADD_OR;
        }

        $this->or[] = array(
            $column => $this->queryToArray($value, $operator)
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
     * @param  bool  $bool
     * @return $this
     */
    public function cache($bool = false)
    {
        $this->conditions["cache"] = $bool;

        return $this;
    }

    /**
     * @param  bool  $bool
     * @return $this
     */
    public function autoIndex($bool = false)
    {
        $this->conditions["autoIndex"] = $bool;

        return $this;
    }

    /**
     * @param  bool  $bool
     * @return $this
     */
    public function test($bool = false)
    {
        $this->conditions["test"] = $bool;

        return $this;
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @return $this
     */
    public function set($column, $value)
    {
        if (!isset($this->conditions["update"])) {
            $this->conditions["update"] = array();
        }

        $this->conditions["update"][$column] = $value;
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

        // or
        if (count($this->getOr())) {
            $this->conditions["or"] = $this->getOr();
        }

        // expire
        $this->conditions["expire"] = $this->getExpire();

        return $this->conditions;
    }

    /**
     * @return \Phalcon\Mvc\Model | null
     */
    public function findFirst()
    {
        $model = $this->getModel();
        return $model::findFirst($this->buildCondition());
    }

    /**
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public function find()
    {
        $model = $this->getModel();
        return $model::find($this->buildCondition());
    }

    /**
     * @param  string $column
     * @return mixed
     */
    public function sum($column)
    {
        $model = $this->getModel();
        return $model::sum(array_merge($this->buildCondition(), array("column" => $column)));
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $model = $this->getModel();
        return $model::count($this->buildCondition());
    }

    /**
     * @return bool
     */
    public function update()
    {
        $model = $this->getModel();
        return $model::queryUpdate($this->buildCondition(), $model);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $model = $this->getModel();
        return $model::queryDelete($this->buildCondition(), $model);
    }

}