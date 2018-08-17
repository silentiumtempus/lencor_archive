<?php

namespace App\Service;

use App\Entity\FileEntity;
use App\Entity\FolderEntity;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DeleteService
 * @package App\Service
 */
class DeleteService
{
    protected $em;
    protected $filesRepository;
    protected $foldersRepository;
    protected $entriesRepository;

    /**
     * DeleteService constructor.
     * @param EntityManagerInterface $entityManager
     */

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->filesRepository = $this->em->getRepository('App:FileEntity');
        $this->foldersRepository = $this->em->getRepository('App:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('App:ArchiveEntryEntity');
    }
}