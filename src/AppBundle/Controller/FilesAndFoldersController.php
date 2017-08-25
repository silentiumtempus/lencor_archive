<?php
/**
 * Created by PhpStorm.
 * User: Vinegar
 * Date: 023 23.08.17
 * Time: 11:54
 */

namespace AppBundle\Controller;

use AppBundle\Entity\FileEntity;
use AppBundle\Entity\FolderEntity;
use AppBundle\Form\FileAddForm;
use AppBundle\Form\FolderAddForm;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;

class FilesAndFoldersController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/new_folder", name="lencor_entries_new_folder")
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
                $this->addFlash('danger', 'Такая директория уже существует. Операция прервана');
            }
        }
        return $this->render('lencor/admin/archive/archive_manager_new_folder.html.twig', array('folderAddForm' => $folderAddForm->createView(), 'entryId' => $entryId));
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("/lencor_entries/new_file", name="lencor_entries_new_file")
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
     * @param String $entryId
     * @Route("/lencor_entries/change_last_update_info", name="lencor_entries_change_last_update_info")
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
     * @Route("/lencor_entries/last_update_info", name="lencor_entries_last_update_info")
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
     * @Route("/lencor_entries/get_folder_entryId", name="lencor_entries_get_folder_entryId")
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
     * $paran Request $request
     * @return Response
     * @Route("/lencor_entries/download_file", name="lencor_entries_download_file")
     */
}