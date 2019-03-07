<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\ArchiveEntryEntity;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;

/**
 * Class EntryFactory
 * @package App\Factory
 */
class EntryFactory
{
    /**
     * @param FormInterface $newEntryForm
     * @param User $user
     * @return ArchiveEntryEntity
     */
    public function prepareEntry(
        FormInterface $newEntryForm,
        User $user
    )
    {
        $newEntry = $newEntryForm->getData();
        $newEntry
            ->setModifiedByUser($user)
            ->setRemovalMark(false)
            ->setMarkedByUser(null);

        return $newEntry;
    }
}
