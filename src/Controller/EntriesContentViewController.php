<?php

namespace App\Controller;

use App\Service\FileService;
use App\Service\FolderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class EntriesContentViewController extends Controller
{
    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view_folders", name = "entries_view_folders")
     */
    public function showEntryFolders(Request $request, FolderService $folderService)
    {
        $folderTree = null;
        if ($request->request->has('folderId')) {
            $folderTree = $folderService->showEntryFolder($request->request->get('folderId'));
        }

        return $this->render('lencor/admin/archive/archive_manager/show_folders.html.twig', array('folderTree' => $folderTree, 'placeholder' => true));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view_files", name = "entries_view_files")
     */
    public function showEntryFiles(Request $request, FileService $fileService)
    {
        $fileList = null;
        if ($request->request->has('folderId')) {
            $fileList = $fileService->showEntryFiles($request->request->get('folderId'));
        }

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $fileList));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/reload_file", name = "entries_reload_file")
     */
    public function changeFileStatus(Request $request, FileService $fileService)
    {
        $fileList = array();
        if ($request->request->has('fileId')) {
            $fileList[0] = $fileService->reloadFileDetails($request->request->get('fileId'));
        }

        return $this->render('lencor/admin/archive/archive_manager/show_files.html.twig', array('fileList' => $fileList));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/view", name = "entries_view")
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
            'addHeaderAndButtons' => $addHeaderAndButtons));
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @param FolderService $folderService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/show_requesters",
     *     options = { "expose" = true },
     *     name = "show_requesters")
     */
    public function showRequesters(Request $request, FileService $fileService, FolderService $folderService)
    {
        $requesters = null;
        if ($request->get('type') && $request->get('id')) {
            $type = $request->get('type');
            $fileId = $request->get('id');

            switch ($type) {
                case 'file':
                    $requesters = $fileService->getFileRequesters($fileId);
                    break;
                case 'folder':
                    $requesters[] = [];
                    break;
                case 'entry':
                    $requesters[] = [];
                    break;
            }
        }


        return $this->render('lencor/admin/archive/archive_manager/show_requesters.html.twig', array('requesters' => $requesters));
    }
}
