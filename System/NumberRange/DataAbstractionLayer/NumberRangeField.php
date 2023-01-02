<?php declare(strict_types=1);

namespace Shopware\Core\System\NumberRange\DataAbstractionLayer;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;

/**
 * @package core
 */
#[Package('core')]
class NumberRangeField extends StringField
{
    public function __construct(string $storageName, string $propertyName, int $maxLength = 64)
    {
        parent::__construct($storageName, $propertyName, $maxLength);
    }
}
