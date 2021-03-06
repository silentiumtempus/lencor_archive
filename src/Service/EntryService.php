<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use App\Factory\EntryFactory;
use App\Factory\FolderFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntryService
 * @package App\Services
 */
class EntryService
{
    private $em;
    private $container;
    private $pathRoot;
    private $pathKeys;
    private $deletedFolder;
    private $entriesRepository;
    private $foldersRepository;
    private $commonArchiveService;
    private $serializerService;
    private $loggingService;
    private $folderFactory;
    private $entryFactory;

    /**
     * EntryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param CommonArchiveService $commonArchiveService
     * @param SerializerService $serializerService
     * @param LoggingService $loggingService
     * @param FolderFactory $folderFactory
     * @param EntryFactory $entryFactory
     */

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        CommonArchiveService $commonArchiveService,
        SerializerService $serializerService,
        LoggingService $loggingService,
        FolderFactory $folderFactory,
        EntryFactory $entryFactory
    )
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->deletedFolder = $this->container->getParameter('archive.deleted.folder_name');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->pathKeys = ['year', 'factory', 'archiveNumber'];
        $this->commonArchiveService = $commonArchiveService;
        $this->serializerService = $serializerService;
        $this->loggingService = $loggingService;
        $this->folderFactory = $folderFactory;
        $this->entryFactory = $entryFactory;
    }

    /**
     * @param int $entryId
     * @return ArchiveEntryEntity|null
     */
    public function getEntryById(int $entryId)
    {
        return $this->entriesRepository->findOneById($entryId);
    }

    /**
     * @param array $entryIdsArray
     * @return ArchiveEntryEntity|null|object
     */
    public function getEntriesList(array $entryIdsArray)
    {
        return $this->entriesRepository->find($entryIdsArray);
    }

    /**
     * @param ArchiveEntryEntity $entryEntity
     * @param User $user
     * @param int|null $entryId
     * @throws \Exception
     */
    public function changeLastUpdateInfo(
        User $user,
        ArchiveEntryEntity $entryEntity = null,
        int $entryId = null
    )
    {
        if (!$entryEntity) {
            $entryEntity = $this->entriesRepository->findOneById($entryId);
        }
        $entryEntity
            ->setModifiedByUser($user)
            ->setLastModified(new \DateTime());
        $this->em->flush();
    }

    /**
     * @param Request $request
     * @return ArchiveEntryEntity
     */
    public function loadLastUpdateInfo(Request $request)
    {
        $lastUpdateInfo = null;
        if ($request->request->has('entryId')) {
            $lastUpdateInfo = $this->entriesRepository->getUpdateInfoByEntry($request->get('entryId'));
        } elseif ($request->request->has('folderId')) {
            $folderNode = $this->foldersRepository->findOneById($request->get('folderId'));
            $lastUpdateInfo =
                $this->entriesRepository->getUpdateInfoByFolder(
                    $folderNode->getRoot()->getArchiveEntry()->getId()
                );
        }

        return $lastUpdateInfo;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function setFolderId(Request $request)
    {
        $session = $this->container->get('session');
        $folderId = $request->get('folderId');
        if ($folderId) {
            $session->set('folderId', $request->get('folderId'));

            return $folderId;
        } else {

            return $session->get('folderId');
        }
    }

    /**
     * @param FormInterface $newEntryForm
     * @param User $user
     * @return ArchiveEntryEntity
     */
    public function createEntry(
        FormInterface $newEntryForm,
        User $user
    ): ?ArchiveEntryEntity
    {
        try {
            $newEntry = $this->entryFactory->prepareEntry($newEntryForm, $user);
            $newFolderEntity = $this->folderFactory->prepareNewRootFolder($newEntry, $user);
            $entryPath = $this->commonArchiveService->checkAndCreateFolders($newEntry, true, false);
            $newEntry->setCataloguePath($newFolderEntity);
            $this->serializerService->serializeEntry($newEntry, $entryPath, true);
            $this->persistEntry($newEntry, $newFolderEntity);
            $this->container->get('session')->getFlashBag()->add(
                'success',
                'Запись успешно создана.'
            );

            $logsDir = $entryPath . "/logs";
            $this->loggingService->logEntry(
                $newEntry,
                $logsDir,
                $user,
                $this->container->get('session')->getFlashBag()->peekAll()
            );
            return $newEntry;
        } catch (IOException $IOException) {
            $this->container->get('session')->getFlashBag()->add(
                'danger',
                'Ошибка записи файла ячейки: ' . $IOException->getMessage()
            );
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add(
                'danger',
                'Произошла непредвиденная ошибка:' . $exception->getMessage()
            );
        }

        return null;
    }

    /**
     * @param ArchiveEntryEntity $newEntry
     * @param FolderEntity $newFolder
     */
    public function persistEntry(ArchiveEntryEntity $newEntry, FolderEntity $newFolder)
    {
        try {
            $this->em->persist($newEntry);
            $this->em->persist($newFolder);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->container->get('session')->getFlashBag()->add(
                'danger',
                'Ошибка сохранения в БД: ' . $exception->getMessage()
            );
        }
    }

    /**
     * @param int $entryId
     * @param User $user
     * @return ArchiveEntryEntity
     */
    public function removeEntry(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
            ->setRemovalMark(true)
            ->setMarkedByUser($user);
        $this->em->flush();

        return $archiveEntry;
    }

    /**
     * @param int $entryId
     * @param User $user
     * @return ArchiveEntryEntity
     */
    public function restoreEntry(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
            ->setRemovalMark(false)
            ->setModifiedByUser($user)
            ->setMarkedByUser(null)
            ->setRequestMark(false)
            ->setRequestedByUsers(null);
        $this->em->flush();

        return $archiveEntry;
    }

    /**
     * @param int $entryId
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public function requestEntry(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        if ($archiveEntry->getRemovalMark()) {
            if ($archiveEntry->getRequestMark() ?? $archiveEntry->getRequestMark() != false) {
                $users = $archiveEntry->getRequestedByUsers();
                if (!$users || (array_search($user->getId(), $users, true)) === false) {
                    $users[] = $user->getId();
                    $archiveEntry->setRequestedByUsers($users);
                    $archiveEntry->setRequestedByUsers($users);
                }
            } else {
                $archiveEntry
                    ->setRequestMark(true)
                    ->setRequestedByUsers([$user->getId()])
                    ->setRequestsCount($archiveEntry->getRequestsCount());
            }
            $this->em->flush();
        }
        $this->updateEntryInfo($archiveEntry, $user, true);

        return $archiveEntry;
    }

    /**
     * @param ArchiveEntryEntity $archiveEntry
     * @return array
     */
    public function getOriginalData(ArchiveEntryEntity $archiveEntry)
    {
        return $this->em->getUnitOfWork()->getOriginalEntityData($archiveEntry);
    }

    /**
     * @param array $originalEntry
     * @param ArchiveEntryEntity $archiveEntry
     * @return array
     */
    public function checkPathChanges(array $originalEntry, ArchiveEntryEntity $archiveEntry)
    {
        $entryUpdates = $this->checkEntryUpdates($originalEntry, $archiveEntry);

        return $this->findPathParameters($entryUpdates);
    }

    /**
     * @param array $originalEntry
     * @param ArchiveEntryEntity $archiveEntry
     * @return array
     */
    public function checkEntryUpdates(array $originalEntry, ArchiveEntryEntity $archiveEntry)
    {
        $updatedEntry = json_decode(json_encode($archiveEntry), true);
        $originalEntry['factory'] = $originalEntry['factory']->getId();

        return array_diff_assoc($originalEntry, $updatedEntry);
    }

    /**
     * @param array $entryUpdates
     * @return  array
     */
    public function findPathParameters(array $entryUpdates)
    {
        $pathParameters = array_flip($this->pathKeys);

        return array_intersect_key($entryUpdates, $pathParameters);
    }

    /**
     * @param ArchiveEntryEntity $archiveEntry
     * @param bool $isDeleted
     * @return string
     */
    public function constructEntryPath(ArchiveEntryEntity $archiveEntry, bool $isDeleted)
    {
        $returnString = null;
        if ($isDeleted) {

            $returnString =
                $this->pathRoot .
                "/" .
                $this->deletedFolder .
                "/" .
                $archiveEntry->getYear() .
                "/" .
                $archiveEntry->getFactory()->getId() .
                "/" .
                $archiveEntry->getArchiveNumber();
        } else {

            $returnString =
                $this->pathRoot .
                "/" .
                $archiveEntry->getYear() .
                "/" .
                $archiveEntry->getFactory()->getId() .
                "/" .
                $archiveEntry->getArchiveNumber();
        }

        return $returnString;
    }

    /**
     * @param array $originalEntry
     * @return string
     */
    public
    function constructExistingPath(array $originalEntry)
    {
        return
            $this->pathRoot .
            "/" .
            $originalEntry['year'] .
            "/" .
            $originalEntry['factory']->getId() .
            "/" .
            $originalEntry['archiveNumber'];
    }

    /**
     * @param ArchiveEntryEntity $archiveEntry
     * @param bool $isDeleted
     * @return bool
     */
    public
    function checkNewPath(ArchiveEntryEntity $archiveEntry, bool $isDeleted)
    {
        $newPath = $this->constructEntryPath($archiveEntry, $isDeleted);
        $fs = new Filesystem();
        if ($fs->exists($newPath)) {

            return false;
        } else {

            return true;
        }
    }

    /**
     * This is called on entry update submit
     */
    public function updateEntry()
    {
        $this->em->flush();
    }

    /**
     * @param array $entriesArray
     * @param bool $delete
     * @return array
     */
    public function handleEntriesDelete(array $entriesArray, bool $delete)
    {
        $entryIdsArray['remove'] = [];
        $entryIdsArray['reload'] = [];
        $entries = $this->entriesRepository->find($entriesArray);
        foreach ($entries as $entry) {
            $entryIdsArray = $this->handleEntryDelete($entry, $delete, $entryIdsArray);
        }

        return $entryIdsArray;
    }

    /**
     * @param ArchiveEntryEntity $entryEntity
     * @param bool $delete
     * @param array $entryIdsArray
     * @return array
     */
    public function handleEntryDelete(
        ArchiveEntryEntity $entryEntity,
        bool $delete,
        array $entryIdsArray
    )
    {
        $this->commonArchiveService->checkAndCreateFolders($entryEntity, false, $delete);
        if ($this->moveEntry($entryEntity, $delete)) {
            $entryEntity->setDeleted($delete);
            $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryEntity);
            $rootFolder->setDeleted($delete);
            $this->updateEntry();
            $entryPath = $this->constructEntryPath($entryEntity, $delete);
            $this->serializerService->serializeEntry($entryEntity, $entryPath, false);
            if ($entryEntity->getDeletedChildren() > 0) {
                $entryIdsArray['reload'][] = $entryEntity->getId();
            } else {
                $entryIdsArray['remove'][] = $entryEntity->getId();
            }
        }

        return $entryIdsArray;
    }

    /**
     * @param ArchiveEntryEntity $entryEntity
     * @param bool $delete
     * @return bool
     */
    private function moveEntry(ArchiveEntryEntity $entryEntity, bool $delete)
    {
        $oldPath = $this->constructEntryPath($entryEntity, ($delete ? false : true));
        $newPath = $this->constructEntryPath($entryEntity, $delete);
        $fs = new Filesystem();
        $fs->rename($oldPath, $newPath);

        return true;
    }

    /**
     * @param ArchiveEntryEntity $entryEntity
     * @param User $user
     * @param bool $updated
     * @throws \Exception
     */
    public function updateEntryInfo(
        ArchiveEntryEntity $entryEntity,
        User $user,
        bool $updated
    )
    {
        $entryPath = $this->constructEntryPath($entryEntity, false);
        (!$updated) ?: $this->changeLastUpdateInfo($user, $entryEntity, null);
        $this->serializerService->serializeEntry($entryEntity, $entryPath, false);
    }
}
