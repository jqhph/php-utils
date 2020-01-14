<?php

namespace Dcat\Utils\RabbitMQ\Contracts;

use Dcat\Utils\RabbitMQ\Channel;

interface Consumer
{
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
    );

    /**
     * 设置处理消息回调.
     *
     * @param callable $callback
     * @return $this
     */
    public function handle(callable $callback);

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

    /**
     * @param int $prefetchCount
     * @return $this
     */
    public function setPrefetchCount(int $prefetchCount);

    /**
     * @return int
     */
    public function getPrefetchCount();
}
