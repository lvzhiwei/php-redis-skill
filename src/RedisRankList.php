<?php
namespace Lzw012\PhpRedisSkill;
/**
 * Redis 常用功能之排行榜功能
 * 数据类型: Redis 的 sorted set 有序集合
 */
class RedisRankList
{
    use RedisTrait;

    const RANK_TYPE_HOT = 0; //热门排行榜, 如热门商品等
    const RANK_TYPE_MATCH = 1; //比赛排行榜, 用于限时冲刺类排行需求,

    private static $instance;
    private $rank_type=self::RANK_TYPE_HOT; // 排行榜类型, 默认为热门排行榜
    private $key='hotgoods'; //Redis 键名
    private $number=10; //Redis zset 的长度
    private $score = 1; //分数
    private $deadline = 0; //截止时间

    /**
     * 设置 集合参数
     * @param string $key 集合的键名
     * @param int $rank_type 排行榜类型
     * @param int $number 排行榜中的单元数量
     * @param int $deadline 排行榜截止时间
     * @return $this
     */
    public function setConfig(string $key='hotgoods', int $number=10, int $keytimeout=-1,int $rank_type=self::RANK_TYPE_HOT, int $deadline=0)
    {
        $this->key = $key;
        $this->number = $number;
        $this->timeout = $keytimeout;
        $this->rank_type = $rank_type;
        $this->deadline = $deadline;
        return $this;
    }

    /**
     * set a member to the redis key, if the element is exists, update the element
     * 给 redis 有序集合增加或修改一个元素
     * @param $item
     * @param $score
     * @return $this
     * @throws $e exception
     */
    public function setMmeber($member, $score)
    {
        $this->redis = $this->getRedis();
        $key = $this->key;
        $score = $this->getScore($score); // 根据排行榜的类型, 设置不同的 score
        try {
            // 注意: redis开启multi后无法进行查询, 不能在事务中写查询逻辑, 很坑, 这里没有使用事务
            $zsetLength = $this->redis->zCard($key);
            if($zsetLength < $this->number)
            {
                // 增加新用户
                $this->redis->zAdd($key,array(), $score, $member);
            } else
            {
                $last_element = $this->redis->zRevRange($key,-1,-1)[0];
                $last_score = $this->redis->zScore($key, $last_element);
//                $this->redis->multi();
//                $this->redis->watch($key);
                // 如果最后一名的值比当前新值小,则写入
                if ($last_score < $score)
                {
                    $this->redis->zRem($key, $last_element); // remove the last member
                    $this->redis->zAdd($key,array(),$score,$member);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }

    public function replaceElement($item,$score)
    {

    }


    /**
     * 给一个商品增加权重
     * @param int $score | default: hotgoods
     * @param string $item 集合中的元素名称, 如当前热门商品名等
     */
    protected function incrScore($item, int $score, $options)
    {
//        $this->redis = $this->setRedis();
//        $this->redis->zAdd();
    }

    protected function getScore($score)
    {
        $return = 1;
        switch ($this->rank_type)
        {
            case self::RANK_TYPE_HOT:
                $return = $score;
                break;
            case self::RANK_TYPE_MATCH:
                $return = $score.($this->deadline - time());
                break;
        }
        return $return;
    }

    /**
     * 获取排行榜数据
     * @param $key
     * @return array $return
     * todo 使用 sort 命令查找关联的排行信息 参考网址: https://blog.csdn.net/w13528476101/article/details/70146064
     * 使用多个键进行查询
     */
    public function getRankList()
    {
        $rankList = $this->redis->zRevRange($this->key,0,-1,true); //带 value=>score 的数组，通过索引，分数从高到低;
        $rankList = (array)$rankList;
        return $rankList;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        unset($this->redis);
    }
}