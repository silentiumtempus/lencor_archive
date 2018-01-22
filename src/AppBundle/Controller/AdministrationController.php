<?php

namespace AppBundle\Controller;

use AppBundle\Entity\FactoryEntity;
use AppBundle\Form\FactoryForm;
use AppBundle\Service\FactoryService;
use AppBundle\Service\SettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

        return $this->render(':lencor/admin/archive/administration:factories_and_settings.html.twig', array('factories' => $factories));
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
     * @param FactoryEntity $factory
     * @param FactoryService $factoryService
     * @return Response
     * @Route("admin/factory/{factory}/edit",
     *     options = { "expose" = true },
     *     name = "admin-factory-edit")
     * @ParamConverter("factory", class="AppBundle:FactoryEntity", options = {"id" = "factory"})
     */
    public function editFactory(Request $request, FactoryEntity $factory, FactoryService $factoryService)
    {
        $factoryEditForm = $this->createForm(FactoryForm::class, $factory, array('attr' => array('id' => 'factory_form', 'function' => 'edit')));
        $factoryEditForm->handleRequest($request);
        if ($factoryEditForm->isSubmitted())
        {
            if ($factoryEditForm->isValid())
            {
                $factoryService->updateFactory();
                return $this->render(':lencor/admin/archive/administration:factories.html.twig', array('factories' => $factory));
            } else {
                $this->addFlash('danger', 'Форма не валидна');
            }
        } /*else { $this->addFlash('danger', 'No submit'); }; */

        return $this->render(':lencor/admin/archive/administration:factory_edit.html.twig', array('factoryForm' => $factoryEditForm->createView()));
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