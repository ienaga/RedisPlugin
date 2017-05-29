<?php

namespace RedisPlugin\Mvc\Model;

interface OperatorInterface
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
    const NOT_LIKE      = "NOT LIKE";
    const IN            = "IN";
    const NOT_IN        = "NOT IN";
    const BETWEEN       = "BETWEEN";
    const ADD_OR        = "OR";
    const ASC           = "ASC";
    const DESC          = "DESC";
    const INNER_JOIN    = "INNER";
    const LEFT_JOIN     = "LEFT";
    const RIGHT_JOIN    = "RIGHT";
}