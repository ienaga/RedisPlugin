<?php

namespace RedisPlugin;

interface RankingInterface
{
    /**
     * @return Ranking
     */
    static function getInstance();

    /**
     * @param  string $key
     * @param  mixed  $member
     * @param  string $option
     * @return int|null
     */
    public function getRank($key, $member, $option = "+inf");

    /**
     * @param  string $key
     * @param  mixed  $member
     * @return bool
     */
    public function isRank($key, $member);
}