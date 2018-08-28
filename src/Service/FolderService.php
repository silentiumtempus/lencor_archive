<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\ConstraintViolationException;
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
    protected $loggingService;

    /**
     * FolderService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param EntryService $entryService
     * @param DeleteSwitcherService $dSwitchService
     * @param CommonArchiveService $commonArchiveService
     * @param LoggingService $loggingService
     */

    public function __construct(EntityManagerInterface $entityManager,
                                ContainerInterface $container,
                                EntryService $entryService,
                                DeleteSwitcherService $dSwitchService,
                                CommonArchiveService $commonArchiveService,
                                LoggingService $loggingService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->entryService = $entryService;
        $this->commonArchiveService = $commonArchiveService;
        $this->loggingService = $loggingService;
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->dSwitchService = $dSwitchService;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->pathPermissions = $this->container->getParameter('archive.storage_permissions');
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
            ->setRemovalMark(false)
            ->setMarkedByUser(null)
            ->setSlug(null);
    }

    /**
     * @param FormInterface $folderAddForm
     * @param User $user
     * @param int $entryId
     */

    public function createNewFolder(FormInterface $folderAddForm, User $user, int $entryId)
    {
        try {
            $newFolderEntity = $this->prepareNewFolder($folderAddForm, $user);
            $fileSystem = new Filesystem();
            $newFolderAbsPath = $this->pathRoot;
            $pathPermissions = $this->pathPermissions;
            $creationNotFailed = true;
            $directoryExistedPreviously = false;

            if ($fileSystem->exists($newFolderAbsPath)) {
                try {
                    $binaryPath = $this->getPath($newFolderEntity->getParentFolder());
                    foreach ($binaryPath as $folderName) {
                        $newFolderAbsPath .= "/" . $folderName;
                        if (!$fileSystem->exists($newFolderAbsPath)) {
                            $this->container->get('session')->getFlashBag()->add('warning', 'Директория ' . $newFolderAbsPath . ' отсутствует в файловой системе. Пересоздаю...');
                            try {
                                $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                                $this->container->get('session')->getFlashBag()->add('success', 'Директория ' . $newFolderAbsPath . ' cоздана.');
                            } catch (IOException $IOException) {
                                $this->container->get('session')->getFlashBag()->add('danger', 'Директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                                $this->container->get('session')->getFlashBag()->add('danger', 'Загрузка в БД прервана: изменения не внесены.');
                                $creationNotFailed = false;
                            }
                        }
                    }
                    $newFolderAbsPath .= "/" . $newFolderEntity->getFolderName();
                    if (!$fileSystem->exists($newFolderAbsPath)) {
                        try {
                            $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                            $this->container->get('session')->getFlashBag()->add('success', 'Новая директория ' . $newFolderEntity->getFolderName() . ' успешно создана.');
                        } catch (IOException $IOException) {
                            $this->container->get('session')->getFlashBag()->add('danger', 'Новая директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                            $creationNotFailed = false;
                        }
                    } else {
                        $directoryExistedPreviously = true;
                        $this->container->get('session')->getFlashBag()->add('warning', 'Директория ' . $newFolderAbsPath . ' уже существует в файловой системе.');
                    }
                } catch (\Exception $exception) {
                    $this->container->get('session')->getFlashBag()->add('danger', 'Новая директория не записана в файловую систему. Ошибка файловой системы: ' . $exception->getMessage());
                }
            } else {
                $this->container->get('session')->getFlashBag()->add('danger', 'Файловая система архива недоступна. Операция не выполнена.');
            }
            if ($creationNotFailed) {
                try {
                    $this->persistFolder($newFolderEntity);
                    $this->entryService->updateEntryInfo($this->entryService->getEntryById($entryId), $user, true);
                    $this->container->get('session')->getFlashBag()->add('success', 'Новая директория успешно добавлена в БД');
                } catch (\Exception $exception) {
                    if ($exception instanceof ConstraintViolationException) {
                        $this->container->get('session')->getFlashBag()->add('danger', ' В БД найдена запись о дубликате создаваемой директории. Именения БД отклонены.');
                    } else {
                        $this->container->get('session')->getFlashBag()->add('danger', 'Директория не записана в БД. Ошибка БД: ' . $exception->getMessage());
                    }
                    if (!$directoryExistedPreviously) {
                        try {
                            $fileSystem->remove($newFolderAbsPath);
                            $this->container->get('session')->getFlashBag()->add('danger', 'Новая директория удалёна из файловой системы в связи с ошибкой БД.');
                        } catch (IOException $IOException) {
                            $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка при удалении новой директории из файловой системы: ' . $IOException->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
        }
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
            ->setRemovalMark(false)
            ->setMarkedByUser(null)
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
     * @param FolderEntity $folder
     * @param User $user
     */
    public function renameFolder(FolderEntity $folder, User $user)
    {
        $originalFolder = $this->getOriginalData($folder);
        if ($originalFolder['folderName'] != $folder->getFolderName()) {
            if ($this->moveFolder($folder, $originalFolder)) {
                $this->flushFolder();
                $this->entryService->updateEntryInfo($folder->getRoot()->getArchiveEntry(), $user, true);
                $this->container->get('session')->getFlashBag()->add('success', 'Переименование ' . $originalFolder['folderName'] . ' > ' . $folder->getFolderName() . ' успешно произведено.');
            } else {
                $this->container->get('session')->getFlashBag()->add('danger', 'Переименование отменено из за внутренней ошибки.');
            }
        } else {
            $this->container->get('session')->getFlashBag()->add('warning', 'Новое имя каталога ' . $folder->getFolderName() . ' совпадает с текущим. Операция отклонена.');
        }
        $this->loggingService->logEntryContent($folder->getRoot()->getArchiveEntry()->getId(), $user, $this->container->get('session')->getFlashBag()->peekAll());
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
        $this->entryService->updateEntryInfo($removedFolder->getRoot()->getArchiveEntry(), $user, false);

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
            $this->entryService->updateEntryInfo($restoredFolder->getRoot()->getArchiveEntry(), $user, false);
        }

        return $foldersArray;
    }

    /**
     * @param FolderEntity $folderEntity
     */

    public function unsetFolderRemovalMark(FolderEntity $folderEntity)
    {
        $folderEntity
            ->setRemovalMark(false)
            ->setMarkedByUser(null)
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
        $requestedFolder = $this->getParentFolder($folderId);
        $binaryPath = $this->getPath($requestedFolder);
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
                        ->setRequestsCount($folder->getRequestsCount());
                }
            }
        }
        $this->em->flush();
        $this->entryService->updateEntryInfo($requestedFolder->getRoot()->getArchiveEntry(), $user, false);

        return $requestedFolder;
    }

    /**
     * @param array $foldersArray
     * @param FileService $fileService
     * @param User $user
     */

    public function deleteFolders(array $foldersArray, FileService $fileService, User $user)
    {
        $deletedFolders = $this->foldersRepository->find($foldersArray);
        foreach ($deletedFolders as $folder) {
            $this->deleteFolder($folder, $fileService, true, $user);
        }
        $this->entryService->updateEntryInfo($deletedFolders[0]->getRoot()->getArchiveEntry(), $user, true);
    }

    /**
     * @param FolderEntity $folderEntity
     * @param FileService $fileService
     * @param bool $multiple
     * @param User $user
     */

    public function deleteFolder(FolderEntity $folderEntity, FileService $fileService, bool $multiple, User $user)
    {
        $foldersChain = $this->foldersRepository->getChildren($folderEntity, false, null, null, true);
        foreach ($foldersChain as $folder) {
            if (!$folder->getDeleted()) {
                $originalFolder['folderName'] = $folder->getFolderName();
                $folder->setFolderName($this->changeFolderName($folder->getFolderName(), true));
                if ($this->moveFolder($folder, $originalFolder, false)) {
                    $folder->setDeleted(true);
                    $this->commonArchiveService->changeDeletesQuantity($folder->getParentFolder(), true);
                }
            }
            $this->deleteFilesByParentFolder($folder, $fileService, $user);
        }
        $this->em->flush();
        if (!$multiple) {
            $this->entryService->updateEntryInfo($folderEntity->getRoot()->getArchiveEntry(), $user, true);
        }
    }

    /**
     * @param FolderEntity $folder
     * @param FileService $fileService
     * @param User $user
     */

    private function deleteFilesByParentFolder(FolderEntity $folder, FileService $fileService, User $user)
    {
        $childFiles = $folder->getFiles();
        if ($childFiles) {
            foreach ($childFiles as $childFile) {
                if (!$childFile->getDeleted()) {
                    $fileService->deleteFile($childFile, false, $user);
                }
            }
        }
    }

    /**
     * @param array $foldersArray
     * @param User $user
     * @return array
     */

    public function unDeleteFolders(array $foldersArray, User $user)
    {
        $folderIdsArray = [];
        $unDeletedFolders = $this->foldersRepository->find($foldersArray);
        foreach ($unDeletedFolders as $folder) {
            $folderIdsArray = $this->unDeleteFolder($folder, $folderIdsArray, true, $user);
        }
        $this->entryService->updateEntryInfo($unDeletedFolders[0]->getRoot()->getArchiveEntry(), $user, true);

        return $folderIdsArray;
    }

    /**
     * @param FolderEntity $folderEntity
     * @param array $folderIdsArray
     * @param bool $multiple
     * @return array
     */

    public function unDeleteFolder(FolderEntity $folderEntity, array $folderIdsArray, bool $multiple, User $user)
    {
        $folderIdsArray['remove'] = [];
        $folderIdsArray['reload'] = [];
        $binaryPath = $this->getPath($folderEntity);
        foreach ($binaryPath as $folder) {
            if ($folder->getDeleted() === true) {
                $originalFolder['folderName'] = $folder->getFolderName();
                $folder->setFolderName($this->changeFolderName($folder->getFolderName(), false));
                if ($this->moveFolder($folder, $originalFolder, false)) {
                    $folder->setDeleted(false);
                    $folder->setFolderName($this->changeFolderName($folder->getFolderName(), false));
                    if ($folder->getRoot()->getId() !== $folder->getId()) {
                        $this->commonArchiveService->changeDeletesQuantity($folder->getParentFolder(), false);
                        $i = ($folder->getDeletedChildren() === 0) ? 'remove' : 'reload';
                        $folderIdsArray[$i][] = $this->commonArchiveService->addFolderIdToArray($folder, $folderIdsArray, $i);
                    }
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
        if (!$multiple) {
            $this->entryService->updateEntryInfo($folderEntity->getRoot()->getArchiveEntry(), $user, true);
        }
        array_reverse($folderIdsArray['remove']);

        return $folderIdsArray;
    }

    /**
     * @param string $folderName
     * @param bool $condition
     * @return mixed|string
     */

    private function changeFolderName(string $folderName, bool $condition)
    {
        $deleted = '_deleted_';
        $restored = '_restored_';
        $delPosIndex = strrpos($folderName, $deleted);
        $resPosIndex = strrpos($folderName, $restored);
        if ($condition === false) {
            if ($delPosIndex != $condition) {

                return substr_replace($folderName, $restored, $delPosIndex, strlen($deleted));
            } elseif ($resPosIndex == $condition) {

                return $folderName . $restored . (hash('crc32', uniqid(), false));
            }
        } elseif ($condition === true) {
            if ($resPosIndex == $condition) {

                return substr_replace($folderName, $deleted, $resPosIndex, strlen($restored));
            } elseif ($delPosIndex != $condition) {

                return $folderName . $deleted . (hash('crc32', uniqid(), false));
            }
        }

        return $folderName;
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

    public function flushFolder()
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
     * @param array $originalEntry
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @return string
     */

    public function moveEntryFolder(array $originalEntry, ArchiveEntryEntity $archiveEntryEntity)
    {
        $oldPath = $this->entryService->constructExistingPath($originalEntry);
        $newPath = $this->commonArchiveService->checkAndCreateFolders($archiveEntryEntity, false, false);
        $fs = new Filesystem();
        $fs->rename($oldPath, $newPath);
        $newEntryFile = $newPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".entry";
        if ($originalEntry['archiveNumber'] != $archiveEntryEntity->getArchiveNumber()) {
            $oldEntryFile = $newPath . "/" . $originalEntry["archiveNumber"] . ".entry";
            $fs->rename($oldEntryFile, $newEntryFile);
        }

        return $newEntryFile;
    }
}
