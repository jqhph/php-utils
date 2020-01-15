<?php

namespace Dcat\Utils\Elasticsearch;

use Dcat\Utils\Elasticsearch\Connections\ConnectionFactory;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Serializers\SmartSerializer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @require elasticsearch/elasticsearch
 */
class BuilderFactory
{
    /**
     * @var Client
     */
    protected static $client;

    /**
     * @var array
     */
    public static $hosts = [];

    /**
     * @var LoggerInterface
     */
    public static $logger;

    /**
     * @var LoggerInterface
     */
    public static $tracer;

    /**
     * @var array
     */
    public static $factoryOptions = [
        'client' => [
            'headers' => [
                'Content-Type' => ['application/json'],
                'Accept' => ['application/json']
            ],
        ],
    ];

    /**
     * 创建一个新的Builder实例.
     *
     * @param array|string $host 192.168.2.66:9200
     * @return ClientBuilder
     */
    public static function create($host = [])
    {
        $builder = ClientBuilder::create();

        $builder->setConnectionFactory(
            new ConnectionFactory(
                ClientBuilder::defaultHandler(),
                static::$factoryOptions,
                new SmartSerializer(),
                static::$logger ?: new NullLogger(),
                static::$tracer ?: new NullLogger()
            )
        );

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
