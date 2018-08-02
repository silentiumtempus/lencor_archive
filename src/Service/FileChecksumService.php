<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\Mappings\FileChecksumError;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileChecksumService
 * @package App\Service
 */

class FileChecksumService
{
    protected $em;
    protected $container;
    protected $filesRepository;
    protected $foldersRepository;
    protected $fileErrorsRepository;
    protected $entriesRepository;

    /**
     * FileChecksumService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->fileErrorsRepository = $this->em->getRepository('App:Mappings\FileChecksumError');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
    }

    /**
     * @param FileEntity $requestedFile
     * @param string $filePath
     * @return bool
     */

    public function checkFile(FileEntity $requestedFile, string $filePath)
    {
        $fs = new Filesystem();
        $absRoot = $this->container->getParameter('lencor_archive.storage_path');
        $absPath = $absRoot . '/' . $filePath;
        if (!$fs->exists($absPath)) {
            $checkStatus = false;
        } else {
            $actualChecksum = md5_file($absPath);
            $checkStatus = ($actualChecksum == $requestedFile->getChecksum()) ? true : false;
        }
        return $checkStatus;
    }

    /**
     * @param FileEntity $fileEntity
     * @param int $userId
     * @return bool
     */

    public function newChecksumError(FileEntity $fileEntity, int $userId)
    {
        $newFileError = new FileChecksumError();
        $newFileError
            ->setFileId($fileEntity)
            ->setParentFolderId($fileEntity->getParentFolder())
            ->setStatus(1)
            ->setLastCheckByUser($userId)
            ->setLastCheckOn(new \DateTime());
        $this->em->persist($newFileError);
        $this->em->flush();

        return true;
    }

    /**
     * @param FileEntity $fileEntity
     * @param int $userId
     * @return bool
     */

    public function reportChecksumError(FileEntity $fileEntity, int $userId)
    {
        $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
        if ($fileEntity->getSumError() == false) {
            $fileEntity->setSumError(true);
            if ($fileError) {
                $fileError->setFirstOccuredOn(new \DateTime());
                $this->changeErrorStatus($fileError, true, $userId);
            } else {
                $this->newChecksumError($fileEntity, $userId);
            }
            $this->changeErrorsQuantity($fileEntity->getParentFolder(), true);
        } else {
            $fileError
                ->setLastCheckByUser($userId)
                ->setLastCheckOn(new \DateTime());
        }
        $this->em->flush();

        return true;
    }

    /**
     * @param FileEntity $fileEntity
     * @param int $userId
     * @return bool
     */

    public function validateChecksumValue(FileEntity $fileEntity, int $userId)
    {
        if ($fileEntity->getSumError() ==  true) {
            $fileEntity->setSumError(false);
            $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
            if ($fileError) {
                $this->changeErrorStatus($fileError, false, $userId);
            }
            $this->changeErrorsQuantity($fileEntity->getParentFolder(), false);
        }
        $this->em->flush();

        return true;
    }

    /**
     * @param FileChecksumError $fileChecksumError
     * @param int $status
     * @param int $userId
     */

    public function changeErrorStatus(FileChecksumError $fileChecksumError, int $status, int $userId)
    {
        $fileChecksumError
            ->setStatus($status)
            ->setLastCheckByUser($userId)
            ->setLastCheckOn(new \DateTime());
    }

    /**
     * @param FolderEntity $parentFolder
     * @param bool $errorState
     */
    
    public function changeErrorsQuantity(FolderEntity $parentFolder, bool $errorState)
    {
        $binaryPath = $this->foldersRepository->getPath($parentFolder);
        foreach ($binaryPath as $folder) {
            if ($errorState) {
                $folder->setSumErrors($folder->getSumErrors()+1);
            } else {
                $folder->setSumErrors($folder->getSumErrors()-1);
            }
        }
        // @TODO: Temporary solution, find out how to use joins with FOSElasticaBundle
        $rootFolder = $this->foldersRepository->findOneById($parentFolder->getRoot());
        $entry = $this->entriesRepository->findOneById($rootFolder->getArchiveEntry());
        $entry->setSumErrors($rootFolder->getSumErrors());
    }
}
