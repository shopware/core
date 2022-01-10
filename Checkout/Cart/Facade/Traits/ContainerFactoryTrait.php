<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Facade\Traits;

use Shopware\Core\Checkout\Cart\Facade\CartFacadeHelper;
use Shopware\Core\Checkout\Cart\Facade\ContainerFacade;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait ContainerFactoryTrait
{
    private LineItemCollection $items;

    private CartFacadeHelper $helper;

    private SalesChannelContext $context;

    /**
     * The `container()` methods creates an empty container line-item with the given id and label.
     *
     * @param string $id The id for the new container line-item.
     * @param string|null $label The optional label of the container line-item.
     *
     * @return ContainerFacade Returns the newly created, empty container line-item.
     */
    public function container(string $id, ?string $label = null): ContainerFacade
    {
        $item = new LineItem($id, LineItem::CONTAINER_LINE_ITEM, $id);
        $item->setLabel($label);
        $item->setRemovable(true);
        $item->setStackable(false);

        return new ContainerFacade($item, $this->helper, $this->context);
    }

    private function getItems(): LineItemCollection
    {
        return $this->items;
    }
}
