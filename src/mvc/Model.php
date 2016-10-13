<?php


namespace RedisPlugin;


class Model extends \Phalcon\Mvc\Model implements ModelInterface
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
     * @return Criteria
     */
    public static function criteria()
    {
        return new Criteria(new static());
    }

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null)
    {
        return Database::save($this);
    }
}