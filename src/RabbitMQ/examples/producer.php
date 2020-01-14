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

// 主题模式
// 强制开启select confirm模式，尽量保证下次不丢失
$topic = Factory::producer($channel)->topic($exchange, 'def1');

// ack回调
$topic->ack(function (AMQPMessage $message) {
    $this->line('ack: '.$message->body);
});

// neck回调
$topic->neck(function (AMQPMessage $message) {
    $this->line('neck: '.$message->body);
});

// 入队列失败回调
$topic->failed(function ($code, $text, $exchange, $routingKey, AMQPMessage $message) {
    $this->line("failed ===> code: {$code}, text: {$text}, exchange: {$exchange}, route: {$routingKey}, message: {$message->body}");
});

// 发布消息
$topic->publish([
    new AMQPMessage('HELLO 1'),
    //            new AMQPMessage('HELLO 2'),
    //            new AMQPMessage('HELLO 3'),
    //            new AMQPMessage('HELLO 4'),
]);

Factory::shutdown();
