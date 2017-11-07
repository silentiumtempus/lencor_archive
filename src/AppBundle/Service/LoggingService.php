<?php

namespace AppBundle\Service;

use AppBundle\Entity\ArchiveEntryEntity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class LoggingService
 * @package AppBundle\Service
 */
class LoggingService
{
    protected $em;
    protected $container;
    protected $pathRoot;
    protected $foldersRepository;
    protected $entriesRepository;

    /**
     * LoggingService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->pathRoot = $this->container->getParameter('lencor_archive.storage_path');
        $this->foldersRepository = $this->em->getRepository('AppBundle:FolderEntity');
        $this->entriesRepository = $this->em->getRepository('AppBundle:ArchiveEntryEntity');

    }

    /**
     * @param ArchiveEntryEntity $archiveEntryEntity
     * @param string $entryPath
     * @param User $user
     * @param array $messages
     */
    public function logEntry(ArchiveEntryEntity $archiveEntryEntity, string $entryPath, User $user, array $messages)
    {
        $fs = new Filesystem();
        $logFileName = $entryPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".log";
        $fs->touch($logFileName);
        foreach ($messages as $message)
        {
            $date = new \DateTime();
            $dateString = $date->format('Y-m-d H:i:s');
            $logRecord = "[" . $dateString . "] :" . " (" . $user->getUsername() . ") " . $message[0] . "\n";
            $fs->appendToFile($logFileName, $logRecord);
        }
    }

    /**
     * @param int $entryId
     * @param User $user
     * @param array $messages
     */
    public function logFolder(int $entryId, User $user, array $messages)
    {
        $entry = $this->entriesRepository->findOneById($entryId);
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $entryPath = $this->pathRoot . "/" . $rootFolder->getFolderName();

        $this->logEntry($entry, $entryPath, $user, $messages);
    }
}