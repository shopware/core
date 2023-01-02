<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Event;

use Shopware\Core\Framework\Log\Package;
/**
 * @package core
 */
#[Package('core')]
class AppUpdatedEvent extends ManifestChangedEvent
{
    public const NAME = 'app.updated';

    public function getName(): string
    {
        return self::NAME;
    }
}
