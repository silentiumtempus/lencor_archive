<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\SettingEntity;
use AppBundle\Form\ArchiveEntryAddForm;
use AppBundle\Form\FactoryAddForm;
use AppBundle\Entity\FactoryEntity;
use AppBundle\Form\SettingAddForm;
use Doctrine\ORM\ORMException;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EntryAdditionController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/archive/new", name="lencor_entries_new")
     */
    public function archiveEntryAdd(Request $request)
    {
        $logger = $this->get('monolog.logger.event');
        $logger->info('Test');
        $newEntry = new ArchiveEntryEntity();
        $newFactory = new FactoryEntity();
        $newSetting = new SettingEntity();
        $newFolder = new FolderEntity();
        $entryForm = $this->createForm(ArchiveEntryAddForm::class, $newEntry);
        $factoryForm = $this->createForm(FactoryAddForm::class, $newFactory);
        $settingForm = $this->createForm(SettingAddForm::class, $newSetting);
        $pathRoot = $this->getParameter('lencor_archive.storage_path');
        $pathPermissions = $this->getParameter('lencor_archive.storage_permissions');
        $fs = new Filesystem();

        $factoryForm->handleRequest($request);
        if ($factoryForm->isSubmitted()) {
            if ($factoryForm->isValid()) {
                try {
                    $newFactory = $factoryForm->getData();
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($newFactory);
                    $em->flush();


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
                    $newSetting = $settingForm->getData();
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($newSetting);
                    $em->flush();
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
                    $userId = $this->getUser()->getId();
                    $newEntry = $entryForm->getData();

                    $pathYear = $pathRoot . "/" . $newEntry->getYear();
                    $pathFactory = $pathYear . "/" . $newEntry->getFactory()->getId();
                    $pathEntry = $pathFactory . "/" . $newEntry->getArchiveNumber();

                    try {
                        if (!$fs->exists($pathYear)) {
                            $fs->mkdir($pathYear, $pathPermissions);
                        }
                        if (!$fs->exists($pathFactory)) {
                            $fs->mkdir($pathFactory, $pathPermissions);
                        }
                        if (!$fs->exists($pathEntry)) {
                            $fs->mkdir($pathEntry, $pathPermissions);
                        } else {
                            $this->addFlash('warning', 'Внимание: директория для новой ячейки: ' . $pathEntry . ' уже существует');
                        }
                    } catch (IOException $IOException) {
                        $this->addFlash('danger', 'Ошибка создания директории: ' . $IOException->getMessage());
                    }
                    $filename = $pathEntry . "/" . $newEntry->getArchiveNumber() . ".txt";
                    if ($fs->exists($filename)) {
                        $this->addFlash('danger', 'Ошибка: файл ячейки: ' . $filename . ' уже существует. Продолжение прервано.');
                        throw new IOException(null);
                    } else {
                        try {
                            $fs->touch($filename);
                            $newEntry->setCataloguePath($newFolder);
                            $newEntry->setModifiedByUserId($userId);
                            $newEntry->setDeleteMark(false);
                            $newEntry->setDeletedByUserId(null);
                            //$newEntry->setSlug(null);

                            $newFolder->setArchiveEntry($newEntry);
                            $newFolder->setFolderName($newEntry->getYear() . "/" . $newEntry->getFactory()->getId() . "/" . $newEntry->getArchiveNumber());
                            $newFolder->setAddedByUserId($userId);
                            $newFolder->setDeleteMark(false);
                            $newFolder->setDeletedByUserId(null);

                            $serializer = SerializerBuilder::create()->build();
                            $jsonContent = $serializer->serialize($newEntry, 'yml');

                            file_put_contents($filename, $jsonContent);
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($newEntry);
                            $em->persist($newFolder);
                            $em->flush();
                            $this->addFlash('success', 'message.entryAdded');
                        } catch (IOException $IOException) {
                            $this->addFlash('danger', 'Ошибка записи файла ячейки: ' . $IOException->getMessage());
                        } catch (ORMException $ORMException) {
                            $this->addFlash('danger', 'Ошибка сохранения в БД: ' . $ORMException->getMessage());
                        }
                    }
                } catch (Exception $exception) {
                    $this->addFlash('danger', 'Произошла непредвиденная ошибка:' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Проверьте правильность заполнения формы');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new.html.twig', array('entryForm' => $entryForm->createView(), 'factoryForm' => $factoryForm->createView(), 'settingForm' => $settingForm->createView()));
    }

}