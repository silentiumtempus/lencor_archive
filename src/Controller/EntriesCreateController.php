<?php

namespace App\Controller;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\SettingEntity;
use App\Form\EntryForm;
use App\Form\FactoryForm;
use App\Entity\FactoryEntity;
use App\Form\SettingForm;
use App\Service\CommonArchiveService;
use App\Service\EntryService;
use App\Service\FactoryService;
use App\Service\FolderService;
use App\Service\LoggingService;
use App\Service\SerializerService;
use App\Service\SettingService;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntryAdditionController
 * @package App\Controller
 */

class EntriesCreateController extends Controller
{
    /**
     * @param Request $request
     * @param EntryService $entryService
     * @param FactoryService $factoryService
     * @param SettingService $settingService
     * @param FolderService $folderService
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\
     * @Security("has_role('ROLE_USER')")
     * @Route("/entries/new",
     *     options = { "expose" = true },
     *     name = "entries-new")
     */

    public function createArchiveEntry(
        Request $request,
        EntryService $entryService,
        FactoryService $factoryService,
        SettingService $settingService,
        FolderService $folderService)
    {
        $entryForm = $this->createForm(EntryForm::class, new ArchiveEntryEntity(), array('attr' => array('id' => 'entry_form', 'function' => 'add')));
        $factoryForm = $this->createForm(FactoryForm::class, new FactoryEntity(), array('attr' => array('id' => 'factory_form', 'function' => 'add')));
        $settingForm = $this->createForm(SettingForm::class, new SettingEntity(), array('attr' => array('id' => 'setting_form', 'function' => 'add')));
        $pathRoot = $this->getParameter('archive.storage_path');
        $fs = new Filesystem();
        $entryId = null;

        $factoryForm->handleRequest($request);
        if ($factoryForm->isSubmitted()) {
            if ($factoryForm->isValid()) {
                try {
                    $factoryService->createFactory($factoryForm->getData());
                    $this->addFlash('success', 'Новый завод добавлен');
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Завод с указанным именем уже добавлен');
            }

            return new Response();
        }

        $settingForm->handleRequest($request);
        if ($settingForm->isSubmitted()) {
            if ($settingForm->isValid()) {
                try {
                    $settingService->createSetting($settingForm->getData());
                    $this->addFlash('success', 'Новая установка добавлена');
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Установка с таким именем уже добавлена для выбранного завода');
            }

            return new Response();
        }

        $entryForm->handleRequest($request);
        if ($entryForm->isSubmitted() && $fs->exists($pathRoot)) {
            if ($entryForm->isValid()) {
                $newEntry = $entryService->createEntry($entryForm->getData(), $this->getUser(), $folderService);

                return new Response($newEntry->getId());
            } else {
                if (!$request->get('submit')) {
                    $this->addFlash('danger', 'Форма заполнена неверно. Проверьте правильность заполнения формы');

                    return new Response();
                }

                return $this->render('lencor/admin/archive/archive_manager/entry_form.html.twig', array('entryForm' => $entryForm->createView(), 'entryId' => $entryId));
            }
        } elseif (!$fs->exists($pathRoot)) {
            $this->addFlash('danger', 'Корневой путь файловой системы архива недоступен');
        }

        return $this->render('lencor/admin/archive/archive_manager/new/new_entry.html.twig', array('entryForm' => $entryForm->createView(), 'factoryForm' => $factoryForm->createView(), 'settingForm' => $settingForm->createView(), 'entryId' => $entryId));
    }
}
