<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\LogRowsCountForm;
use App\Form\LogSearchForm;
use App\Service\EntryService;
use App\Service\LoggingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogManagerController
 * @package App\Controller
 */
class LogManagerController extends Controller
{
    /**
     * @param integer $entryId
     * @param Request $request
     * @param LoggingService $loggingService
     * @param EntryService $archiveEntryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/logging/{entryId}",
     *     requirements = { "entryId" = "\d+" },
     *     defaults = { "entryId" : "0" },
     *     options = { "expose" = true},
     *     name = "logging")
     */
    public function logManagerIndex(
        int $entryId,
        Request $request,
        LoggingService $loggingService,
        EntryService $archiveEntryService
    )
    {
        $logSearchForm = $this->createForm(LogSearchForm::class);
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

        return $this->render('lencor/admin/archive/logging_manager/show_logs.html.twig', array(
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
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/logging/{entryId}/open-sub-dir",
     *     requirements = { "entryId" = "\d+" },
     *     defaults = { "entryId" : "0" },
     *     options = { "expose" = true },
     *     name = "logging-open-sub-dir")
     * @TODO : use type-hint for entry in request instead of db querying
     */
    public function openSubDir(
        Request $request,
        int $entryId,
        LoggingService $loggingService
    )
    {
        if ($request->get('folder')) {
            $logsFolderPath =
                $loggingService->getLogsNavigationPath($request->get('parentFolder'), $request->get('folder'));
            $currentFolder =
                $loggingService->getLogsCurrentFolder($request->get('parentFolder'), $request->get('folder'));
            $logsPath = $loggingService->getLogsRootPath($entryId) . "/" . $currentFolder;
            $logFolders = $loggingService->getEntryLogFolders($logsPath);
            $logFiles = $loggingService->getEntryLogFiles($logsPath);

            return $this->render('lencor/admin/archive/logging_manager/logs_list.html.twig', array(
                'entryExists' => true,
                'logsFolderPath' => $logsFolderPath,
                'currentFolder' => $currentFolder,
                'entryId' => $entryId,
                'logFolders' => $logFolders,
                'logFiles' => $logFiles));
        } else {
            return $this->redirectToRoute('logging', array('entryId' => $entryId));
        }
    }

    /**
     * @param Request $request
     * @param LoggingService $loggingService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/logging/open-file",
     *     options = { "expose" = true },
     *     name = "logging-open-file")
     */
    public function openLogFile(Request $request, LoggingService $loggingService)
    {
        if ($request->get('file') || $request->get('log_rows_count_form')) {
            $fileContent = null;
            $entryId = $request->get('entryId');
            $file = $request->get('parentFolder') . "/" . $request->get('file');
            $rowsCountForm = $this->createForm(
                LogRowsCountForm::class,
                null,
                array('attr' => array(
                    'file' => $file,
                    'entryId' => $entryId,
                    'id' => 'logs_rows_count_form'
                ))
            );
            $rowsCountForm->handleRequest($request);
            if (
                $rowsCountForm->isSubmitted() &&
                $rowsCountForm->isValid() &&
                $request->isMethod('POST')
            ) {
                $fileContent = $loggingService->getFileContent(
                    $rowsCountForm->get('entryId')->getViewData(),
                    $rowsCountForm->get('file')->getViewData(),
                    $rowsCountForm->get('rowsCount')->getViewData()
                );
            } else {
                $fileContent = $loggingService->getFileContent($entryId, $file, 100);
            }
            return $this->render('lencor/admin/archive/logging_manager/logfile.html.twig', array(
                'rowsCountForm' => $rowsCountForm->createView(),
                'entryId' => $entryId,
                'fileContent' => $fileContent));
        } else {
            return $this->redirectToRoute('logging');
        }
    }
}
