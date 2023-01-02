<?php declare(strict_types=1);

namespace Shopware\Core\DevOps\Environment;

use Shopware\Core\Framework\Log\Package;
/**
 * @package core
 */
#[Package('core')]
interface EnvironmentHelperTransformerInterface
{
    public static function transform(EnvironmentHelperTransformerData $data): void;
}
