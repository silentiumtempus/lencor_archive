<?php

namespace App\Controller;
use App\Entity\ArchiveEntryEntity;
use App\Service\EntryService;
use App\Service\FileService;
use App\Service\RecoveryService;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RecoveryController
 * @package App\Controller
 */

class RecoveryController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/admin/recovery",
     *     options = { "expose" = true },
     *     name = "recovery")
     */

    public function recoveryIndex(Request $request) {

        return $this->render('lencor/admin/archive/administration/recovery/index.html.twig');
    }

    /**
     * @param Request $request
     * @param FileService $fileService
     * @return Response
     * @Route("/admin/recovery/find",
     *     options = { "expose" = true },
     *     name = "recovery-find")
     */

    public function findEntryFiles(Request $request, FileService $fileService)
    {
        $files = $fileService->locateFiles();

        return $this->render('lencor/admin/archive/administration/recovery/find_entry_files.html.twig', array('files' => $files));

    }

    /**
     * @param RecoveryService $recoveryService
     * @return Response
     * @Route("/admin/recovery/exec",
     *     options = { "expose" = true },
     *     name = "recovery-exec")
     */

    public function restoreEntries(RecoveryService $recoveryService)
    {
        $result = null;
        $recoveryService->restoreDatabase();

        //$serializer = SerializerBuilder::create()->build();
//        foreach ($files as $file)
 //       {
            //try {

            //$xml = file_get_contents($file);
            //$result = $serializer->deserialize($xml, 'App\Entity\ArchiveEntryEntity', 'xml');
            //} catch (\Exception $exception) {
            //     $this->addFlash('danger', $exception->getMessage());
           // }
   //     }

            //$entryService->restoreEntriesFromFiles($files);

        //return $this->json($xml);
        return $this->render('lencor/admin/archive/administration/recovery/recovery_result.html.twig', array('result' => $result));
    }
}