<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ImportExportProfileTranslationEntity>
 *
 * @package system-settings
 */
#[Package('system-settings')]
class ImportExportProfileTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ImportExportProfileTranslationEntity::class;
    }
}
