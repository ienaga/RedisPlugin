<?php

namespace RedisPlugin;

class Ranking extends Connection implements RankingInterface
{
    /**
     * @var \RedisPlugin\Ranking
     */
    private static $_instance = null;

    /**
     * @return Ranking
     */
    static function getInstance()
    {
        return (self::$_instance === null)
            ? self::$_instance = new static
            : self::$_instance;
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
}