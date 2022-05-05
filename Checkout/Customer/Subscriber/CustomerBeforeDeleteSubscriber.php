<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\Event\CustomerDeletedEvent;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\BeforeDeleteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerBeforeDeleteSubscriber implements EventSubscriberInterface
{
    private EntityRepositoryInterface $customerRepository;

    private SalesChannelContextServiceInterface $salesChannelContextService;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * @internal
     */
    public function __construct(
        EntityRepositoryInterface $customerRepository,
        SalesChannelContextServiceInterface $salesChannelContextService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->customerRepository = $customerRepository;
        $this->salesChannelContextService = $salesChannelContextService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeDeleteEvent::class => 'beforeDelete',
        ];
    }

    public function beforeDelete(BeforeDeleteEvent $event): void
    {
        $context = $event->getContext();

        $ids = $event->getIds(CustomerDefinition::ENTITY_NAME);

        if (empty($ids)) {
            return;
        }

        $source = $context->getSource();
        $salesChannelId = null;

        if ($source instanceof SalesChannelApiSource) {
            $salesChannelId = $source->getSalesChannelId();
        }

        $customers = $this->customerRepository->search(new Criteria($ids), $context);

        $event->addSuccess(function () use ($customers, $context, $salesChannelId): void {
            foreach ($customers->getElements() as $customer) {
                $salesChannelContext = $this->salesChannelContextService->get(
                    new SalesChannelContextServiceParameters(
                        $salesChannelId ?? $customer->getSalesChannelId(),
                        Random::getAlphanumericString(32),
                        $customer->getLanguageId(),
                        null,
                        null,
                        $context,
                    )
                );

                $this->eventDispatcher->dispatch(new CustomerDeletedEvent($salesChannelContext, $customer));
            }
        });
    }
}
