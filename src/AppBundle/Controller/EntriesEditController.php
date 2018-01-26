<?php

namespace AppBundle\Controller;

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
    public function loadEntryDetails(Request $request)
    {

        return $this->render(':lencor/admin/archive/archive_manager:entry_edit.html.twig');
    }
}