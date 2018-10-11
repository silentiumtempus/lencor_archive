<?php

namespace App\Controller;
use App\Entity\ArchiveEntryEntity;
use App\Service\EntryService;
use App\Service\FileService;
use App\Service\RecoveryService;
use JMS\Serializer\SerializerBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/recovery",
     *     options = { "expose" = true },
     *     name = "admin-recovery")
     */

    public function recoveryIndex() {

        return $this->render('lencor/admin/archive/administration/recovery/index.html.twig');
    }

    /**
     * @param RecoveryService $recoveryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/recovery/find",
     *     options = { "expose" = true },
     *     name = "admin-recovery-find")
     */

    public function findEntryFiles(RecoveryService $recoveryService)
    {
        $files = $recoveryService->locateFiles();

        return $this->render('lencor/admin/archive/administration/recovery/find_entry_files.html.twig', array('files' => $files));
    }

    /**
     * @param RecoveryService $recoveryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/recovery/exec",
     *     options = { "expose" = true },
     *     name = "admin-recovery-exec")
     */

    public function restoreEntries(RecoveryService $recoveryService)
    {
        $result = null;
        $recoveryService->restoreDatabase();

        return $this->render('lencor/admin/archive/administration/recovery/recovery_result.html.twig', array('result' => $result));
    }
}