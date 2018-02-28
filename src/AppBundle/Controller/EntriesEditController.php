<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Form\EntryForm;
use AppBundle\Form\EntrySearchByIdForm;
use AppBundle\Service\EntryService;
use AppBundle\Service\FactoryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntriesEditController
 * @package AppBundle\Controller
 */
class EntriesEditController extends Controller
{
    /**
     * @param Request $request
     * @param EntryService $entryService
     * @param $entryId
     * @return Response
     * @Route("admin/entries/{entryId}",
     *     options = { "expose" = true },
     *     name = "admin-entries",
     *     requirements = { "$entryId" = "\d+" },
     *     defaults = { "entryId" : "0" }))
     */
    public function entryEditIndex(Request $request, EntryService $entryService, int $entryId)
    {
        $updateStatus = false;
        $archiveEntryEntity = null;
        $entrySearchByIdForm = $this->createForm(EntrySearchByIdForm::class);
        if ($request->request->has('entry_search_by_id_form')) {
            $entrySearchByIdForm->handleRequest($request);
            if ($entrySearchByIdForm->isSubmitted() && $request->isMethod("POST")) {
                if ($entrySearchByIdForm->isValid()) {
                    $archiveEntryEntity = $entryService->getEntryById($entrySearchByIdForm->get('id')->getData());
                }
            }
        }
        if ($entryId) {
            $archiveEntryEntity = $entryService->getEntryById($entryId);
        }
        if ($archiveEntryEntity) {
            $entryForm = $this->createForm(
                EntryForm::class,
                $archiveEntryEntity,
                array('attr' => array('id' => 'archive_entry_form', 'function' => 'edit')));
            $entryForm->handleRequest($request);
            if ($entryForm->isSubmitted()) {
                if ($entryForm->isValid()) {
                    try {
                        $entryService->updateEntry();
                        $updateStatus = true;
                    } catch (\Exception $exception) {
                        $this->addFlash('danger', 'Ошибка обновления ячейки: ' . $exception->getMessage());
                    }
                }
            }
            if (!$entryId || $updateStatus) {
                if ($updateStatus) {
                    $this->addFlash('success', 'Изменения сохранены');
                }

                return $this->render(':lencor/admin/archive/administration:entry_edit.html.twig', array(
                        'entryForm' => $entryForm->createView(),
                        'entryId' => $archiveEntryEntity->getId())
                );
            } else {

                return $this->render(':lencor/admin/archive/administration:entries.html.twig', array(
                        'entrySearchByIdForm' => $entrySearchByIdForm->createView(),
                        'entryForm' => $entryForm->createView(),
                        'entryId' => $archiveEntryEntity->getId())
                );
            }
        }

        return $this->render(':lencor/admin/archive/administration:entries.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView()));

    }
}