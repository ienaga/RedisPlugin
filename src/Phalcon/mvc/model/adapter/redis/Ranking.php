<?php

namespace Phalcon\Mvc\Model\Adapter\Redis;

class Ranking extends Connection implements RankingInterface
{
    /**
     * @var Ranking
     */
    private static $_instance = null;

    /**
     * Ranking constructor.
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * @return Connection
     */
    static function getInstance(): Connection
    {
        return (self::$_instance === null)
            ? self::$_instance = new static
            : self::$_instance;
    }

    /**
     * @param  array $config
     * @return Connection
     * @throws Exception
     */
    public function connect($config = array()): Connection
    {
        parent::connect($config);
        return $this;
    }

    /**
     * @param  string $key
     * @param  mixed  $member
     * @param  string $option
     * @return int|null
     */
    public function getRank($key, $member, $option = "+inf")
    {
        if (!$this->isRank($key, $member)) {
            return null;
        }
        $score = $this->getRedis()->zScore($key, $member) + 1;
        return $this->getRedis()->zCount($key, $score, $option) + 1;
    }

    /**
     * @param  string $key
     * @param  mixed  $member
     * @return bool
     */
    public function isRank($key, $member)
    {
        return ($this->getRedis()->zRank($key, $member) !== false);
    }

    /**
     * @param string $key
     * @param int    $offset
     * @param int    $limit
     * @param bool   $bool
     * @return array
     */
    public function getRanking($key, $offset = 0, $limit = -1, $bool = true)
    {
        return $this->getRedis()->zRevRange($key, $offset, $limit, $bool);
    }
}