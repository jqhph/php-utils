<?php

use Dcat\Utils\RabbitMQ\Factory;
use PhpAmqpLib\Message\AMQPMessage;

$connection = Factory::createConnection([
    'host' => '192.168.2.208',
    'port' =>  5672,
    'user' => 'root',
    'password' => 'gdjztw2019',
    'vhost' => '/',
], [
    'insist' => false,
    'login_method' => 'AMQPLAIN',
    'login_response' => null,
    'locale' => 'en_US',
    'connection_timeout' => 3.0,
    'read_write_timeout' => 10.0,
    'context' => null,
    'keepalive' => true,
    'heartbeat' => 2,
]);

$channel = Factory::channel($connection);

$exchange = 'la-topic-test';
$queue = Factory::queue('la-topic-test-queue')
    ->setRoutes(['def1', 'def2'])
    ->setDurable(true)
    ->setAutoDelete(false);

// 消费者 主题模式
$topic = Factory::consumer($channel)->topic($exchange, $queue);

// 设置处理消息回调
$topic->handle(function (AMQPMessage $message, \Closure $ack, \Closure $reject) {
    $this->line('消费者收到消息啦: '.$message->body);

    $this->line('start:'.date('Y-m-d H:i:s'));

    $time = time();
    while (1) {
        if (time() - $time > 60 * 8) {
            break;
        }
    }

    $this->line('end:'.date('Y-m-d H:i:s'));

    // 如果是手动应答模式必须执行此回调函数，否则消息会一直被重复消费
    $ack();
});

$topic->consume();
