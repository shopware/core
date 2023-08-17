<?php declare(strict_types=1);

namespace Shopware\Core\Content\Media\Core\Path\Strategy;

use Shopware\Core\Content\Media\Domain\Path\AbstractMediaPathStrategy;
use Shopware\Core\Content\Media\Domain\Path\Struct\MediaLocationStruct;
use Shopware\Core\Content\Media\Domain\Path\Struct\ThumbnailLocationStruct;
use Shopware\Core\Framework\Log\Package;

/**
 * @internal Concrete implementation is not allowed to be decorated or extended. The implementation details can change
 */
#[Package('content')]
class PhysicalFilenamePathStrategy extends AbstractMediaPathStrategy
{
    public function name(): string
    {
        return 'physical_file_name';
    }

    protected function value(MediaLocationStruct|ThumbnailLocationStruct $location): ?string
    {
        $media = $location instanceof ThumbnailLocationStruct ? $location->media : $location;

        $timestamp = $media->uploadedAt ? $media->uploadedAt->getTimestamp() . '/' : '';

        return $timestamp . $media->fileName;
    }
}