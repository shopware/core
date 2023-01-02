<?php declare(strict_types=1);

namespace Shopware\Core\Content\Product\Cart;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @package inventory
 */
#[Package('inventory')]
interface ProductGatewayInterface
{
    public function get(array $ids, SalesChannelContext $context): ProductCollection;
}
