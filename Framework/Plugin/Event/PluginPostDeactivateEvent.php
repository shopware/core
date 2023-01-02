<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Plugin\Event;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\PluginEntity;

/**
 * @package core
 */
#[Package('core')]
class PluginPostDeactivateEvent extends PluginLifecycleEvent
{
    /**
     * @var DeactivateContext
     */
    private $context;

    public function __construct(PluginEntity $plugin, DeactivateContext $context)
    {
        parent::__construct($plugin);
        $this->context = $context;
    }

    public function getContext(): DeactivateContext
    {
        return $this->context;
    }
}
