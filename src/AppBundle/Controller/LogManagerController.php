<?php

namespace AppBundle\Controller;

use AppBundle\Form\ArchiveEntryLogSearchForm;
use AppBundle\Service\LoggingService;
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
     * @param LoggingService $loggingService
     * @return Response
     * @Route("/logging/", name="logging")
     */
    public function logManagerIndex(Request $request, LoggingService $loggingService)
    {
        $logSearchForm = $this->createForm(ArchiveEntryLogSearchForm::class);
        try {
            $logRecords = '';
            $logSearchForm->handleRequest($request);
            if ($logSearchForm->isSubmitted() && $logSearchForm->isValid() && $request->isMethod('POST'))
            {
                $logRecords = $loggingService->getEntryLogs($logSearchForm);
            }
        } catch (\Exception $e) {
            //$folderPath = "failed : " . $e->getMessage();
        }

        return $this->render('lencor/admin/archive/logging_manager/show_logs.html.twig', array('logSearchForm' => $logSearchForm->createView(), 'logRecords' => $logRecords));
    }
}