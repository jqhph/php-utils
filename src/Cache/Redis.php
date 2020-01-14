<?php

namespace Dcat\Utils\Cache;

use Illuminate\Support\Facades\Redis as RedisFacade;

/**
 * @author jqh
 * @date 2019/10/21
 */
abstract class Redis extends Cache
{
    /**
     * @var string
     */
    protected $connection;

    /**
     * @var \Illuminate\Redis\Connections\Connection|\Redis
     */
    protected $storage;

    /**
     * 初始化.
     *
     * @throws \Exception
     */
    protected function initStorage()
    {
        if (! $this->storage) {
            $this->storage = RedisFacade::connection($this->connection);
        }
    }

    /**
     * 获取缓存数据.
     *
     * @return mixed
     */
    public function get()
    {
        $this->initStorage();

        throw new \Exception('Redis缓存请手动实现get方法');
    }

    /**
     * 保存缓存.
     *
     * @param mixed $value
     * @return mixed
     */
    public function store($value = null)
    {
        $this->initStorage();

        throw new \Exception('Redis缓存请手动实现store方法');
    }

    /**
     * 判断缓存是否存在.
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function exist()
    {
        $this->initStorage();

        throw new \Exception('Redis缓存请手动实现exist方法');
    }

    /**
     * 删除缓存.
     *
     * @return mixed
     */
    public function forget()
    {
        $this->initStorage();

        throw new \Exception('Redis缓存请手动实现forget方法');
    }

    /**
     * @return \Illuminate\Redis\Connections\Connection|\Redis
     * @throws \Exception
     */
    public function getStorage()
    {
        return parent::getStorage();
    }
}
