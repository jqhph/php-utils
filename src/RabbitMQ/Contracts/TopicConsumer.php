<?php

namespace Dcat\Utils\RabbitMQ\Contracts;

use Dcat\Utils\RabbitMQ\Queue;

interface TopicConsumer
{
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
        $durable = false,
        $auto_delete = false,
        $internal = false,
        $nowait = false,
        $arguments = [],
        $ticket = null
    );

    /**
     * @param string $exchange
     *
     * @return $this
     */
    public function setExchange(string $exchange);

    /**
     * @return string
     */
    public function getExchange();

    /**
     * @param Queue $queue
     *
     * @return $this
     */
    public function setQueue(Queue $queue);

    /**
     * @return Queue
     */
    public function getQueue();
}
