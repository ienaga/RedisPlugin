<?php
/**
 * RedisManager.php
 *
 * @copyright   Copyright (c) 2013 sonicmoov Co.,Ltd.
 * @package
 * @subpackage
 * @version     $Id$
 */

namespace RedisPlugin;

use \Redis;

class RedisManager
{
    /**
     * @var Redis
     */
    private static $redis = null;

    /**
     * @var \Redis[]
     */
    private $connections = array();

    /**
     * @var RedisManager
     */
    private static $instance = null;

    /**
     * construct
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * インスタンス
     * @return RedisManager
     */
    static function getInstance()
    {
        return (self::$instance === null)
            ? self::$instance = new self()
            : self::$instance;
    }

    # ----------------------------------------------------------------------------
    # connect
    # ----------------------------------------------------------------------------

    /**
     * @param  array $config
     * @return $this
     */
    public function connect($config = array())
    {
        if (!$config) {
            $configs = \Phalcon\DI::getDefault()
                ->get('config')
                ->get('redis')
                ->get('server')
                ->toArray();

            $config = array_shift($configs);
        }

        $host = $config['host'];
        $port = $config['port'];
        $key = $host .':'. $port;

        if (!isset($this->connections[$key]))
            $this->connections[$key] = $this->createClient($host, $port);

        $select = 0;
        if (isset($config['select']))
            $select = $config['select'];


        self::$redis = $this->connections[$key];
        self::$redis->select($select);

        return $this;
    }

    /**
     * @param  string $host
     * @param  int    $port
     * @return Redis
     */
    public function createClient($host = '127.0.0.1', $port = 6379)
    {
        $redis = new Redis();
        $redis->pconnect($host, $port, 0, 'x');
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $redis;
    }

    # ----------------------------------------------------------------------------
    # KVS
    # ----------------------------------------------------------------------------

