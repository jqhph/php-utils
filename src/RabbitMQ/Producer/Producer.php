<?php

namespace Dcat\Utils\RabbitMQ\Producer;

use Dcat\Utils\RabbitMQ\Channel;
use Dcat\Utils\RabbitMQ\Contracts\Producer as ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 生产者.
 *
 * Class Topic
 */
abstract class Producer implements ProducerInterface
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var array
     */
    protected $listeners = [
        'ack' => [],
        'neck' => [],
        'return' => [],
    ];

    public function __construct(Channel $channel)
    {
        $this->setChannel($channel);
    }

    /**
     * 设置收到应答消息回调.
     *
     * @param callable $callback
     * @return $this
     */
    public function ack(callable $callback)
    {
        $this->listeners['ack'][] = $callback;

        return $this;
    }

    /**
     * 设置无应答回调(推送消息丢失).
     *
     * @param callable $callback
     * @return $this
     */
    public function neck(callable $callback)
    {
        $this->listeners['neck'][] = $callback;

        return $this;
    }

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
    public function failed(callable $callback)
    {
        $this->listeners['return'][] = $callback;

        return $this;
    }

    /**
     * 设置收到应答消息回调.
     */
    protected function setAckHandler()
    {
        if (empty($this->listeners['ack'])) {
            return;
        }
        $this->channel->set_ack_handler(function (AMQPMessage $message) {
            foreach ($this->listeners['ack'] as $listener) {
                call_user_func($listener, $message);
            }
        });
    }

    /**
     * 设置无应答回调(推送消息丢失).
     */
    protected function setNackHandler()
    {
        if (empty($this->listeners['neck'])) {
            return;
        }
        $this->channel->set_nack_handler(function (AMQPMessage $message) {
            foreach ($this->listeners['neck'] as $listener) {
                call_user_func($listener, $message);
            }
        });
    }

    /**
     *  设置收到发布消息失败回调.
     */
    protected function setReturnListener()
    {
        if (empty($this->listeners['return'])) {
            return;
        }
        $this->channel->set_return_listener(function ($replyCode, $replyText, $exchange, $routingKey, AMQPMessage $message) {
            foreach ($this->listeners['return'] as $listener) {
                call_user_func_array($listener, [$replyCode, $replyText, $exchange, $routingKey, $message]);
            }
        });
    }

    /**
     * 启用应答模式.
     */
    protected function confirmSelect()
    {
        $this->channel->confirm_select();
    }

    /**
     * 等待应答消息返回
     * 如果消息已落地到队列会返回“ack”消息
     * 如果失败会返回“nack”消息.
     */
    protected function waitForPendingAcksReturns()
    {
        $this->channel->wait_for_pending_acks_returns();
    }

    /**
     * 发布消息.
     *
     * @param string $exchange
     * @param string $route
     * @param AMQPMessage[] $messages 批量推送
     * @param bool $mandatory
     * @param bool $immediate 延时设定,可以延时队列
     * @param null $ticket
     * @return mixed|void
     */
    protected function basicPublish(
        $exchange,
        $route,
        array $messages,
        $mandatory = false,
        $immediate = false,
        $ticket = null
    ) {
        // 设置收到消息应答回调
        $this->setAckHandler();

        // 设置无应答回调
        $this->setNackHandler();

        // 设置收到发布消息失败回调
        $this->setReturnListener();

        // 启用应答模式
        $this->confirmSelect();

        // 批量发布消息
        $this->channel->batchPublish(
            $messages,
            $exchange,
            $route,
            $mandatory,
            $immediate,
            $ticket
        );

        // 等待消息应答推送
        $this->waitForPendingAcksReturns();
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
