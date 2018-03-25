<?php

namespace App\Controller;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\SettingEntity;
use App\Form\EntryForm;
use App\Form\FactoryForm;
use App\Entity\FactoryEntity;
use App\Form\SettingForm;
use App\Service\EntryService;
use App\Service\FactoryService;
use App\Service\FolderService;
use App\Service\LoggingService;
use App\Service\SettingService;
use Doctrine\ORM\ORMException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntryAdditionController
 * @package App\Controller
 */
class EntriesAdditionController extends Controller
{
    /**
     * @param Request $request
     * @param EntryService $archiveEntryService
     * @param FactoryService $factoryService
     * @param SettingService $settingService
     * @param FolderService $folderService
     * @param LoggingService $loggingService
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\
     * @Route("/entries/new",
     *     options = { "expose" = true },
     *     name = "entries-new")
     */
    public function archiveEntryAdd(
        Request $request,
        EntryService $archiveEntryService,
        FactoryService $factoryService,
        SettingService $settingService,
        FolderService $folderService,
        LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $entryForm = $this->createForm(EntryForm::class, new ArchiveEntryEntity(), array('attr' => array('id' => 'entry_form', 'function' => 'add')));
        $factoryForm = $this->createForm(FactoryForm::class, new FactoryEntity(), array('attr' => array('id' => 'factory_form', 'function' => 'add')));
        $settingForm = $this->createForm(SettingForm::class, new SettingEntity(), array('attr' => array('id' => 'setting_form', 'function' => 'add')));
        $pathRoot = $this->getParameter('lencor_archive.storage_path');
        $fs = new Filesystem();

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
        }

        $entryForm->handleRequest($request);
        if ($entryForm->isSubmitted() && $fs->exists($pathRoot) ) {
            $this->addFlash('warning', 'Сабмит есть');
            if ($entryForm->isValid()) {
                $this->addFlash('warning', 'Ваоидация пройдена');
                try {
                    $newEntryEntity = $entryForm->getData();
                    $entryPath = $folderService->checkAndCreateFolders($newEntryEntity);
                    $filename = $entryPath . "/" . $newEntryEntity->getArchiveNumber() . ".txt";
                    $logsDir = $entryPath . "/logs";

                    //TODO: mb change the below design ?
                    if ($fs->exists($filename)) {
                        $this->addFlash('danger', 'Ошибка: файл ячейки: ' . $filename . ' уже существует. Продолжение прервано.');
                        throw new IOException('Файл ячейки уже существует');
                    } else {
                        try {
                            $newFolderEntity = new FolderEntity();
                            $archiveEntryService->prepareEntry($newEntryEntity, $newFolderEntity, $this->getUser()->getId());
                            $folderService->prepareNewRootFolder($newFolderEntity, $newEntryEntity, $this->getUser()->getId());
                            $archiveEntryService->writeDataToEntryFile($newEntryEntity, $filename);
                            $archiveEntryService->persistEntry($newEntryEntity, $newFolderEntity);
                            $this->addFlash('success', 'Запись успешно создана.');
                        } catch (IOException $IOException) {
                            $this->addFlash('danger', 'Ошибка записи файла ячейки: ' . $IOException->getMessage());
                        } catch (ORMException $ORMException) {
                            $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $ORMException->getMessage());
                        }
                    }
                    $loggingService->logEntry($newEntryEntity, $logsDir, $this->getUser(), $session->getFlashBag()->peekAll());
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Произошла непредвиденная ошибка:' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Проверьте правильность заполнения формы');
            }
        }

        return $this->render('lencor/admin/archive/archive_manager/new_entry.html.twig', array('entryForm' => $entryForm->createView(), 'factoryForm' => $factoryForm->createView(), 'settingForm' => $settingForm->createView()));
    }

}