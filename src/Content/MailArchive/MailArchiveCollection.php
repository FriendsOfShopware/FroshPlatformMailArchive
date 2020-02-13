<?php


namespace Frosh\MailArchive\Content\MailArchive;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                   add(MailArchiveEntity $entity)
 * @method void                   set(string $key, MailArchiveEntity $entity)
 * @method MailArchiveEntity[]    getIterator()
 * @method MailArchiveEntity[]    getElements()
 * @method MailArchiveEntity|null get(string $key)
 * @method MailArchiveEntity|null first()
 * @method MailArchiveEntity|null last()
 */
class MailArchiveCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MailArchiveEntity::class;
    }
}
