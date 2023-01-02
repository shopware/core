<?php declare(strict_types=1);

namespace Shopware\Core\System\Country\Event;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;

/**
 * @package system-settings
 */
#[Package('system-settings')]
class CountryRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
