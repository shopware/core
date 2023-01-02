<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Field;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\VariantListingConfigFieldSerializer;

/**
 * @package core
 */
#[Package('core')]
class VariantListingConfigField extends JsonField
{
    public function __construct(string $storageName, string $propertyName)
    {
        parent::__construct($storageName, $propertyName);
    }

    protected function getSerializerClass(): string
    {
        return VariantListingConfigFieldSerializer::class;
    }
}
