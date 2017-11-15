<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Form\ArchiveEntrySearchForm;
use AppBundle\Service\ArchiveEntrySearchService;
use AppBundle\Service\ArchiveEntryService;
use AppBundle\Service\FileService;
use AppBundle\Service\FolderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Elastica\Query\BoolQuery;
use Elastica\Query;

/**
 * Class ArchiveViewController
 * @package AppBundle\Controller
 */
class ArchiveViewController extends Controller
{

    /*set_include_path('/var/www/lencor/public_html/new/web/');
$file = 'test.txt';

$wr = file_get_contents($file);

$wr = $wr . $request->get('entryId') . "!!!!!!!!!!!!!!" . "\n\n";
//$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";

file_put_contents($file, $wr); */

    /**
     * @param Request $request
     * @param ArchiveEntrySearchService $entrySearchService
     * @return Response
     * @Route("/entries/", name="entries")
     */
    public function loadEntries(Request $request, ArchiveEntrySearchService $entrySearchService)
    {
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entrySearchEntity = new ArchiveEntryEntity();
        $searchForm = $this->createForm(ArchiveEntrySearchForm::class, $entrySearchEntity);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid() && $request->isMethod('POST')) {
            try {
                $filterQuery = $entrySearchService->performSearch($searchForm, $filterQuery);
            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
        $archiveEntries = $entrySearchService->getQueryResult($finalQuery, $filterQuery);
        $rootPath = $this->getParameter('lencor_archive.storage_path');

        return $this->render('/lencor/admin/archive/archive_manager/show_entries.html.twig', array('archiveEntries' => $archiveEntries, 'searchForm' => $searchForm->createView(), 'rootPath' => $rootPath));
    }

    /**
     * @param Request $request
     * @param FolderService $folderService
     * @return Response
     * @Route("/entries/view_folders", name="entries_view_folders")
     */
    function showEntryFolders(Request $request, FolderService $folderService)
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
     * @Route("/entries/view_files", name="entries_view_files")
     */
    function showEntryFiles(Request $request, FileService $fileService)
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
     * @Route("/entries/reload_file", name="entries_reload_file")
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
     * @Route("/entries/view", name="entries_view")
     */
    function showEntryDetails(Request $request, FolderService $folderService)
    {
        $entryId = null;
        $folderId = null;
        $addHeaderAndButtons = true;

        if ($request->request->has('entryId')) {
            $entryId = $request->get('entryId');
            $folderId = $folderService->getRootFolder($entryId);
        }
        /** for file system handling **/
        //@TODO: review the code below
        /*$entryId = $request->get('entryId');
        $pathRoot = $this->getParameter('lencor_archive.storage_path');
        $targetEntry = $this->getDoctrine()->getRepository('AppBundle:ArchiveEntryEntity')->find($entryId);
        $entryCatalogue = $pathRoot . "/" . $targetEntry->getCataloguePath();

        $finder = new Finder();
        $filesystem = new Filesystem();
        $entryDirectories = $finder->directories()->in($entryCatalogue);
        $entryDirectories->sortByName();
        $entryDirectories = array_keys(iterator_to_array($entryDirectories));

        foreach ($entryDirectories as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            unset($entryDirectories[$key]);
            $entryDirectories[$relativePath] = $relativePath;
        }

        $entryFiles = $finder->files()->in($entryCatalogue)->depth('== 0');
        $entryFiles->sortByName();
        $entryFiles = array_keys(iterator_to_array($entryFiles));


        foreach ($entryFiles as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            $relativePath = rtrim($relativePath, "/");
            unset($entryFiles[$key]);
            $entryFiles[$absolutePath] = $relativePath; */
        //}
        //} catch (\Exception $exception) {
        //    $this->addFlash('error', $exception->getMessage());
        //}
        //}

        return $this->render('lencor/admin/archive/archive_manager/entries_head.html.twig', array('folderId' => $folderId, 'entryId' => $entryId, 'addHeaderAndButtons' => $addHeaderAndButtons));
    }

    /**
     * @param Request $request
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("entries/remove_entry", name="entries_remove_entry")
     */
    public function removeEntry(Request $request, ArchiveEntryService $archiveEntryService)
    {
        $archiveEntries = array();
        if ($request->request->has('entryId'))
        {
            $archiveEntry = $archiveEntryService->removeEntry($request->get('entryId'), $this->getUser()->getId());
            array_push($archiveEntries, $archiveEntry);
        }

        return $this->render('lencor/admin/archive/archive_manager/entries_list.html.twig', array('archiveEntries' => $archiveEntries));
    }

    /**
     * @param Request $request
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("entries/restore_entry", name="entries_restore_entry")
     */
    public function restoreEntry(Request $request, ArchiveEntryService $archiveEntryService)
    {
        $archiveEntries = array();
        if ($request->request->has('entryId'))
        {
            $archiveEntry = $archiveEntryService->restoreEntry($request->get('entryId'), $this->getUser()->getId());
            array_push($archiveEntries, $archiveEntry);
        }

        return $this->render('lencor/admin/archive/archive_manager/entries_list.html.twig', array('archiveEntries' => $archiveEntries));
    }

    /**
     * @return Response
     * @Route("entries/flash_messages", name="flash_messages")
     */
    public function showFlashMessages()
    {
        return $this->render('lencor/admin/archive/flash_messages/archive_manager/flash_messages.html.twig');
    }

    /**
     * @return Response
     * @Route("entries/flash_messages_summary", name="flash_messages_summary")
     */
    public function showFlashMessagesSummary()
    {
        return $this->render('lencor/admin/archive/flash_messages/archive_manager/summary.html.twig');
    }

}

