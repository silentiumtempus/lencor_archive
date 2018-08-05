<?php

namespace App\Controller;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use App\Form\FileAddForm;
use App\Form\FileRenameForm;
use App\Form\FolderAddForm;
use App\Form\FolderRenameForm;
use App\Service\DeleteService;
use App\Service\EntryService;
use App\Service\FileChecksumService;
use App\Service\FileService;
use App\Service\FolderService;
use App\Service\LoggingService;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class FilesAndFoldersController
 * @package App\Controller
 */
class FilesAndFoldersController extends Controller
{
    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param EntryService $archiveEntryService
     * @param LoggingService $loggingService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/new_folder",
     *     options = { "expose" = true },
     *     name = "entries_new_folder")
     */

    public function createNewFolder(Request $request, FolderService $folderService, EntryService $archiveEntryService, LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $folderId = $archiveEntryService->setFolderId($request);
        $entryId = $folderService->getFolderEntry($folderId)->getId();
        $user = $this->getUser();
        $newFolder = new FolderEntity();
        $isRoot = $folderService->isRoot($folderId);

        $folderAddForm = $this->createForm(
            FolderAddForm::class,
            $newFolder,
            array('action' => $this->generateUrl('entries_new_folder'), 'attr' => array('isRoot' => $isRoot, 'folderId' => $folderId, 'id' => 'folder_add_form'))
        );

        $folderAddForm->handleRequest($request);
        if ($folderAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($folderAddForm->isValid()) {
                try {
                    $newFolderEntity = $folderService->prepareNewFolder($folderAddForm, $user);
                    $fileSystem = new Filesystem();
                    $newFolderAbsPath = $this->getParameter('lencor_archive.storage_path');
                    $pathPermissions = $this->getParameter('lencor_archive.storage_permissions');
                    $creationNotFailed = true;
                    $directoryExistedPreviously = false;

                    if ($fileSystem->exists($newFolderAbsPath)) {
                        try {
                            $binaryPath = $folderService->getPath($newFolderEntity->getParentFolder());
                            foreach ($binaryPath as $folderName) {
                                $newFolderAbsPath .= "/" . $folderName;
                                if (!$fileSystem->exists($newFolderAbsPath)) {
                                    $this->addFlash('warning', 'Директория ' . $newFolderAbsPath . ' отсутствует в файловой системе. Пересоздаю...');
                                    try {
                                        $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                                        $this->addFlash('success', 'Директория ' . $newFolderAbsPath . ' cоздана.');
                                    } catch (IOException $IOException) {
                                        $this->addFlash('danger', 'Директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                                        $this->addFlash('danger', 'Загрузка в БД прервана: изменения не внесены.');
                                        $creationNotFailed = false;
                                    }
                                }
                            }
                            $newFolderAbsPath .= "/" . $newFolderEntity->getFolderName();
                            if (!$fileSystem->exists($newFolderAbsPath)) {
                                try {
                                    $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                                    $this->addFlash('success', 'Новая директория ' . $newFolderEntity->getFolderName() . ' успешно создана.');
                                } catch (IOException $IOException) {
                                    $this->addFlash('danger', 'Новая директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                                    $creationNotFailed = false;
                                }
                            } else {
                                $directoryExistedPreviously = true;
                                $this->addFlash('warning', 'Директория ' . $newFolderAbsPath . ' уже существует в файловой системе.');
                            }
                        } catch (\Exception $exception) {
                            $this->addFlash('danger', 'Новая директория не записана в файловую систему. Ошибка файловой системы: ' . $exception->getMessage());
                        }
                    } else {
                        $this->addFlash('danger', 'Файловая система архива недоступна. Операция не выполнена.');
                    }
                    if ($creationNotFailed) {
                        try {
                            $folderService->persistFolder($newFolderEntity);
                            $archiveEntryService->changeLastUpdateInfo($entryId, $user);
                            $this->addFlash('success', 'Новая директория успешно добавлена в БД');
                        } catch (\Exception $exception) {
                            if ($exception instanceof ConstraintViolationException) {
                                $this->addFlash('danger', ' В БД найдена запись о дубликате создаваемой директории. Именения БД отклонены.');
                            } else {
                                $this->addFlash('danger', 'Директория не записана в БД. Ошибка БД: ' . $exception->getMessage());
                            }
                            if (!$directoryExistedPreviously) {
                                try {
                                    $fileSystem->remove($newFolderAbsPath);
                                    $this->addFlash('danger', 'Новая директория удалёна из файловой системы в связи с ошибкой БД.');
                                } catch (IOException $IOException) {
                                    $this->addFlash('danger', 'Ошибка при удалении новой директории из файловой системы: ' . $IOException->getMessage());
                                }
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Директория ' . $folderAddForm->getName() . ' уже существует в каталоге ' . $folderAddForm->getParent() . '. Операция прервана');
            }
            $loggingService->logEntryContent($entryId, $this->getUser(), $session->getFlashBag()->peekAll());
        }
        return $this->render('lencor/admin/archive/archive_manager/new_folder.html.twig', array('folderAddForm' => $folderAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @param EntryService $archiveEntryService
     * @param LoggingService $loggingService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/new_file",
     *     options = { "expose" = true },
     *     name = "entries_new_file")
     */

    public function uploadNewFile(Request $request, FileService $fileService, FolderService $folderService, EntryService $archiveEntryService, LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $folderId = $archiveEntryService->setFolderId($request);
        $entryId = $folderService->getFolderEntry($folderId)->getId();
        $newFile = new FileEntity();
        $user = $this->getUser();
        $isRoot = $folderService->isRoot($folderId);

        $fileAddForm = $this->createForm(
            FileAddForm::class,
            $newFile,
            array('action' => $this->generateUrl('entries_new_file'), 'method' => 'POST', 'attr' => array('isRoot' => $isRoot, 'folderId' => $folderId, 'id' => 'file_add_form'))
        );

        $fileAddForm->handleRequest($request);
        if ($fileAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($fileAddForm->isValid()) {
                try {
                    $parentFolder = null;
                    $folderAbsPath = null;
                    $uploadNotFailed = true;
                    $newFilesArray = $fileAddForm->getData();
                    $this->get('session')->getFlashBag()->clear();
                    try {
                        $parentFolder = $folderService->getParentFolder($fileAddForm->get('parentFolder')->getViewData());
                        $folderAbsPath = $folderService->constructFolderAbsPath($parentFolder);
                    } catch (\Exception $exception) {
                        $this->addFlash('danger', "Ошибка создания пути: " . $exception->getMessage());
                    }
                    try {
                        $passed = 0;
                        $errors = 0;
                        foreach ($newFilesArray->getFiles() as $newFile) {
                            $newFileEntity = $fileService->createFileEntityFromArray($newFilesArray, $newFile);
                            $originalName = pathinfo($newFileEntity->getFileName()->getClientOriginalName(), PATHINFO_FILENAME) . "-" . (hash('crc32', uniqid(), false) . "." . $newFileEntity->getFileName()->getClientOriginalExtension());
                            $fileWithAbsPath = $fileService->constructFileAbsPath($folderAbsPath, $originalName);
                            $fileSystem = new Filesystem();
                            if (!$fileSystem->exists($fileWithAbsPath)) {
                                $fileExistedPreviously = false;
                                try {
                                    $newFileEntity->getFileName()->move($folderAbsPath, $originalName);
                                    $fileService->prepareNewFile($newFileEntity, $parentFolder, $originalName, $user);
                                    $newFileEntity->setChecksum(md5_file($fileWithAbsPath));
                                    $this->addFlash('success', 'Новый документ ' . $originalName . ' записан в директорию ' . $parentFolder);
                                } catch (\Exception $exception) {
                                    $uploadNotFailed = false;
                                    $this->addFlash('danger', 'Новый документ не записан в директорию. Ошибка файловой системы: ' . $exception->getMessage());
                                    $this->addFlash('danger', 'Загрузка в БД прервана: изменения не внесены.');
                                    $errors++;
                                }
                            } else {
                                $fileExistedPreviously = true;
                                $this->addFlash('danger', 'Документ с таким именем уже существует в директории назначения. Перезапись отклонена.');
                                $errors++;
                            }

                            if ($uploadNotFailed) {
                                try {
                                    $fileService->persistFile($newFileEntity);
                                    $archiveEntryService->changeLastUpdateInfo($entryId, $user);
                                    $this->addFlash('success', 'Новый документ добавлен в БД');
                                    $passed++;
                                } catch (\Exception $exception) {
                                    if ($exception instanceof ConstraintViolationException) {
                                        $this->addFlash('danger', ' В БД найдена запись о дубликате загружаемого документа. Именения БД отклонены.' . $exception->getMessage());
                                    } else {
                                        $this->addFlash('danger', 'Документ не записан в БД. Ошибка БД: ' . $exception->getMessage());
                                    }
                                    if (!$fileExistedPreviously) {
                                        try {
                                            $fileSystem->remove($fileWithAbsPath);
                                            $this->addFlash('danger', 'Новый документ удалён из директории в связи с ошибкой БД.');
                                        } catch (IOException $IOException) {
                                            $this->addFlash('danger', 'Ошибка файловой системы при удалении загруженного документа: ' . $IOException->getMessage());
                                        };
                                    }
                                    $errors++;
                                }
                            };
                        }
                        if ($passed != 0) {
                            $this->addFlash('passed', $passed . ' файлов успешно загружено.');
                        }
                        if ($errors != 0) {
                            $this->addFlash('errors', $errors . ' ошибок при загрузке.');
                        }
                    } catch (\Exception $exception) {
                        $this->addFlash('danger', "Ошибка загрузки файла(ов) : " . $exception->getMessage());
                    }
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Операция не выполнена.');
            }
            $loggingService->logEntryContent($entryId, $this->getUser(), $session->getFlashBag()->peekAll());
        }

        return $this->render('lencor/admin/archive/archive_manager/new_file.html.twig', array('fileAddForm' => $fileAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/remove_file",
     *     options = { "expose" = true },
     *     name = "entries_remove_file")
     */
    //@TODO: Unite two methods below
    public function removeFile(Request $request, FileService $fileService)
    {
        $deletedFile = $fileService->removeFile($request->get('fileId'), $this->getUser());

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $deletedFile));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/entries/restore_file",
     *     options = { "expose" = true },
     *     name = "entries_restore_file")
     */

    public function restoreFile(Request $request, FileService $fileService)
    {
        $restoredFile = $fileService->restoreFile($request->get('fileId'), $this->getUser());

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $restoredFile));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/request_file",
     *     options = { "expose" = true },
     *     name = "entries_request_file")
     */

    public function requestFile(Request $request, FileService $fileService, FolderService $folderService)
    {
        $requestedFile = $fileService->requestFile($request->get('fileId'), $this->getUser(), $folderService);

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $requestedFile));
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param FileService $fileService
     * @param LoggingService $loggingService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/rename_file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_rename_file")
     * @ParamConverter("file", class = "App:FileEntity", options = { "id" = "file" })
     */

    public function renameFile(Request $request, FileEntity $file, FileService $fileService, LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $form_id = 'file_rename_form_' . $file->getId();
        $fileRenameForm = $this->createForm(FileRenameForm::class, $file, array('attr' => array('id' => $form_id)));
        $fileRenameForm->handleRequest($request);
        if ($fileRenameForm->isSubmitted()) {
            if ($fileRenameForm->isValid()) {
                $originalFile = $fileService->getOriginalData($file);
                if ($originalFile['fileName'] != $file->getFileName()) {
                    if ($fileService->moveFile($file, $originalFile)) {
                        $fileService->flushFile();
                        $this->addFlash('success', 'Переименование ' . $originalFile['fileName'] . ' > ' . $file->getFileName() . ' успешно произведено.');
                    } else {
                        $this->addFlash('danger', 'Переименование отменено из за внутренней ошибки.');
                    }
                } else {
                    $this->addFlash('warning', 'Новое имя файла ' . $file->getFileName() . ' совпадает с текущим. Операция отклонена.');
                }
                $loggingService->logEntryContent($file->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());

                return $this->render('lencor/admin/archive/archive_manager/file.html.twig', array('file' => $file));
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно, недопустимое или уже существующее имя файла ' . $file->getFileName() . '.');
            }
            $loggingService->logEntryContent($file->getParentFolder()->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());
        }

        return $this->render('lencor/admin/archive/administration/file_rename.html.twig', array('fileRenameForm' => $fileRenameForm->createView()));
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/reload_file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_reload_file")
     * @ParamConverter("file", class = "App:FileEntity", isOptional = true, options = { "id" = "file" })
     */

    public function reloadFile(Request $request, FileEntity $file, FileService $fileService)
    {
        if ($request->request->has('filesArray')) {
            $filesArray = $fileService->getFilesList($request->get('filesArray'));

            return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $filesArray));
        } elseif ($file) {

            return $this->render('lencor/admin/archive/archive_manager/file.html.twig', array('file' => $file));
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param DeleteService $deleteService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/delete/file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_delete_file")
     * @ParamConverter("file", class = "App:FileEntity", isOptional = true, options = { "id" = "file" })
     */
    public function deleteFile(Request $request, FileEntity $file, DeleteService $deleteService)
    {
        if ($request->request->has('filesArray')) {
            try {
                $deleteService->deleteFiles($request->get('filesArray'));
                $this->addFlash('success', 'Файлы успешно удалены');

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Файлы не удалёны из за непредвиденной ошибки: ' . $exception->getMessage());

                return new Response(0);
            }
        } elseif ($file) {
            try {
                $deleteService->deleteFile($file);
                $this->addFlash('success', 'Файл '. $file->getFileName() . ' успешно удалён');

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Файл ' . $file->getFileName() . ' не удалён из за непредвиденной ошибки: ' . $exception->getMessage());

                return new Response(0);
            }
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/remove_folder",
     *     options = { "expose" = true },
     *     name = "entries_remove_folder")
     */

    public function removeFolder(Request $request, FolderService $folderService, FileService $fileService)
    {
        $deletedFolder = $folderService->removeFolder($request->get('folderId'), $this->getUser(), $fileService);

        return $this->render('lencor/admin/archive/archive_manager/show_folders.html.twig', array('folderTree' => $deletedFolder));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return JsonResponse
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/entries/restore_folder",
     *     options = { "expose" = true },
     *     name = "entries_restore_folder")
     */

    public function restoreFolder(Request $request, FolderService $folderService)
    {
        $restoredFolders = $folderService->restoreFolder($request->get('folderId'), $this->getUser());

        return new JsonResponse($restoredFolders);
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/request_folder",
     *     options = { "expose" = true },
     *     name = "entries_request_folder")
     */

    public function requestFolder(Request $request, FolderService $folderService)
    {
        $requestedFolder[] = $folderService->requestFolder($request->get('folderId'), $this->getUser());

        return $this->render('lencor/admin/archive/archive_manager/show_folders.html.twig', array('folderTree' => $requestedFolder));
    }

    /**
     * @param Request $request
     * @param FolderEntity $folder
     * @param FolderService $folderService
     * @param LoggingService $loggingService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/rename_folder/{folder}",
     *     options = { "expose" = true },
     *     name = "entries_rename_folder")
     * @ParamConverter("folder", class = "App:FolderEntity", options = { "id" = "folder" })
     */

    public function renameFolder(Request $request, FolderEntity $folder, FolderService $folderService, LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $form_id = 'folder_rename_form_' . $folder->getId();
        $folderRenameForm = $this->createForm(FolderRenameForm::class, $folder, array('attr' => array('id' => $form_id)));
        $folderRenameForm->handleRequest($request);
        if ($folderRenameForm->isSubmitted()) {
            if ($folderRenameForm->isValid()) {
                $originalFolder = $folderService->getOriginalData($folder);
                if ($originalFolder['folderName'] != $folder->getFolderName()) {
                    if ($folderService->moveFolder($folder, $originalFolder)) {
                        $folderService->renameFolder();
                        $this->addFlash('success', 'Переименование ' . $originalFolder['folderName'] . ' > ' . $folder->getFolderName() . ' успешно произведено.');
                    } else {
                        $this->addFlash('danger', 'Переименование отменено из за внутренней ошибки.');
                    }
                } else {
                    $this->addFlash('warning', 'Новое имя каталога ' . $folder->getFolderName() . ' совпадает с текущим. Операция отклонена.');
                }
                $loggingService->logEntryContent($folder->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());

                return $this->render('lencor/admin/archive/archive_manager/folder.html.twig', array('folder' => $folder));
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно, недопустимое или уже существующее имя каталога ' . $folder->getFolderName() . '.');
            }

            $loggingService->logEntryContent($folder->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());
        }

        return $this->render('lencor/admin/archive/administration/folder_rename.html.twig', array('folderRenameForm' => $folderRenameForm->createView()));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param FolderEntity $folder
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/reload_folder/{folder}",
     *     requirements = { "folder" = "\d+" },
     *     defaults = { "folder" : "" },
     *     options = { "expose" = true },
     *     name = "entries_reload_folder")
     * @ParamConverter("folder", class = "App:FolderEntity", isOptional = true, options = { "id" = "folder" })
     */

    public function reloadFolder(Request $request, FolderService $folderService, FolderEntity $folder = null)
    {
        if ($request->request->has('foldersArray')) {
            $foldersArray = $folderService->getFoldersList($request->get('foldersArray'));

            return $this->render('lencor/admin/archive/archive_manager/show_folders.html.twig', array('folderTree' => $foldersArray));
        } else if ($folder) {

            return $this->render('lencor/admin/archive/archive_manager/folder.html.twig', array('folder' => $folder));
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param EntryService $archiveEntryService
     * @param String $entryId
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/change_last_update_info",
     *     options = { "expose" = true },
     *     name = "entries_change_last_update_info")
     */

    public function changeLastUpdateInfo(EntryService $archiveEntryService, $entryId = null)
    {
        if ($entryId) {
            try {
                $archiveEntryService->changeLastUpdateInfo($entryId, $this->getUser());
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Информация об изменениях не записана в ячейку. Ошибка: ' . $exception->getMessage());
            }
        }
        // @TODO: create proper return
        return new Response();
    }

    /**
     * @param Request $request
     * @param EntryService $archiveEntryService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/last_update_info",
     *     options = { "expose" = true },
     *     name = "entries_last_update_info")
     */

    public function loadLastUpdateInfo(Request $request, EntryService $archiveEntryService)
    {
        $lastUpdateInfo = $archiveEntryService->loadLastUpdateInfo($request);

        return $this->render('lencor/admin/archive/archive_manager/entries_update_info.html.twig', array('lastUpdateInfo' => $lastUpdateInfo));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/get_folder_entryId",
     *     options = { "expose" = true },
     *     name = "entries_get_folder_entryId")
     */

    public function getFolderEntryId(Request $request, FolderService $folderService)
    {
        $entry = null;
        if ($request->request->has('folderId')) {
            $entry = $folderService->getFolderEntry($request->get('folderId'));
        }

        return new Response($entry->getId());
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param FileService $fileService
     * @param FileChecksumService $fileChecksumService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/download_file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_download_file")
     * @ParamConverter("file", class = "App:FileEntity", options = { "id" = "file" })
     */

    public function downloadFile(Request $request, FileEntity $file, FileService $fileService, FileChecksumService $fileChecksumService)
    {
        $checkStatus = null;
        $sharePath = null;
        $httpPath = null;
        $filePath = $fileService->getFilePath($file, true);
        $httpPath = $fileService->getFileHTTPUrl($filePath);
        $sharePath = $fileService->getFileSharePath($file);
        $checkStatus = $fileChecksumService->checkFile($file, $filePath);
        if (!$checkStatus) {
            $fileChecksumService->reportChecksumError($file, $this->getUser()->getId());
        } else {
            $fileChecksumService->validateChecksumValue($file, $this->getUser()->getId());
        }

        return $this->render('lencor/admin/archive/archive_manager/download_file.html.twig', array('requestedFile' => $file, 'downloadLink' => $httpPath, 'sharePath' => $sharePath, 'checkPass' => $checkStatus));
    }
}
