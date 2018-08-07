<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;

/**
 * Class FolderService
 * @package App\Services
 */

class FolderService
{
    protected $em;
    protected $container;
    protected $foldersRepository;
    protected $filesRepository;
    protected $pathRoot;
    protected $pathPermissions;
    protected $entryService;
    protected $dSwitchService;
    protected $commonArchiveService;

    /**
     * FolderService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param EntryService $entryService
     * @param DeleteSwitcherService $dSwitchService
     * @param CommonArchiveService $commonArchiveService
     */

    public function __construct(EntityManagerInterface $entityManager,
                                ContainerInterface $container,
                                EntryService $entryService,
                                DeleteSwitcherService $dSwitchService,
                                CommonArchiveService $commonArchiveService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->entryService = $entryService;
        $this->commonArchiveService = $commonArchiveService;
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->pathPermissions = $this->container->getParameter('lencor_archive.storage_permissions');
        $this->dSwitchService = $dSwitchService;
    }

    /**
     * @param int $folderId
     * @return mixed
     */

    public function getFolderEntry(int $folderId)
    {
        $folderNode = $this->foldersRepository->find($folderId);

        return $folderNode->getRoot()->getArchiveEntry();
    }

    /**
     * @param $folderId
     * @return mixed
     */

    //@TODO: To be removed
    /*public function getFolderEntryId(int $folderId)
    {
        $folderNode = $this->foldersRepository->findOneById($folderId);
        return $folderNode->getRoot()->getArchiveEntry()->getId();
    } */

    /**
     * @param $entryId
     * @return mixed
     */

    public function getRootFolder(int $entryId)
    {
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        return $folderId;
    }

    /**
     * @param integer $folderId
     * @return bool
     */

    public function isRoot(int $folderId)
    {
        $folderEntity = $this->foldersRepository->find($folderId);

        return ($folderEntity->getId() == $folderEntity->getRoot()->getId()) ? true : false;
    }

    /**
     * @param $parentFolder
     * @return mixed
     */

    public function getParentFolder($parentFolder)
    {
        return $this->foldersRepository->find($parentFolder);
    }

    /**
     * @param FolderEntity $folder
     * @return array
     */

    public function getPath(FolderEntity $folder)
    {
        return $this->foldersRepository->getPath($folder);
    }

    /**
     * @param array $foldersArray
     * @return FolderEntity||array
     */

    public function getFoldersList(array $foldersArray)
    {
        return $this->foldersRepository->findById($foldersArray);
    }

    /**
     * @param FolderEntity $newFolderEntity
     * @param ArchiveEntryEntity $newEntry
     * @param User $user
     */

    public function prepareNewRootFolder(FolderEntity $newFolderEntity, ArchiveEntryEntity $newEntry, User $user)
    {
        $newFolderEntity
            ->setArchiveEntry($newEntry)
            ->setFolderName($newEntry->getYear() . "/" . $newEntry->getFactory()->getId() . "/" . $newEntry->getArchiveNumber())
            ->setAddedByUser($user)
            ->setremovalMark(false)
            ->setmarkedByUser(null)
            ->setSlug(null);
    }

    /**
     * @param FormInterface $folderAddForm
     * @param User $user
     * @return FolderEntity
     */

    public function prepareNewFolder(FormInterface $folderAddForm, User $user)
    {
        $newFolderEntity = $folderAddForm->getData();
        $parentFolder = $this->foldersRepository->find($folderAddForm->get('parentFolder')->getViewData());
        $newFolderEntity
            ->setParentFolder($parentFolder)
            ->setAddedByUser($user)
            ->setremovalMark(false)
            ->setmarkedByUser(null)
            ->setSlug(null);

        return $newFolderEntity;
    }

    /**
     * @param FolderEntity $folderEntity
     */

    public function persistFolder(FolderEntity $folderEntity)
    {
        $this->em->persist($folderEntity);
        $this->em->flush();
    }

    /**
     * @param $folderId
     * @param User $user
     * @param FileService $fileService
     * @return mixed
     */

