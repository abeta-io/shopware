<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Core\Content\Extension;

use Abeta\PunchOut\Core\Content\AbetaLogin\AbetaLoginDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SalesChannelExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(new OneToManyAssociationField('abetalogin', AbetaLoginDefinition::class, 'id'));
    }

    public function getDefinitionClass(): string
    {
        return SalesChannelDefinition::class;
    }
}
