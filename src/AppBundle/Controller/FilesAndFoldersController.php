<?php

namespace AppBundle\Controller;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Form\FileAddForm;
use AppBundle\Form\FolderAddForm;
use AppBundle\Service\ArchiveEntryService;
use AppBundle\Service\FileChecksumService;
use AppBundle\Service\FileService;
use AppBundle\Service\FolderService;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class FilesAndFoldersController
 * @package AppBundle\Controller
 */
class FilesAndFoldersController extends Controller
{
    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/lencor_entries/new_folder", name="lencor_entries_new_folder")
     */

    public function createNewFolder(Request $request, FolderService $folderService, ArchiveEntryService $archiveEntryService)
    {
        $entryId = $archiveEntryService->setEntryId($request);
        $user = $this->getUser();
        $newFolder = new FolderEntity();
        $folderRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $folderId = $folderService->getRootFolder($entryId);

        $folderAddForm = $this->createForm(
            FolderAddForm::class,
            $newFolder,
            array('action' => $this->generateUrl('lencor_entries_new_folder'), 'attr' => array('folderId' => $folderId, 'id' => 'folder_add_form')));

        $folderAddForm->handleRequest($request);
        if ($folderAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($folderAddForm->isValid()) {
                try {
                    $newFolderEntity = $folderAddForm->getData();
                    $parentFolder = $folderRepository->findOneById($folderAddForm->get('parentFolder')->getViewData());
                    $newFolderEntity = $folderService->prepareNewFolder($newFolderEntity, $parentFolder, $user);

                    $fileSystem = new Filesystem();
                    $storagePath = $this->getParameter('lencor_archive.storage_path');
                    $pathPermissions = $this->getParameter('lencor_archive.storage_permissions');
                    $creationNotFailed = true;
                    $directoryExistedPreviously = false;

                    if ($fileSystem->exists($storagePath)) {
                        try {
                            $newFolderAbsPath = $storagePath;
                            $binaryPath = $folderRepository->getPath($parentFolder);
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
                $this->addFlash('danger', 'Такая директория уже существует. Операция прервана');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new_folder.html.twig', array('folderAddForm' => $folderAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/lencor_entries/new_file", name="lencor_entries_new_file")
     */

    public function uploadNewFile(Request $request, FileService $fileService, FolderService $folderService, ArchiveEntryService $archiveEntryService)
    {
        $newFile = new FileEntity();
        $entryId = $entryId = $archiveEntryService->setEntryId($request);
        $user = $this->getUser();
        $folderId = $folderService->getRootFolder($entryId);

        $fileAddForm = $this->createForm(
            FileAddForm::class,
            $newFile,
            array('action' => $this->generateUrl('lencor_entries_new_file'), 'method' => 'POST', 'attr' => array('folderId' => $folderId, 'id' => 'file_add_form')));

        $fileAddForm->handleRequest($request);
        if ($fileAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($fileAddForm->isValid()) {
                try {
                    $uploadNotFailed = true;
                    $newFileEntity = $fileAddForm->getData();
                    //$newFilesArray = $fileAddForm->getData();
                    $parentFolder = $folderService->getParentFolder($fileAddForm->get('parentFolder')->getViewData());
                    //foreach ($newFilesArray as $newFileEntity) {
                    $rootPath = $this->getParameter('lencor_archive.storage_path');
                    $folderAbsPath = $folderService->constructFolderAbsPath($rootPath, $parentFolder);

                    $originalName = pathinfo($newFileEntity->getFileName()->getClientOriginalName(), PATHINFO_FILENAME) . "-" . (hash('crc32', uniqid(), false) . "." . $newFileEntity->getFileName()->getClientOriginalExtension());
                    $fileWithAbsPath = $fileService->constructFileAbsPath($folderAbsPath, $originalName);

                    $newFileEntity = $fileService->prepareNewFile($newFileEntity, $parentFolder, $originalName, $user);
                    $fileSystem = new Filesystem();

                    if (!$fileSystem->exists($fileWithAbsPath)) {
                        $fileExistedPreviously = false;
                        try {
                            $newFileEntity->getFileName()->move($folderAbsPath, $originalName);
                            $newFileEntity->setChecksum(md5_file($fileWithAbsPath));
                            $this->addFlash('success', 'Новый документ записан в директорию ' . $parentFolder);
                        } catch (\Exception $exception) {
                            $uploadNotFailed = false;
                            $this->addFlash('danger', 'Новый документ не записан в директорию. Ошибка файловой системы: ' . $exception->getMessage());
                            $this->addFlash('danger', 'Загрузка в БД прервана: изменения не внесены.');
                        }
                    } else {
                        $fileExistedPreviously = true;
                        $this->addFlash('danger', 'Документ с таким именем уже существует в директории назначения. Перезапись отклонена.');
                    }

                    if ($uploadNotFailed) {
                        try {
                            $fileService->persistFile($newFileEntity);
                            $this->changeLastUpdateInfo($entryId, $archiveEntryService);

                            $this->addFlash('success', 'Ноsый документ добавлен в БД');
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
                        }
                    };
                    //}
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Операция не выполнена.');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new_file.html.twig', array('fileAddForm' => $fileAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Route("/lencor_entries/remove_file", name="lencor_entries_remove_file")
     */

    //@TODO: Unite two methods below
    public function removeFile(Request $request, FileService $fileService)
    {
        $deletedFile = $fileService->removeFile($request->get('fileId'), $this->getUser()->getid());

        return $this->render('lencor/admin/archive/archive_manager_file.html.twig', array('fileList' => $deletedFile));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Route("/lencor_entries/restore_file", name="lencor_entries_restore_file")
     */

    public function restoreFile(Request $request, FileService $fileService)
    {
        $restoredFile = $fileService->restoreFile($request->get('fileId'));

        return $this->render('lencor/admin/archive/archive_manager_file.html.twig', array('fileList' => $restoredFile));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param FileService $fileService
     * @return Response
     * @Route("/lencor_entries/remove_folder", name="lencor_entries_remove_folder")
     */

    public function removeFolder(Request $request, FolderService $folderService, FileService $fileService)
    {
        $deletedFolder = $folderService->removeFolder($request->get('folderId'), $this->getUser()->getId(), $fileService);

        return $this->render('lencor/admin/archive/archive_manager_folder.html.twig', array('folderTree' => $deletedFolder));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Route("/lencor_entries/restore_folder", name="lencor_entries_restore_folder")
     */

    public function restoreFolder(Request $request, FolderService $folderService)
    {
        $restoredFolder = $folderService->restoreFolder($request->get('folderId'));

        return $this->render('lencor/admin/archive/archive_manager_folder.html.twig', array('folderTree' => $restoredFolder));
    }

    /**
     * @param String $entryId
     * @param ArchiveEntryService $archiveEntryService
     * @Route("/lencor_entries/change_last_update_info", name="lencor_entries_change_last_update_info")
     */

    public function changeLastUpdateInfo($entryId, ArchiveEntryService $archiveEntryService)
    {
        try {
            $archiveEntryService->changeLastUpdateInfo($entryId, $this->getUser()->getId());
        } catch (\Exception $exception) {
            $this->addFlash('error', 'Информация об изменениях не записана в ячейку. Ошибка: ' . $exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/lencor_entries/last_update_info", name="lencor_entries_last_update_info")
     */
    public function loadLastUpdateInfo(Request $request, ArchiveEntryService $archiveEntryService)
    {
        $lastUpdateInfo = $archiveEntryService->loadLastUpdateInfo($request);

        return $this->render('lencor/admin/archive/archive_manager_entries_update_info.html.twig', array('lastUpdateInfo' => $lastUpdateInfo));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Route("/lencor_entries/get_folder_entryId", name="lencor_entries_get_folder_entryId")
     */

    public function getFolderEntryId(Request $request, FolderService $folderService)
    {
        $entryId = null;
        if ($request->request->has('folderId')) {
            $entryId = $folderService->getFolderEntryId($request->get('folderId'));
        }

        return new Response($entryId);
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FileChecksumService $fileChecksumService
     * @return Response
     * @Route("/lencor_entries/download_file", name="lencor_entries_download_file")
     */

    public function downloadFile(Request $request, FileService $fileService, FileChecksumService $fileChecksumService)
    {
        $requestedFile = null;
        $checkStatus = null;
        $httpPath = null;
        if ($request->request->has('fileId'))
        {
            $requestedFile = $fileService->getFileById($request->get('fileId'));
            $filePath = $fileChecksumService->getFilePath($requestedFile);
            $httpPath = $fileChecksumService->getFileHttpUrl($filePath);
            $checkStatus = $fileChecksumService->checkFile($requestedFile, $filePath);
            if (!$checkStatus) {
                $fileChecksumService->reportChecksumError($requestedFile, $this->getUser()->getId());
            } else {
                $fileChecksumService->validateChecksumValue($requestedFile, $this->getUser()->getId());
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_download_file.html.twig', array('requestedFile' => $requestedFile, 'downloadLink' => $httpPath, 'checkPass' => $checkStatus));
    }
}