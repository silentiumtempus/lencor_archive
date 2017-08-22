<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 021 21.02.17
 * Time: 19:41
 */

namespace AppBundle\Controller;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Entity\FileEntity;
use AppBundle\Entity\SettingEntity;
use AppBundle\Form\ArchiveEntryAddForm;
use AppBundle\Form\ArchiveEntrySearchForm;
use AppBundle\Form\FactoryAddForm;
use AppBundle\Entity\FactoryEntity;
use AppBundle\Form\FileAddForm;
use AppBundle\Form\FolderAddForm;
use AppBundle\Form\SettingAddForm;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use JMS\Serializer\SerializerBuilder;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Elastica\Query\BoolQuery;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Elastica\Query;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Exception\IOException;


class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/welcome", name="lencor_welcome")
     */
    public function welcomeAction()
    {

        /*set_include_path('/var/www/lencor/public_html/new/web/');
$file = 'test.txt';

$wr = file_get_contents($file);

$wr = $wr . $request->get('entryId') . "!!!!!!!!!!!!!!" . "\n\n";
//$wr = $wr . $newFolder>get('parentFolder')->getViewData() . "!!!!!!!!!!!!!!" . "\n\n";

file_put_contents($file, $wr); */

        return $this->render('base.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/welcome_index", name="lencor_welcome_index")
     */

    public function welcomeIndexAction(Request $request)
    {
        $entrySearchEntity = new ArchiveEntryEntity();
        $searchForm = $this->createForm(ArchiveEntrySearchForm::class, $entrySearchEntity);
        $elasticManager = $this->container->get('fos_elastica.finder.lencor_archive.archive_entries');
        $finalQuery = new Query();
        $filterQuery = new BoolQuery();
        $entryCatalogue = null;

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid() && $request->isMethod('POST')) {
            try {
                // if ($form->getData()) {

                foreach ($searchForm->getIterator() as $key => $child) {
                    if ($child->getData()) {
                        if ($key == 'factory') {
                            $conditionFactory = (new Term())->setTerm('factory.id', $child->getViewData());
                            $filterQuery->addMust($conditionFactory);
                        } else if ($key == 'setting') {
                            $conditionSetting = (new Term())->setTerm('setting.id', $child->getViewData());
                            $filterQuery->addMust($conditionSetting);
                        } else {
                            $filterMatchField = (new Match())->setFieldQuery($child->getName(), $child->getViewData());
                            $filterQuery->addMust($filterMatchField);
                        }
                    }
                }
                $finalQuery->setQuery($filterQuery);
                //}
            } catch (\Exception $exception) {
                $this->addFlash('error', $exception->getMessage());
            }
        }
        $finalQuery->addSort(array('year' => array('order' => 'ASC')));
        $archiveEntries = $elasticManager->find($finalQuery, 3000);
        $boolQuery = null;
        $finalQuery = null;

        return $this->render('lencor/admin/archive/index.html.twig', array('archiveEntries' => $archiveEntries, 'searchForm' => $searchForm->createView()));
    }


    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_view", name="lencor_entries_view")
     */

    function showEntryDetails(Request $request)
    {
        $entryCatalogue = null;
        $entryDirectories = null;
        $entryId = null;
        $htmlTree = null;
        $nodeFiles = null;

        /** review in case of any session data utilization needs **/

        /*$session = $this->container->get('session');
        if ($request->request->has('entryId')) {
                $session->set('entryId', $request->get('entryId'));
            }
        elseif (!$entryId) {
                $entryId = $session->get('entryId');
            } */

        /** **************************************************** **/

        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $filesRepository = $this->getDoctrine()->getRepository('AppBundle:FileEntity');
        $addHeaderAndButtons = false;
        $entryUpdatedData = null;


        $options = array();

        if ($request->request->has('entryId')) {
            $entryId = $request->get('entryId');
            $folderNode = $foldersRepository->findOneByArchiveEntry($entryId);
            $addHeaderAndButtons = true;

        } else if ($request->request->has('folderId')) {
            $folderId = $request->get('folderId');
            $folderNode = $foldersRepository->findOneById($folderId);

        }

        //$entryUpdatedData->
        $fileNodes = $filesRepository->findByParentFolder($folderNode);
        $htmlTree = $foldersRepository->childrenHierarchy($folderNode, /* starting from root nodes */
            true, /* true: If you pass true as a value for this argument, you'll get only the direct children of the node */
            $options, false /* false: don't include parent node); */);


        /** for file system handling **/
        /*$entryId = $request->get('entryId');
        $pathRoot = $this->getParameter('lencor_archive.storage_path');
        $targetEntry = $this->getDoctrine()->getRepository('AppBundle:ArchiveEntryEntity')->find($entryId);
        $entryCatalogue = $pathRoot . "/" . $targetEntry->getCataloguePath();

        $finder = new Finder();
        $filesystem = new Filesystem();
        $entryDirectories = $finder->directories()->in($entryCatalogue);
        $entryDirectories->sortByName();
        $entryDirectories = array_keys(iterator_to_array($entryDirectories));

        foreach ($entryDirectories as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            unset($entryDirectories[$key]);
            $entryDirectories[$relativePath] = $relativePath;
        }

        $entryFiles = $finder->files()->in($entryCatalogue)->depth('== 0');
        $entryFiles->sortByName();
        $entryFiles = array_keys(iterator_to_array($entryFiles));


        foreach ($entryFiles as $key => $absolutePath) {
            $relativePath = $filesystem->makePathRelative($absolutePath, $entryCatalogue);
            $relativePath = rtrim($relativePath, "/");
            unset($entryFiles[$key]);
            $entryFiles[$absolutePath] = $relativePath; */
        //}
        //} catch (\Exception $exception) {
        //    $this->addFlash('error', $exception->getMessage());
        //}
        //}

        return $this->render('lencor/admin/archive/archive_manager_entries_view.html.twig', array('fileNodes' => $fileNodes, 'entryId' => $entryId, 'htmlTree' => $htmlTree, 'addHeaderAndButtons' => $addHeaderAndButtons));

    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_new_folder", name="lencor_entries_new_folder")
     */
    public function createNewFolder(Request $request)
    {

        $newFolder = new FolderEntity();
        $session = $this->container->get('session');
        $entryId = $request->get('entryId');

        if ($entryId) {

            $session->set('entryId', $request->get('entryId'));
        } elseif (!$entryId) {
            $entryId = $session->get('entryId');
        }

        $repository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $rootFolder = $repository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        $folderAddForm = $this->createForm(FolderAddForm::class, $newFolder, array('action' => $this->generateUrl('lencor_entries_new_folder'), 'attr' => array('folderId' => $folderId, 'id' => 'folder_add_form')));

        $folderAddForm->handleRequest($request);
        if ($folderAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($folderAddForm->isValid()) {
                try {
                    $newFolderEntity = $folderAddForm->getData();
                    $parentFolder = $repository->findOneById($folderAddForm->get('parentFolder')->getViewData());
                    $newFolderEntity->setParentFolder($parentFolder);
                    $userId = $this->getUser()->getId();
                    $newFolderEntity->setAddedByUserId($userId);
                    $newFolderEntity->setDeleteMark(false);
                    $newFolderEntity->setDeletedByUserId(null);
                    $newFolderEntity->setSlug(null);
                    $fileSystem = new Filesystem();
                    $storagePath = $this->getParameter('lencor_archive.storage_path');
                    $pathPermissions = $this->getParameter('lencor_archive.storage_permissions');
                    $creationNotFailed = true;
                    $directoryExistedPreviously = false;

                    if ($fileSystem->exists($storagePath)) {
                        try {
                            $newFolderAbsPath = $storagePath;
                            $binaryPath = $repository->getPath($parentFolder);
                            foreach ($binaryPath as $folderName) {
                                $newFolderAbsPath .= "/" . $folderName;
                                if (!$fileSystem->exists($newFolderAbsPath)) {
                                    $this->addFlash('warning', 'Директория ' . $newFolderAbsPath . ' отсутствует в файловой системе. Пересоздаю...');
                                    try {
                                        $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                                        $this->addFlash('success', 'Директория ' . $newFolderAbsPath . ' cоздана.');
                                    } catch (IOException $IOException) {
                                        $this->addFlash('danger', 'Директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                                        $this->addFlash('danger', 'Загрузка в БД прервана: изменения не внесены.');
                                        $creationNotFailed = false;
                                    }
                                }
                            }
                            $newFolderAbsPath .= "/" . $newFolderEntity->getFolderName();
                            if (!$fileSystem->exists($newFolderAbsPath)) {
                                try {
                                    $fileSystem->mkdir($newFolderAbsPath, $pathPermissions);
                                    $this->addFlash('success', 'Новая директория ' . $newFolderEntity->getFolderName() . ' успешно создана.');
                                } catch (IOException $IOException) {
                                    $this->addFlash('danger', 'Новая директория ' . $newFolderAbsPath . ' не создана. Ошибка файловой системы: ' . $IOException->getMessage());
                                    $creationNotFailed = false;
                                }
                            } else {
                                $directoryExistedPreviously = true;
                                $this->addFlash('warning', 'Директория ' . $newFolderAbsPath . ' уже существует в файловой системе.');
                            }
                        } catch (\Exception $exception) {
                            $this->addFlash('danger', 'Новая директория не записана в файловую систему. Ошибка файловой системы: ' . $exception->getMessage());
                        }
                    } else {
                        $this->addFlash('danger', 'Файловая система архива недоступна. Операция не выполнена.');
                    }

                    if ($creationNotFailed) {
                        try {
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($newFolderEntity);
                            $em->flush();
                            $this->changeLastUpdateInfo($entryId);
                            $this->addFlash('success', 'Новая директория успешно добавлена в БД');
                        } catch (\Exception $exception) {
                            if ($exception instanceof ConstraintViolationException) {
                                $this->addFlash('danger', ' В БД найдена запись о дубликате создаваемой директории. Именения БД отклонены.');
                            } else {
                                $this->addFlash('danger', 'Директория не записана в БД. Ошибка БД: ' . $exception->getMessage());
                            }
                            if (!$directoryExistedPreviously) {
                                try {
                                    $fileSystem->remove($newFolderAbsPath);
                                    $this->addFlash('danger', 'Новая директория удалёна из файловой системы в связи с ошибкой БД.');
                                } catch (IOException $IOException) {
                                    $this->addFlash('danger', 'Ошибка при удалении новой директории из файловой системы: ' . $IOException->getMessage());
                                }
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Операция не выполнена.');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new_folder.html.twig', array('folderAddForm' => $folderAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_new_file", name="lencor_entries_new_file")
     */
    public function uploadNewFile(Request $request)
    {
        $newFile = new FileEntity();
        $session = $this->container->get('session');
        $entryId = $request->get('entryId');

        if ($entryId) {
            $session->set('entryId', $request->get('entryId'));
        } elseif (!$entryId) {
            $entryId = $session->get('entryId');
        }

        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $entriesRepository = $this->getDoctrine()->getRepository('AppBundle:ArchiveEntryEntity');
        $rootFolder = $foldersRepository->findOneByArchiveEntry($entryId);
        $folderId = $rootFolder->getRoot()->getId();

        $fileAddForm = $this->createForm(FileAddForm::class, $newFile, array('action' => $this->generateUrl('lencor_entries_new_file'), 'method' => 'POST', 'attr' => array('folderId' => $folderId, 'id' => 'file_add_form')));

        $fileAddForm->handleRequest($request);
        if ($fileAddForm->isSubmitted() && $request->isMethod('POST')) {
            if ($fileAddForm->isValid()) {
                try {
                    $newFileEntity = $fileAddForm->getData();
                    $parentFolder = $foldersRepository->findOneById($fileAddForm->get('parentFolder')->getViewData());
                    $newFileEntity->setParentFolder($parentFolder);
                    $userId = $this->getUser()->getId();
                    $newFileEntity->setAddedByUserId($userId);
                    $newFileEntity->setDeleteMark(false);
                    $newFileEntity->setSlug(null);
                    $newFileEntity->setDeletedByUserId(null);

                    $rootPath = $this->getParameter('lencor_archive.storage_path');
                    $folderAbsPath = $rootPath;
                    $binaryPath = $foldersRepository->getPath($parentFolder);
                    $uploadNotFailed = true;

                    foreach ($binaryPath as $folderName) {
                        $folderAbsPath .= "/" . $folderName;
                    }
                    $originalName = pathinfo($newFileEntity->getFileName()->getClientOriginalName(), PATHINFO_FILENAME) . "-" . (hash('crc32', uniqid(), false) . "." . $newFileEntity->getFileName()->getClientOriginalExtension());
                    $fileWithAbsPath = $folderAbsPath . "/" . $originalName;
                    $fileSystem = new Filesystem();

                    if (!$fileSystem->exists($fileWithAbsPath)) {
                        $fileExistedPreviously = false;
                        try {
                            $newFileEntity->getFileName()->move($folderAbsPath, $originalName);
                            $newFileEntity->setChecksum(md5_file($fileWithAbsPath));
                            $this->addFlash('success', 'Новый документ записан в директорию ' . $parentFolder);
                        } catch (\Exception $exception) {
                            $uploadNotFailed = false;
                            $this->addFlash('danger', 'Новый документ не записан в директорию. Ошибка файловой системы: ' . $exception->getMessage());
                            $this->addFlash('danger', 'Загрузка в БД прервана: изменения не внесены.');
                        }
                    } else {
                        $fileExistedPreviously = true;
                        $this->addFlash('danger', 'Документ с таким именем уже существует в директории назначения. Перезапись отклонена.');
                    }

                    if ($uploadNotFailed) {
                        try {
                            $em = $this->getDoctrine()->getManager();
                            $newFileEntity->setFileName($originalName);
                            $em->persist($newFileEntity);
                            $em->flush();
                            $this->changeLastUpdateInfo($entryId);

                            $this->addFlash('success', 'Ноsый документ добавлен в БД');
                        } catch (\Exception $exception) {
                            if ($exception instanceof ConstraintViolationException) {
                                $this->addFlash('danger', ' В БД найдена запись о дубликате загружаемого документа. Именения БД отклонены.' . $exception->getMessage());
                            } else {
                                $this->addFlash('danger', 'Документ не записан в БД. Ошибка БД: ' . $exception->getMessage());
                            }
                            if (!$fileExistedPreviously) {
                                try {
                                    $fileSystem->remove($fileWithAbsPath);
                                    $this->addFlash('danger', 'Новый документ удалён из директории в связи с ошибкой БД.');

                                } catch (IOException $IOException) {
                                    $this->addFlash('danger', 'Ошибка файловой системы при удалении загруженного документа: ' . $IOException->getMessage());
                                };
                            }
                        }
                    };
                } catch (\Exception $exception) {
                    $this->addFlash('danger', 'Невозможно выполнить операцию. Ошибка: ' . $exception->getMessage());
                }
            } else {
                $this->addFlash('danger', 'Форма заполнена неверно. Операция не выполнена.');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new_file.html.twig', array('fileAddForm' => $fileAddForm->createView(), 'entryId' => $entryId));
    }

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

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/factories_manager", name="lencor_factories_manager")
     */
    public function factoriesManager()
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:FactoryEntity');
        $factories = $repository->findAll();
        return $this->render('lencor/admin/archive/factories_manager.html.twig', array('factories' => $factories));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/factories_manager/new", name="lencor_factories_manager_new")
     */
    public function factoriesManagerAdd(Request $request)
    {
        $factoryAddForm = new FactoryEntity();
        $form = $this->createForm(FactoryAddForm::class, $factoryAddForm);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $newFactory = $form->getData();
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($newFactory);
                    $em->flush();

                    $this->addFlash('success', 'Новый завод добавлен');
                    return $this->redirectToRoute('lencor_entries_new');
                } catch (ORMException $ORMException) {

                    $this->get('session')->getFlashBag()->add('error', 'Your custom message');
                    $this->addFlash('error', 'Завод с таким именем уже добавлен');
                    return $this->redirectToRoute('lencor_entries_new');
                } catch (\Exception $exception) {
                    $this->addFlash('error', 'Добавление нового завода НЕ произведено');
                    return $this->redirectToRoute('lencor_entries_new');
                }
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Завод с таким именем уже добавлен');
            return $this->redirectToRoute('lencor_entries_new');
        }
        return $this->render('lencor/admin/archive/factories_manager_new.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/settings_manager", name="lencor_settings_manager")
     */
    public function settingsManager()
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:SettingEntity');
        $settings = $repository->findAll();
        return $this->render('lencor/admin/archive/settings_manager.html.twig', array('settings' => $settings));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/settings_manager/new", name="lencor_settings_manager_new")
     */
    public function settingsManagerAdd(Request $request)
    {
        $settingAddForm = new SettingEntity();
        $form = $this->createForm(SettingAddForm::class, $settingAddForm);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $newSetting = $form->getData();
                $em = $this->getDoctrine()->getManager();
                $em->persist($newSetting);
                $em->flush();

                $this->addFlash('success', 'Новая установка добавлена');
                return $this->redirectToRoute('lencor_settings_manager');
            } catch (\Exception $exception) {
                $this->addFlash('error', 'Добавление нового завода НЕ произведено');
                return $this->redirectToRoute('lencor_welcome_index');
            }
        }
        return $this->render('lencor/admin/archive/settings_manager_new.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param String $entryId
     * @Route("/lencor_entries_change_last_update_info", name="lencor_entries_change_last_update_info")
     */

    public function changeLastUpdateInfo($entryId)
    {
        try {
            $em = $this->getDoctrine()->getManager();
            $entriesRepository = $this->getDoctrine()->getRepository('AppBundle:ArchiveEntryEntity');
            $archiveEntry = $entriesRepository->findOneById($entryId);
            $archiveEntry->setModifiedbyUserId($this->getUser()->getId());
            $em->flush();

        } catch (\Exception $exception) {
            $this->addFlash('error', 'Информация об изменениях не записана в ячейку. Ошибка: ' . $exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_last_update_info", name="lencor_entries_last_update_info")
     */
    public function loadLastUpdateInfo(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $lastUpdateInfo = null;

        if ($request->request->has('entryId')) {
            $entryId = $request->get('entryId');
            $qb->select('en.lastModified', 'us.usernameCanonical')->from('AppBundle:ArchiveEntryEntity', 'en')->leftJoin('AppBundle:User', 'us', \Doctrine\ORM\Query\Expr\Join::WITH, 'en.modifiedByUserId = us.id')->where('en.id = ' . $entryId);
            $entryUpdatedDataQuery = $qb->getQuery();
            $lastUpdateInfo = $entryUpdatedDataQuery->getResult();
        } else if ($request->request->has('folderId')) {
            $folderNode = $foldersRepository->findOneById($request->get('folderId'));

            $entryId = $folderNode->getRoot()->getArchiveEntry()->getId();
            //$qb->select('root_id')->from('archive_files')->where('id = ' . $folderId);
            $qb->select('en.lastModified', 'us.usernameCanonical')->from('AppBundle:ArchiveEntryEntity', 'en')->leftJoin('AppBundle:User', 'us', \Doctrine\ORM\Query\Expr\Join::WITH, 'en.modifiedByUserId = us.id')->where('en.id IN (:archiveEntryId)')->setParameter('archiveEntryId', $entryId);
            $entryUpdatedDataQuery = $qb->getQuery();
            $lastUpdateInfo = $entryUpdatedDataQuery->getResult();
        }
        return $this->render('lencor/admin/archive/archive_manager_entries_update_info.html.twig', array('lastUpdateInfo' => $lastUpdateInfo));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries_get_folder_entryId", name="lencor_entries_get_folder_entryId")
     */

    public function getFolderEntryId(Request $request)
    {
        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $entryId = null;

        if ($request->request->has('folderId')) {
            $folderNode = $foldersRepository->findOneById($request->get('folderId'));
            $entryId = $folderNode->getRoot()->getArchiveEntry()->getId();
        }
        return new Response($entryId);
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/remove_file", name="lencor_entries_remove_file")
     */

    public function removeFile(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $deletedFile = $em->getRepository('AppBundle:FileEntity')->findOneById($request->get('fileId'));
        $deletedFile->setDeleteMark(true);
        $deletedFile->setDeletedByUserId($this->getUser()->getId());
        $em->flush();

        return $this->render('lencor/admin/archive/archive_manager_file.html.twig', array('deletedFile' => $deletedFile));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/restore_file", name="lencor_entries_restore_file")
     */

    public function restoreFile(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $deletedFile = $em->getRepository('AppBundle:FileEntity')->findOneById($request->get('fileId'));
        $deletedFile->setDeleteMark(false);
        $deletedFile->setDeletedByUserId(null);
        $em->flush();

        return $this->render('lencor/admin/archive/archive_manager_file.html.twig', array('deletedFile' => $deletedFile));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/remove_folder", name="lencor_entries_remove_folder")
     */

    public function removeFolder(Request $request)
    {

        $foldersRepository = $this->getDoctrine()->getRepository('AppBundle:FolderEntity');
        $filesRepository = $this->getDoctrine()->getRepository('AppBundle:FileEntity');
        $em = $this->getDoctrine()->getManager();
        $deletedFolder = $foldersRepository->findOneById($request->get('folderId'));
        $folderChildren = $foldersRepository->getChildren($deletedFolder, false, null, null, true);

        if ($folderChildren) {
            foreach ($folderChildren as $childFolder) {
                if (!$childFolder->getDeleteMark()) {
                    $childFolder->setDeleteMark(true);
                    $childFolder->setDeletedByUserId($this->getUser()->getId());
                    $childFiles = $filesRepository->findByParentFolder($childFolder->getId());
                    if ($childFiles) {
                        foreach ($childFiles as $childFile) {
                            if (!$childFile->getDeleteMark()) {
                                $childFile->setDeleteMark(true);
                                $childFile->setDeletedByUserId($this->getUser()->getId());
                            }
                        }
                    }
                }
            }
        }
        $em->flush();

        return $this->render('lencor/admin/archive/archive_manager_folder.html.twig', array('directory' => $deletedFolder));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/restore_folder", name="lencor_entries_restore_folder")
     */

    public function restoreFolder(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $deletedFolder = $em->getRepository('AppBundle:FolderEntity')->findOneById($request->get('folderId'));
        $deletedFolder->setDeleteMark(false);
        $deletedFolder->setDeletedByUserId(null);
        $em->flush();

        return $this->render('lencor/admin/archive/archive_manager_folder.html.twig', array('directory' => $deletedFolder));
    }


    /**
     * @return Response
     * @Route("/lencor_flash_messages", name="lencor_flash_messages")
     */
    public function showFlashMessages()
    {
        return $this->render('lencor/default/flash_messages.html.twig');
    }
}