    public function removeFolder(int $folderId, User $user, FileService $fileService)
    {
        $removedFolder = $this->foldersRepository->find($folderId);
            $folderChildren = $this->foldersRepository->getChildren($removedFolder, false, null, null, true);
            if ($folderChildren) {
                foreach ($folderChildren as $childFolder) {
                    if (!$childFolder->getRemovalMark()) {
                        $childFolder->setRemovalMark(true);
                        $childFolder->setMarkedByUser($user);
                        $fileService->removeFilesByParentFolder($childFolder, $user);
                    }
                }
            }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($removedFolder->getRoot()->getArchiveEntry()->getId(), $user);

        return $removedFolder;
    }

    /**
     * @param $folderId
     * @param User $user
     * @return mixed
     */

    public function restoreFolder(int $folderId, User $user)
    {
        $foldersArray = [];
        $restoredFolder = $this->foldersRepository->find($folderId);
        if ($restoredFolder) {
            $foldersArray[] = $restoredFolder->getId();
            $this->unsetFolderremovalMark($restoredFolder);
            $binaryPath = $this->getPath($restoredFolder);
            foreach ($binaryPath as $folder) {
                if ($folder->getRemovalMark()) {
                    $this->unsetFolderremovalMark($folder);
                    $foldersArray[] = $folder->getId();
                }
            }
            $this->em->flush();
            $this->entryService->changeLastUpdateInfo($restoredFolder->getRoot()->getArchiveEntry()->getId(), $user);
        }

        return $foldersArray;
    }

    /**
     * @param FolderEntity $folderEntity
     */

    public function unsetFolderRemovalMark(FolderEntity $folderEntity)
    {
        $folderEntity
            ->setremovalMark(false)
            ->setmarkedByUser(null)
            ->setRequestMark(false)
            ->setRequestedByUsers(null);
    }

    /**
     * @param int $folderId
     * @param User $user
     * @return FolderEntity
     */

