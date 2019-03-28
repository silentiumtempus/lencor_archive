<?php
declare(strict_types=1);

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
            $folderTree = $folderService->showEntryFolders((int)$request->get('folderId'), (bool) $request->get('deleted'));
        }

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig',
            array('folderTree' => $folderTree, 'placeholder' => true)
        );
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param EntryService $archiveEntryService
     * @param LoggingService $loggingService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/new_folder",
     *     options = { "expose" = true },
     *     name = "entries_new_folder")
     */
    public function createNewFolder(
        Request $request,
        FolderService $folderService,
        EntryService $archiveEntryService,
        LoggingService $loggingService
    )
    {
        $session = $this->container->get('session');
        $folderId = $archiveEntryService->setFolderId($request);
        $entry = $folderService->getFolderEntry($folderId);
        $newFolder = new FolderEntity();
        $isRoot = $folderService->isRoot($folderId);
        $folderAddForm = $this->createForm(
            FolderAddForm::class,
            $newFolder,
            array(
                'action' => $this->generateUrl('entries_new_folder'),
                'attr' => array('isRoot' => $isRoot, 'folderId' => $folderId, 'id' => 'folder_add_form')
            )
        );
        $folderAddForm->handleRequest($request);
        if ($folderAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($folderAddForm->isValid()) {
                $folderService->createNewFolder($folderAddForm, $this->getUser(), $entry->getId());
            } else {
                $this->addFlash(
                    'danger',
                    'Директория ' . $folderAddForm->getName() . ' уже существует в каталоге ' . $folderAddForm->getParent()->getViewData() . '. Операция прервана'
                );
            }
            $loggingService->logEntryContent($entry, $this->getUser(), $session->getFlashBag()->peekAll());
        }
        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/new_folder.html.twig',
            array(
                'folderAddForm' => $folderAddForm->createView(),
                'entryId' => $entry->getId())
        );
    }


    /**
     * @param Request $request
     * @param FolderService $folderService
     * @param FileService $fileService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/remove_folder",
     *     options = { "expose" = true },
     *     name = "entries_remove_folder")
     */
    public function removeFolder(
        Request $request,
        FolderService $folderService,
        FileService $fileService
    )
    {
        $removedFolders[] = $folderService->removeFolder(
            (int)$request->get('folderId'),
            $this->getUser(),
            $fileService
        );

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig',
            array('folderTree' => $removedFolders)
        );
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return JsonResponse
     * @throws \Exception
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/entries/restore_folder",
     *     options = { "expose" = true },
     *     name = "entries_restore_folder")
     */
    public function restoreFolder(Request $request, FolderService $folderService)
    {
        $restoredFolders = $folderService->restoreFolder(
            (int)$request->get('folderId'),
            $this->getUser()
        );

        return new JsonResponse($restoredFolders);
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/request_folder",
     *     options = { "expose" = true },
     *     name = "entries_request_folder")
     */
    public function requestFolder(Request $request, FolderService $folderService)
    {
        $requestedFolder[] = $folderService->requestFolder((int)$request->get('folderId'), $this->getUser());

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig',
            array('folderTree' => $requestedFolder)
        );
    }

    /**
     * @param Request $request
     * @param FolderEntity $folder
     * @param FolderService $folderService
     * @param LoggingService $loggingService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/rename_folder/{folder}",
     *     options = { "expose" = true },
     *     name = "entries_rename_folder")
     * @ParamConverter("folder", class = "App:FolderEntity", options = { "id" = "folder" })
     */
    public function renameFolder(
        Request $request,
        FolderEntity $folder,
        FolderService $folderService,
        LoggingService $loggingService
    )
    {
        $session = $this->container->get('session');
        $form_id = 'folder_rename_form_' . $folder->getId();
        $folderRenameForm = $this->createForm(FolderRenameForm::class, $folder, array('attr' => array('id' => $form_id)));
        $folderRenameForm->handleRequest($request);
        if ($folderRenameForm->isSubmitted()) {
            if ($folderRenameForm->isValid()) {
                $folderService->renameFolder($folder, $this->getUser());
                return $this->render(
                    'lencor/admin/archive/archive_manager/files_and_folders/folder.html.twig',
                    array('folder' => $folder)
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Форма заполнена неверно, недопустимое или уже существующее имя каталога ' . $folder->getFolderName() . '.'
                );
            }

            $loggingService->logEntryContent(
                $folder->getRoot()->getArchiveEntry(),
                $this->getUser(),
                $session->getFlashBag()->peekAll()
            );
        }

        return $this->render(
            'lencor/admin/archive/administration/files_and_folders/folder_rename.html.twig',
            array('folderRenameForm' => $folderRenameForm->createView())
        );
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
    public function reloadFolder(
        Request $request,
        FolderService $folderService,
        FolderEntity $folder = null
    )
    {
        if ($request->request->has('foldersArray')) {
            $foldersArray = $folderService->getFoldersList($request->get('foldersArray'));

            return $this->render(
                'lencor/admin/archive/archive_manager/files_and_folders/show_folders.html.twig',
                array('folderTree' => $foldersArray)
            );
        } else if ($folder) {

            return $this->render(
                'lencor/admin/archive/archive_manager/files_and_folders/folder.html.twig',
                array('folder' => $folder)
            );
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
    public function deleteFolder(
        Request $request,
        FolderEntity $folder,
        FolderService $folderService,
        FileService $fileService
    )
    {
        if ($request->request->has('foldersArray')) {
            try {
                $folderService->deleteFolders($request->get('filesArray'), $fileService, $this->getUser());
                $this->addFlash(
                    'success',
                    'Директории успешно удалены'
                );

                return new Response(1);
            } catch (\Exception $exception) {
                $this->addFlash(
                    'danger',
                    'Директории не удалёны из за непредвиденной ошибки: ' . $exception->getMessage()
                );

                return new Response(0);
            }
        } elseif ($folder) {
            try {
                $folderService->deleteFolder($folder, $fileService, false, $this->getUser());

                return new Response(1);
            } catch (\Exception $exception) {

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
    public function unDeleteFolder(
        Request $request,
        FolderEntity $folder,
        FolderService $folderService
    )
    {
        //TODO: to be improved
        if ($request->request->has('filesArray')) {
            try {
                $folders = $folderService->unDeleteFolders($request->get('filesArray'), $this->getUser());

                return new JsonResponse($folders);
            } catch (\Exception $exception) {

                return new JsonResponse(0);
            }
        } elseif ($folder) {
            try {
                $folders = $folderService->unDeleteFolder($folder, [], false, $this->getUser());

                return new JsonResponse($folders);
            } catch (\Exception $exception) {

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
            $entry = $folderService->getFolderEntry((int)$request->get('folderId'));
        }

        return new Response($entry->getId());
    }
}