<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FileService
 * @package App\Services
 */
class FileService
{
    protected $em;
    protected $container;
    protected $folderService;
    protected $filesRepository;
    protected $userService;
    protected $entryService;

    /**
     * FileService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FolderService $folderService
     * @param UserService $userService
     * @param EntryService $entryService
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, FolderService $folderService, UserService $userService, EntryService $entryService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->userService = $userService;
        $this->folderService = $folderService;
        $this->entryService = $entryService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
    }

    /**
     * @param int $fileId
     * @return mixed
     */
    public function getFileById(int $fileId)
    {
        return $this->filesRepository->findOneById($fileId);
    }

    /**
     * @param string $folderAbsPath
     * @param string $originalName
     * @return string
     */
    public function constructFileAbsPath(string $folderAbsPath, string $originalName)
    {
        return $folderAbsPath . "/" . $originalName;
    }

    /**
     * @param FileEntity $requestedFile
     * @param string $slashDirection
     * @return null|string
     */
    public function getFilePath(FileEntity $requestedFile, string $slashDirection)
    {
        $path = null;
        $slash = ($slashDirection) ? '/' : '\\';
        $binaryPath = $this->folderService->getPath($requestedFile->getParentFolder());
        foreach ($binaryPath as $folderName) {
            $path .= $folderName . $slash;
        }
        $path .= $requestedFile->getFileName();

        return $path;
    }

    /**
     * @param FileEntity $requestedFile
     * @return string
     */
    public function getFileSharePath(FileEntity $requestedFile)
    {
        $share_root = $this->container->getParameter('lencor_archive.share_path');
        $fileAbsPath = $this->getFilePath($requestedFile, false);

        return $share_root . '\\' . $fileAbsPath;

    }

    /**
     * @param array $filesArray
     * @return FolderEntity||array
     */
    public function getFilesList(array $filesArray)
    {
        return $this->filesRepository->findById($filesArray);
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function getFileHTTPUrl(string $filePath)
    {
        $httpRoot = $this->container->getParameter('lencor_archive.http_path');
        $httpPath = $httpRoot . '/' . $filePath;

        return $httpPath;
    }

    /**
     * @param FileEntity $fileArrayEntity
     * @param $file
     * @return FileEntity
     */
    public function createFileEntityFromArray(FileEntity $fileArrayEntity, $file)
    {
        $newFileEntity = clone $fileArrayEntity;
        $newFileEntity
            ->setFileName($file)
            ->setFiles(null);

        return $newFileEntity;
    }

    /**
     * @param FileEntity $newFileEntity
     * @param FolderEntity $parentFolder
     * @param string $originalName
     * @param User $user
     */
    public function prepareNewFile(FileEntity $newFileEntity, FolderEntity $parentFolder, string $originalName, User $user)
    {
        $parentFolder = $this->folderService->getParentFolder($parentFolder);
        $newFileEntity
            ->setParentFolder($parentFolder)
            ->setFileName($originalName)
            ->setAddedByUser($user)
            ->setDeleteMark(false)
            ->setSlug(null)
            ->setDeletedByUser(null);
    }

    /**
     * @param FileEntity $fileEntity
     */
    public function persistFile(FileEntity $fileEntity)
    {
        $this->em->persist($fileEntity);
        $this->em->flush();
    }

    //@TODO: Unite two methods below

    /**
     * @param int $fileId
     * @param User $user
     * @return mixed
     */
    public function removeFile(int $fileId, User $user)
    {
        $deletedFile = $this->filesRepository->findById($fileId);
        foreach ($deletedFile as $file) {
            $file
                ->setDeleteMark(true)
                ->setDeletedByUser($user);
        }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($deletedFile[0]->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $user);

        return $deletedFile;
    }

    /**
     * @param int $fileId
     * @param User $user
     * @return mixed
     */
    public function restoreFile(int $fileId, User $user)
    {
        $restoredFile = $this->filesRepository->findById($fileId);
        foreach ($restoredFile as $file) {
            $file
                ->setDeleteMark(false)
                ->setDeletedByUser(null)
                ->setRequestMark(false)
                ->setRequestedByUsers(null);
        }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($restoredFile[0]->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $user);

        return $restoredFile;
    }

    /**
     * @param int $fileId
     * @param User $user
     * @param FolderService $folderService
     * @return mixed
     */
    public function requestFile(int $fileId, User $user, FolderService $folderService)
    {
        $requestedFile = $this->filesRepository->findById($fileId);
        foreach ($requestedFile as $file) {
            if ($file->getRequestMark() != null && $file->getRequestMark() != false) {
                $users = $file->getRequestedByUsers();
                if ((array_search($user->getId(), $users, true)) === false) {
                    $users[] = $user;
                }
            } else {
                $users[] = $user;
            }
            $file
                ->setRequestMark(true)
                ->setRequestedByUsers($users);
            $folderService->requestFolder($file->getParentFolder()->getId(), $user);
        }
        $this->em->flush();

        return $requestedFile;
    }


    /**
     * @param FileEntity $newFile
     * @return bool
     */
    public function moveFile(FileEntity $newFile)
    {
        try {
            $targetFile = $this->em->getUnitOfWork()->getOriginalEntityData($newFile);
            $absPath = $this->folderService->constructFolderAbsPath($newFile->getParentFolder());
            $fs = new Filesystem();
            $fs->rename($absPath . "/" . $targetFile['fileName'], $absPath . "/" . $newFile->getFileName());

            return true;
        } catch (\Exception $exception) {

            return false;
        }
    }

    /**
     * This is for file name update
     */
    public function renameFile()
    {
        $this->em->flush();
    }

    /**
     * @param int $folderId
     * @param User $user
     * @return bool
     */
    public function removeFilesByParentFolder(int $folderId, User $user)
    {
        $childFiles = $this->filesRepository->findByParentFolder($folderId);
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getDeleteMark()) {
                    $childFile
                        ->setDeleteMark(true)
                        ->setDeletedByUser($user);
                }
            }
        }

        return true;
    }

    /**
     * @param int $folderId
     * @return mixed
     */
    public function showEntryFiles(int $folderId)
    {
        $files = $this->filesRepository->findByParentFolder($folderId);
        foreach ($files as $file) {
            if ($file->getRequestMark()) {
                $file->setRequestsCount(count($file->getRequestedByUsers()));
            }
        }

        return $files;
    }

    /**
     * @param int $fileId
     * @return mixed
     */
    public function reloadFileDetails(int $fileId)
    {
        return $this->filesRepository->findOneById($fileId);
    }
}
