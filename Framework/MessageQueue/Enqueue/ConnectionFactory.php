<?php declare(strict_types=1);

namespace Shopware\Core\Framework\MessageQueue\Enqueue;

use Shopware\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\Context;

/**
 * @deprecated tag:v6.5.0 - reason:remove-decorator - will be removed, as we remove enqueue
 * @package core
 */
#[Package('core')]
class ConnectionFactory implements \Interop\Queue\ConnectionFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $config;

    /**
     * @internal
     */
    public function __construct(Connection $connection, array $config)
    {
        $this->connection = $connection;
        $this->config = $config;
    }

    public function createContext(): Context
    {
        return new DbalContext($this->connection, $this->config);
    }
}
