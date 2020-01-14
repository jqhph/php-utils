<?php

namespace Dcat\Utils\RabbitMQ\Contracts;

use Dcat\Utils\RabbitMQ\Channel;
use PhpAmqpLib\Message\AMQPMessage;

interface Producer
{
    /**
     * 发布消息.
     *
     * @param AMQPMessage[] $msg 批量推送
     * @param bool $mandatory
     * @param bool $immediate 延时设定,可以延时队列
     * @param null $ticket
     * @return mixed|void
     */
    public function publish(array $msg, $mandatory = false, $immediate = false, $ticket = null);

    /**
     * 设置收到应答消息回调.
     *
     * @param callable $callback
     * @return $this
     */
    public function ack(callable $callback);

    /**
     * 设置无应答回调(推送消息丢失).
     *
     * @param callable $callback
     * @return $this
     */
    public function neck(callable $callback);

    /**
     * 设置收到发布消息失败回调.
     *
     * @example
     *  $topic->failed(function ($code, $text, $exchange, $routingKey, AMQPMessage $message) {
     *
     *  });
     *
     * @param callable $callback
     * @return $this
     */
    public function failed(callable $callback);

    /**
     * @param Channel $channel
     *
     * @return $this
     */
    public function setChannel(Channel $channel);

    /**
     * @return Channel
     */
    public function getChannel(): Channel;
}
