<?php

namespace AppBundle\Service;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\Mappings\FileChecksumError;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileChecksumService
 * @package AppBundle\Service
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
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->fileErrorsRepository = $this->em->getRepository('AppBundle:Mappings\FileChecksumError');
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
    }


    /**
     * @param FileEntity $requestedFile
     * @param $filePath
     * @return bool
     */
    public function checkFile(FileEntity $requestedFile, $filePath)
    {
        $fs = new Filesystem();
        $absRoot = $this->container->getParameter('lencor_archive.storage_path');
        $absPath = $absRoot . $filePath;
        if (!$fs->exists($absPath))
        {
            $checkStatus = false;
        } else {
            $actualChecksum = md5_file($absPath);
            $checkStatus = ($actualChecksum == $requestedFile->getChecksum()) ? true : false;
        }
        return $checkStatus;
    }

    /**
     * @param FileEntity $fileEntity
     * @param $userId
     * @return bool
     */
    public function newChecksumError(FileEntity $fileEntity, $userId)
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
     * @param $userId
     * @return bool
     */
    public function reportChecksumError(FileEntity $fileEntity, $userId)
    {
        $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
        if ($fileEntity->getSumError() == false) {
            $fileEntity->setSumError(true);
            if ($fileError)
            {
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
     * @param $userId
     * @return bool
     */
    public function validateChecksumValue(FileEntity $fileEntity, $userId)
    {
        if ($fileEntity->getSumError() ==  true) {
            $fileEntity->setSumError(false);
            $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
            if ($fileError)
            {
                $this->changeErrorStatus($fileError, false, $userId);
            }
            $this->changeErrorsQuantity($fileEntity->getParentFolder(), false);
        }
        $this->em->flush();

        return true;
    }

    /**
     * @param FileChecksumError $fileChecksumError
     * @param $status
     * @param $userId
     */
    public function changeErrorStatus(FileChecksumError $fileChecksumError, $status, $userId)
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
        foreach ($binaryPath as $folder)
        {
            if ($errorState)
            {
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