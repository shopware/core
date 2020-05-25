<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\Acl\Role;

use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReadProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\User\UserDefinition;

class AclRoleDefinition extends EntityDefinition
{
    public const PRIVILEGE_LIST = 'list';
    public const PRIVILEGE_DETAIL = 'detail';
    public const PRIVILEGE_CREATE = 'create';
    public const PRIVILEGE_UPDATE = 'update';
    public const PRIVILEGE_DELETE = 'delete';

    public const ENTITY_NAME = 'acl_role';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AclRoleCollection::class;
    }

    public function getEntityClass(): string
    {
        return AclRoleEntity::class;
    }

    public function getDefaults(): array
    {
        return ['privileges' => []];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            new CreatedAtField(),
            new UpdatedAtField(),

            (new StringField('name', 'name'))->addFlags(new Required()),

            (new ListField('privileges', 'privileges'))->addFlags(new Required()),

            (new ManyToManyAssociationField('users', UserDefinition::class, AclUserRoleDefinition::class, 'acl_role_id', 'user_id'))
                ->addFlags(new ReadProtected(SalesChannelApiSource::class)),
        ]);
    }
}
