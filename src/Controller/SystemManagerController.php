<?php

namespace App\Controller;

use App\Service\SystemService;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SystemManagerController
 * @package App\Controller
 */

class SystemManagerController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/",
     *     options = { "expose" = true },
     *     name = "system")
     */

    public function systemIndex(Request $request)
    {
        return $this->render('lencor/admin/archive/system_manager/index.html.twig');
    }

    /**
     * @param Request $request
     * @param SystemService $systemService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/info",
     *     options = { "expose" = true },
     *     name = "system-info")
     */

    public function systemInfo(Request $request, SystemService $systemService)
    {
        $sysInfo = $systemService->getSystemInfo($request);
        return $this->render('lencor/admin/archive/system_manager/info.html.twig', array(
            'sysInfo' => $sysInfo
        ));
    }

    /**
     * @param Request $request
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/php-info",
     *     options = { "expose" = true },
     *     name = "system-php-info")
     */

    public function phpInfo(Request $request)
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        return $this->render('lencor/admin/archive/system_manager/PHPInfo.html.twig', array(
            'phpinfo' => $phpinfo,
        ));
    }
}
