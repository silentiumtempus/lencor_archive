<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\SettingEntity;
use AppBundle\Form\ArchiveEntryAddForm;
use AppBundle\Form\FactoryAddForm;
use AppBundle\Entity\FactoryEntity;
use AppBundle\Form\SettingAddForm;
use AppBundle\Service\ArchiveEntryService;
use AppBundle\Service\FactoryService;
use AppBundle\Service\FolderService;
use AppBundle\Service\LoggingService;
use AppBundle\Service\SettingService;
use Doctrine\ORM\ORMException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EntryAdditionController
 * @package AppBundle\Controller
 */
class EntryAdditionController extends Controller
{
    /**
     * @param Request $request
     * @param ArchiveEntryService $archiveEntryService
     * @param FactoryService $factoryService
     * @param SettingService $settingService
     * @param FolderService $folderService
     * @param LoggingService $loggingService
     * @return Response
     * @Route("/entries/new", name="entries_new")
     */
    public function archiveEntryAdd(
        Request $request,
        ArchiveEntryService $archiveEntryService,
        FactoryService $factoryService,
        SettingService $settingService,
        FolderService $folderService,
        LoggingService $loggingService)
    {
        $session = $this->container->get('session');
        $entryForm = $this->createForm(ArchiveEntryAddForm::class, new ArchiveEntryEntity());
        $factoryForm = $this->createForm(FactoryAddForm::class, new FactoryEntity());
        $settingForm = $this->createForm(SettingAddForm::class, new SettingEntity());
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
                $this->addFlash('danger', 'Завод с указанныс именем уже добавлен');
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
        if ($entryForm->isSubmitted() && $fs->exists($pathRoot)) {
            if ($entryForm->isValid()) {
                try {
                    $newEntryEntity = $entryForm->getData();
                    $entryPath = $folderService->checkAndCreateFolders($newEntryEntity);
                    $filename = $entryPath . "/" . $newEntryEntity->getArchiveNumber() . ".txt";

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
                    $loggingService->logEntry($newEntryEntity, $entryPath, $this->getUser(), $session->getFlashBag()->peekAll());
                } catch (Exception $exception) {
                    $this->addFlash('danger', 'Произошла непредвиденная ошибка:' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Проверьте правильность заполнения формы');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager/new_entry.html.twig', array('entryForm' => $entryForm->createView(), 'factoryForm' => $factoryForm->createView(), 'settingForm' => $settingForm->createView()));
    }

}