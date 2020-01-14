<?php

namespace Dcat\Utils\RabbitMQ;

use Dcat\Utils\RabbitMQ\Contracts\TopicProducer;
use Dcat\Utils\RabbitMQ\Producer\Topic;

/**
 * 生产者对象管理.
 *
 * Class Producer
 */
class Producer
{
    /**
     * @var Channel
     */
    protected $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    public function fanout()
    {
    }

    /**
     * 创建生产者主题模式.
     *
     * @param string|null $exchange
     * @param string|null $route
     * @return TopicProducer|\Dcat\Utils\RabbitMQ\Contracts\Producer
     */
    public function topic(?string $exchange, ?string $route)
    {
        return new Topic($this->channel, $exchange, $route);
    }

    public function direct()
    {
    }
}
