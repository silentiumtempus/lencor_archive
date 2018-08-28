<?php

namespace App\Controller;

use App\Entity\FolderEntity;
use App\Form\FolderAddForm;
use App\Form\FolderRenameForm;
use App\Service\EntryService;
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
 * Class FoldersController
 * @package App\Controller
 */
class FoldersController extends Controller
{
    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view_folders",
     *     options = { "expose" = true },
     *     name = "entries_view_folders")
     */

    public function showEntryFolders(Request $request, FolderService $folderService)
    {
        $folderTree = null;
        if ($request->request->has('folderId')) {
            $folderTree = $folderService->showEntryFolders($request->get('folderId'), (bool) $request->get('deleted'));
        }

        return $this->render('lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig', array('folderTree' => $folderTree, 'placeholder' => true));
    }

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
                    $newFolderAbsPath = $this->getParameter('archive.storage_path');
                    $pathPermissions = $this->getParameter('archive.storage_permissions');
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
                $this->addFlash('danger', 'Директория ' . $folderAddForm->getName() . ' уже существует в каталоге ' . $folderAddForm->getParent()->getViewData() . '. Операция прервана');
            }
            $loggingService->logEntryContent($entryId, $this->getUser(), $session->getFlashBag()->peekAll());
        }
        return $this->render('lencor/admin/archive/archive_manager/files_and_folders/new_folder.html.twig', array('folderAddForm' => $folderAddForm->createView(), 'entryId' => $entryId));
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
        $removedFolders[] = $folderService->removeFolder($request->get('folderId'), $this->getUser(), $fileService);

        return $this->render('lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig', array('folderTree' => $removedFolders));
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

        return $this->render('lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig', array('folderTree' => $requestedFolder));
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
                        $folderService->flushFolder();
                        $this->addFlash('success', 'Переименование ' . $originalFolder['folderName'] . ' > ' . $folder->getFolderName() . ' успешно произведено.');
                    } else {
                        $this->addFlash('danger', 'Переименование отменено из за внутренней ошибки.');
                    }
                } else {
                    $this->addFlash('warning', 'Новое имя каталога ' . $folder->getFolderName() . ' совпадает с текущим. Операция отклонена.');
                }
                $loggingService->logEntryContent($folder->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());

                return $this->render('lencor/admin/archive/archive_manager/files_and_folders/folder.html.twig', array('folder' => $folder));
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно, недопустимое или уже существующее имя каталога ' . $folder->getFolderName() . '.');
            }

            $loggingService->logEntryContent($folder->getRoot()->getArchiveEntry()->getId(), $this->getUser(), $session->getFlashBag()->peekAll());
        }

        return $this->render('lencor/admin/archive/administration/files_and_folders/folder_rename.html.twig', array('folderRenameForm' => $folderRenameForm->createView()));
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

            return $this->render('lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig', array('folderTree' => $foldersArray));
        } else if ($folder) {

            return $this->render('lencor/admin/archive/archive_manager/files_and_folders/folder.html.twig', array('folder' => $folder));
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param Request $request
     * @param FolderEntity $folder
     * @param FolderService $folderService
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/delete/folder/{folder}",
     *     requirements = { "folder" = "\d+" },
     *     defaults = { "folder" : "" },
     *     options = { "expose" = true },
     *     name = "entries_delete_folder")
     * @ParamConverter("folder", class = "App:FolderEntity", isOptional = true, options = { "id" = "folder" })
     */
    public function deleteFolder(Request $request, FolderEntity $folder, FolderService $folderService, FileService $fileService) {
        if ($request->request->has('foldersArray')) {
            try {
                $folderService->deleteFolders($request->get('filesArray'), $fileService);
                $this->addFlash('success', 'Директории успешно удалены');

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Директории не удалёны из за непредвиденной ошибки: ' . $exception->getMessage());

                return new Response(0);
            }
        } elseif ($folder) {
            try {
                $folderService->deleteFolder($folder, $fileService);
                $this->addFlash('success', 'Директория '. $folder->getFolderName() . ' успешно удалёна');

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Директория ' . $folder->getFolderName() . ' не удалёна из за непредвиденной ошибки: ' . $exception->getMessage());

                return new Response(0);
            }
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param Request $request
     * @param FolderEntity $folder
     * @param FolderService $folderService
     * @return JsonResponse
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/undelete/folder/{folder}",
     *     options = { "expose" = true },
     *     name = "entries_undelete_folder",
     *     requirements = { "folder" = "\d+" },
     *     defaults = { "folder" : "" }
     *     )
     * @ParamConverter("folder", class = "App:FolderEntity", isOptional = true, options = { "id" = "folder" })
     */

    public function unDeleteFolder(Request $request, FolderEntity $folder, FolderService $folderService)
    {
        if ($request->request->has('filesArray')) {
            try {
                $folders = $folderService->unDeleteFolders($request->get('filesArray'));
                $this->addFlash('success', 'Каталоги успешно восстановлены');

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Каталоги не восстановлены из за непредвиденной ошибки: ' . $exception->getMessage());

                return new JsonResponse(0);
            }
        } elseif ($folder) {
            try {
                $folders = $folderService->unDeleteFolder($folder, []);
                $this->addFlash('success', 'Каталог ' . $folder->getFolderName() . ' успешно восстановлен');

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash('danger', 'Каталог ' . $folder->getFolderName() . ' не восстановлен из за непредвиденной ошибки: ' . $exception->getMessage());

                return new JsonResponse(0);
            }
        } else {

            return new JsonResponse(1);
        }
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
}