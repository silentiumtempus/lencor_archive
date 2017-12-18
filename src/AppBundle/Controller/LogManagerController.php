<?php

namespace AppBundle\Controller;

use AppBundle\Form\ArchiveEntryLogSearchForm;
use AppBundle\Service\ArchiveEntryService;
use AppBundle\Service\LoggingService;
use PhpExtended\Tail\Tail;
use PhpExtended\Tail\TailException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class LogManagerController
 * @package AppBundle\Controller
 */
class LogManagerController extends Controller
{
    /**
     * @param int $entryId
     * @param Request $request
     * @param LoggingService $loggingService
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/logging/{entryId}", name="logging", requirements={"entryId"="\d+"}, defaults={"entryId" : "0"})
     */
    public function logManagerIndex(int $entryId, Request $request, LoggingService $loggingService, ArchiveEntryService $archiveEntryService)
    {
        $logSearchForm = $this->createForm(ArchiveEntryLogSearchForm::class);
        try {
            $logsPath = null;
            $logsHTTPPath = null;
            $logFiles = null;
            $logFolders = null;
            $entryExists = false;
            $logSearchForm->handleRequest($request);
            if ($logSearchForm->isSubmitted() && $logSearchForm->isValid() && $request->isMethod('POST')) {
                $entryId = $logSearchForm->get('id')->getData();
            }
            if ($archiveEntryService->getEntryById($entryId)) {
                $entryExists = true;
                $logsPath = $loggingService->getLogsPath($entryId);
                $logsHTTPPath = $loggingService->getLogsHTTPPath($entryId);
                if ($logsPath) {
                    $logFolders = $loggingService->getEntryLogFolders($logsPath);
                    $logFiles = $loggingService->getEntryLogFiles($logsPath);
                }
            }
        } catch (\Exception $e) {
            //$folderPath = "failed : " . $e->getMessage();
        }

        return $this->render(':lencor/admin/archive/logging_manager:show_logs.html.twig', array('logSearchForm' => $logSearchForm->createView(), 'logsPath' => $logsHTTPPath, 'logFolders' => $logFolders, 'logFiles' => $logFiles, 'entryExists' => $entryExists, 'entryId' => $entryId));
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
            try {
                $tail = new Tail($file);
                $fileContent = $tail->smart(100, null, false);
            } catch (TailException $tailException) {
                $fileContent[0] = 'Exception : ' . $tailException->getMessage();
            }

            //$process = new Process("tail -100 " . $file . "");
            //$process->run();
            //$fileContent = $process->getOutput();
            //$fileContent = explode("\n", file_get_contents($file));
        }
        $entryId = $request->get('entryId') ;
        return $this->render(':lencor/admin/archive/logging_manager:logfile.html.twig', array('entryId' => $entryId, 'fileContent' => $fileContent));
    }
}