    /**
     * Get
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return self::$redis->get($key);
    }

    /**
     * mGet
     *
     * @param array $keys
     * @return array
     */
    public function mGet($keys = array())
    {
        return self::$redis->mGet($keys);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function gets($key)
    {
        return self::$redis->keys($key);
    }

    /**
     * Set
     *
     * @param  string $key
     * @param  string $value
     * @param  int    $expire
     * @return bool
     */
    public function set($key, $value, $expire = -1)
    {
        return self::$redis->set($key, $expire, $value);
    }

    /**
     * exists
     *
     * @param  string $key
     * @param  string $expire
     * @return bool
     */
    public function exists($key, $expire = -1)
    {
        return self::$redis->exists($key, $expire);
    }

    /**
     * delete
     *
     * @param  string $key
     * @return bool
     */
    public function delete($key)
    {
        return self::$redis->delete($key);
    }

    /**
     * incr
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function incr($key, $value)
    {
        return self::$redis->incr($key, $value);
    }

    /**
     * decr
     *
     * @param  $key
     * @param  $value
     * @return mixed
     */
    public function decr($key, $value)
    {
        return self::$redis->decr($key, $value);
    }

    /**
     * expire
     *
     * @param $key
     * @param $time
     * @return mixed
     */
    public function setTimeout($key, $time)
    {
        return self::$redis->setTimeout($key, $time);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getTtl($key)
    {
        return self::$redis->ttl($key);
    }


    /**
     * 期限が設定されているか
     *
     * @param  string $key
     * @return bool
     */
    public function isTimeout($key)
    {
        return (self::$redis->ttl($key) > 0);
    }

    # ----------------------------------------------------------------------------
    # ハッシュ関連
    # ----------------------------------------------------------------------------

    /**
     * rPush
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function rPush($key, $value)
    {
        return self::$redis->rPush($key, $value);
    }

    /**
     * lPush
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function lPush($key, $value)
    {
        return self::$redis->rPush($key, $value);
    }

    /**
     * lLen
     *
     * @param  string $key
     * @return mixed
     */
    public function lLen($key)
    {
        return self::$redis->lLen($key);
    }


    # ----------------------------------------------------------------------------
    # ハッシュ関連
    # ----------------------------------------------------------------------------

    /**
     * 登録
     *
     * @param  string $key
     * @param  string $filed
     * @param  string $value
     * @return bool
     *
     */
    public function hSet($key, $filed, $value)
    {
        return self::$redis->hSet($key, $filed, $value);
    }

    /**
     * 取得
     *
     * @param  string $key
     * @param  string $filed
     * @return mixed|bool
     */
    public function hGet($key, $filed)
    {
        return self::$redis->hGet($key, $filed);
    }

    /**
     * ハッシュの指定したフィールドを削除
     *
     * @param  string $key
     * @param  string $filed
     * @return bool
     */
    public function hDel($key, $filed)
    {
        return self::$redis->hDel($key, $filed);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hLen($key)
    {
        return self::$redis->hLen($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hKeys($key)
    {
        return self::$redis->hKeys($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hVals($key)
    {
        return self::$redis->hVals($key);
    }

    /**
     * @param $key
     * @return array
     */
    public function hGetAll($key)
    {
        $results = array();

        $array = self::$redis->hGetAll($key);
        foreach($array as $key => $value){
            $results[$key] = $value;
        }

        return $results;
    }

    # ----------------------------------------------------------------------------
    # メンバー関連
    # ----------------------------------------------------------------------------

    /**
     * 登録
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function sAdd($key, $value)
    {
        return self::$redis->sAdd($key, $value);
    }

    /**
     * 一覧で取得
     *
     * @param  string $key
     * @return mixed
     */
    public function sMembers($key)
    {
        return self::$redis->sMembers($key);
    }


    /**
     * ランダム取得
     *
     * @param  string      $key
     * @param  string|null $count
     * @return mixed
     */
    public function sRandMember($key, $count = null)
    {
        return self::$redis->sRandMember($key, $count);
    }

    /**
     * Memberから削除
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function sRem($key, $value)
    {
        return self::$redis->sRem($key, $value);
    }


    # ----------------------------------------------------------------------------
    # ランキング
    # ----------------------------------------------------------------------------

    /**
     * ランキングにセット
     *
     * @param  string $key
     * @param  int    $score
     * @param  string $userId
     * @return mixed
     */
    public function zAdd($key, $score, $userId)
    {
        return self::$redis->zAdd($key, $score, $userId);
    }

    /**
     * スコアを取得
     *
     * @param string $key
     * @param string $userId
     */
    public function zScore($key, $userId)
    {
        return self::$redis->zScore($key, $userId);
    }

    /**
     * ランキングを取得
     *
     * @param string $key
     * @param int    $score
     * @param string $option
     */
    public function zCount($key, $score, $option = '+inf')
    {
        return self::$redis->zCount($key, $score, $option);
    }

    /**
     * ランキングを取得
     *
     * @param string $key
     * @param string $userId
     * @param string $option
     */
    public function getRank($key, $userId, $option = '+inf')
    {
        $score = $this->zScore($key, $userId) + 1;
        return $this->zCount($key, $score, $option) + 1;
    }

    /**
     * ランクを取得
     *
     * @param  string $key
     * @param  string $userId
     * @return mixed
     */
    public function zRank($key, $userId)
    {
        return self::$redis->zRank($key, $userId);
    }

    /**
     * ランク情報があるか確認
     *
     * @param  string $key
     * @param  string $userId
     * @return bool
     */
    public function isRank($key, $userId)
    {
        return ($this->zRank($key, $userId) !== false);
    }

    /**
     * ランク情報を指定数取得
     *
     * @param  string $key
     * @param  int    $offset
     * @param  int    $limit
     * @param  bool   $bool
     * @return array
     */
    public function zRevRange($key, $offset = 0, $limit = -1, $bool = true)
    {
        return self::$redis->zRevRange($key, $offset, $limit, $bool);
    }


    # ----------------------------------------------------------------------------
    # トランザクションっぽいもの
    # ----------------------------------------------------------------------------

    /**
     * transaction
     */
    public function beginTransaction()
    {
        self::$redis->multi();
    }

    /**
     * commit
     *
     * @return bool
     */
    public function commit()
    {
        return self::$redis->exec();
    }

    /**
     * rollback
     *
     * @return bool
     */
    public function rollback()
    {
        return self::$redis->discard();
    }

    /**
     * close
     */
    public function __destruct()
    {
        foreach ($this->connections as $redis) {
                $redis->close();
        }

        $this->connections = array();
        self::$redis = null;
    }
}
