<?php
declare(strict_types=1);

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
     * @Route("/system/env/info",
     *     options = { "expose" = true },
     *     name = "system-env-info")
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
     * @param SystemService $systemService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/config/info",
     *     options = { "expose" = true },
     *     name = "system-config-info")
     */
    public function configInfo(Request $request, SystemService $systemService)
    {
        $configInfo = $systemService->getConfigInfo($request);

        return $this->render('lencor/admin/archive/system_manager/config_info.html.twig', array(
            'configInfo' => $configInfo
        ));
    }

    /**
     * @param Request $request
     * @param SystemService $systemService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/php-config/info",
     *     options = { "expose" = true },
     *     name = "system-php-config-info")
     */
    public function PHPConfigInfo(Request $request, SystemService $systemService)
    {
        $PHPConfigInfo = $systemService->getPHPConfigInfo($request);

        return $this->render('lencor/admin/archive/system_manager/php_config_info.html.twig', array(
            'PHPConfigInfo' => $PHPConfigInfo
        ));
    }

    /**
     * @param Request $request
     * @param SystemService $systemService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/permissions/info",
     *     options = { "expose" = true },
     *     name = "system-permissions-info")
     */
    public function permissionsInfo(Request $request, SystemService $systemService)
    {
        $configInfo = $systemService->getPermissionsInfo($request);

        return $this->render('lencor/admin/archive/system_manager/permissions_info.html.twig', array(
            'configInfo' => $configInfo
        ));
    }

    /**
     * @param SystemService $systemService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/system/php-info",
     *     options = { "expose" = true },
     *     name = "system-php-info")
     */
    public function phpInfo(SystemService $systemService)
    {
        $phpinfo = $systemService->getPHPInfo();

        return $this->render('lencor/admin/archive/system_manager/PHPInfo.html.twig', array(
            'phpinfo' => $phpinfo,
        ));
    }
}
