<?php
namespace Lzw012\PhpRedisSkill;

trait RedisTrait {
    private $redis;
    /*public function getRedis()
    {
        if (is_null($this->redis))
        {
            $this->redis = new \Redis();
            $this->redis->connect('127.0.0.1',6379,10);
        }
        return $this->redis;
    }*/

    /**
     * 设置redis 对象, 如果未设置, 则使用默认配置
     */
    public function setRedis($host='127.0.0.1',$port=6379,$timeout=10)
    {
        $redis = new \Redis();
        $redis->connect($host,$port,$timeout);
        $this->redis = $redis;
        return $this;
    }

}