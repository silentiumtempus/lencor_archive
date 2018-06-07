<?php

namespace App\Service;

use App\Entity\ArchiveEntryEntity;
use App\Entity\FolderEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
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
    protected $entriesRepository;
    protected $foldersRepository;

    /**
     * EntryService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->pathKeys = ['year', 'factory', 'archiveNumber'];
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
     * @param int $entryId
     * @param User $user
     */
    public function changeLastUpdateInfo(int $entryId, User $user)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
            ->setModifiedByUserId($user)
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
            ->setCataloguePath($newFolder->getId())
            ->setModifiedByUserId($user)
            ->setDeleteMark(false)
            ->setDeletedByUser(null);
    }

    /**
     * @param ArchiveEntryEntity $newEntry
     * @param string $filename
     * @return bool
     */
    public function writeDataToEntryFile(ArchiveEntryEntity $newEntry, string $filename)
    {
        // try {
        $fs = new Filesystem();
        $fs->touch($filename);
        //$encoders = array(new XmlEncoder());
        //$normalizers = array(new ObjectNormalizer());
        //$serializer = new Serializer($normalizers, $encoders);
        $serializer = SerializerBuilder::create()->build();
        $entryJSONFile = $serializer->serialize($newEntry, 'xml');
        file_put_contents($filename, $entryJSONFile);

        return true;
        // } catch (\Exception $exception) {

        //     return false;
        //  }
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
            ->setDeleteMark(true)
            ->setDeletedByUser($user);
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
            ->setDeleteMark(false)
            ->setModifiedByUserId($user)
            ->setDeletedByUser(null)
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
        if ($archiveEntry->getDeleteMark()) {
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
                    ->setRequestsCount(count($archiveEntry->getRequestedByUsers()));
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
     * @return string
     */
    public function constructEntryPath(ArchiveEntryEntity $archiveEntry)
    {
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
     * @return bool
     */
    public function checkNewPath(ArchiveEntryEntity $archiveEntry)
    {
        $newPath = $this->constructEntryPath($archiveEntry);
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

    public function restoreEntriesFromFiles(array $files)
    {

        foreach ($files as $file)
        {
            $serializer = SerializerBuilder::create()->build();
            //try {
                $entry = $serializer->deserialize($file, 'ArchiveEntryEntity', 'xml');
                $this->em->persist($entry);
            //} catch (\Exception $exception) {
           //     $this->container->get('session')->getFlashBag()->add('danger', 'Ошибка :' . $exception->getMessage());
           // }
        }
        $this->em->flush();
    }
}
