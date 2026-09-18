<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Core\Content\AbetaLogin;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @package core
 * @method void                add(AbetaLoginEntity $entity)
 * @method void                set(string $key, AbetaLoginEntity $entity)
 * @method AbetaLoginEntity[]    getIterator()
 * @method AbetaLoginEntity[]    getElements()
 * @method AbetaLoginEntity|null get(string $key)
 * @method AbetaLoginEntity|null first()
 * @method AbetaLoginEntity|null last()
 */
class AbetaLoginCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AbetaLoginEntity::class;
    }
}
