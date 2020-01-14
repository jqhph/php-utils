<?php

namespace Dcat\Utils\RabbitMQ;

/**
 * RabbitMQ队列定义
 *
 * Class Queue
 * @package Dcat\Utils\RabbitMQ
 */
class Queue
{
    /**
     * 队列名称（不填系统会自动生成）
     *
     * @var string
     */
    protected $name;

    /**
     * 绑定路由（仅topic和direct模式需要）
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 检查队列名称是否存在，有就返回给你，没有报错
     *
     * @var bool
     */
    protected $passive = false;

    /**
     * 队列是否是持久化，默认true
     *
     * @var bool
     */
    protected $durable = true;

    /**
     * 代表只能在当前channel下通信。当连接断开队列会消失,不管是不是持久(很重要)
     *
     * @var bool
     */
    protected $exclusive = false;

    /**
     * 是否自动删除，默认否
     *
     * @var bool
     */
    protected $auto_delete = false;

    /**
     * @var bool
     */
    protected $nowait = false;

    /**
     * 扩展参数
     *
     * @var array
     */
    protected $arguments = array();

    protected $ticket = null;

    public function __construct(?string $name = null, array $routes = [])
    {
        $this->setName($name);
        $this->setRoutes($routes);
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $routes
     *
     * @return $this
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param bool $passive
     *
     * @return $this
     */
    public function setPassive(bool $passive)
    {
        $this->passive = $passive;

        return $this;
    }

    public function getPassive()
    {
        return $this->passive;
    }

    /**
     * @param bool $durable
     *
     * @return $this
     */
    public function setDurable(bool $durable)
    {
        $this->durable = $durable;

        return $this;
    }

    public function getDurable()
    {
        return $this->durable;
    }

    /**
     * @param bool $exclusive
     *
     * @return $this
     */
    public function setExclusive(bool $exclusive)
    {
        $this->exclusive = $exclusive;

        return $this;
    }

    public function getExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param bool $auto_delete
     *
     * @return $this
     */
    public function setAutoDelete(bool $auto_delete)
    {
        $this->auto_delete = $auto_delete;

        return $this;
    }

    public function getAutoDelete()
    {
        return $this->auto_delete;
    }

    /**
     * @param bool $nowait
     *
     * @return $this
     */
    public function setNowait(bool $nowait)
    {
        $this->nowait = $nowait;

        return $this;
    }

    public function getNowait()
    {
        return $this->nowait;
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param null $ticket
     *
     * @return $this
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * @return null
     */
    public function getTicket()
    {
        return $this->ticket;
    }
}
