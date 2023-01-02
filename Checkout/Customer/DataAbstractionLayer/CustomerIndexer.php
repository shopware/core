<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\DataAbstractionLayer;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\Event\CustomerIndexerEvent;
use Shopware\Core\Content\Newsletter\DataAbstractionLayer\Indexing\CustomerNewsletterSalesChannelsUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\ManyToManyIdFieldUpdater;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @package customer-order
 */
#[Package('customer-order')]
class CustomerIndexer extends EntityIndexer
{
    public const MANY_TO_MANY_ID_FIELD_UPDATER = 'customer.many-to-many-id-field';
    public const NEWSLETTER_SALES_CHANNELS_UPDATER = 'customer.newsletter-sales-channels';

    private const PRIMARY_KEYS_WITH_PROPERTY_CHANGE = ['email', 'firstName', 'lastName'];

    private IteratorFactory $iteratorFactory;

    private EntityRepositoryInterface $repository;

    private ManyToManyIdFieldUpdater $manyToManyIdFieldUpdater;

    private CustomerNewsletterSalesChannelsUpdater $customerNewsletterSalesChannelsUpdater;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * @internal
     */
    public function __construct(
        IteratorFactory $iteratorFactory,
        EntityRepositoryInterface $repository,
        ManyToManyIdFieldUpdater $manyToManyIdFieldUpdater,
        CustomerNewsletterSalesChannelsUpdater $customerNewsletterSalesChannelsUpdater,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->iteratorFactory = $iteratorFactory;
        $this->repository = $repository;
        $this->manyToManyIdFieldUpdater = $manyToManyIdFieldUpdater;
        $this->customerNewsletterSalesChannelsUpdater = $customerNewsletterSalesChannelsUpdater;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getName(): string
    {
        return 'customer.indexer';
    }

    /**
     * @param array<string, string>|null $offset
     *
     * @deprecated tag:v6.5.0 The parameter $offset will be native typed
     */
    public function iterate(/*?array */$offset): ?EntityIndexingMessage
    {
        if ($offset !== null && !\is_array($offset)) {
            Feature::triggerDeprecationOrThrow(
                'v6.5.0.0',
                'Parameter `$offset` of method "iterate()" in class "CustomerIndexer" will be natively typed to `?array` in v6.5.0.0.'
            );
        }

        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new CustomerIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $updates = $event->getPrimaryKeys(CustomerDefinition::ENTITY_NAME);

        if (empty($updates)) {
            return null;
        }

        $indexing = new CustomerIndexingMessage(array_values($updates), null, $event->getContext());

        if ($getIdsWithProfileChange = $event->getPrimaryKeysWithPropertyChange(CustomerDefinition::ENTITY_NAME, self::PRIMARY_KEYS_WITH_PROPERTY_CHANGE)) {
            $indexing->setIds($getIdsWithProfileChange);
        }

        return $indexing;
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        $ids = array_unique(array_filter($ids));

        if (empty($ids) || !$message instanceof CustomerIndexingMessage) {
            return;
        }

        $context = $message->getContext();

        if (!empty($message->getIds())) {
            $this->customerNewsletterSalesChannelsUpdater->updateCustomersRecipient($message->getIds());
        }

        if ($message->allow(self::MANY_TO_MANY_ID_FIELD_UPDATER)) {
            $this->manyToManyIdFieldUpdater->update(CustomerDefinition::ENTITY_NAME, $ids, $context);
        }

        if ($message->allow(self::NEWSLETTER_SALES_CHANNELS_UPDATER)) {
            $this->customerNewsletterSalesChannelsUpdater->update($ids, true);
        }

        $this->eventDispatcher->dispatch(new CustomerIndexerEvent($ids, $context, $message->getSkip()));
    }

    public function getOptions(): array
    {
        return [
            self::MANY_TO_MANY_ID_FIELD_UPDATER,
            self::NEWSLETTER_SALES_CHANNELS_UPDATER,
        ];
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }
}
