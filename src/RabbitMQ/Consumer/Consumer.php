<?php

namespace Dcat\Utils\RabbitMQ\Consumer;

use Dcat\Utils\RabbitMQ\Channel;
use Dcat\Utils\RabbitMQ\Contracts\Consumer as ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 消费者.
 *
 * Class Consumer
 */
abstract class Consumer implements ConsumerInterface
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $listeners = [
        'handlers' => [],
    ];

    /**
     * 设置消费者一次处理消息的数量.
     *
     * @var int
     */
    protected $prefetchCount = 1;

    public function __construct(Channel $channel)
    {
        $this->setChannel($channel);
    }

    /**
     * 设置处理消息回调.
     *
     * @param callable $callback
     * @return $this
     */
    public function handle(callable $callback)
    {
        $this->listeners['handlers'][] = $callback;

        return $this;
    }

    /**
     * 当消费者处理完当前消息（即空闲状态）时，才会消费新的消息
     * 以防止某些消费者过于繁忙，而某些消费者处于空闲状态
     */
    protected function basicQos()
    {
        $this->channel->basicQos(null, $this->prefetchCount, null);
    }

    /**
     * @return \Closure
     */
    protected function generateHandler()
    {
        return function (AMQPMessage $message) {
            // 是否返回ack消息表示消息已消费
            $ack = function () use ($message) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            };

            // 拒绝应答
            // 可以控制是否重新入队列
            // $requeue = false 标识丢弃该条消息
            $reject = function (bool $requeue = false) use ($message) {
                $message->delivery_info['channel']->basic_reject($message->delivery_info['delivery_tag'], $requeue);
            };

            foreach ($this->listeners['handlers'] as $listener) {
                call_user_func($listener, $message, $ack, $reject);
            }
        };
    }

    /**
     * 等待队列消费完毕.
     */
    protected function waitWhenConsuming()
    {
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * @param int $prefetchCount
     * @return $this
     */
    public function setPrefetchCount(int $prefetchCount)
    {
        if ($prefetchCount < 1) {
            throw new \InvalidArgumentException('设置消费者消费消息数量失败，不能小于1');
        }

        $this->prefetchCount = $prefetchCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrefetchCount()
    {
        return $this->prefetchCount;
    }

    /**
     * @param Channel $channel
     *
     * @return $this
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return Channel
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }
}
