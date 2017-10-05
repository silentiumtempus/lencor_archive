<?php

namespace AppBundle\Service;

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


    public function __construct(EntityManager $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->container = $container;
    }

    public function changeLastUpdateInfo(string $entryId, string $userId)
    {
        $archiveEntry = $this->entriesRepository->findOneById($entryId);
        $archiveEntry->setModifiedbyUserId($userId);
        $this->em->flush();
    }

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

    public function setEntryId(Request $request)
    {
        $session = $this->container->get('session');
        $entryId = $request->get('entryId');
        if ($entryId) {
            $session->set('entryId', $request->get('entryId'));
            return $entryId;
        } elseif (!$entryId) {
            return $session->get('entryId');
        }
    }
}