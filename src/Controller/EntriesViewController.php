<?php

namespace App\Controller;

use App\Entity\ArchiveEntryEntity;
use App\Form\EntrySearchForm;
use App\Service\CommonArchiveService;
use App\Service\DeleteService;
use App\Service\EntrySearchService;
use App\Service\EntryService;
use App\Service\FolderService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Elastica\Query\BoolQuery;
use Elastica\Query;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @param string $deleted
     * @return Response
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/{entry}",
     *     options = { "expose" = true },
     *     name = "entries",
     *     requirements = { "entry" = "\d+" },
     *     defaults = { "entry" : "" }))
     * @Route("/admin/{deleted}/{entry}",
     *     options = { "expose" = true },
     *     name = "admin-deleted-entries",
     *     requirements = {
     *          "deleted" = "deleted",
     *          "entry" = "\d+"
     *     },
     *     defaults = {
     *          "deleted" = "deleted",
     *          "entry" : ""
     *     },
     * )
     * @ParamConverter("entry", class = "App:ArchiveEntryEntity", options = { "id" = "entry" }, isOptional="true")
     */

    public function loadEntries(Request $request, EntrySearchService $entrySearchService, string $deleted = null, ArchiveEntryEntity $entry = null)
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
            $archiveEntries = $entrySearchService->getQueryResult($finalQuery, $filterQuery, $search_limit, $deleted ?: false);
        }

        return $this->render('/lencor/admin/archive/archive_manager/show_entries.html.twig', array('archiveEntries' => $archiveEntries, 'searchForm' => $searchForm->createView(), 'rootPath' => $rootPath, 'deleted' => $deleted));
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
     * @param DeleteService $deleteService
     * @param ArchiveEntryEntity $entry
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/delete/entry/{entry}",
     *     options = { "expose" = true },
     *     name = "entries_delete",
     *     requirements = { "entry" = "\d+" },
     *     defaults = {"entry" : ""}
     *     )
     * @ParamConverter("entry", class = "App:ArchiveEntryEntity", options = { "id" = "entry" }, isOptional="true")
     */
    public function deleteEntry(Request $request, DeleteService $deleteService, ArchiveEntryEntity $entry = null)
    {

        $resp = $entry ? $entry->getArchiveNumber() : 'Нет данных';
        return new Response($resp);
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
