<?php


namespace RedisPlugin;


interface ModelInterface
{

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function save($data = null, $whiteList = null);

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function update($data = null, $whiteList = null);

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function create($data = null, $whiteList = null);

    /**
     * @param  null $data
     * @param  null $whiteList
     * @return bool
     */
    public function delete($data = null, $whiteList = null);

}