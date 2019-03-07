<?php
declare(strict_types=1);

namespace App\Factory;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;

/**
 * Class FolderFactory
 * @package App\Factory
 */
class FolderFactory
{
    /**
     * @param ArchiveEntryEntity $newEntry
     * @param User $user
     * @return FolderEntity
     */
    public function prepareNewRootFolder(ArchiveEntryEntity $newEntry, User $user): FolderEntity
    {
        $newFolderEntity = new FolderEntity();
        $newFolderEntity
            ->setArchiveEntry($newEntry)
            ->setFolderName(
                $newEntry->getYear() .
                "/" .
                $newEntry->getFactory()->getId() .
                "/" .
                $newEntry->getArchiveNumber()
            )
            ->setAddedByUser($user)
            ->setRemovalMark(false)
            ->setMarkedByUser(null)
            ->setSlug(null);

        return $newFolderEntity;
    }

    /**
     * @param FormInterface $folderAddForm
     * @param User $user
     * @return FolderEntity
     */
    public function prepareNewFolder(FormInterface $folderAddForm, User $user)
    {
        $newFolderEntity = $folderAddForm->getData();
        $newFolderEntity
            ->setAddedByUser($user)
            ->setRemovalMark(false)
            ->setMarkedByUser(null)
            ->setSlug(null);

        return $newFolderEntity;
    }
}
