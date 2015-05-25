<?php
namespace RedisPlugin;

class RedisCriteria
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

    /**
     * @var array
     */
    private $conditions = array('where' => array());

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
        $this->setModel($model);
        $this->setExpire($expire);
    }


    /**
     * @param  string $column
     * @param  mixed  $value
     * @param  string $operator
     * @return $this
     */
    public function add($column, $value, $operator = self::EQUAL)
    {
        $this->conditions['where'][$column] = array(
            'operator' => $operator,
            'value' => $value
        );

        return $this;
    }

    /**
     * @param  array|string $value
     * @return $this
     */
    public function limit($value)
    {
        $this->conditions['limit'] = $value;

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
     * @return array
     */
    public function getConditions()
    {
        if (isset($this->conditions['where']))
            $this->conditions = RedisDb::_createKey($this->conditions);

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
     * @param  int $expire
     * @return $this
     */
    public function setExpire($expire = 0)
    {
        $this->expire = $expire;

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
     * @return \Phalcon\Mvc\Model
     */
    public function findFirst()
    {
        return RedisDb::findFirst($this, $this->getModel(), $this->getExpire());
    }

    /**
     * @return \Phalcon\Mvc\Model[]
     */
    public function find()
    {
        return RedisDb::find($this, $this->getModel(), $this->getExpire());
    }
}