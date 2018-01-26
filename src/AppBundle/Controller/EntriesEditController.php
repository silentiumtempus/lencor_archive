<?php

namespace AppBundle\Controller;

use AppBundle\Form\EntrySearchByIdForm;
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
     * @return Response
     * @Route("entries/edit", name = "entry-edit")
     */
    public function entryEditIndex(Request $request)
    {
        $entrySearchByIdForm = $this->createForm(EntrySearchByIdForm::class);

        return $this->render(':lencor/admin/archive/administration:entry_edit.html.twig', array('entrySearchByIdForm' => $entrySearchByIdForm->createView()));
    }
}