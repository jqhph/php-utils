<?php

namespace Dcat\Utils\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * @author jqh
 * @date 2019/10/21
 */
abstract class Cache
{
    /**
     * @var string
     */
    protected $keyPrefix;

    /**
     * @var string
     */
    protected $key;

    /**
     * 过期时间，秒
     *
     * @var int
     */
    protected $ttl;

    /**
     * @var \Illuminate\Cache\CacheManager|Repository
     */
    protected $storage;

    /**
     * 初始化
     *
     * @throws \Exception
     */
    protected function initStorage()
    {
        if (! $this->storage) {
            $this->storage = cache();
        }
    }

    /**
     * 格式化要返回的数据
     *
     * @param $data
     * @return mixed
     */
    protected function formatGettingData($data)
    {
        return $data;
    }

    /**
     * 格式化要保存的数据
     *
     * @param $data
     * @return mixed
     */
    protected function formatSettingData($data)
    {
        return $data;
    }

    /**
     * 获取缓存数据
     *
     * @return mixed
     */
    public function get()
    {
        $value = $this->getStorage()->get($this->getKey());

        if ($value === null) {
            return $value;
        }

        return $this->formatGettingData($value);
    }

    /**
     * 保存缓存
     *
     * @param mixed $value
     * @return mixed
     */
    public function store($value = null)
    {
        return $this->getStorage()->put(
            $this->getKey(),
            $this->formatSettingData($value),
            $this->ttl
        );
    }

    /**
     * 判断缓存是否存在
     *
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function exist()
    {
        return $this->getStorage()->has($this->getKey());
    }

    /**
     * 删除缓存
     *
     * @return mixed
     */
    public function forget()
    {
        return $this->getStorage()->forget($this->getKey());
    }

    /**
     * 获取缓存数据并删除
     *
     * @return mixed
     */
    public function pull()
    {
        $value = $this->get();

        $this->forget();

        return $value;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  \Closure $callback
     * @return mixed
     */
    public function remember(Closure $callback)
    {
        $value = $this->get();

        if (! is_null($value)) {
            return $value;
        }

        $this->store($value = $callback());

        return $value;
    }

    /**
     * 设置永不过期
     *
     * @return $this
     */
    public function forever()
    {
        $this->expire(3600 * 24 * 3650);

        return $this;
    }

    /**
     * 设置过期时间
     *
     * @param int $seconds
     * @return $this
     */
    public function expire(int $seconds)
    {
        $this->ttl = $seconds;

        return $this;
    }

    /**
     * 指定过期时间
     *
     * @param string $datetime
     * @return $this
     */
    public function expiredAt(string $datetime)
    {
        return $this->expire(strtotime($datetime) - time());
    }

    /**
     * 设置缓存键名
     *
     * @param mixed $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * 获取缓存key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->keyPrefix.'|'.$this->key;
    }

    /**
     * @return string
     */
    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }

    /**
     * @return \Illuminate\Cache\CacheManager|Repository
     * @throws \Exception
     */
    public function getStorage()
    {
        $this->initStorage();

        return $this->storage;
    }

    /**
     * @param mixed ...$params
     * @return $this
     */
    public static function make(...$params)
    {
        return new static(...$params);
    }

    /**
     * 清空所有缓存
     *
     * @return mixed
     */
    public static function flush()
    {
    }
}
