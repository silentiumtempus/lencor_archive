<?php

namespace App\Service;

use App\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommonArchiveService
 * @package App\Service
 */

class CommonArchiveService
{
    protected $em;
    protected $container;
    protected $filesRepository;
    protected $foldersRepository;
    protected $entriesRepository;
    protected $userService;

    /**
     * CommonArchiveService constructor.
     * @param EntityManagerInterface $entityManager
     * @param UserService $userService
     */

    public function __construct(EntityManagerInterface $entityManager, UserService $userService)
    {
        $this->em = $entityManager;
        $this->userService = $userService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
    }

    /**
     * @param int $id
     * @param string $type
     * @return array
     */

    public function getRequesters(int $id, string $type)
    {
        $repository = null;
        switch ($type) {
            case 'file' : $repository = $this->filesRepository;
            break;
            case 'folder' : $repository = $this->foldersRepository;
            break;
            case 'entry' : $repository = $this->entriesRepository;
        }
        $source = $repository->findOneById($id);
        $requesterIds = $source->getRequestedByUsers();
        $requesters = $this->userService->getUsers($requesterIds);

        return $requesters;
    }

    /**
     * @param FolderEntity $parentFolder
     * @param bool $deleteState
     */

    public function changeDeletesQuantity(FolderEntity $parentFolder, bool $deleteState)
    {
        $binaryPath = $this->foldersRepository->getPath($parentFolder);
        foreach ($binaryPath as $folder) {
            if ($deleteState) {
                $folder->setDeletedChildren($folder->getDeletedChildren() + 1);
            } else {
                $folder->setDeletedChildren($folder->getDeletedChildren() - 1);
            }
        }
        // @TODO: Temporary solution, find out how to use joins with FOSElasticaBundle
        $rootFolder = $parentFolder->getRoot();
        $entry = $parentFolder->getRoot()->getArchiveEntry();
        $entry->setDeletedChildren($rootFolder->getDeletedChildren());
    }

    /**
     * @param FolderEntity $folder
     * @param array $foldersArray
     * @param string $i
     * @return int|null
     */

    public function addFolderIdToArray(FolderEntity $folder, array $foldersArray, string $i)
    {
        if (!array_search($folder->getId(), $foldersArray[$i])) {

            return $folder->getId();
        }

        return null;
    }
}