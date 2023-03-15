<?php declare(strict_types=1);

namespace Shopware\Core\Content\Flow\Dispatching\Storer;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\Log\Package;

#[Package('business-ops')]
class OrderStorer extends FlowStorer
{
    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $orderRepository)
    {
    }

    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof OrderAware || isset($stored[OrderAware::ORDER_ID])) {
            return $stored;
        }

        $stored[OrderAware::ORDER_ID] = $event->getOrderId();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(OrderAware::ORDER_ID)) {
            return;
        }

        $storable->lazy(
            OrderAware::ORDER,
            $this->load(...),
            [$storable->getStore(OrderAware::ORDER_ID), $storable->getContext()]
        );
    }

    /**
     * @param array<int, mixed> $args
     */
    public function load(array $args): ?OrderEntity
    {
        [$orderId, $context] = $args;

        $criteria = new Criteria([$orderId]);

        $criteria->addAssociations([
            'orderCustomer',
            'lineItems.downloads.media',
            'deliveries.shippingMethod',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.countryState',
            'stateMachineState',
            'transactions.stateMachineState',
            'transactions.paymentMethod',
            'deliveries.stateMachineState',
            'currency',
            'addresses.country',
            'tags',
        ]);

        $order = $this->orderRepository->search($criteria, $context)->get($orderId);

        if ($order) {
            /** @var OrderEntity $order */
            return $order;
        }

        return null;
    }
}
