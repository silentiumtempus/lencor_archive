<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FactoryEntity;
use App\Entity\FolderEntity;
use App\Entity\SettingEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class EntryService
 * @package App\Services
 */
class EntryService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $pathKeys;
    protected $deletedFolder;
    protected $entriesRepository;
    protected $foldersRepository;
    protected $commonArchiveService;

    /**
     * EntryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     * @param CommonArchiveService $commonArchiveService
     */

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, CommonArchiveService $commonArchiveService)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('archive.storage_path');
        $this->deletedFolder = $this->container->getParameter('archive.deleted.folder_name');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->pathKeys = ['year', 'factory', 'archiveNumber'];
        $this->commonArchiveService = $commonArchiveService;
    }

    //@TODO: why string?

    /**
     * @param string $entryId
     * @return ArchiveEntryEntity|null
     */

    public function getEntryById(string $entryId)
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
     * @param int $entryId
     * @param User $user
     */

    public function changeLastUpdateInfo(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
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
            $lastUpdateInfo = $this->entriesRepository->getUpdateInfoByFolder($folderNode->getRoot()->getArchiveEntry()->getId());
        }
        return $lastUpdateInfo;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function setEntryId(Request $request)
    {
        $session = $this->container->get('session');
        $entryId = $request->get('entryId');
        if ($entryId) {
            $session->set('entryId', $request->get('entryId'));
            return $entryId;
        } else {
            return $session->get('entryId');
        }
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
     * @param ArchiveEntryEntity $newEntry
     * @param FolderEntity $newFolder
     * @param User $user
     */

    public function prepareEntry(ArchiveEntryEntity $newEntry, FolderEntity $newFolder, User $user)
    {
        $newEntry
            ->setCataloguePath($newFolder)
            ->setAddedByUser($user)
            ->setRemovalMark(false)
            ->setMarkedByUser(null);
    }

    /**
     * @param ArchiveEntryEntity $newEntry
     * @param FolderEntity $newFolder
     */

    public function persistEntry(ArchiveEntryEntity $newEntry, FolderEntity $newFolder)
    {
        $this->em->persist($newEntry);
        $this->em->persist($newFolder);
        $this->em->flush();
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
            ->setremovalMark(true)
            ->setmarkedByUser($user);
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
            ->setmarkedByUser(null)
            ->setRequestMark(false)
            ->setRequestedByUsers(null);
        $this->em->flush();

        return $archiveEntry;
    }

    /**
     * @param int $entryId
     * @param User $user
     * @return ArchiveEntryEntity
     */

    public function requestEntry(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        if ($archiveEntry->getremovalMark()) {
            if ($archiveEntry->getRequestMark() ?? $archiveEntry->getRequestMark() != false) {
                $users = $archiveEntry->getRequestedByUsers();
                if (!$users || (array_search($user->getId(), $users, true)) === false) {
                    $users[] = $user->getId();
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
        $this->changeLastUpdateInfo($entryId, $user);

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
        //$updatedEntry['factory'] = $archiveEntry->getFactory()->getId();
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
        if ($isDeleted) {

            return $this->pathRoot . "/" . $this->deletedFolder . "/" . $archiveEntry->getYear() . "/" . $archiveEntry->getFactory()->getId() . "/" . $archiveEntry->getArchiveNumber();
        }

        return $this->pathRoot . "/" . $archiveEntry->getYear() . "/" . $archiveEntry->getFactory()->getId() . "/" . $archiveEntry->getArchiveNumber();
    }

    /**
     * @param array $originalEntry
     * @return string
     */

    public function constructExistingPath(array $originalEntry)
    {
        return $this->pathRoot . "/" . $originalEntry['year'] . "/" . $originalEntry['factory']->getId() . "/" . $originalEntry['archiveNumber'];
    }

    /**
     * @param ArchiveEntryEntity $archiveEntry
     * @param bool $isDeleted
     * @return bool
     */

    public function checkNewPath(ArchiveEntryEntity $archiveEntry, bool $isDeleted)
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

    public function handleEntryDelete(ArchiveEntryEntity $entryEntity, bool $delete, array $entryIdsArray)
    {
        $this->commonArchiveService->checkAndCreateFolders($entryEntity, false, $delete);
        if ($this->moveEntry($entryEntity, $delete)) {
            $entryEntity->setDeleted($delete);
            $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryEntity);
            $rootFolder->setDeleted($delete);
            $this->updateEntry();
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
     * @param array $files
     */

    public function restoreEntriesFromFiles(array $files)
    {
        //$serializer = $this->container->get('jms_serializer');
        $serializer = SerializerBuilder::create()->build();

        foreach ($files as $file) {
            $xml = file_get_contents($file);

            //try {
            $entry = $serializer->deserialize($xml, 'App\Entity\ArchiveEntryEntity', 'xml');

            //} catch (\Exception $exception) {
            //     $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка :' . $exception->getMessage());
            // }
            $this->em->persist($entry);
        }

        $this->em->flush();
    }
}
