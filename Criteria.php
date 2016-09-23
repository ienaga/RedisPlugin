<?php


namespace RedisPlugin;


class Criteria
{
    /** operator list */
    const EQUAL = '=';
    const NOT_EQUAL = '<>';
    const GREATER_THAN = '>';
    const LESS_THAN = '<';
    const GREATER_EQUAL = '>=';
    const LESS_EQUAL = '<=';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';
    const LIKE = 'LIKE';
    const I_LIKE = 'ILIKE';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const BETWEEN = 'BETWEEN';
    const ADD_OR = 'OR';

    /**
     * @var array
     */
    private $conditions = array('query' => array());

    /**
     * @var null
     */
    private $model = null;

    /**
     * @var int
     */
    private $expire = 0;


    /**
     * @param \Phalcon\Mvc\Model $model
     * @param int $expire
     */
    public function __construct($model, $expire = 0)
    {
        $this
            ->setModel($model)
            ->setExpire($expire);
    }

    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function add($column, $value, $operator = self::EQUAL)
    {
        $this->conditions['query'][$column] =
            $this->_buildArray($value, $operator);

        return $this;
    }

    /**
     * @param  string $column
     * @param  array $values
     * @return Criteria
     */
    public function in($column, $values = array())
    {
        return $this->add($column, $values, self::IN);
    }

    /**
     * @param  string $column
     * @param  array $values
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
        $this->conditions['query'][] = array(
            'operator' => self::ADD_OR,
            $column => $this->_buildArray($value, $operator)
        );

        return $this;
    }

    /**
     * @param  mixed  $value
     * @param  string $operator
     * @return array
     */
    private function _buildArray($value, $operator = self::EQUAL)
    {
        return array(
            'operator' => $operator,
            'value' => $value
        );
    }

    /**
     * @param  int $limit
     * @param  int $offset
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        if ($offset) {
            $this->conditions['limit'] = array(
                'number' => $limit,
                'offset' => $offset
            );
        } else {
            $this->conditions['limit'] = $limit;
        }

        return $this;
    }

    /**
     * @param  string $value
     * @return $this
     */
    public function order($value)
    {
        $this->conditions['order'] = $value;

        return $this;
    }

    /**
     * @param  array|string $value
     * @return $this
     */
    public function group($value)
    {
        $this->conditions['group'] = $value;

        return $this;
    }

    /**
     * @param  bool $bool
     * @return $this
     */
    public function cache($bool = false)
    {
        $this->conditions['cache'] = $bool;

        return $this;
    }

    /**
     * @param  bool $bool
     * @return $this
     */
    public function autoIndex($bool = false)
    {
        $this->conditions['autoIndex'] = $bool;

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
    public function setModel($model)
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
        return RedisDb::findFirst($this->getConditions(), $this->getModel(), $this->getExpire());
    }

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    public function find()
    {
        return RedisDb::find($this->getConditions(), $this->getModel(), $this->getExpire());
    }
}