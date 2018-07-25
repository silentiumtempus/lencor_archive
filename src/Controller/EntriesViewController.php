<?php

namespace App\Controller;

use App\Entity\ArchiveEntryEntity;
use App\Form\EntrySearchForm;
use App\Service\EntrySearchService;
use App\Service\EntryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    /**
     * @param Request $request
     * @param EntrySearchService $entrySearchService
     * @param ArchiveEntryEntity $entry
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/{entry}",
     *     options = { "expose" = true },
     *     name = "entries",
     *     requirements = { "entry" = "\d+" },
     *     defaults = { "entry" : "" }))
     * @ParamConverter("entry", class="App:ArchiveEntryEntity", options = { "id" = "entry" }, isOptional="true")
     */
    public function loadEntries(Request $request, EntrySearchService $entrySearchService, ArchiveEntryEntity $entry = null)
    {
        $search_limit = $this->getParameter('archive.entries_search_limit');
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entrySearchEntity = new ArchiveEntryEntity();
        $searchForm = $this->createForm(EntrySearchForm::class, $entrySearchEntity);
        $rootPath = $this->getParameter('lencor_archive.storage_path');
        if ($entry) {
            $archiveEntries[] = $entry;
        } else {
            $searchForm->handleRequest($request);
            if ($searchForm->isSubmitted()) {
                if ($searchForm->isValid() && $request->isMethod('POST')) {
                    try {
                        $filterQuery = $entrySearchService->performSearch($searchForm, $filterQuery);
                    } catch (\Exception $exception) {
                        $this->addFlash('error', $exception->getMessage());
                    }
                }
            }
            $archiveEntries = $entrySearchService->getQueryResult($finalQuery, $filterQuery, $search_limit, false);
        }

        return $this->render('/lencor/admin/archive/archive_manager/show_entries.html.twig', array('archiveEntries' => $archiveEntries, 'searchForm' => $searchForm->createView(), 'rootPath' => $rootPath));
    }

    /**
     * @param Request $request
     * @param EntryService $entryService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/remove_entry",
     *     options = { "expose" = true },
     *     name = "entries_remove_entry")
     */
    public function removeEntry(Request $request, EntryService $entryService)
    {
        $archiveEntry = null;
        if ($request->request->has('entryId')) {
            $archiveEntry = $entryService->removeEntry($request->get('entryId'), $this->getUser());
        }

        return $this->render('lencor/admin/archive/archive_manager/entry.html.twig', array('entry' => $archiveEntry));
    }

    /**
     * @param Request $request
     * @param EntryService $entryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("entries/restore_entry",
     *     options = { "expose" = true },
     *     name = "entries_restore_entry")
     */
    public function restoreEntry(Request $request, EntryService $entryService)
    {
        $archiveEntry = null;
        if ($request->request->has('entryId')) {
            $archiveEntry = $entryService->restoreEntry($request->get('entryId'), $this->getUser());
        }

        return $this->render('lencor/admin/archive/archive_manager/entry.html.twig', array('entry' => $archiveEntry));
    }

    /**
     * @param Request $request
     * @param EntryService $entryService
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/request_entry",
     *     options = { "expose" = true },
     *     name = "entries_request_entry")
     */
    public function requestEntry(Request $request, EntryService $entryService)
    {
        $archiveEntry = null;
        if ($request->request->has('entryId')) {
            $archiveEntry = $entryService->requestEntry($request->get('entryId'), $this->getUser());
        }

        return $this->render('lencor/admin/archive/archive_manager/entry.html.twig', array('entry' => $archiveEntry));
    }

    /**
     * @param Request $request
     * @param EntrySearchService $entrySearchService
     * @param string $field
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("entries/search_hints/{field}",
     *     name = "entries_search_hints",
     *     options = { "expose" = true },
     *     defaults = { "field" : "0" }
     *     )
     */
    public function loadSearchHints(Request $request, EntrySearchService $entrySearchService, string $field)
    {
        $limit = $this->getParameter('lencor_archive.search_hints_limit');
        $data = [];
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entrySearchEntity = new ArchiveEntryEntity();
        /** $searchForm, It doesn't work without form creation */
        $searchForm = $this->createForm(EntrySearchForm::class, $entrySearchEntity);
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid() && $request->isMethod('POST')) {

            $filterQuery = $entrySearchService->performSearch($searchForm, $filterQuery);
            $archiveEntries = $entrySearchService->getQueryResult($finalQuery, $filterQuery, $limit, true);
            if ($field !== 0) {
                foreach ($archiveEntries as $entry) {
                    switch ($field) {
                        case 'archiveNumber' :
                            $data[] = $entry->getArchiveNumber();
                            break;
                        case 'registerNumber' :
                            $data[] = $entry->getRegisterNumber();
                            break;
                        case 'contractNumber' :
                            $data[] = $entry->getContractNumber();
                            break;
                        case 'fullConclusionName' :
                            $data[] = $entry->getFullConclusionName();
                    }
                }
            }
        }

        return $this->json($data);
    }
}
