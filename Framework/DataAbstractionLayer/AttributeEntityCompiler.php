<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\AutoIncrement;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\CustomFields as CustomFieldsAttr;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\FieldType;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ForeignKey;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ManyToMany;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ManyToOne;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\OnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\OneToMany;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\OneToOne;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\PrimaryKey as PrimaryKeyAttr;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Protection;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\ReferenceVersion;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Required as RequiredAttr;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Serialized;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Translations;
use Shopware\Core\Framework\DataAbstractionLayer\Attribute\Version;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AutoIncrementField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateIntervalField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AsArray;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RestrictDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\SerializedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayEntity;

#[Package('core')]
class AttributeEntityCompiler
{
    private const FIELD_ATTRIBUTES = [
        Translations::class,
        AutoIncrement::class,
        Serialized::class,
        ForeignKey::class,
        Version::class,
        Field::class,
        OneToMany::class,
        ManyToMany::class,
        ManyToOne::class,
        OneToOne::class,
    ];

    private const ASSOCIATIONS = [
        OneToMany::class,
        ManyToMany::class,
        ManyToOne::class,
        OneToOne::class,
    ];

    private static function snake_case(string $name): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

    private static function camel_case(string $name): string
    {
        return lcfirst(str_replace('_', '', ucwords($name, '_')));
    }

    /**
     * @param class-string<object> $class
     *
     * @return array<array<string, mixed>>
     */
    public function compile(string $class): array
    {
        $reflection = new \ReflectionClass($class);

        $collection = $reflection->getAttributes(Entity::class);

        if (empty($collection)) {
            return [];
        }

        /** @var Entity $instance */
        $instance = $collection[0]->newInstance();

        $properties = $reflection->getProperties();

        $fields = [];
        foreach ($properties as $property) {
            $field = $this->parseField($instance->name, $property);

            if ($field === null) {
                continue;
            }

            $fields[] = $field;

            if ($field['type'] === ManyToMany::TYPE) {
                $definitions[] = $this->mapping($instance->name, $property);
            }
        }

        $definitions[] = [
            'type' => 'entity',
            'parent' => $instance->parent,
            'entity_class' => $class,
            'entity_name' => $instance->name,
            'fields' => $fields,
        ];

        return $definitions;
    }

