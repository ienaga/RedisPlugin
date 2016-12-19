<?php

namespace RedisPlugin;

interface RankingInterface
{

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

    /**
     * @param string $key
     * @param int    $offset
     * @param int    $limit
     * @param bool   $bool
     * @return array
     */
    public function getRanking($key, $offset = 0, $limit = -1, $bool = true);
}