<?php

namespace Dcat\Utils\RabbitMQ;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class Channel
{
    /**
     * @var AbstractConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var mixed
     */
    protected $channelId;

    public function __construct(AbstractConnection $connection, $channelId = null)
    {
        $this->connection = $connection;

        $this->channel = $this->connection->channel($channelId);
    }

    /**
     * 生成一个交换机exchange
     *
     * 没有返回值,rabbitmq内部生成
     *
     * @param string $name 交换机名称
     * @param string $typen 交换机类型   路由类型rounting key,fanout(广播),direct(路由),topic(主题)   默认fanout
     * @param bool   $passive 检查是否存在
     * @param bool   $durable 是否持久————决定机器重启时候是否自动恢复
     * @param bool   $auto_delete 是否自动删除(rabbitmq底层默认true)
     * @param bool $internal
     * @param bool $nowait
     * @param array $arguments
     * @param int $ticket
     *
     * @return bool
     */
    public function exchangeDeclare(
        $name,
        $type = AMQPExchangeType::FANOUT,
        $passive = false,
        $durable = false,
        $auto_delete = false,
        $internal = false,
        $nowait = false,
        $arguments = array(),
        $ticket = null
    )
    {
        return $this->channel->exchange_declare(
            $name,
            $type,
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
     * 生成一个队列queue
     *
     * @param string $queue_name 队列名称.默认为空,系统自动生成.
     * @param string $passive 检查队列名称是否存在  有就返回给你,没有报错..如此鸡肋  没有用queue_declare的方法会自动创建
     * @param bool   $auto_delete 是否自动删除 默认自动删除
     * @param bool   $durable 是否是持久
     * @param bool   $exclusive 是否排外,排外就是代表只能在当前channel下通信.排外的通道当连接断开队列会消失,不管是不是持久(很重要).
     * @param bool $nowait
     * @param array $arguments
     * @param int $ticket
     *
     * @return string|null
     */
    public function queueDeclare(
        $queue_name = '',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        array $arguments = [],
        $ticket = null
    )
    {
        return $this->channel->queue_declare($queue_name, $passive, $durable, $exclusive, $auto_delete, $nowait, $arguments, $ticket);
    }


    /**
     * 队列绑定
     *
     * @param string $queue_name 队列名称
     * @param string $exchange 交换机名称
     * @param string $routing_key 路由规则
     *
     * @return mixed|null
     */
    public function queueBind($queue_name, $exchange, $routing_key = '')
    {
        return $this->channel->queue_bind($queue_name, $exchange, $routing_key);
    }


    /**
     * 推送消息——生产者提交
     *
     * @param AMQPMessage $msg 传递内容
     * @param string $exchange 交换机
     * @param string $routing_key 路由规则
     * @param mixed  $mandatory
     * @param bool   $immediate 延时设定,可以延时队列
     * @param mixed  $ticket
     *
     * @return null
     */
    public function basicPublish(
        $msg,
        $exchange,
        $routing_key,
        $mandatory = false,
        $immediate = false,
        $ticket = null
    )
    {
        return $this->channel->basic_publish($msg, $exchange, $routing_key, $mandatory, $immediate, $ticket);
    }

    /**
     * 批量推送
     *
     * @param AMQPMessage[] $messages
     * @param $exchange
     * @param $routing_key
     * @param bool $mandatory
     * @param bool $immediate
     * @param null $ticket
     */
    public function batchPublish(
        array $messages,
        $exchange,
        $routing_key,
        $mandatory = false,
        $immediate = false,
        $ticket = null
    )
    {
        foreach ($messages as $v) {
            if (! $v instanceof AMQPMessage) {
                $v = new AMQPMessage($v);
            }

            $this->channel->batch_basic_publish(
                $v,
                $exchange,
                $routing_key,
                $mandatory,
                $immediate,
                $ticket
            );
        }

        $this->channel->publish_batch();
    }


    /**
     * 设定队列一次处理的消息条数————主要用于ack确定模式,保证一次只处理一条消息
     *
     * @param string $prefetch_size
     * @param int    $prefetch_count 一次处理message数量
     * @param string $a_global
     *
     * @return mixed
     */
    public function basicQos($prefetch_size = null, $prefetch_count = 1, $a_global = null)
    {
        return $this->channel->basic_qos($prefetch_size, $prefetch_count, $a_global);
    }


    /**
     * 推送消息——消费者提交
     *
     * @param string $queue_name 队列名称
     * @param string $consumer_tag 消费者的标记,内部生成一个唯一标识
     * @param bool   $no_local = false,
     * @param bool   $no_ack = false, 是不是需要ack来确定 false关闭ack自动应答,true启动主动应答
     * @param bool   $exclusive = false, 代表只能在当前channel下通信。当连接断开队列会消失,不管是不是持久(很重要)
     * @param bool   $nowait = false,    需不需要等待
     * @param mixed  $callback = null,   回调函数
     *
     * @return mixed|string
     */
    public function basicConsume(
        $queue_name,
        $consumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null,
        $arguments = array()
    )
    {
        return $this->channel->basic_consume(
            $queue_name,
            $consumer_tag,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $callback,
            $ticket,
            $arguments
        );
    }

    /**
     * 关闭通道
     */
    public function close()
    {
        $this->channel->close();
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return AbstractConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function __call($method, $arguments = [])
    {
        $method = str_snake($method);

        return $this->channel->{$method}(...$arguments);
    }

}
