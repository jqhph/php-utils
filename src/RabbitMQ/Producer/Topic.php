<?php

namespace Dcat\Utils\RabbitMQ\Producer;

use Dcat\Utils\RabbitMQ\Channel;
use Dcat\Utils\RabbitMQ\Contracts\TopicProducer;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 生产者主题模式.
 *
 * Class Topic
 */
class Topic extends Producer implements TopicProducer
{
    /**
     * 推送消息的交换机名称.
     *
     * @var string
     */
    protected $exchange;

    /**
     * 路由名称.
     *
     * @var string
     */
    protected $route;

    /**
     * @var bool
     */
    protected $exchangeDeclared;

    public function __construct(Channel $channel, ?string $exchange, ?string $route)
    {
        $this->setChannel($channel);
        $this->setExchange($exchange);
        $this->setRoute($route);
    }

    /**
     * 发布消息.
     *
     * @param AMQPMessage[] $messages 批量推送
     * @param bool $mandatory
     * @param bool $immediate 延时设定,可以延时队列
     * @param null $ticket
     * @return mixed|void
     */
    public function publish(array $messages, $mandatory = false, $immediate = false, $ticket = null)
    {
        // 声明交换机
        $this->exchangeDeclare(false, true, false);

        // 发布消息
        $this->basicPublish($this->exchange, $this->route, $messages, $mandatory, $immediate, $ticket);
    }

    /**
     * 声明交换机exchange.
     *
     * @param bool   $passive 检查是否存在
     * @param bool   $durable 是否持久————决定机器重启时候是否自动恢复
     * @param bool   $auto_delete 是否自动删除(rabbitmq底层默认true)
     * @param bool $internal
     * @param bool $nowait
     * @param array $arguments
     * @param int $ticket
     */
    public function exchangeDeclare(
        $passive = false,
        $durable = true,
        $auto_delete = false,
        $internal = false,
        $nowait = false,
        $arguments = [],
        $ticket = null
    ) {
        if ($this->exchangeDeclared) {
            return;
        }
        $this->exchangeDeclared = true;

        $this->channel->exchangeDeclare(
            $this->exchange,
            AMQPExchangeType::TOPIC,
            $passive,
            $durable,
            $auto_delete,
            $internal,
            $nowait,
            $arguments,
            $ticket
        );
    }

    /**
     * @param string $exchange
     *
     * @return $this
     */
    public function setExchange(string $exchange)
    {
        $this->exchange = $exchange;

        return $this;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param string $route
     *
     * @return $this
     */
    public function setRoute(string $route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
