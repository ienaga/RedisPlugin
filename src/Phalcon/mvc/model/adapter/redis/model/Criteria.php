<?php

namespace Phalcon\Mvc\Model\Adapter\Redis\Model;

use Phalcon\Mvc\Model\Adapter\Redis\Model;

class Criteria implements CriteriaInterface, OperatorInterface
{

    /**
     * @var array
     */
    protected $conditions = array("query" => array());

    /**
     * @var Model|null
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
     * @var null
     */
    protected $distinct = null;


    /**
     * @param Model $model
     * @param int   $expire
     */
    public function __construct(Model $model = null, $expire = 0)
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
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @param  array $conditions
     * @return Criteria
     */
    public function setConditions($conditions = array()): Criteria
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param  Model $model
     * @return Criteria
     */
    public function setModel(Model $model): Criteria
    {
        $model->initialize();

        $this->model = $model;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * @param  int $expire
     * @return Criteria
     */
    public function setExpire(int $expire = 0): Criteria
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * @return array
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return array
     */
    public function getOr(): array
    {
        return $this->or;
    }

    /**
     * @param  mixed  $value
     * @param  string $operator
     * @return array
     */
    public function queryToArray($value, $operator = self::EQUAL): array
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
     * @return Criteria
     */
    public function add(string $column, $value, $operator = self::EQUAL): Criteria
    {
        $this->conditions["query"][$column] = $this->queryToArray($value, $operator);

        return $this;
    }

    /**
     * @param  string $column
     * @param  array  $values
     * @return Criteria
     */
    public function in(string $column, $values = array()): Criteria
    {
        sort($values);
        return $this->add($column, $values, self::IN);
    }

    /**
     * @param  string $column
     * @param  array  $values
     * @return Criteria
     */
    public function notIn(string $column, $values = array()): Criteria
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
    public function between(string $column, $start, $end): Criteria
    {
        return $this->add($column, array($start, $end), self::BETWEEN);
    }

    /**
     * @param  string $column
     * @return Criteria
     */
    public function isNull(string $column): Criteria
    {
        return $this->add($column, null, self::IS_NULL);
    }

    /**
     * @param  string $column
     * @return Criteria
     */
    public function isNotNull(string $column): Criteria
    {
        return $this->add($column, null, self::IS_NOT_NULL);
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return Criteria
     */
    public function addOr(string $column, $value, $operator = self::EQUAL): Criteria
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
     * @return Criteria
     */
    public function limit(int $limit, $offset = 0): Criteria
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
     * @return Criteria
     */
    public function addOrder(string $value, $sort = self::ASC): Criteria
    {
        $this->orders[] = $value ." ". $sort;

        return $this;
    }

    /**
     * @param  string $value
     * @return Criteria
     */
    public function addGroup(string $value): Criteria
    {
        $this->groups[] = $value;

        return $this;
    }

    /**
     * @param  string $columns
     * @return Criteria
     */
    public function setColumns(string $columns): Criteria
    {
        $this->conditions["columns"] = $columns;

        return $this;
    }

    /**
     * @param  string $column
     * @return Criteria
     */
    public function setDistinct(string $column): Criteria
    {
        $this->distinct = $column;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDistinct()
    {
        return $this->distinct;
    }

    /**
     * @param  bool  $bool
     * @return Criteria
     */
    public function cache($bool = false): Criteria
    {
        $this->conditions["cache"] = $bool;

        return $this;
    }

    /**
     * @param  bool  $bool
     * @return Criteria
     */
    public function autoIndex($bool = false): Criteria
    {
        $this->conditions["autoIndex"] = $bool;

        return $this;
    }

    /**
     * @param  bool  $bool
     * @return Criteria
     */
    public function test($bool = false): Criteria
    {
        $this->conditions["test"] = $bool;

        return $this;
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @return Criteria
     */
    public function set(string $column, $value): Criteria
    {
        if (!isset($this->conditions["update"])) {
            $this->conditions["update"] = array();
        }

        $this->conditions["update"][$column] = $value;
        return $this;
    }

    /**
     * @param  $model
     * @param  null $conditions
     * @param  null $alias
     * @return Criteria
     */
    public function innerJoin($model, $conditions = null, $alias = null): Criteria
    {
        return $this->join($model, $conditions, $alias, self::INNER_JOIN);
    }

    /**
     * @param  $model
     * @param  null $conditions
     * @param  null $alias
     * @return Criteria
     */
    public function leftJoin($model, $conditions = null, $alias = null): Criteria
    {
        return $this->join($model, $conditions, $alias, self::LEFT_JOIN);
    }

    /**
     * @param  $model
     * @param  null $conditions
     * @param  null $alias
     * @return Criteria
     */
    public function rightJoin($model, $conditions = null, $alias = null): Criteria
    {
        return $this->join($model, $conditions, $alias, self::RIGHT_JOIN);
    }

    /**
     * @param  $model
     * @param  null   $conditions
     * @param  null   $alias
     * @param  string $type
     * @return $this
     */
    public function join($model, $conditions = null, $alias = null, $type = self::INNER_JOIN)
    {
//        $this->conditions["query"][$column] = $this->queryToArray($value, $operator);

        return $this;
    }


    /**
     * @return array
     */
    public function buildCondition(): array
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

        // distinct
        if ($this->getDistinct()) {
            if (isset($this->conditions["columns"])) {
                $this->conditions["columns"] .= sprintf(", DISTINCT %s", $this->getDistinct());
            } else {
                $this->conditions["columns"]  = sprintf("DISTINCT %s", $this->getDistinct());
            }
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
        $model->initialize();
        return $model::findFirst($this->buildCondition());
    }

    /**
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function find(): \Phalcon\Mvc\Model\ResultsetInterface
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::find($this->buildCondition());
    }

    /**
     * @param  string $column
     * @return mixed
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function sum(string $column)
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::sum(array_merge($this->buildCondition(), array("column" => $column)));
    }

    /**
     * @return mixed
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function count()
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::count($this->buildCondition());
    }

    /**
     * @return bool
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function update(): bool
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::queryUpdate($this->buildCondition(), $model);
    }

    /**
     * @return bool
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function delete(): bool
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::queryDelete($this->buildCondition(), $model);
    }

    /**
     * @return bool
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function truncate(): bool
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::truncate($model);
    }

    /**
     * @param  string $column
     * @return mixed
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function max(string $column)
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::max(array_merge($this->buildCondition(), array("column" => $column)));
    }

    /**
     * @param string $column
     * @return mixed
     * @throws \Phalcon\Mvc\Model\Adapter\Redis\Exception
     */
    public function min(string $column)
    {
        $model = $this->getModel();
        $model->initialize();
        return $model::min(array_merge($this->buildCondition(), array("column" => $column)));
    }
}