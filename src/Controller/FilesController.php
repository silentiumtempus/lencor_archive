<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileEntity;
use App\Form\FileAddForm;
use App\Form\FileRenameForm;
use App\Service\EntryService;
use App\Service\FileChecksumService;
use App\Service\FileService;
use App\Service\FolderService;
use App\Service\LoggingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
            $fileList = $fileService->showEntryFiles(
                $request->get('folderId'),
                (bool)$request->get('deleted')
            );
        }

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_files.html.twig',
            array('fileList' => $fileList)
        );
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @param EntryService $archiveEntryService
     * @param LoggingService $loggingService
     * @throws \Exception
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/new_file",
     *     options = { "expose" = true },
     *     name = "entries_new_file")
     */

    public function uploadNewFile(
        Request $request,
        FileService $fileService,
        FolderService $folderService,
        EntryService $archiveEntryService,
        LoggingService $loggingService
    )
    {
        $session = $this->container->get('session');
        $folderId = $archiveEntryService->setFolderId($request);
        $entry = $folderService->getFolderEntry($folderId);
        $isRoot = $folderService->isRoot($folderId);
        $fileAddForm = $this->createForm(
            FileAddForm::class,
            new FileEntity(),
            array(
                'action' => $this->generateUrl('entries_new_file'),
                'method' => 'POST',
                'attr' => array('isRoot' => $isRoot, 'folderId' => $folderId, 'id' => 'file_add_form')
            )
        );
        $fileAddForm->handleRequest($request);
        if ($fileAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($fileAddForm->isValid()) {
                $fileService->uploadFiles($fileAddForm, $this->getUser(), $entry->getId());
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Операция не выполнена.');
            }
            $loggingService->logEntryContent($entry, $this->getUser(), $session->getFlashBag()->peekAll());
        }

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/new_file.html.twig',
            array(
                'fileAddForm' => $fileAddForm->createView(),
                'entryId' => $entry->getId()
            )
        );
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/remove_file",
     *     options = { "expose" = true },
     *     name = "entries_remove_file")
     */
    //@TODO: Unite two methods below
    public function removeFile(Request $request, FileService $fileService)
    {
        $removedFile = $fileService->removeFile($request->get('fileId'), $this->getUser());

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_files.html.twig',
            array('fileList' => $removedFile)
        );
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/entries/restore_file",
     *     options = { "expose" = true },
     *     name = "entries_restore_file")
     */

    public function restoreFile(Request $request, FileService $fileService)
    {
        $restoredFile = $fileService->restoreFile($request->get('fileId'), $this->getUser());

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_files.html.twig',
            array('fileList' => $restoredFile)
        );
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/request_file",
     *     options = { "expose" = true },
     *     name = "entries_request_file")
     */

    public function requestFile(
        Request $request,
        FileService $fileService,
        FolderService $folderService
    )
    {
        $requestedFile = $fileService->requestFile($request->get('fileId'), $this->getUser(), $folderService);

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/show_files.html.twig',
            array('fileList' => $requestedFile)
        );
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param FileService $fileService
     * @param LoggingService $loggingService
     * @throws \Exception
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/rename_file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_rename_file")
     * @ParamConverter("file", class = "App:FileEntity", options = { "id" = "file" })
     */

    public function renameFile(
        Request $request,
        FileEntity $file,
        FileService $fileService,
        LoggingService $loggingService
    )
    {
        $session = $this->container->get('session');
        $form_id = 'file_rename_form_' . $file->getId();
        $fileRenameForm = $this->createForm(
            FileRenameForm::class,
            $file,
            array('attr' => array('id' => $form_id))
        );
        $fileRenameForm->handleRequest($request);
        if ($fileRenameForm->isSubmitted()) {
            if ($fileRenameForm->isValid()) {
                $fileService->renameFile($file, $this->getUser());
                $loggingService->logEntryContent(
                    $file->getParentFolder()->getRoot()->getArchiveEntry(),
                    $this->getUser(),
                    $session->getFlashBag()->peekAll()
                );

                return $this->render(
                    'lencor/admin/archive/archive_manager/files_and_folders/file.html.twig',
                    array('file' => $file)
                );
            } else {
                $this->addFlash(
                    'danger',
                    'Форма заполнена неверно, недопустимое или уже существующее имя файла ' . $file->getFileName() . '.'
                );
            }
            $loggingService->logEntryContent(
                $file->getParentFolder()->getRoot()->getArchiveEntry(),
                $this->getUser(),
                $session->getFlashBag()->peekAll()
            );
        }

        return $this->render(
            'lencor/admin/archive/administration/files_and_folders/file_rename.html.twig',
            array('fileRenameForm' => $fileRenameForm->createView())
        );
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

    public function reloadFile(
        Request $request,
        FileEntity $file,
        FileService $fileService
    )
    {
        if ($request->request->has('filesArray')) {
            $filesArray = $fileService->getFilesList($request->get('filesArray'));

            return $this->render(
                'lencor/admin/archive/archive_manager/files_and_folders/show_files.html.twig',
                array('fileList' => $filesArray)
            );
        } elseif ($file) {

            return $this->render(
                'lencor/admin/archive/archive_manager/files_and_folders/file.html.twig',
                array('file' => $file)
            );
        } else {

            return $this->redirectToRoute('entries');
        }
    }

    /**
     * @param Request $request
     * @param FileEntity $file
     * @param FileService $fileService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/delete/file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_delete_file")
     * @ParamConverter("file", class = "App:FileEntity", isOptional = true, options = { "id" = "file" })
     */

    public function deleteFile(
        Request $request,
        FileEntity $file,
        FileService $fileService
    )
    {
        if ($request->request->has('filesArray')) {
            $result = $fileService->deleteFiles($request->get('filesArray'), $this->getUser());

            return new Response($result);
        } elseif ($file) {
            $result = $fileService->deleteFile($file, false, $this->getUser());

            return new Response($result);
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

    public function unDeleteFile(
        Request $request,
        FileEntity $file,
        FileService $fileService
    )
    {
        if ($request->request->has('filesArray')) {
            try {
                $folders = $fileService->unDeleteFiles($request->get('filesArray'), $this->getUser());
                $this->addFlash(
                    'success',
                    'Файлы успешно восстановлены'
                );

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash(
                    'danger',
                    'Файлы не восстановлены из за непредвиденной ошибки: ' . $exception->getMessage()
                );

                return new JsonResponse(0);
            }
        } elseif ($file) {
            try {
                $folders = $fileService->unDeleteFile($file, [], false, $this->getUser());
                $this->addFlash(
                    'success',
                    'Файл ' . $file->getFileName() . ' успешно восстановлен'
                );

                return new JsonResponse($folders);
            } catch (\Exception $exception) {
                $this->addFlash(
                    'danger',
                    'Файл ' . $file->getFileName() . ' не восстановлен из за непредвиденной ошибки: ' . $exception->getMessage()
                );

                return new JsonResponse(0);
            }
        } else {

            return new JsonResponse(1);
        }
    }

    /**
     * @param FileEntity $file
     * @param FileService $fileService
     * @param FileChecksumService $fileChecksumService
     * @return Response
     * @throws \Exception
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/download_file/{file}",
     *     requirements = { "file" = "\d+" },
     *     defaults = { "file" : "" },
     *     options = { "expose" = true },
     *     name = "entries_download_file")
     * @ParamConverter("file", class = "App:FileEntity", options = { "id" = "file" })
     */

    public function downloadFile(
        FileEntity $file,
        FileService $fileService,
        FileChecksumService $fileChecksumService
    )
    {
        $fileInfo = $fileService->getFileDownloadInfo($file);
        if (!$fileInfo['check_status']) {
            $fileChecksumService->reportChecksumError($file, $this->getUser()->getId());
        } else {
            $fileChecksumService->validateChecksumValue($file, $this->getUser()->getId());
        }

        return $this->render(
            'lencor/admin/archive/archive_manager/files_and_folders/download_file.html.twig',
            array(
                'requestedFile' => $file,
                'downloadLink' => $fileInfo['http_url'],
                'sharePath' => $fileInfo['share_path'],
                'checkPass' => $fileInfo['check_status']
            )
        );
    }
}