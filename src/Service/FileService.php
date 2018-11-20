<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;

/**
 * Class FileService
 * @package App\Services
 */
class FileService
{
    protected $em;
    protected $container;
    protected $session;
    protected $folderService;
    protected $filesRepository;
    protected $entryService;
    protected $pathRoot;
    protected $deletedFolder;
    protected $commonArchiveService;
    protected $dSwitchService;
    protected $fileChecksumService;
    protected $loggingService;
    protected $serializerService;

    /**
     * FileService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param FolderService $folderService
     * @param EntryService $entryService
     * @param DeleteSwitcherService $dSwitchService
     * @param CommonArchiveService $commonArchiveService
     * @param FileChecksumService $fileChecksumService
     * @param LoggingService $loggingService
     * @param SerializerService $serializerService
     */

    public function __construct(EntityManagerInterface $entityManager,
                                ContainerInterface $container,
                                FolderService $folderService,
                                EntryService $entryService,
                                DeleteSwitcherService $dSwitchService,
                                CommonArchiveService $commonArchiveService,
                                FileChecksumService $fileChecksumService,
                                LoggingService $loggingService,
                                SerializerService $serializerService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->session = $this->container->get('session');
        $this->folderService = $folderService;
        $this->entryService = $entryService;
        $this->commonArchiveService = $commonArchiveService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->dSwitchService = $dSwitchService;
        $this->fileChecksumService = $fileChecksumService;
        $this->loggingService = $loggingService;
        $this->serializerService = $serializerService;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
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
        $binaryPath = $this->folderService->getPath($requestedFile->getParentFolder());
        if ($slashDirection)
        {
            $slash = '/';
        } else {
            $slash = '\\';
            $binaryPath[0] = str_replace('/', '\\', $binaryPath[0]);
        }
        foreach ($binaryPath as $folderName) {
            $path .= $folderName . $slash;
        }

        return $path . $requestedFile->getFileName();
    }

    /**
     * @param FileEntity $fileEntity
     * @param bool $deleted
     * @return string
     */

    public function getFileHTTPUrl(FileEntity $fileEntity, bool $deleted)
    {
        $filePath = $this->getFilePath($fileEntity, true);

        return $this->container->getParameter('archive.http_path') . '/' . ($deleted ? '/' . $this->deletedFolder : '') . $filePath;
    }

    /**
     * @param FileEntity $fileEntity
     * @param bool $deleted ;
     * @return string
     */

