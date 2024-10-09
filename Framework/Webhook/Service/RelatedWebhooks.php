<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Webhook\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Webhook\WebhookCollection;

/**
 * @internal
 *
 * @codeCoverageIgnore @see \Shopware\Tests\Integration\Core\Framework\Webhook\Service\RelatedWebhooksTest
 */
#[Package('core')]
class RelatedWebhooks
{
    /**
     * @param EntityRepository<WebhookCollection> $webhookRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $webhookRepository,
    ) {
    }

    /**
     * Fetch the id's of all similar webhooks (same event, url, live config)
     *
     * @return array<string>
     */
    private function fetchIds(string $webhookId): array
    {
        $result = $this->connection->fetchFirstColumn(
            <<<'SQL'
                SELECT LOWER(HEX(t1.id))
                FROM webhook t1
                JOIN webhook t2 ON t1.event_name = t2.event_name AND t1.url = t2.url AND t1.only_live_version = t2.only_live_version
                WHERE t2.id = :id;
            SQL,
            ['id' => Uuid::fromHexToBytes($webhookId)],
        );

        /** @var array<string> $result */
        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateRelated(string $webhookId, array $data, Context $context): void
    {
        $relatedIds = $this->fetchIds($webhookId);

        $payload = [];
        foreach ($relatedIds as $relatedId) {
            $payload[] = [...$data, 'id' => $relatedId];
        }

        $this->webhookRepository->update($payload, $context);
    }
}
