<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DeleteService
 * @package App\Service
 */
class DeleteService
{
    protected $em;
    protected $filesRepository;
    protected $foldersRepository;
    protected $entriesRepository;

    /**
     * DeleteService constructor.
     * @param EntityManagerInterface $entityManager
     */

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
    }

    /**
     * @param array $filesArray
     */

    public function deleteFiles(array $filesArray)
    {
        foreach ($filesArray as $file) {
            $fileEntity = $this->filesRepository->find($file);
            $this->deleteFile($fileEntity);
        }
    }

    /**
     * @param FolderEntity $folder
     */

    public function deleteFilesByParentFolder(FolderEntity $folder)
    {
        $childFiles = $folder->getFiles();
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getDeleted()) {
                    $childFile->setDeleted(true);
                    $this->changeDeletesQuantity($folder, true);
                }
            }
        }
    }

    /**
     * @param FileEntity $file
     */

    public function deleteFile(FileEntity $file)
    {
        $file->setDeleted(true);
        $this->changeDeletesQuantity($file->getParentFolder(), true);
        $this->em->flush();
    }

    /**
     * @param array $foldersArray
     */

    public function deleteFolders(array $foldersArray)
    {
        foreach ($foldersArray as $folder) {
            $folderEntity = $this->foldersRepository->find($folder);
            $this->deleteFolder($folderEntity);
        }
        //$this->entryService->changeLastUpdateInfo($removedFolder[0]->getRoot()->getArchiveEntry()->getId(), $user);
    }

    /**
     * @param FolderEntity $folder
     */

    public function deleteFolder(FolderEntity $folder)
    {
        $foldersChain = $this->foldersRepository->getChildren($folder, false, null, null, true);
        foreach ($foldersChain as $folder) {
            if (!$folder->getDeleted()) {
                $folder->setDeleted(true);
                $this->changeDeletesQuantity($folder, true);
            }
            $this->deleteFilesByParentFolder($folder);
        }
        $this->em->flush();
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
}