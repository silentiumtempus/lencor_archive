<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntryService
 * @package AppBundle\Services
 */
class EntryService
{
    protected $em;
    protected $container;
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
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->container = $container;
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
     * @param string $entryId
     * @param string $userId
     */
    public function changeLastUpdateInfo(string $entryId, string $userId)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry->setModifiedbyUserId($userId);
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
        } else if ($request->request->has('folderId')) {
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
     * @param $userId
     */
    public function prepareEntry(ArchiveEntryEntity $newEntry, FolderEntity $newFolder, int $userId)
    {
        $newEntry
            ->setCataloguePath($newFolder->getId())
            ->setModifiedByUserId($userId)
            ->setDeleteMark(false)
            ->setDeletedByUserId(null);
    }

    public function writeDataToEntryFile(ArchiveEntryEntity $newEntry, string $filename)
    {
        $fs = new Filesystem();
        $fs->touch($filename);
        $serializer = SerializerBuilder::create()->build();
        $entryJSONFile = $serializer->serialize($newEntry, 'yml');
        file_put_contents($filename, $entryJSONFile);
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
     * @param int $userId
     * @return ArchiveEntryEntity
     */
    public function removeEntry(int $entryId, int $userId)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
            ->setDeleteMark(true)
            ->setDeletedByUserId($userId);
        $this->em->flush();

        return $archiveEntry;
    }

    /**
     * @param int $entryId
     * @param int $userId
     * @return ArchiveEntryEntity
     */
    public function restoreEntry(int $entryId, int $userId)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry
            ->setDeleteMark(false)
            ->setModifiedByUserId($userId)
            ->setDeletedByUserId(null);
        $this->em->flush();

        return $archiveEntry;
    }

    /**
     * This is called on entry update submit
     */
    public function updateEntry()
    {
        try {
            $this->em->flush();

            return true;
        } catch (\Exception $exception) {

            return false;
        }
    }
}