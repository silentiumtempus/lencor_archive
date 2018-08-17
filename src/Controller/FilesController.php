<?php

namespace App\Controller;

use App\Entity\FileEntity;
use App\Form\FileAddForm;
use App\Form\FileRenameForm;
use App\Service\DeleteService;
use App\Service\EntryService;
use App\Service\FileChecksumService;
use App\Service\FileService;
use App\Service\FolderService;
use App\Service\LoggingService;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class FilesController
 * @package App\Controller
 */
class FilesController extends Controller
{
    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view_files",
     *     options = { "expose" = true },
     *     name = "entries_view_files")
     */

    public function showEntryFiles(Request $request, FileService $fileService)
    {
        $fileList = null;
        if ($request->request->has('folderId')) {
            $fileList = $fileService->showEntryFiles($request->get('folderId'), (bool)$request->get('deleted'));
        }

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $fileList));
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
        $removedFile = $fileService->removeFile($request->get('fileId'), $this->getUser());

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $removedFile));
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
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/delete/file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_delete_file")
     * @ParamConverter("file", class = "App:FileEntity", isOptional = true, options = { "id" = "file" })
     */

    public function deleteFile(Request $request, FileEntity $file, FileService $fileService)
    {
        if ($request->request->has('filesArray')) {
            try {
                $fileService->deleteFiles($request->get('filesArray'), $fileService);
                $this->addFlash('success', 'Файлы успешно удалены');

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Файлы не удалёны из за непредвиденной ошибки: ' . $exception->getMessage());

                return new Response(0);
            }
        } elseif ($file) {
            try {
                $fileService->deleteFile($file, $fileService);
                $this->addFlash('success', 'Файл ' . $file->getFileName() . ' успешно удалён');

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
     * @param FileEntity $file
     * @param FileService $fileService
     * @return JsonResponse
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/undelete/file/{file}",
     *     options = { "expose" = true },
     *     name = "entries_undelete_file",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" }
     *     )
     * @ParamConverter("file", class = "App:FileEntity", isOptional = true, options = { "id" = "file" })
     */

    public function unDeleteFile(Request $request, FileEntity $file, FileService $fileService)
    {
        if ($request->request->has('filesArray')) {
            try {
                $folders = $fileService->unDeleteFiles($request->get('filesArray'));
                $this->addFlash('success', 'Файлы успешно восстановлены');

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Файлы не восстановлены из за непредвиденной ошибки: ' . $exception->getMessage());

                return new JsonResponse(0);
            }
        } elseif ($file) {
            try {
                $folders = $fileService->unDeleteFile($file, []);
                $this->addFlash('success', 'Файл ' . $file->getFileName() . ' успешно восстановлен');

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Файл ' . $file->getFileName() . ' не восстановлен из за непредвиденной ошибки: ' . $exception->getMessage());

                return new JsonResponse(0);
            }
        } else {

            return new JsonResponse(1);
        }
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