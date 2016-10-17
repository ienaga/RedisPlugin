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
        Database::setPrefix($this);

        Database::connect($this, Database::getPrefix());

        // transaction
        $this->setTransaction(Database::getTransaction(Database::getPrefix()));

        if (!parent::save($data, $whiteList)) {

            Database::outputErrorMessage($this);

            return false;

        }

        Database::addModels($this);

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
        Database::setPrefix($this);

        Database::connect($this, Database::getPrefix());

        $this->setTransaction(Database::getTransaction(Database::getPrefix()));

        if (!parent::delete($data, $whiteList)) {

            Database::outputErrorMessage($this);

        }

        Database::addModel($this);

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