    public function requestFolder(int $folderId, User $user)
    {
        $folder = $this->getParentFolder($folderId);
        $binaryPath = $this->getPath($folder);
        foreach ($binaryPath as $folder) {
            if ($folder->getremovalMark()) {
                if ($folder->getRequestMark() ?? $folder->getRequestMark() != false) {
                    $users = $folder->getRequestedByUsers();
                    if (!$users || (array_search($user->getId(), $users, true)) === false) {
                        $users->add($user);
                        $folder->setRequestedByUsers($users);
                    }
                } else {
                    $folder
                        ->setRequestMark(true)
                        ->setRequestedByUsers(new ArrayCollection($user))
                        ->setRequestsCount(count($folder->getRequestedByUsers()));
                }
            }
        }
        $this->em->flush();
        $this->entryService->changeLastUpdateInfo($folder->getRoot()->getArchiveEntry()->getId(), $user);

        return $folder;
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
                $this->commonArchiveService->changeDeletesQuantity($folder, true);
            }
            $this->deleteFilesByParentFolder($folder);
        }
        $this->em->flush();
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
                    $this->commonArchiveService->changeDeletesQuantity($folder, true);
                }
            }
        }
    }

    /**
     * @param FolderEntity $newFolder
     * @param array $originalFolder
     * @return bool
     */

    public function moveFolder(FolderEntity $newFolder, array $originalFolder)
    {
        try {
            $absPath = $this->constructFolderAbsPath($newFolder->getParentFolder());
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка при получении информации о каталоге из базы данных :' . $exception->getMessage());

            return false;
        }
        try {
            $fs = new Filesystem();
            $fs->rename($absPath . "/" . $originalFolder['folderName'], $absPath . "/" . $newFolder->getFolderName());

            return true;
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка файловой системы при переименовании каталога :' . $exception->getMessage());

            return false;
        }
    }

    /**
     * @param FolderEntity $folder
     * @return array
     */

    public function getOriginalData(FolderEntity $folder)
    {
        return $this->em->getUnitOfWork()->getOriginalEntityData($folder);
    }

    /**
     * This is for folder name update
     */

    public function renameFolder()
    {
        $this->em->flush();
    }

    /**
     * @param FolderEntity $folder
     * @return string
     */

    public function constructFolderAbsPath(FolderEntity $folder)
    {
        $folderAbsPath = $this->pathRoot;
        $binaryPath = $this->getPath($folder);
        foreach ($binaryPath as $pathElement) {
            $folderAbsPath .= "/" . $pathElement;
        }

        return $folderAbsPath;
    }

    /**
     * @param int $folderId
     * @param bool $deleted
     * @return mixed
     */

    public function showEntryFolders(int $folderId, bool $deleted)
    {
        /** First code version to retrieve folders as nested tree */
        //$options = array();
        //$folderNode = $this->foldersRepository->findOneById($folderId);
        //$folderTree = $this->foldersRepository->childrenHierarchy($folderNode, true, $options, false);
        if ($this->getFolderEntry($folderId)->getDeleted()) {
            $this->dSwitchService->switchDeleted(null);
        } else {
            $this->dSwitchService->switchDeleted($deleted);
        }
        $folderList = $this->foldersRepository->findByParentFolder($folderId);

        return $folderList;
    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param boolean $isNew
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */

    public function checkAndCreateFolders(ArchiveEntryEntity $archiveEntryEntity, bool $isNew)
    {
        $fs = new Filesystem();
        $pathYear = $this->pathRoot . "/" . $archiveEntryEntity->getYear();
        $pathFactory = $pathYear . "/" . $archiveEntryEntity->getFactory()->getId();
        $pathEntry = $pathFactory . "/" . $archiveEntryEntity->getArchiveNumber();
        $pathLogs = $pathEntry . "/logs";

        try {
            if (!$fs->exists($pathYear)) {
                $fs->mkdir($pathYear, $this->pathPermissions);
            }
            if (!$fs->exists($pathFactory)) {
                $fs->mkdir($pathFactory, $this->pathPermissions);
            }
            if ($isNew) {
                if (!$fs->exists($pathEntry)) {
                    $fs->mkdir($pathEntry, $this->pathPermissions);
                } else {
                    $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! Директория для новой ячейки: ' . $pathEntry . ' уже существует');
                }
                if (!$fs->exists($pathLogs)) {
                    $fs->mkdir($pathLogs, $this->pathPermissions);
                } else {
                    $this->container->get('session')->getFlashBag()->add('warning', 'Внимание! директория логов: ' . $pathEntry . ' уже существует');
                }
            } else {
                if ($fs->exists($pathEntry)) {
                    $this->container->get('session')->getFlashBag()->add('danger', 'Внимание! Директория назначения: ' . $pathEntry . ' уже существует. Операция прервана.');

                } else {
                    if ($fs->exists($pathLogs)) {
                        $this->container->get('session')->getFlashBag()->add('danger', 'Внимание! Директория логов: ' . $pathLogs . 'уже существует. Операция прервана.');
                    }
                }
            }

        } catch (IOException $IOException) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка создания директории: ' . $IOException->getMessage());
        }

        return $pathEntry;
    }

    /**
     * @param array $originalEntry
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @return string
     */

    public function moveEntryFolder(array $originalEntry, ArchiveEntryEntity $archiveEntryEntity)
    {
        $oldPath = $this->entryService->constructExistingPath($originalEntry);
        $newPath = $this->checkAndCreateFolders($archiveEntryEntity, false);
        $fs = new Filesystem();
        $fs->rename($oldPath, $newPath);
        $newEntryFile = $newPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".entry";
        if ($originalEntry['archiveNumber'] != $archiveEntryEntity->getArchiveNumber())
        {
            $oldEntryFile = $newPath . "/" . $originalEntry["archiveNumber"] . ".entry";
            $fs->rename($oldEntryFile, $newEntryFile);
        }

        return $newEntryFile;
    }
}
