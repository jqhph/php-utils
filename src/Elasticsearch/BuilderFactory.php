<?php

namespace Dcat\Utils\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * @require elasticsearch/elasticsearch
 */
class BuilderFactory
{
    protected static $client;

    public static $hosts = [];

    /**
     * 创建一个新的Builder实例.
     *
     * @param array|string $host 192.168.2.66:9200
     * @return ClientBuilder
     */
    public static function create($host = [])
    {
        $builder = ClientBuilder::create();

        $builder->setHosts(static::parseHosts($host));

        return $builder;
    }

    /**
     * 获取单例Client实例.
     *
     * @return Client
     */
    public static function getClient()
    {
        return static::$client ?: (static::$client = static::create()->build());
    }

    /**
     * @param $config
     *
     * @return array
     */
    protected static function parseHosts($config)
    {
        $config = $config ?: (static::$hosts ?: config('elastic.connections', []));

        if (is_string($config)) {
            $config = explode(',', $config);
        }

        return $config;
    }
}
