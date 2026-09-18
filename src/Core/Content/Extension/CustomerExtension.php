<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Core\Content\Extension;

use Abeta\PunchOut\Core\Content\AbetaLogin\AbetaLoginDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CustomerExtension extends EntityExtension
{

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'customerData',
                AbetaLoginDefinition::class,
                'customer_id',
                'id'
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return CustomerDefinition::class;
    }
}
