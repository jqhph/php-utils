<?php

namespace Dcat\Utils\RabbitMQ\Consumer;

use Dcat\Utils\RabbitMQ\Channel;
use Dcat\Utils\RabbitMQ\Contracts\TopicConsumer;
use Dcat\Utils\RabbitMQ\Queue;
use PhpAmqpLib\Exchange\AMQPExchangeType;

/**
 * 消费者主题模式.
 *
 * Class Topic
 */
class Topic extends Consumer implements TopicConsumer
{
    /**
     * 推送消息的交换机名称.
     *
     * @var string
     */
    protected $exchange;

    /**
     * 队列以及绑定路由指定.
     *
     * @var Queue
     */
    protected $queue;

    /**
     * @var bool
     */
    protected $exchangeDeclared;

    public function __construct(Channel $channel, ?string $exchange, Queue $queue)
    {
        $this->setChannel($channel);
        $this->setExchange($exchange);
        $this->setQueue($queue);
    }

    /**
     * 开始消费.
     *
     * @param string $consumer_tag
     * @param bool $no_local
     * @param bool $no_ack
     * @param bool $exclusive
     * @param bool $nowait
     * @param null $ticket
     * @param array $arguments
     * @return mixed
     */
    public function consume(
        $consumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $ticket = null,
        $arguments = []
    ) {
        // 生命交换机
        $this->exchangeDeclare(false, true, false);

        // 声明队列，绑定交换机和路由
        $queueName = $this->queueDeclare();

        // 当消费者处理完当前消息（即空闲状态）时，才会消费新的消息
        // 以防止某些消费者过于繁忙，而某些消费者处于空闲状态
        $this->basicQos();

        $this->channel->basicConsume(
            $queueName,
            $consumer_tag,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $this->generateHandler(),
            $ticket,
            $arguments
        );

        $this->waitWhenConsuming();
    }

    /**
     * 声明队列，绑定交换机和路由.
     *
     * @return string
     * @throws \Exception
     */
    protected function queueDeclare()
    {
        if (! $this->queue) {
            throw new \Exception('主题模式消费消息出错，未定义队列');
        }
        if (empty($this->queue->getRoutes())) {
            throw new \Exception('主题模式消费消息出错，未定义路由');
        }

        $name = $this->channel->queueDeclare(
            $this->queue->getName(),
            $this->queue->getPassive(),
            $this->queue->getDurable(),
            $this->queue->getExclusive(),
            $this->queue->getAutoDelete(),
            $this->queue->getNowait(),
            $this->queue->getArguments(),
            $this->queue->getTicket()
        );

        $name = is_array($name) ? current($name) : $name;
        $name = $name ?: $this->queue->getName();

        foreach ($this->queue->getRoutes() as $route) {
            $this->channel->queueBind($name ?: $this->queue->getName(), $this->exchange, $route);
        }

        return $name;
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
     * @param Queue $queue
     *
     * @return $this
     */
    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
