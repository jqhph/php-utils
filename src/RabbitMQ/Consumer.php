<?php

namespace Dcat\Utils\RabbitMQ;

use Dcat\Utils\RabbitMQ\Consumer\Topic;
use Dcat\Utils\RabbitMQ\Contracts\TopicConsumer;

class Consumer
{
    /**
     * @var Channel
     */
    protected $channel;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * 消费者主题模式.
     *
     * @return TopicConsumer|\Dcat\Utils\RabbitMQ\Contracts\Consumer
     */
    public function topic(?string $exchange, Queue $queue)
    {
        return new Topic($this->channel, $exchange, $queue);
    }
}
