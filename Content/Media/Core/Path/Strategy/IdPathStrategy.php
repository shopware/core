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
class IdPathStrategy extends AbstractMediaPathStrategy
{
    public function name(): string
    {
        return 'id';
    }

    protected function value(MediaLocationStruct|ThumbnailLocationStruct $location): ?string
    {
        return $location instanceof MediaLocationStruct ? $location->id : $location->media->id;
    }
}