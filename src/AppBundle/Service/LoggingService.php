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
     * @param array $flashArray
     */
    public function logEntry(ArchiveEntryEntity $archiveEntryEntity, string $entryPath, User $user, array $flashArray)
    {
        $fs = new Filesystem();
        $logFileName = $entryPath . "/" . $archiveEntryEntity->getArchiveNumber() . ".log";
        if (!$fs->exists($logFileName)) {
            $fs->touch($logFileName);
        }
        $date = new \DateTime();
        $dateString = $date->format('Y-m-d H:i:s');
        $recordTemplate = "[" . $dateString . "] :" . " (" . $user->getUsername() . ") ";
        foreach ($flashArray as $messagesArray) {
            foreach ($messagesArray as $message) {
                $logRecord = $recordTemplate . $message . "\n";
                $fs->appendToFile($logFileName, $logRecord);
            }
        }
    }

    /**
     * @param int $entryId
     * @param User $user
     * @param array $messages
     */
    public function logEntryContent(int $entryId, User $user, array $messages)
    {
        $entry = $this->entriesRepository->findOneById($entryId);
        $rootFolder = $this->foldersRepository->findOneByArchiveEntry($entryId);
        $entryPath = $this->pathRoot . "/" . $rootFolder->getFolderName();
        $this->logEntry($entry, $entryPath, $user, $messages);
    }
}