<?php
declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidSerializerFieldException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedByField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;

/**
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Will be internal
 * @package core
 */
#[Package('core')]
class CreatedByFieldSerializer extends FkFieldSerializer
{
    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): \Generator
    {
        if (!($field instanceof CreatedByField)) {
            throw new InvalidSerializerFieldException(CreatedByField::class, $field);
        }

        // only required for new entities
        if ($existence->exists()) {
            return;
        }

        $context = $parameters->getContext()->getContext();
        $scope = $context->getScope();

        if (!\in_array($scope, $field->getAllowedWriteScopes(), true)) {
            return;
        }

        if ($data->getValue()) {
            yield from parent::encode($field, $existence, $data, $parameters);

            return;
        }

        if (!$context->getSource() instanceof AdminApiSource) {
            return;
        }

        $userId = $context->getSource()->getUserId();

        if (!$userId) {
            return;
        }

        $data->setValue($userId);

        yield from parent::encode($field, $existence, $data, $parameters);
    }
}
