<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\FolderEntity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ArchiveEntryService
 * @package AppBundle\Services
 */
class ArchiveEntryService
{
    protected $em;
    protected $container;
    protected $entriesRepository;
    protected $foldersRepository;

    /**
     * ArchiveEntryService constructor.
     * @param EntityManager $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->container = $container;
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
     * @return null
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
     * @param ArchiveEntryEntity $newEntry
     * @param FolderEntity $newFolder
     */
    public function persistEntry(ArchiveEntryEntity $newEntry, FolderEntity $newFolder)
    {
        $this->em->persist($newEntry);
        $this->em->persist($newFolder);
        $this->em->flush();
    }

}