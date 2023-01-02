<?php declare(strict_types=1);

namespace Shopware\Core\Content\Product\Aggregate\ProductFeatureSetTranslation;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductFeatureSetTranslationEntity>
 *
 * @package inventory
 */
#[Package('inventory')]
class ProductFeatureSetTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductFeatureSetTranslationEntity::class;
    }
}
