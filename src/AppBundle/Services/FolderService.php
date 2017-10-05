<?php

namespace AppBundle\Services;

use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Class FolderService
 * @package AppBundle\Services
 */
class FolderService
{
    protected $em;
    protected $foldersRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
    }

    public function constructFolderAbsPath($rootPath, $parentFolder)
    {
        $folderAbsPath = $rootPath;
        $binaryPath = $this->foldersRepository->getPath($parentFolder);

        foreach ($binaryPath as $folderName) {
            $folderAbsPath .= "/" . $folderName;
        }

        return $folderAbsPath;
    }

    public function getRootFolder($entryId)
    {
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        return $folderId;
    }

    public function getParentFolder(FolderEntity $parentFolder)
    {
        $parentFolder = $this->foldersRepository->findOneById($parentFolder);

        return $parentFolder;
    }

    public function prepareNewFolder(FolderEntity $newFolderEntity, FolderEntity $parentFolder, User $user)
    {
        $parentFolder = $this->getParentFolder($parentFolder);
        $newFolderEntity->setParentFolder($parentFolder)
            ->setAddedByUserId($user->getId())
            ->setDeleteMark(false)
            ->setDeletedByUserId(null)
            ->setSlug(null);

        return $newFolderEntity;
    }

    public function persistFolder(FolderEntity $folderEntity)
    {
        $this->em->persist($folderEntity);
        $this->em->flush();
    }

}