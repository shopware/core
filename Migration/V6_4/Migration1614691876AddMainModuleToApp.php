<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_4;

use Shopware\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @package core
 *
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Migrations will be internal in v6.5.0
 */
#[Package('core')]
class Migration1614691876AddMainModuleToApp extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614691876;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<'SQL'
ALTER TABLE `app`
    ADD COLUMN `main_module` JSON NULL AFTER `modules`,
    ADD CONSTRAINT `json.app.main_module` CHECK (JSON_VALID(`main_module`));
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
