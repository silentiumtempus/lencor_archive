<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Form\ArchiveEntryLogSearchForm;
use AppBundle\Service\FolderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogManagerController
 * @package AppBundle\Controller
 */
class LogManagerController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/logging/", name="logging")
     */
    public function logManagerIndex(Request $request, FolderService $folderService)
    {
        $logSearchForm = $this->createForm(ArchiveEntryLogSearchForm::class, new ArchiveEntryEntity());
        $logSearchForm->handleRequest($request);
        if ($logSearchForm->isSubmitted() && $logSearchForm->isValid() && $request->isMethod('POST'))
        {

        }

        return $this->render('lencor/admin/archive/logging_manager/index.html.twig', array('logSearchForm' => $logSearchForm->createView()));
    }
}