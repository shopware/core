<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport\Processing\Writer;

use Shopware\Core\Framework\Log\Package;
use League\Flysystem\FilesystemInterface;
use Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;

/**
 * @package system-settings
 */
#[Package('system-settings')]
class CsvFileWriterFactory extends AbstractWriterFactory
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @internal
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function create(ImportExportLogEntity $logEntity): AbstractWriter
    {
        return new CsvFileWriter($this->filesystem);
    }

    public function supports(ImportExportLogEntity $logEntity): bool
    {
        return $logEntity->getProfile()->getFileType() === 'text/csv';
    }
}
