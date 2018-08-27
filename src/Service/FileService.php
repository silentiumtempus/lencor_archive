<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
    protected $entryService;
    protected $pathRoot;
    protected $deletedFolder;
    protected $commonArchiveService;
    protected $dSwitchService;
    protected $fileChecksumService;

    /**
     * FileService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FolderService $folderService
     * @param EntryService $entryService
     * @param DeleteSwitcherService $dSwitchService
     * @param CommonArchiveService $commonArchiveService
     * @param FileChecksumService $fileChecksumService
     */

    public function __construct(EntityManagerInterface $entityManager,
                                ContainerInterface $container,
                                FolderService $folderService,
                                EntryService $entryService,
                                DeleteSwitcherService $dSwitchService,
                                CommonArchiveService $commonArchiveService,
                                FileChecksumService $fileChecksumService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->folderService = $folderService;
        $this->entryService = $entryService;
        $this->commonArchiveService = $commonArchiveService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->dSwitchService = $dSwitchService;
        $this->fileChecksumService = $fileChecksumService;
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->deletedFolder = $this->container->getParameter('archive.deleted.folder_name');

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
     * @param FileEntity $fileEntity
     * @param bool $deleted
     * @return string
     */

    public function getFileHTTPUrl(FileEntity $fileEntity, bool $deleted)
    {
        $filePath = $this->getFilePath($fileEntity, true);

        return $this->container->getParameter('lencor_archive.http_path') . '/' . ($deleted ? '/' . $this->deletedFolder : '') . $filePath;
    }


    /**
     * @param FileEntity $fileEntity
     * @param bool $deleted ;
     * @return string
     */

    public function getFileSharePath(FileEntity $fileEntity, bool $deleted)
    {
        $fileAbsPath = $this->getFilePath($fileEntity, false);

        return $this->container->getParameter('lencor_archive.share_path') . ($deleted ? '\\' . $this->deletedFolder : '') . '\\' . $fileAbsPath;

    }

    /**
     * @param FileEntity $file
     * @return mixed
     */

    public function getFileDownloadInfo(FileEntity $file)
    {
        $parentEntry = $file->getParentFolder()->getRoot()->getArchiveEntry();
        $deleted = ($file->getDeleted() || $parentEntry->getDeleted()) ? true : false;
        $filePath = $this->getFilePath($file, true);
        $fileInfo['share_path'] = $this->getFileSharePath($file, $deleted);
        $fileInfo['http_url'] = $this->getFileHTTPUrl($file, $deleted);
        $fileInfo['check_status'] = $this->fileChecksumService->checkFile($file, $filePath, $deleted);

        return $fileInfo;
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
     * @param FileEntity $fileArrayEntity
     * @param $file
     * @return FileEntity
     */

    public function createFileEntityFromArray(FileEntity $fileArrayEntity, $file)
    {
        $newFileEntity = clone $fileArrayEntity;
        $newFileEntity
            ->setFileName($file)
            ->setUploadedFiles(null);

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
            ->setremovalMark(false)
            ->setSlug(null)
            ->setmarkedByUser(null);
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
        $markedFile = $this->filesRepository->findById($fileId);
        foreach ($markedFile as $file) {
            $file
                ->setRemovalMark(true)
                ->setMarkedByUser($user);
        }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($markedFile[0]->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $user);

        return $markedFile;
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
            if ($file->getRequestMark()) {
                $users = $file->getRequestedByUsers();
                if ((array_search($user->getId(), $users, true)) === false) {
                    //if(!$users->contains($user)) {
                    //     $users->add($user);
                    //  }
                    $users[] = $user->getId();
                    //}
                }
            } else {
                $users[] = $user->getId();
                //$users = new ArrayCollection([$user->getId()]);
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
     * @param int $fileId
     * @param User $user
     * @return mixed
     */

    public function restoreFile(int $fileId, User $user)
    {
        $restoredFile = $this->filesRepository->findById($fileId);
        foreach ($restoredFile as $file) {
            $file
                ->setremovalMark(false)
                ->setmarkedByUser(null)
                ->setRequestMark(false)
                ->setRequestedByUsers(null);
        }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($restoredFile[0]->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $user);

        return $restoredFile;
    }

    /**
     * @param array $filesArray
     */

    public function deleteFiles(array $filesArray)
    {
        $files = $this->filesRepository->find($filesArray);
        foreach ($files as $file) {
            $this->deleteFile($file);
        }
    }

    /**
     * @param FileEntity $file
     */

    public function deleteFile(FileEntity $file)
    {
        $deleted = '_deleted_';
        $restored = '_restored_';
        $originalFile["fileName"] = $file->getFileName();
        $extPosIndex = strrpos($file->getFileName(), '.');
        $resPosIndex = strrpos($file->getFileName(), $restored);
        if ($resPosIndex === false) {
            if ($extPosIndex === false) {
                $file->setFileName($file->getFileName() . $deleted);
            } else {
                $targz = '.tar.gz';
                $targzPosIndex = strrpos($file->getFileName(), $targz);
                if ($targzPosIndex === false) {
                    $ext = $extPosIndex;
                } else {
                    $ext = $targzPosIndex;
                }
                $file->setFileName(substr($file->getFileName(), 0, $ext) . $deleted . substr($file->getFileName(), $ext));
            }
        } else {
            $restored_length = strlen($restored);
            $file->setFileName(substr_replace($file->getFileName(), $deleted, $resPosIndex, $restored_length));
        }
        if ($this->moveFile($file, $originalFile) === true) {
            $file->setDeleted(true);
            $this->commonArchiveService->changeDeletesQuantity($file->getParentFolder(), true);
            $this->em->flush();
        }
    }

    /**
     * @param array $filesArray
     * @return array
     */

    public function unDeleteFiles(array $filesArray)
    {
        $folderIdsArray = [];
        $fileEntities = $this->filesRepository->find($filesArray);
        foreach ($fileEntities as $file) {
            $folderIdsArray = $this->unDeleteFile($file, $folderIdsArray);
        }

        return $folderIdsArray;
    }

    /**
     * @param FileEntity $file
     * @param array $folderIdsArray
     * @return array
     */

    public function unDeleteFile(FileEntity $file, array $folderIdsArray)
    {
        $folderIdsArray['remove'] = [];
        $folderIdsArray['reload'] = [];
        $deleted = '_deleted_';
        $restored = '_restored_';
        $originalFile["fileName"] = $file->getFileName();
        $delPosIndex = strrpos($file->getFileName(), $deleted);
        $resPosIndex = strrpos($file->getFileName(), $restored);
        if ($delPosIndex === true) {
            $file->setFileName(substr_replace($file->getFileName(), $restored, $delPosIndex, strlen($deleted)));
        } elseif ($resPosIndex === false) {
            $extPosIndex = strrpos($file->getFileName(), '.');
            if ($extPosIndex === false) {
                $file->setFileName($file->getFileName() . "_restored_");
            } else {
                $targz = '.tar.gz';
                $targzPosIndex = strrpos($file->getFileName(), $targz);
                if ($targzPosIndex === false) {
                    $ext = $extPosIndex;
                } else {
                    $ext = $targzPosIndex;
                }
                $file->setFileName(substr($file->getFileName(), 0, $ext) . '_restored_' . substr($file->getFileName(), $ext));
            }
        }
        if ($this->moveFile($file, $originalFile) === true) {
            $file->setDeleted(false);
            $this->commonArchiveService->changeDeletesQuantity($file->getParentFolder(), false);
            $binaryPath = $this->folderService->getPath($file->getParentFolder());
            foreach ($binaryPath as $folder) {
                if ($folder->getDeleted() === true) {
                    $folder->setDeleted(false);
                    if ($folder->getRoot()->getId() !== $folder->getId()) {
                        $this->commonArchiveService->changeDeletesQuantity($folder->getParentFolder(), false);
                        $i = ($folder->getDeletedChildren() === 0) ? 'remove' : 'reload';
                        $folderIdsArray[$i][] = $this->commonArchiveService->addFolderIdToArray($folder, $folderIdsArray, $i);
                    }
                } else {
                    if ($folder->getRoot()->getId() !== $folder->getId()) {
                        if ($folder->getDeletedChildren() === 0) {
                            $folderIdsArray['remove'][] = $this->commonArchiveService->addFolderIdToArray($folder, $folderIdsArray, 'remove');
                        }
                    }
                }
            }
            $this->em->flush();
        }
        array_reverse($folderIdsArray['remove']);

        return $folderIdsArray;
    }

    /**
     * @param FileEntity $newFile
     * @param array $originalFile
     * @return bool
     */

    public function moveFile(FileEntity $newFile, array $originalFile)
    {
        try {
            $absPath = $this->folderService->constructFolderAbsPath($newFile->getParentFolder());
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка при получении информации о файле из базы данных :' . $exception->getMessage());

            return false;
        }
        try {
            $fs = new Filesystem();
            $fs->rename($absPath . "/" . $originalFile["fileName"], $absPath . "/" . $newFile->getFileName());

            return true;
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка файловой системы при переименовании файла :' . $exception->getMessage());

            return false;
        }
    }

    /**
     * @param FileEntity $file
     * @return array
     */

    public function getOriginalData(FileEntity $file)
    {
        return $this->em->getUnitOfWork()->getOriginalEntityData($file);
    }

    /**
     * This is for file name update
     */

    public function flushFile()
    {
        $this->em->flush();
    }

    /**
     * @param FolderEntity $folder
     * @param User $user
     * @return bool
     */

    public function removeFilesByParentFolder(FolderEntity $folder, User $user)
    {
        $childFiles = $folder->getFiles();
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getRemovalMark()) {
                    $childFile
                        ->setRemovalMark(true)
                        ->setMarkedByUser($user);
                }
            }
        }

        return true;
    }

    /**
     * @param int $folderId
     * @param bool $deleted
     * @return mixed
     */

    public function showEntryFiles(int $folderId, bool $deleted)
    {
        if ($this->folderService->getFolderEntry($folderId)->getDeleted()) {
            $this->dSwitchService->switchDeleted(null);
        } else {
            $this->dSwitchService->switchDeleted($deleted);
        }
        $files = $this->filesRepository->findByParentFolder($folderId);
        foreach ($files as $file) {
            if ($file->getRequestMark()) {
                $file->setRequestsCount($file->getRequestsCount());
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

    /**
     * @return array
     */

    public function locateFiles()
    {
        $finder = new Finder();
        $finder
            ->files()->name('*.entry')
            ->in($this->pathRoot)
            ->exclude('logs');
        $entryFiles = iterator_to_array($finder);

        return $entryFiles;
    }
}
