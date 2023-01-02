<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package system-settings
 */
#[Package('system-settings')]
class FileEmptyException extends ShopwareHttpException
{
    public function __construct(string $filename)
    {
        parent::__construct('The file {{ filename }} is empty.', ['fieldName' => $filename]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'CONTENT__IMPORT_EXPORT_FILE_EMPTY';
    }
}
