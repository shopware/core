<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_3;

use Shopware\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Migrations will be internal in v6.5.0
 * @package core
 */
#[Package('core')]
class Migration1571210820AddPaymentMethodIdsToSalesChannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1571210820;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sales_channel`
            ADD COLUMN `payment_method_ids` JSON NULL AFTER `mail_header_footer_id`,
            ADD CONSTRAINT `json.sales_channel.payment_method_ids` CHECK (JSON_VALID(`payment_method_ids`));
        ');

        $this->registerIndexer($connection, 'sales_channel.indexer');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
