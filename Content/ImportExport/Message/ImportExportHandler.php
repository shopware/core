<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport\Message;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Shopware\Core\Content\ImportExport\Exception\ProcessingException;
use Shopware\Core\Content\ImportExport\ImportExportFactory;
use Shopware\Core\Content\ImportExport\Struct\Progress;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Will only implement MessageHandlerInterface and all MessageHandler will be internal and final starting with v6.5.0.0
 *
 * @package system-settings
 */
#[Package('system-settings')]
class ImportExportHandler extends AbstractMessageHandler
{
    private MessageBusInterface $messageBus;

    private ImportExportFactory $importExportFactory;

    /**
     * @internal
     */
    public function __construct(MessageBusInterface $messageBus, ImportExportFactory $importExportFactory)
    {
        $this->messageBus = $messageBus;
        $this->importExportFactory = $importExportFactory;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ImportExportMessage::class,
        ];
    }

    /**
     * @param ImportExportMessage $message
     */
    public function handle($message): void
    {
        $importExport = $this->importExportFactory->create($message->getLogId(), 50, 50);
        $logEntity = $importExport->getLogEntity();

        if ($logEntity->getState() === Progress::STATE_ABORTED) {
            return;
        }

        if (
            $logEntity->getActivity() === ImportExportLogEntity::ACTIVITY_IMPORT
            || $logEntity->getActivity() === ImportExportLogEntity::ACTIVITY_DRYRUN
        ) {
            $progress = $importExport->import($message->getContext(), $message->getOffset());
        } elseif ($logEntity->getActivity() === ImportExportLogEntity::ACTIVITY_EXPORT) {
            $progress = $importExport->export($message->getContext(), new Criteria(), $message->getOffset());
        } else {
            throw new ProcessingException('Unknown activity');
        }

        if (!$progress->isFinished()) {
            $this->messageBus->dispatch(new ImportExportMessage(
                $message->getContext(),
                $logEntity->getId(),
                $logEntity->getActivity(),
                $progress->getOffset()
            ));
        }
    }
}