    public function getFileSharePath(FileEntity $fileEntity, bool $deleted)
    {
        $fileAbsPath = $this->getFilePath($fileEntity, false);

        return $this->container->getParameter('archive.share_path') . ($deleted ? '\\' . $this->deletedFolder : '') . '\\' . $fileAbsPath;
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
     * @param FormInterface $fileAddForm
     * @param User $user
     * @param int $entryId
     */

    public function uploadFiles(FormInterface $fileAddForm, User $user, int $entryId)
    {
        try {
            $parentFolder = null;
            $folderAbsPath = null;
            $uploadNotFailed = true;
            $newFilesArray = $fileAddForm->getData();
            $this->session->getFlashBag()->clear();
            try {
                $parentFolder = $this->folderService->getParentFolder($fileAddForm->get('parentFolder')->getViewData());
                $folderAbsPath = $this->folderService->constructFolderAbsPath($parentFolder);
            } catch (\Exception $exception) {
                $this->session->getFlashBag()->add('danger', "Ошибка создания пути: " . $exception->getMessage());
            }
            try {
                $passed = 0;
                $errors = 0;
                foreach ($newFilesArray->getUploadedFiles() as $newFile) {
                    $newFileEntity = $this->createFileEntityFromArray($newFilesArray, $newFile);
                    $originalName = pathinfo($newFileEntity->getFileName()->getClientOriginalName(), PATHINFO_FILENAME) . "-" . (hash('crc32', uniqid(), false) . "." . $newFileEntity->getFileName()->getClientOriginalExtension());
                    $fileWithAbsPath = $this->constructFileAbsPath($folderAbsPath, $originalName);
                    $fileSystem = new Filesystem();
                    if (!$fileSystem->exists($fileWithAbsPath)) {
                        $fileExistedPreviously = false;
                        try {
                            $newFileEntity->getFileName()->move($folderAbsPath, $originalName);
                            $this->prepareNewFile($newFileEntity, $parentFolder, $originalName, $user);
                            $newFileEntity->setChecksum(md5_file($fileWithAbsPath));
                            $this->session->getFlashBag()->add('success', 'Новый документ ' . $originalName . ' записан в директорию ' . $parentFolder);
                        } catch (\Exception $exception) {
                            $uploadNotFailed = false;
                            $this->session->getFlashBag()->add('danger', 'Новый документ не записан в директорию. Ошибка файловой системы: ' . $exception->getMessage());
                            $this->session->getFlashBag()->add('danger', 'Загрузка в БД прервана: изменения не внесены.');
                            $errors++;
                        }
                    } else {
                        $fileExistedPreviously = true;
                        $this->session->getFlashBag()->add('danger', 'Документ с таким именем уже существует в директории назначения. Перезапись отклонена.');
                        $errors++;
                    }
                    if ($uploadNotFailed) {
                        try {
                            $this->persistFile($newFileEntity);
                            $this->session->getFlashBag()->add('success', 'Новый документ добавлен в БД');
                            $passed++;
                        } catch (\Exception $exception) {
                            if ($exception instanceof ConstraintViolationException) {
                                $this->session->getFlashBag()->add('danger', ' В БД найдена запись о дубликате загружаемого документа. Именения БД отклонены.' . $exception->getMessage());
                            } else {
                                $this->session->getFlashBag()->add('danger', 'Документ не записан в БД. Ошибка БД: ' . $exception->getMessage());
                            }
                            if (!$fileExistedPreviously) {
                                try {
                                    $fileSystem->remove($fileWithAbsPath);
                                    $this->session->getFlashBag()->add('danger', 'Новый документ удалён из директории в связи с ошибкой БД.');
                                } catch (IOException $IOException) {
                                    $this->session->getFlashBag()->add('danger', 'Ошибка файловой системы при удалении загруженного документа: ' . $IOException->getMessage());
                                };
                            }
                            $errors++;
                        }
                    };
                }
                if ($passed != 0) {
                    $this->session->getFlashBag()->add('passed', $passed . ' файлов успешно загружено.');
                    $this->entryService->updateEntryInfo($this->entryService->getEntryById($entryId), $user, true);
                }
                if ($errors != 0) {
                    $this->session->getFlashBag()->add('errors', $errors . ' ошибок при загрузке.');
                }
            } catch (\Exception $exception) {
                $this->session->getFlashBag()->add('danger', "Ошибка загрузки файла(ов) : " . $exception->getMessage());
            }
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
        }
    }

    /**
     * @param FileEntity $fileArrayEntity
     * @param $file
     * @return FileEntity
     */

    private function createFileEntityFromArray(FileEntity $fileArrayEntity, $file)
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

    private function prepareNewFile(FileEntity $newFileEntity, FolderEntity $parentFolder, string $originalName, User $user)
    {
        $parentFolder = $this->folderService->getParentFolder($parentFolder);
        $newFileEntity
            ->setParentFolder($parentFolder)
            ->setFileName($originalName)
            ->setAddedByUser($user)
            ->setRemovalMark(false)
            ->setSlug(null)
            ->setmarkedByUser(null);
    }

    /**
     * @param FileEntity $fileEntity
     */

    private function persistFile(FileEntity $fileEntity)
    {
        $this->em->persist($fileEntity);
        $this->em->flush();
    }

    /**
     * @param int $fileId
     * @param User $user
     * @return mixed
     */

    public function removeFile(int $fileId, User $user)
    {
        $markedFiles = $this->filesRepository->findById($fileId);
        $archiveEntryEntity = $markedFiles[0]->getParentFolder()->getRoot()->getArchiveEntry();
        try {
            foreach ($markedFiles as $file) {
                $file
                    ->setRemovalMark(true)
                    ->setMarkedByUser($user);
            }
            $this->em->flush();
            $this->entryService->updateEntryInfo($archiveEntryEntity, $user, false);
            $this->session->getFlashBag()->add('success', 'Файл(ы) успешно помечены на удаление.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Файл(ы) не помечены на удаление: ' . $exception->getMessage());
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());

        return $markedFiles;
    }

    /**
     * @param int $fileId
     * @param User $user
     * @param FolderService $folderService
     * @return mixed
     */

    public function requestFile(int $fileId, User $user, FolderService $folderService)
    {
        $requestedFiles = $this->filesRepository->findById($fileId);
        $archiveEntryEntity = $requestedFiles[0]->getParentFolder()->getRoot()->getArchiveEntry();
        try {
            foreach ($requestedFiles as $file) {
                if ($file->getRequestMark()) {
                    $users = $file->getRequestedByUsers();
                    if ((array_search($user->getId(), $users, true)) === false) {
                        $users[] = $user->getId();
                    }
                } else {
                    $users[] = $user->getId();
                }
                $file
                    ->setRequestMark(true)
                    ->setRequestedByUsers($users);
                $folderService->requestFolder($file->getParentFolder()->getId(), $user);
            }
            $this->em->flush();
            $this->entryService->updateEntryInfo($archiveEntryEntity, $user, false);
            $this->session->getFlashBag()->add('success', 'Запрос на восстановление файла(ов) выполнен успешно.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Запрос на восстановление файла(ов) выполнен некорректно:  ' . $exception->getMessage());
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());

        return $requestedFiles;
    }

    /**
     * @param int $fileId
     * @param User $user
     * @return mixed
     */

    public function restoreFile(int $fileId, User $user)
    {
        $restoredFiles = $this->filesRepository->findById($fileId);
        $archiveEntryEntity = $restoredFiles[0]->getParentFolder()->getRoot()->getArchiveEntry();
        try {
            foreach ($restoredFiles as $file) {
                $file
                    ->setRemovalMark(false)
                    ->setMarkedByUser(null)
                    ->setRequestMark(false)
                    ->setRequestedByUsers(null);
            }
            $this->em->flush();
            $this->entryService->updateEntryInfo($archiveEntryEntity, $user, false);
            $this->session->getFlashBag()->add('success', 'Метка на удаление файла(ов) снята успешно.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Снятие метки на удаление не выполнено:  ' . $exception->getMessage());
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());

        return $restoredFiles;
    }

    /**
     * @param array $filesArray
     * @param User $user
     * @return int $status
     */

    public function deleteFiles(array $filesArray, User $user)
    {
        $deletedFiles = $this->filesRepository->find($filesArray);
        $archiveEntryEntity = $deletedFiles[0]->getParentFolder()->getRoot()->getArchiveEntry();
        try {
            //@TODO: to be improved
            $status = 1;
            foreach ($deletedFiles as $file) {
                $status = $this->deleteFile($file, true, null);
            }
            $this->entryService->updateEntryInfo($archiveEntryEntity, $user, true);
            $this->session->getFlashBag()->add('success', 'Файл(ы) успешно удалены.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Удалене файла(ов) не выполнено:  ' . $exception->getMessage());
            $status = 0;
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());

        return $status;
    }

    /**
     * @param FileEntity $file
     * @param bool $multiple
     * @param User $user
     * @return int $status
     */

    public function deleteFile(FileEntity $file, bool $multiple, User $user)
    {
        try {
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
            $archiveEntry = $file->getParentFolder()->getRoot()->getArchiveEntry();
            if ($this->moveFile($file, $originalFile) === true) {
                $file->setDeleted(true);
                $this->commonArchiveService->changeDeletesQuantity($file->getParentFolder(), true);
                $this->em->flush();
                if (!$multiple) {
                    $this->entryService->updateEntryInfo($archiveEntry, $user, true);
                }
            }
            $this->session->getFlashBag()->add('success', 'Файл ' . $file->getFileName() . ' успешно удалён');
            $this->loggingService->logEntryContent($archiveEntry, $user, $this->session->getFlashBag()->peekAll());
            $status = 1;
        } catch (\Exception $exception)
        {
            $this->session->getFlashBag()->add('danger', 'Файл ' . $file->getFileName() . ' не удалён из за непредвиденной ошибки: ' . $exception->getMessage());
            $status = 0;
        }
        $this->loggingService->logEntryContent($file->getParentFolder()->getRoot()->getArchiveEntry(), $user, $this->session->getFlashBag()->peekAll());

        return $status;
    }

    /**
     * @param array $filesArray
     * @param User $user
     * @return array
     */

    public function unDeleteFiles(array $filesArray, User $user)
    {
        $folderIdsArray = [];
        $unDeletedFiles = $this->filesRepository->find($filesArray);
        $archiveEntryEntity = $unDeletedFiles[0]->getParentFolder()->getRoot()->getArchiveEntry();
        try {
            foreach ($unDeletedFiles as $file) {
                $folderIdsArray = $this->unDeleteFile($file, $folderIdsArray, true, null);
            }
            $this->entryService->updateEntryInfo($archiveEntryEntity, $user, true);
            $this->session->getFlashBag()->add('success', 'Файлы успешно восстановлены.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Файлы не восстановлены: ' . $exception->getMessage());
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());

        return $folderIdsArray;
    }

    /**
     * @param FileEntity $file
     * @param array $folderIdsArray
     * @param bool $multiple
     * @param User $user
     * @return array
     */

    public function unDeleteFile(FileEntity $file, array $folderIdsArray, bool $multiple, User $user)
    {
        $folderIdsArray['remove'] = [];
        $folderIdsArray['reload'] = [];
        $deleted = '_deleted_';
        $restored = '_restored_';
        $originalFile['fileName'] = $file->getFileName();
        try {
            $delPosIndex = strrpos($file->getFileName(), $deleted);
            $resPosIndex = strrpos($file->getFileName(), $restored);
            if ($delPosIndex) {
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
                if (!$multiple) {
                    $this->entryService->updateEntryInfo($file->getParentFolder()->getRoot()->getArchiveEntry(), $user, true);
                }
            }
            array_reverse($folderIdsArray['remove']);
            $this->session->getFlashBag()->add('success', 'Файл ' . $originalFile['fileName'] . ' успешно восстановлен.');
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Файл ' . $originalFile['fileName'] . ' не восстановлен. Ошибка' . $exception->getMessage());
        }
        $this->loggingService->logEntryContent($file->getParentFolder()->getRoot()->getArchiveEntry(), $user, $this->session->getFlashBag()->peekAll());

        return $folderIdsArray;
    }

    /**
     * @param FileEntity $file
     * @param User $user
     */

    public function renameFile(FileEntity $file, User $user)
    {
        $originalFile = $this->getOriginalData($file);
        $archiveEntryEntity = $file->getParentFolder()->getRoot()->getArchiveEntry();
        if ($file->getDeleted() !== true) {
            if ($originalFile['fileName'] != $file->getFileName()) {
                if ($this->moveFile($file, $originalFile)) {
                    $this->flushFile();
                    $this->entryService->updateEntryInfo($archiveEntryEntity, $user, true);
                    $this->session->getFlashBag()->add('success', 'Переименование ' . $originalFile['fileName'] . ' > ' . $file->getFileName() . ' успешно произведено.');
                } else {
                    $this->session->getFlashBag()->add('danger', 'Переименование отменено из за внутренней ошибки.');
                }
            } else {
                $this->session->getFlashBag()->add('warning', 'Новое имя файла ' . $file->getFileName() . ' совпадает с текущим. Операция отклонена.');
            }
        } else {
            $this->session->getFlashBag()->add('warning', 'Переименование удалённого файла запрещено');
        }
        $this->loggingService->logEntryContent($archiveEntryEntity, $user, $this->session->getFlashBag()->peekAll());
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
            $this->session->getFlashBag()->add('danger', 'Ошибка при получении информации о файле из базы данных :' . $exception->getMessage());

            return false;
        }
        try {
            $fs = new Filesystem();
            $fs->rename($absPath . "/" . $originalFile["fileName"], $absPath . "/" . $newFile->getFileName());

            return true;
        } catch (\Exception $exception) {
            $this->session->getFlashBag()->add('danger', 'Ошибка файловой системы при переименовании файла :' . $exception->getMessage());

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
}
