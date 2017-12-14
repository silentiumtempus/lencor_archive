<?php

namespace AppBundle\Controller;

use AppBundle\Form\ArchiveEntryLogSearchForm;
use AppBundle\Service\ArchiveEntryService;
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
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/logging/", name="logging")
     */
    public function logManagerIndex(Request $request, LoggingService $loggingService, ArchiveEntryService $archiveEntryService)
    {
        $logSearchForm = $this->createForm(ArchiveEntryLogSearchForm::class);
        try {
            $entryId = null;
            $logsPath = null;
            $logRecords = null;
            $entryExists = false;
            $logSearchForm->handleRequest($request);
            if ($logSearchForm->isSubmitted() && $logSearchForm->isValid() && $request->isMethod('POST')) {
                $entryId = $logSearchForm->get('id')->getData();
                if ($archiveEntryService->getEntryById($entryId)) {
                    $entryExists = true;
                    $logsPath = $loggingService->getLogsHTTPPath($entryId);
                    $logRecords = $loggingService->getEntryLogs($entryId);
                }
            }
        } catch (\Exception $e) {
            //$folderPath = "failed : " . $e->getMessage();
        }

        return $this->render(':lencor/admin/archive/logging_manager:show_logs.html.twig', array('logSearchForm' => $logSearchForm->createView(), 'logsPath' => $logsPath, 'logRecords' => $logRecords, 'entryExists' => $entryExists, 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param LoggingService $loggingService
     * @return Response
     * @Route("/logging/open-file", name="open-file")
     */
    public function openLogFile(Request $request, LoggingService $loggingService)
    {
        $fileContent = null;
        $path = $loggingService->getLogsPath($request->get('entryId'));
        $file = $path . "/" . $request->get('file');
        if (filesize($file)>0) {
            $fileContent = explode("\n", file_get_contents($file));
        }
        $entryId = $request->get('entryId') ;
        return $this->render(':lencor/admin/archive/logging_manager:logfile.html.twig', array('entryId' => $entryId, 'fileContent' => $fileContent));
    }
}