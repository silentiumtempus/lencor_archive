<?php

namespace AppBundle\Controller;

use AppBundle\Form\LogRowsCountForm;
use AppBundle\Form\LogSearchForm;
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
     * @param int $entryId
     * @param Request $request
     * @param LoggingService $loggingService
     * @param ArchiveEntryService $archiveEntryService
     * @return Response
     * @Route("/logging/{entryId}",
     *     name = "logging",
     *     requirements = { "entryId" = "\d+" },
     *     defaults = { "entryId" : "0" })
     */
    public function logManagerIndex(int $entryId, Request $request, LoggingService $loggingService, ArchiveEntryService $archiveEntryService)
    {
        $logSearchForm = $this->createForm(LogSearchForm::class);
        try {
            $currentFolder = "";
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
                $logsPath = $loggingService->getLogsRootPath($entryId);
                //$logsHTTPPath = $loggingService->getLogsHTTPPath($entryId);
                if ($logsPath) {
                    $logFolders = $loggingService->getEntryLogFolders($logsPath);
                    $logFiles = $loggingService->getEntryLogFiles($logsPath);
                }
            }
        } catch (\Exception $e) {
            //$folderPath = "failed : " . $e->getMessage();
        }

        return $this->render(':lencor/admin/archive/logging_manager:show_logs.html.twig', array(
            'logSearchForm' => $logSearchForm->createView(),
            'logFolders' => $logFolders,
            'logFiles' => $logFiles,
            'currentFolder' => $currentFolder,
            'entryExists' => $entryExists,
            'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @param int $entryId
     * @param LoggingService $loggingService
     * @return Response
     * @Route("/logging/{entryId}/open-sub-dir",
     *     requirements = { "entryId" = "\d+" },
     *     defaults = { "entryId" : "0" },
     *     options = { "expose" = true },
     *     name = "logging-open-sub-dir")
     */
    public function openSubDir(Request $request, int $entryId, LoggingService $loggingService)
    {
        $logsFolderPath = $loggingService->getLogsNavigationPath($request->get('parentFolder'), $request->get('folder'));
        $currentFolder = $loggingService->getLogsCurrentFolder($request->get('parentFolder'), $request->get('folder'));
        $logsPath = $loggingService->getLogsRootPath($entryId) . "/" . $currentFolder;
        $logFolders = $loggingService->getEntryLogFolders($logsPath);
        $logFiles = $loggingService->getEntryLogFiles($logsPath);

        return $this->render(':lencor/admin/archive/logging_manager:logs_list.html.twig', array(
            'entryExists' => true,
            'logsFolderPath' => $logsFolderPath,
            'currentFolder' => $currentFolder,
            'entryId' => $entryId,
            'logFolders' => $logFolders,
            'logFiles' => $logFiles));
    }

    /**
     * @param Request $request
     * @param LoggingService $loggingService
     * @return Response
     * @Route("/logging/open-file",
     *     options = { "expose" = true },
     *     name = "logging-open-file")
     */
    public function openLogFile(Request $request, LoggingService $loggingService)
    {
        $fileContent = null;
        $file = $request->get('parentFolder') . "/" . $request->get('file');
        $rowsCountForm = $this->createForm(
            LogRowsCountForm::class,
            null,
            array('attr' => array(
                'file' => $file,
                'entryId' => $request->get('entryId'),
                'id' => 'logs_rows_count_form'
            )));
        $rowsCountForm->handleRequest($request);
        if ($rowsCountForm->isSubmitted() && $rowsCountForm->isValid() && $request->isMethod('POST')) {
            $fileContent = $loggingService->getFileContent(
                $rowsCountForm->get('entryId')->getViewData(),
                $rowsCountForm->get('file')->getViewData(),
                $rowsCountForm->get('rowsCount')->getData()
            );
        } else {
            $fileContent = $loggingService->getFileContent($request->get('entryId'), $file, 100);
        }
        $entryId = $request->get('entryId');
        return $this->render(':lencor/admin/archive/logging_manager:logfile.html.twig', array(
            'rowsCountForm' => $rowsCountForm->createView(),
            'entryId' => $entryId,
            'fileContent' => $fileContent));
    }
}