<?php

namespace App\Controller;

use App\Entity\ArchiveEntryEntity;
use App\Form\EntrySearchForm;
use App\Service\EntrySearchService;
use App\Service\EntryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Elastica\Query\BoolQuery;
use Elastica\Query;

/**
 * Class ArchiveViewController
 * @package App\Controller
 */
class EntriesViewController extends Controller
{

    /*set_include_path('/var/www/lencor/public_html/new/web/');
$file = 'test.txt';

$wr = file_get_contents($file);

$wr = $wr . $request->get('entryId') . "!!!!!!!!!!!!!!" . "\n\n";
//$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";

file_put_contents($file, $wr); */

    /**
     * @param Request $request
     * @param EntrySearchService $entrySearchService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/", name="entries",
     *     options = { "expose" = true }
     *     )
     */
    public function loadEntries(Request $request, EntrySearchService $entrySearchService)
    {
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entrySearchEntity = new ArchiveEntryEntity();
        $searchForm = $this->createForm(EntrySearchForm::class, $entrySearchEntity);
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
     * @param EntryService $archiveEntryService
     * @return Response
     * @Route("entries/remove_entry", name = "entries_remove_entry")
     */
    public function removeEntry(Request $request, EntryService $archiveEntryService)
    {
        $archiveEntry = null;
        if ($request->request->has('entryId')) {
            $archiveEntry = $archiveEntryService->removeEntry($request->get('entryId'), $this->getUser()->getId());
        }

        return $this->render('lencor/admin/archive/archive_manager/entry.html.twig', array('entry' => $archiveEntry));
    }

    /**
     * @param Request $request
     * @param EntryService $archiveEntryService
     * @return Response
     * @Route("entries/restore_entry", name = "entries_restore_entry")
     */
    public function restoreEntry(Request $request, EntryService $archiveEntryService)
    {
        $archiveEntry = null;
        if ($request->request->has('entryId')) {
            $archiveEntry = $archiveEntryService->restoreEntry($request->get('entryId'), $this->getUser()->getId());
        }

        return $this->render('lencor/admin/archive/archive_manager/entry.html.twig', array('entry' => $archiveEntry));
    }
}
