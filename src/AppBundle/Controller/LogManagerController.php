<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Form\ArchiveEntryLogSearchForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogManagerController
 * @package AppBundle\Controller
 */
class LogManagerController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/logging/", name="logging")
     */
    public function logManagerIndex()
    {
        $logSearchForm = $this->createForm(ArchiveEntryLogSearchForm::class, new ArchiveEntryEntity());
        return $this->render('lencor/admin/archive/logging_manager/index.html.twig', array('logSearchForm' => $logSearchForm->createView()));
    }
}