    /**
     * @return \ReflectionAttribute<object>
     */
    private function getAttribute(\ReflectionProperty $property, string ...$list): ?\ReflectionAttribute
    {
        foreach ($list as $attribute) {
            $attribute = $property->getAttributes($attribute);
            if (!empty($attribute)) {
                return $attribute[0];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseField(string $entity, \ReflectionProperty $property): ?array
    {
        $attribute = $this->getAttribute($property, ...self::FIELD_ATTRIBUTES);

        if (!$attribute) {
            return null;
        }
        /** @var Field $field */
        $field = $attribute->newInstance();

        $field->nullable = $property->getType()?->allowsNull() ?? true;

        return [
            'type' => $field->type,
            'name' => $property->getName(),
            'class' => $this->getFieldClass($field),
            'flags' => $this->getFlags($field, $property),
            'translated' => $field->translated,
            'args' => $this->getFieldArgs($entity, $field, $property),
        ];
    }

    private function getFieldClass(Field $field): string
    {
        return match ($field->type) {
            FieldType::INT => IntField::class,
            FieldType::TEXT => LongTextField::class,
            FieldType::FLOAT => FloatField::class,
            FieldType::BOOL => BoolField::class,
            FieldType::DATETIME => DateTimeField::class,
            FieldType::UUID => IdField::class,
            AutoIncrement::TYPE => AutoIncrementField::class,
            CustomFieldsAttr::TYPE => CustomFields::class,
            Serialized::TYPE => SerializedField::class,
            FieldType::JSON => JsonField::class,
            FieldType::DATE => DateField::class,
            FieldType::DATE_INTERVAL => DateIntervalField::class,
            FieldType::TIME_ZONE => TimeZoneField::class,
            OneToMany::TYPE => OneToManyAssociationField::class,
            OneToOne::TYPE => OneToOneAssociationField::class,
            ManyToOne::TYPE => ManyToOneAssociationField::class,
            ManyToMany::TYPE => ManyToManyAssociationField::class,
            ForeignKey::TYPE => FkField::class,
            Version::TYPE => VersionField::class,
            ReferenceVersion::TYPE => ReferenceVersionField::class,
            Translations::TYPE => TranslationsAssociationField::class,
            default => StringField::class,
        };
    }

    /**
     * @return array<mixed>
     */
    private function getFieldArgs(string $entity, OneToMany|ManyToMany|ManyToOne|OneToOne|Field|Serialized|AutoIncrement $field, \ReflectionProperty $property): array
    {
        $storage = self::snake_case($property->getName());

        return match (true) {
            $field instanceof Translations => [$entity . '_translation', $entity . '_id'],
            $field instanceof ForeignKey => [$storage, $property->getName(), $field->entity],
            $field instanceof OneToOne => [$property->getName(), $field->column ?? $storage . '_id', $field->ref, $field->entity, false],
            $field instanceof ManyToOne => [$property->getName(), $storage . '_id', $field->entity, $field->ref],
            $field instanceof OneToMany => [$property->getName(), $field->entity, $field->ref, 'id'],
            $field instanceof ManyToMany => [$property->getName(), $field->entity, self::mappingName($entity, $field), $entity . '_id', $field->entity . '_id'],
            $field instanceof AutoIncrement, $field instanceof Version => [],
            $field instanceof ReferenceVersion => [$field->entity, $storage],
            $field instanceof Serialized => [$storage, $property->getName(), $field->serializer],
            default => [$storage, $property->getName()]
        };
    }

    private static function mappingName(string $entity, ManyToMany $field): string
    {
        $items = [$entity, $field->entity];
        sort($items);

        return implode('_', $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFlags(Field $field, \ReflectionProperty $property): array
    {
        $flags = [];

        if (!$field->nullable) {
            $flags[Required::class] = ['class' => Required::class];
        }

        if ($this->getAttribute($property, RequiredAttr::class)) {
            $flags[Required::class] = ['class' => Required::class];
        }

        if ($this->getAttribute($property, PrimaryKeyAttr::class)) {
            $flags[PrimaryKey::class] = ['class' => PrimaryKey::class];
            $flags[Required::class] = ['class' => Required::class];
        }

        if ($field->api !== false) {
            $aware = [];
            if (\is_array($field->api)) {
                if (isset($field->api['admin-api']) && $field->api['admin-api'] === true) {
                    $aware[] = AdminApiSource::class;
                }
                if (isset($field->api['store-api']) && $field->api['store-api'] === true) {
                    $aware[] = SalesChannelApiSource::class;
                }
            }

            $flags[ApiAware::class] = ['class' => ApiAware::class, 'args' => $aware];
        }

        if ($protection = $this->getAttribute($property, Protection::class)) {
            $protection = $protection->newInstance();

            /** @var Protection $protection */
            $flags[WriteProtected::class] = ['class' => WriteProtected::class, 'args' => $protection->write];
        }

        if ($this->getAttribute($property, ManyToMany::class, OneToMany::class, Translations::class)) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === 'array') {
                $flags[AsArray::class] = ['class' => AsArray::class];
            }
        }

        if ($association = $this->getAttribute($property, ...self::ASSOCIATIONS)) {
            $association = $association->newInstance();

            /** @var OneToMany|ManyToMany|ManyToOne|OneToOne $association */
            $flags['cascade'] = match ($association->onDelete) {
                OnDelete::CASCADE => ['class' => CascadeDelete::class],
                OnDelete::SET_NULL => ['class' => SetNullOnDelete::class],
                OnDelete::RESTRICT => ['class' => RestrictDelete::class],
                default => null
            };
        }

        if ($field->type === AutoIncrement::TYPE) {
            unset($flags[Required::class]);
        }

        return $flags;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapping(string $entity, \ReflectionProperty $property): array
    {
        $attribute = $this->getAttribute($property, ManyToMany::class);

        if (!$attribute) {
            throw DataAbstractionLayerException::canNotFindAttribute(ManyToMany::class, $property->getName());
        }
        /** @var ManyToMany $field */
        $field = $attribute->newInstance();

        $srcProperty = self::camel_case($entity);
        $refProperty = self::camel_case($field->entity);

        $fields = [
            [
                'class' => FkField::class,
                'translated' => false,
                'args' => [$entity . '_id', $srcProperty . 'Id', $entity],
                'flags' => [
                    ['class' => PrimaryKey::class],
                    ['class' => Required::class],
                ],
            ],
            [
                'class' => FkField::class,
                'translated' => false,
                'args' => [$field->entity . '_id', $refProperty . 'Id', $field->entity],
                'flags' => [
                    ['class' => PrimaryKey::class],
                    ['class' => Required::class],
                ],
            ],
            [
                'class' => ManyToOneAssociationField::class,
                'translated' => false,
                'args' => [$srcProperty, $entity . '_id', $entity, 'id'],
                'flags' => [],
            ],
            [
                'class' => ManyToOneAssociationField::class,
                'translated' => false,
                'args' => [$refProperty, $field->entity . '_id', $field->entity, 'id'],
                'flags' => [],
            ],
        ];

        return [
            'type' => 'mapping',
            'parent' => null,
            'entity_class' => ArrayEntity::class,
            'entity_name' => self::mappingName($entity, $field),
            'fields' => $fields,
        ];
    }
}
