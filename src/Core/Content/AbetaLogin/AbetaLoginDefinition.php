<?php
declare(strict_types=1);

namespace MagmodulesAbeta\Core\Content\AbetaLogin;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class AbetaLoginDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'abeta_customer_login';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return AbetaLoginCollection::class;
    }

    public function getEntityClass(): string
    {
        return AbetaLoginEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection(
            [
                (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
                (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
                (new StringField('session_id', 'sessionId')),
                (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class)),
                (new StringField('return_url', 'returnUrl')),
                (new StringField('token', 'token')),
                (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id')),
                new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id'),
            ]
        );
    }
}
