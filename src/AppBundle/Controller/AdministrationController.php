<?php

namespace AppBundle\Controller;

use AppBundle\Service\FactoryService;
use AppBundle\Service\SettingService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdministrationController
 * @package AppBundle\Controller
 */
class AdministrationController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/admin", name="admin")
     */
    public function adminIndex(Request $request)
    {
        return $this->render(':lencor/admin/archive/administration:index.html.twig');
    }

    /**
     * @param Request $request
     * @param FactoryService $factoryService
     * @param SettingService $settingService
     * @return Response
     * @Route("/admin/factories-and-settings", name = "admin-factories-and-settings")
     */
    public function factoriesAndSettings(Request $request, FactoryService $factoryService, SettingService $settingService)
    {
        $factories = $factoryService->getFactories();
        $settings = $settingService->findSettingsByFactoryId($factories[0]->getId());

        return $this->render(':lencor/admin/archive/administration:factories_and_settings.html.twig', array('factories' => $factories, 'settings' => $settings));
    }

    /**
     * @param Request $request
     * @param SettingService $settingService
     * @return Response
     * @Route("admin/settings",
     *     options = { "expose" = true },
     *     name = "admin-settings")
     */
    public function loadSettings(Request $request, SettingService $settingService)
    {
        $settings = $settingService->findSettingsByFactoryId($request->get('factoryId'));
        return $this->render(':lencor/admin/archive/administration:settings.html.twig', array('settings' => $settings));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("admin/news", name="admin-news")
     */
    public function news(Request $request)
    {
        return $this->render(':lencor/admin/archive/administration:news.html.twig');
    }
}