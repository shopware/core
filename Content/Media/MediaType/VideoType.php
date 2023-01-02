<?php declare(strict_types=1);

namespace Shopware\Core\Content\Media\MediaType;

use Shopware\Core\Framework\Log\Package;
/**
 * @package content
 */
#[Package('content')]
class VideoType extends MediaType
{
    protected $name = 'VIDEO';
}
