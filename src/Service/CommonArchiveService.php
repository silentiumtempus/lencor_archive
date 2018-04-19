<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CommonArchiveService
 * @package App\Service
 */
class CommonArchiveService
{
    protected $em;
    protected $container;
    protected $filesRepository;
    protected $foldersRepository;
    protected $entriesRepository;
    protected $userService;

    public function __construct(EntityManagerInterface $entityManager, UserService $userService)
    {
        $this->em = $entityManager;
        $this->userService = $userService;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
    }

    /**
     * @param int $id
     * @param string $type
     * @return array
     */
    public function getRequesters(int $id, string $type)
    {
        $repository = null;
        switch ($type) {
            case 'file' : $repository = $this->filesRepository;
            break;
            case 'folder' : $repository = $this->foldersRepository;
            break;
            case 'entry' : $repository = $this->entriesRepository;
        }
        $source = $repository->findOneById($id);
        $requesterIds = $source->getRequestedByUsers();
        $requesters = $this->userService->getUsers($requesterIds);

        return $requesters;
    }

}