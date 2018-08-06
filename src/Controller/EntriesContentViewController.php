<?php

namespace App\Controller;

use App\Service\CommonArchiveService;
use App\Service\EntryService;
use App\Service\FileService;
use App\Service\FolderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EntriesContentViewController
 * @package App\Controller
 */

class EntriesContentViewController extends Controller
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

        return $this->render('lencor/admin/archive/archive_manager/show_folders.html.twig', array('folderTree' => $folderTree, 'placeholder' => true));
    }

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
            $fileList = $fileService->showEntryFiles($request->get('folderId'), (bool) $request->get('deleted'));
        }

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $fileList));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view",
     *     options = { "expose" = true },
     *     name = "entries_view")
     */

    public function showEntryDetails(Request $request, FolderService $folderService)
    {
        $entryId = null;
        $folderId = null;
        $addHeaderAndButtons = true;

        if ($request->request->has('entryId')) {
            $entryId = $request->get('entryId');
            $folderId = $folderService->getRootFolder($entryId);
        }

        return $this->render('lencor/admin/archive/archive_manager/entries_head.html.twig', array(
            'folderId' => $folderId,
            'entryId' => $entryId,
            'addHeaderAndButtons' => $addHeaderAndButtons,
            'deleted' => $request->get('deleted')));
    }

    /**
     * @param Request $request
     * @param CommonArchiveService $commonArchiveService
     * @param TranslatorInterface $translator
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/show_requesters",
     *     options = { "expose" = true },
     *     name = "show_requesters")
     */

    public function showRequesters(Request $request, CommonArchiveService $commonArchiveService, TranslatorInterface $translator)
    {
        $requesters = null;
        $headerText = null;
        $translator->addLoader('yml', new YamlFileLoader());
        $translator->addResource('yml', 'files_folders.ru.yml', 'ru_RU', 'files_folders');
        if ($request->get('type') && $request->get('id')) {
            $type = $request->get('type');
            $id = $request->get('id');
            $requesters = $commonArchiveService->getRequesters($id, $type);
            switch ($type) {
                case 'file':
                    $headerText = $translator->trans('file.self', array(), 'files_folders') . " " . $translator->trans('requested', array(), 'files_folders');
                    break;
                case 'folder':
                    $headerText = $translator->trans('folder.self', array(), 'files_folders') . " " . $translator->trans('requested', array(), 'files_folders');
                    break;
                case 'entry':
                    $translator->addResource('yml', 'entries.ru.yml', 'ru_RU', 'entries');
                    $headerText = $translator->trans('entries.self', array(), 'entries') . " " . $translator->trans('entries.requested', array(), 'entries');
                    break;
            }
        }

        return $this->render('lencor/admin/archive/archive_manager/show_requesters.html.twig', array('requesters' => $requesters, 'headerText' => $headerText));
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
}
