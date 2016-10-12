<?php


namespace RedisPlugin;


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
    const ADD_OR        = "OR";
    const ASC           = "ASC";
    const DESC          = "DESC";


    /**
     * @var array
     */
    protected $conditions = array("query" => array());

    /**
     * @var array
     */
    protected $order = array();

    /**
     * @var array
     */
    protected $group = array();

    /**
     * @var null
     */
    protected $model = null;

    /**
     * @var int
     */
    protected $expire = 0;


    /**
     * @param \Phalcon\Mvc\Model $model
     * @param int $expire
     */
    public function __construct($model = null, $expire = 0)
    {
        $this
            ->setModel($model)
            ->setExpire($expire);
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function add($column, $value, $operator = self::EQUAL)
    {
        $this->conditions["query"][$column] =
            $this->buildArray($value, $operator);

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
            "operator" => self::ADD_OR,
            $column    => $this->buildArray($value, $operator)
        );

        return $this;
    }

    /**
     * @param  mixed  $value
     * @param  string $operator
     * @return array
     */
    protected function buildArray($value, $operator = self::EQUAL)
    {
        return array(
            "operator" => $operator,
            "value"    => $value
        );
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
    public function order($value, $sort = self::ASC)
    {
        $this->order[] = $value ." ". $sort;

        return $this;
    }

    /**
     * @param  array|string $value
     * @return $this
     */
    public function group($value)
    {
        $this->group[] = $value;

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
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param  array $condition
     * @return $this
     */
    public function setConditions($condition = array())
    {
        $this->conditions = $condition;

        return $this;
    }

    /**
     * @return array
     */
    public function buildCondition()
    {
        // order by
        if (count($this->order)) {
            $this->conditions["order"] = join(", ", $this->order);
        }

        // group by
        if (count($this->group)) {
            $this->conditions["group"] = join(", ", $this->group);
        }

        return $this->conditions;
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
     * @return \Phalcon\Mvc\Model
     */
    public function findFirst()
    {
        return Database::findFirst($this->buildCondition(), $this->getModel(), $this->getExpire());
    }

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    public function find()
    {
        return Database::find($this->buildCondition(), $this->getModel(), $this->getExpire());
    }

    /**
     * TODO
     */
    public function delete()
    {

    }

    /**
     * TODO
     */
    public function update()
    {

    }
}