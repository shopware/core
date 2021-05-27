<?php declare(strict_types=1);

namespace Shopware\Core\Content\Flow\Action;

use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Event\WebhookAware;

/**
 * @internal (FEATURE_NEXT_8225)
 */
class SetOrderStateAction extends FlowAction
{
    public function getName(): string
    {
        return FlowAction::SET_ORDER_STATE;
    }

    public static function getSubscribedEvents()
    {
        return [
            FlowAction::SET_ORDER_STATE => 'handle',
        ];
    }

    public function requirements(): array
    {
        return [OrderAware::class, WebhookAware::class, SalesChannelAware::class];
    }

    public function handle(FlowEvent $event): void
    {
        //TODO
    }
}
