<?php

namespace Dcat\Utils\RabbitMQ;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Factory
{
    /**
     * @var AbstractConnection[]
     */
    protected static $allConnections = [];

    /**
     * @var Channel[]
     */
    protected static $allChannels = [];

    /**
     * @var AbstractConnection
     */
    protected static $connection;

    /**
     * @var bool
     */
    protected static $closed = false;

    /**
     * 创建rabbit mq连接（允许配置多主机）.
     *
     * @example
     *     Factory::createConnection('amqp://root:123456@192.168.2.208:5672');
     *
     *     // 多个主机使用","隔开
     *     Factory::createConnection('amqp://root:123456@192.168.2.208:5672/test,//root:123456@192.168.2.209:5672');
     *
     *     Factory::createConnection([
     *         'host' => '192.168.2.208',
     *         'port' =>  5672,//
     *         'user' => 'root',
     *         'password' => 'gdjztw2019',
     *         'vhost' => '/',
     *     ]);
     *
     *     Factory::createConnection([
     *          [
     *              'host' => '192.168.2.208',
     *             'port' =>  5672,//
     *             'user' => 'root',
     *             'password' => 'gdjztw2019',
     *             'vhost' => '/',
     *         ]
     *     ]);
     *
     * @param array|string|null $config
     * @param array|null $options
     *
     * @return AbstractConnection
     *
     * @throws \Exception
     */
    public static function createConnection($config = null, array $options = null)
    {
        $config = static::parseConfig($config);

        $options = (array) ($options ?: config('rabbit-mq.options'));

        return static::$allConnections[] = AMQPStreamConnection::create_connection($config, $options);
    }

    /**
     * @param $config
     *
     * @return array
     */
    protected static function parseConfig($config)
    {
        $config = $config ?: config('rabbit-mq.connections', []);

        if (is_string($config)) {
            $config = static::parseUrls($config);
        }

        if (! is_array(current($config))) {
            $config = [
                [
                    'host' => $config['host'] ?? '127.0.0.1',
                    'port' =>  $config['port'] ?? null,
                    'user' => $config['user'] ?? null,
                    'password' => $config['password'] ?? null,
                    'vhost' => $config['vhost'] ?? null,
                ],
            ];
        }

        return $config;
    }

    /**
     * 生产者.
     *
     * @param Channel $channel
     * @return \Dcat\Utils\RabbitMQ\Producer
     */
    public static function producer(Channel $channel = null)
    {
        $channel = $channel ?: static::channel();

        return new Producer($channel);
    }

    /**
     * 生产者.
     *
     * @param Channel $channel
     * @return \Dcat\Utils\RabbitMQ\Consumer
     */
    public static function consumer(Channel $channel = null)
    {
        $channel = $channel ?: static::channel();

        return new Consumer($channel);
    }

    /**
     * @param string|null $name
     * @param array $routes
     * @return Queue
     */
    public static function queue(?string $name = null, array $routes = [])
    {
        return new Queue($name, $routes);
    }

    /**
     * 创建持久化消息实例.
     *
     * @param string|null $message
     * @param array $options
     * @return AMQPMessage
     */
    public static function persistentMessage(?string $message, array $options = [])
    {
        return new AMQPMessage(
            $message,
            array_merge($options, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT])
        );
    }

    /**
     * 关闭所有启动过的通道和连接.
     *
     * @throws \Exception
     */
    public static function shutdown()
    {
        if (static::$closed) {
            return;
        }
        static::$closed = true;

        try {
            foreach (static::$allChannels as $channel) {
                $channel->close();
            }

            foreach (static::$allConnections as $connection) {
                $connection->close();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取一个单例rabbit-mq连接.
     *
     * @return AbstractConnection
     */
    public static function getConnection()
    {
        return static::$connection ?: (static::$connection = static::createConnection());
    }

    /**
     * @param AbstractConnection|null $connection
     * @param null $channelId
     *
     * @return Channel
     */
    public static function channel(AbstractConnection $connection = null, $channelId = null)
    {
        $connection = $connection ?: static::getConnection();

        return static::$allChannels[] = new Channel($connection, $channelId);
    }

    /**
     * 批量解析url.
     *
     * @param string|null $url
     * @return array
     */
    public static function parseUrls(?string $url)
    {
        if (! $url) {
            return [];
        }

        $result = [];
        foreach (explode(',', $url) as $value) {
            $result[] = static::parseUrl($value);
        }

        return $result;
    }

    /**
     * 解析单个url.
     *
     * @param string|null $url
     * @return array
     */
    public static function parseUrl(?string $url)
    {
        if (strpos($url, '//') === false) {
            $url = 'amqp://'.$url;
        }

        $result = parse_url($url);

        return [
            'host' => $result['host'] ?? '127.0.0.1',
            'port' => $result['port'] ?? 80,
            'user' => $result['user'] ?? null,
            'password' => $result['pass'] ?? null,
            'vhost' => trim($result['path'] ?? null, '/') ?: '/',
        ];
    }
}
