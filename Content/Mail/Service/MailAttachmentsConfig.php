<?php declare(strict_types=1);

namespace Shopware\Core\Content\Mail\Service;

use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Shopware\Core\Framework\Context;

/**
 * @internal
 * @package system-settings
 */
class MailAttachmentsConfig
{
    private Context $context;

    private MailTemplateEntity $mailTemplate;

    private MailSendSubscriberConfig $extension;

    /**
     * @var mixed[]
     */
    private array $eventConfig;

    private ?string $orderId;

    /**
     * @param mixed[] $eventConfig
     */
    public function __construct(
        Context $context,
        MailTemplateEntity $mailTemplate,
        MailSendSubscriberConfig $extension,
        array $eventConfig,
        ?string $orderId
    ) {
        $this->context = $context;
        $this->mailTemplate = $mailTemplate;
        $this->extension = $extension;
        $this->eventConfig = $eventConfig;
        $this->orderId = $orderId;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getMailTemplate(): MailTemplateEntity
    {
        return $this->mailTemplate;
    }

    public function setMailTemplate(MailTemplateEntity $mailTemplate): void
    {
        $this->mailTemplate = $mailTemplate;
    }

    public function getExtension(): MailSendSubscriberConfig
    {
        return $this->extension;
    }

    public function setExtension(MailSendSubscriberConfig $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return mixed[]
     */
    public function getEventConfig(): array
    {
        return $this->eventConfig;
    }

    /**
     * @param mixed[] $eventConfig
     */
    public function setEventConfig(array $eventConfig): void
    {
        $this->eventConfig = $eventConfig;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }
}
