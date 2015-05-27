<?php


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
     * @return RedisManager
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
        $this->getRedis()->select($select);

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

    /**
     * @return null|Redis
     */
    public function getRedis()
    {
        return self::$redis;
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
        return $this->getRedis()->get($key);
    }

    /**
     * mGet
     *
     * @param array $keys
     * @return array
     */
    public function mGet($keys = array())
    {
        return $this->getRedis()->mGet($keys);
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
        return $this->getRedis()->set($key, $value, $expire);
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
        return $this->getRedis()->exists($key, $expire);
    }

    /**
     * delete
     *
     * @param  mixed $key
     * @return bool
     */
    public function delete($key)
    {
        return $this->getRedis()->delete($key);
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
        return $this->getRedis()->incr($key, $value);
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
        return $this->getRedis()->decr($key, $value);
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
        return $this->getRedis()->setTimeout($key, $time);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getTtl($key)
    {
        return $this->getRedis()->ttl($key);
    }


    /**
     * 期限が設定されているか
     *
     * @param  string $key
     * @return bool
     */
    public function isTimeout($key)
    {
        return ($this->getTtl($key) > 0);
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
        return $this->getRedis()->rPush($key, $value);
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
        return $this->getRedis()->lPush($key, $value);
    }

    /**
     * lLen
     *
     * @param  string $key
     * @return mixed
     */
    public function lLen($key)
    {
        return $this->getRedis()->lLen($key);
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
        return $this->getRedis()->hSet($key, $filed, $value);
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
        return $this->getRedis()->hGet($key, $filed);
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
        return $this->getRedis()->hDel($key, $filed);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hLen($key)
    {
        return $this->getRedis()->hLen($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hKeys($key)
    {
        return $this->getRedis()->hKeys($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function hVals($key)
    {
        return $this->getRedis()->hVals($key);
    }

    /**
     * @param $key
     * @return array
     */
    public function hGetAll($key)
    {
        $results = array();

        $array = $this->getRedis()->hGetAll($key);
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
        return $this->getRedis()->sAdd($key, $value);
    }

    /**
     * 一覧で取得
     *
     * @param  string $key
     * @return mixed
     */
    public function sMembers($key)
    {
        return $this->getRedis()->sMembers($key);
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
        return $this->getRedis()->sRandMember($key, $count);
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
        return $this->getRedis()->sRem($key, $value);
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
        return $this->getRedis()->zAdd($key, $score, $userId);
    }

    /**
     * スコアを取得
     *
     * @param string $key
     * @param string $userId
     */
    public function zScore($key, $userId)
    {
        return $this->getRedis()->zScore($key, $userId);
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
        return $this->getRedis()->zCount($key, $score, $option);
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
        return $this->getRedis()->zRank($key, $userId);
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
        return $this->getRedis()->zRevRange($key, $offset, $limit, $bool);
    }


    # ----------------------------------------------------------------------------
    # トランザクションっぽいもの
    # ----------------------------------------------------------------------------

    /**
     * transaction
     */
    public function beginTransaction()
    {
        $this->getRedis()->multi();
    }

    /**
     * commit
     *
     * @return bool
     */
    public function commit()
    {
        return $this->getRedis()->exec();
    }

    /**
     * rollback
     *
     * @return bool
     */
    public function rollback()
    {
        return $this->getRedis()->discard();
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
