<?php

namespace Dcat\Utils\Elasticsearch\ConnectionPool;

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Elasticsearch\ConnectionPool\AbstractConnectionPool;
use Elasticsearch\ConnectionPool\ConnectionPoolInterface;
use Elasticsearch\ConnectionPool\Selectors\SelectorInterface;
use Elasticsearch\Connections\ConnectionFactoryInterface;
use Elasticsearch\Connections\ConnectionInterface;

class StaticNoPingConnectionPool extends AbstractConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var int
     */
    private $pingTimeout = 60;

    /**
     * @var int
     */
    private $maxPingTimeout = 3600;

    /**
     * {@inheritdoc}
     */
    public function __construct($connections, SelectorInterface $selector, ConnectionFactoryInterface $factory, $connectionPoolParams)
    {
        parent::__construct($connections, $selector, $factory, $connectionPoolParams);
    }

    /**
     * @param bool $force
     *
     * @return ConnectionInterface
     * @throws \Elasticsearch\Common\Exceptions\NoNodesAvailableException
     */
    public function nextConnection(bool $force = false): ConnectionInterface
    {
        $total = count($this->connections);
        while ($total--) {
            /** @var ConnectionInterface $connection */
            $connection = $this->selector->select($this->connections);
            if ($connection->isAlive() === true) {
                return $connection;
            }

            if ($this->readyToRevive($connection) === true) {
                return $connection;
            }
        }

        throw new NoNodesAvailableException('No alive nodes found in your cluster');
    }

    public function scheduleCheck(): void
    {
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return bool
     */
    private function readyToRevive(ConnectionInterface $connection)
    {
        $timeout = min(
            $this->pingTimeout * pow(2, $connection->getPingFailures()),
            $this->maxPingTimeout
        );

        if ($connection->getLastPing() + $timeout < time()) {
            return true;
        }

        return false;
    }
}
