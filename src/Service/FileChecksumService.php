<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\Mappings\FileChecksumError;
use App\Factory\FileChecksumErrorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileChecksumService
 * @package App\Service
 */
class FileChecksumService
{
    private $em;
    private $pathRoot;
    private $container;
    private $filesRepository;
    private $foldersRepository;
    private $fileErrorsRepository;
    private $entriesRepository;
    private $deletedFolder;
    private $fileChecksumErrorFactory;

    /**
     * FileChecksumService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FileChecksumErrorFactory $fileChecksumErrorFactory
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        FileChecksumErrorFactory $fileChecksumErrorFactory
    )
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->fileChecksumErrorFactory = $fileChecksumErrorFactory;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->fileErrorsRepository = $this->em->getRepository('App:Mappings\FileChecksumError');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->deletedFolder = $this->container->getParameter('archive.deleted.folder_name');
    }

    /**
     * @param FileEntity $requestedFile
     * @param string $filePath
     * @param bool $deleted
     * @return bool
     */
    public function checkFile(FileEntity $requestedFile, string $filePath, bool $deleted)
    {
        $fs = new Filesystem();
        $absPath = $this->pathRoot . ($deleted ? '/' . $this->deletedFolder : '') . '/' . $filePath;
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
     * @throws \Exception
     */
    public function newChecksumError(FileEntity $fileEntity, int $userId)
    {
        $newFileError = $this->fileChecksumErrorFactory->prepareChecksumError($fileEntity, $userId);
        $this->em->persist($newFileError);
        $this->em->flush();

        return true;
    }

    /**
     * @param FileEntity $fileEntity
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function reportChecksumError(FileEntity $fileEntity, int $userId)
    {
        $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
        if ($fileEntity->getSumError() == false) {
            $fileEntity->setSumError(true);
            if ($fileError) {
                $fileError->setFirstOccuredOn(new \DateTime());
                $this->changeErrorStatus($fileError, 1, $userId);
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
     * @throws \Exception
     */
    public function validateChecksumValue(FileEntity $fileEntity, int $userId)
    {
        if ($fileEntity->getSumError() ==  true) {
            $fileEntity->setSumError(false);
            $fileError = $this->fileErrorsRepository->findOneByFileId($fileEntity->getId());
            if ($fileError) {
                $this->changeErrorStatus($fileError, 0, $userId);
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
     * @throws \Exception
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
