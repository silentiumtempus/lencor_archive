<?php

namespace App\Controller;

use App\Entity\FactoryEntity;
use App\Entity\SettingEntity;
use App\Form\FactoryForm;
use App\Form\SettingForm;
use App\Service\FactoryService;
use App\Service\SerializerService;
use App\Service\SettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdministrationController
 * @package App\Controller
 */
class AdministrationController extends Controller
{
    /**
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin",
     *     options = { "expose" = true },
     *     name="admin")
     */

    public function adminIndex()
    {
        return $this->render('lencor/admin/archive/administration/index.html.twig');
    }

    /**
     * @param FactoryService $factoryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("/admin/factories",
     *     options = { "expose" = true },
     *     name = "admin-factories-and-settings")
     */

    public function adminFactoriesAndSettings(FactoryService $factoryService)
    {
        $factories = $factoryService->getFactories();

        return $this->render('lencor/admin/archive/administration/factories_and_settings/factories_and_settings.html.twig', array('factories' => $factories));
    }

    /**
     * @param FactoryEntity $factory
     * @param SettingService $settingService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/factories/{factory}/settings",
     *     requirements = { "factory" = "\d+" },
     *     defaults = { "factory" : "" },
     *     options = { "expose" = true },
     *     name = "admin-settings")
     * @ParamConverter("factory", class="App:FactoryEntity", options = { "id" = "factory" })
     */

    public function loadSettings(FactoryEntity $factory, SettingService $settingService)
    {
        $settings = $settingService->findSettingsByFactoryId($factory->getId());

        return $this->render('lencor/admin/archive/administration/factories_and_settings/settings.html.twig', array('settings' => $settings));
    }

    /**
     * @param FactoryEntity $factory
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/factories/{factory}/load",
     *     requirements = { "factory" = "\d+" },
     *     defaults = { "factory" : "" },
     *     options = { "expose" = true },
     *     name = "admin-factory-load")
     * @ParamConverter("factory", class="App:FactoryEntity", options = { "id" = "factory" })
     */

    public function loadFactory(FactoryEntity $factory)
    {
        return $this->render('lencor/admin/archive/administration/factories_and_settings/factories.html.twig', array('factories' => $factory));
    }

    /**
     * @param Request $request
     * @param FactoryEntity $factory
     * @param FactoryService $factoryService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/factories/{factory}/edit",
     *     requirements = { "factory" = "\d+" },
     *     defaults = { "factory" : "" },
     *     options = { "expose" = true },
     *     name = "admin-factory-edit")
     * @ParamConverter("factory", class = "App:FactoryEntity", options = { "id" = "factory" })
     */

    public function editFactory(Request $request, FactoryEntity $factory, FactoryService $factoryService)
    {
        $form_id = 'factory_form_' . $factory->getId();
        $factoryEditForm = $this->createForm(FactoryForm::class, $factory, array('attr' => array('id' => $form_id, 'function' => 'edit')));
        $factoryEditForm->handleRequest($request);
        if ($factoryEditForm->isSubmitted()) {
            if ($factoryEditForm->isValid()) {
                try {
                    $factoryService->updateFactory();
                    $this->addFlash('success', 'Завод переименован');
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $exception->getMessage());
                }
                return $this->render('lencor/admin/archive/administration/factories_and_settings/factories.html.twig', array('factories' => $factory));
            } else {
                $this->addFlash('danger', 'Завод с таким наименованием уже существует или форма заполнена неправильно');
            }
        }

        return $this->render('lencor/admin/archive/administration/factories_and_settings/factory_edit.html.twig', array('factoryForm' => $factoryEditForm->createView()));
    }

    /**
     * @param SettingEntity $setting
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/settings/{setting}/load",
     *     requirements = { "setting" = "\d+" },
     *     defaults = { "setting" : "" },
     *     options = { "expose" = true },
     *     name = "admin-setting-load")
     * @ParamConverter("setting", class="App:SettingEntity", options = { "id" = "setting" })
     */

    public function loadSetting(SettingEntity $setting)
    {
        return $this->render('lencor/admin/archive/administration/factories_and_settings/settings.html.twig', array('settings' => $setting));
    }

    /**
     * @param Request $request
     * @param SettingEntity $setting
     * @param SettingService $settingService
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/settings/{setting}/edit",
     *     requirements = { "setting" = "\d+" },
     *     defaults = { "setting" : "" },
     *     options = { "expose" = true },
     *     name = "admin-setting-edit")
     * @ParamConverter("setting", class = "App:SettingEntity", options = { "id" = "setting" })
     */

    public function editSetting(Request $request, SettingEntity $setting, SettingService $settingService)
    {
        $form_id = 'setting_form_' . $setting->getId();
        $settingEditForm = $this->createForm(SettingForm::class, $setting, array('attr' => array('id' => $form_id, 'function' => 'edit')));
        $settingEditForm->handleRequest($request);
        if ($settingEditForm->isSubmitted()) {
            if ($settingEditForm->isValid()) {
                try {
                    $settingService->updateSetting();
                    $this->addFlash('success', 'Установка переименована');
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $exception->getMessage());
                }
                return $this->render('lencor/admin/archive/administration/factories_and_settings/settings.html.twig', array('settings' => $setting));
            } else {
                $this->addFlash('danger', 'Установка с таким наименованием уже существует или форма заполнена неправильно');
            }
        }

        return $this->render('lencor/admin/archive/administration/factories_and_settings/setting_edit.html.twig', array('settingForm' => $settingEditForm->createView()));
    }

    /**
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/users",
     *     options = { "expose" = true },
     *     name = "admin-users")
     */

    public function adminUsers()
    {
        return $this->render('lencor/admin/archive/administration/users/index.html.twig');
    }

    /**
     * @param SerializerService $serializerService
     * @return JsonResponse
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/serialize/users",
     *     options = { "expose" = true },
     *     name = "admin-serialize-users")
     */
    public function serializeUsers(SerializerService $serializerService)
    {
        $serializerService->serializeUsers();

        return new JsonResponse(0);
    }

    /**
     * @return Response
     * @Security("has_role('ROLE_ADMIN')")
     * @Route("admin/news",
     *     options = { "expose" = true },
     *     name = "admin-news")
     */

    public function adminNews()
    {
        return $this->render('lencor/admin/archive/administration/news/index.html.twig');
    }
}
