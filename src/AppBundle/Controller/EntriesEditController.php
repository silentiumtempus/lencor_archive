<?php

namespace AppBundle\Controller;

use AppBundle\Form\EntryForm;
use AppBundle\Form\EntrySearchByIdForm;
use AppBundle\Form\EntrySearchForm;
use AppBundle\Service\EntryService;
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
     * @param integer $entryId
     * @return Response
     * @Route("admin/entries/{entryId}",
     *     options = { "expose" = true },
     *     name = "admin-entries",
     *     requirements = { "$entryId" = "\d+" },
     *     defaults = { "entryId" : "0" }))
     */
    public function entryEditIndex(Request $request, EntryService $entryService, int $entryId)
    {
        $entrySearchByIdForm = $this->createForm(EntrySearchByIdForm::class);
        $entrySearchByIdForm->handleRequest($request);
        if ($entrySearchByIdForm->isSubmitted() && $request->isMethod("POST")) {
            if ($entrySearchByIdForm->isValid()) {
                $archiveEntryEntity = $entryService->getEntryById($entrySearchByIdForm->get('id')->getData());
                if ($archiveEntryEntity) {
                    $entryEditForm = $this->createForm(
                        EntryForm::class,
                        $archiveEntryEntity,
                        array('attr' => array('id' => 'archive_entry_form', 'function' => 'add')));

                    return $this->render(':lencor/admin/archive/administration:entry_edit.html.twig', array('entryForm' => $entryEditForm->createView()));
                }
            }

            return $this->render(':lencor/admin/archive/administration:entry_edit.html.twig');
        }

        $archiveEntryEntity = $entryService->getEntryById($entryId);
        if ($archiveEntryEntity) {
            $entryEditForm = $this->createForm(
                EntryForm::class,
                $archiveEntryEntity,
                array('attr' => array('id' => 'archive_entry_form', 'function' => 'edit')));

            return $this->render(':lencor/admin/archive/administration:entries.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView(), 'entryForm' => $entryEditForm->createView()));
        }

        return $this->render(':lencor/admin/archive/administration:entries.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView()));
    }
}