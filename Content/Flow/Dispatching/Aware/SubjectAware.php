<?php declare(strict_types=1);

namespace Shopware\Core\Content\Flow\Dispatching\Aware;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Event\FlowEventAware;

/**
 * @package business-ops
 */
#[Package('business-ops')]
interface SubjectAware extends FlowEventAware
{
    public const SUBJECT = 'subject';

    public function getSubject(): string;
}
