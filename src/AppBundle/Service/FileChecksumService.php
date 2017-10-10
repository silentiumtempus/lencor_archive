<?php

namespace AppBundle\Service;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\Mappings\FileChecksumError;
use Doctrine\ORM\EntityManager;
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

    /**
     * FileChecksumService constructor.
     * @param EntityManager $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->filesRepository = $this->em->getRepository('AppBundle:FileEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->fileErrorsRepository = $this->em->getRepository('AppBundle:Mappings\FileChecksumError');
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
        $this->changeErrorsQuantity($fileEntity->getParentFolder(), true);
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
                $this->changeErrorsQuantity($fileEntity->getParentFolder(), true);
            } else {
                $this->newChecksumError($fileEntity, $userId);
            }
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
     * @param $parentFolder
     * @param $errorState
     */
    public function changeErrorsQuantity($parentFolder, $errorState)
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
    }
}